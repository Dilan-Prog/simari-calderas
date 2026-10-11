<?php

namespace App\Support;

use App\Models\Products;
use App\Models\Redirect;
use Illuminate\Support\Facades\DB;

/**
 * Mantiene al día la URL canónica guardada en OTROS productos.
 *
 * `products.canonical_url` es una COPIA de la URL del producto destino
 * (canonical_product_id) tomada al guardar. Si el destino cambia de slug, esas
 * copias quedaban apuntando a la URL vieja (que solo redirige) en vez de a la
 * nueva. Aquí se refrescan: al cambiar el slug (syncDependents, llamado desde
 * Products::booted) y como reparación de datos ya desfasados (repairAll).
 *
 * Solo se reemplaza el PATH: el host/esquema guardado se conserva (en
 * producción las canónicas llevan el dominio real aunque se edite desde otro
 * host).
 */
class CanonicalUrls
{
    /** Path normalizado ('/producto/slug', sin barra final) de una URL, o null. */
    public static function pathOf(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $path = parse_url(trim($url), PHP_URL_PATH);

        return is_string($path) && $path !== '' ? '/' . trim($path, '/') : null;
    }

    /** Misma URL con otro path (conserva esquema/host); sin host usa la URL base de la app. */
    public static function withPath(?string $url, string $path): string
    {
        $parts = $url ? parse_url(trim($url)) : false;

        if (is_array($parts) && !empty($parts['host'])) {
            $port = isset($parts['port']) ? ':' . $parts['port'] : '';

            return ($parts['scheme'] ?? 'https') . '://' . $parts['host'] . $port . $path;
        }

        return url($path);
    }

    /**
     * Cuando $target cambió de $oldSlug a su slug actual: actualiza la
     * canonical_url de los productos que lo tienen como canónico, tanto los
     * ligados por canonical_product_id como los de URL escrita a mano que
     * apuntaban a la URL vieja. Usa query builder (sin eventos) para no
     * disparar el hook de nuevo. Devuelve cuántos productos actualizó.
     */
    public static function syncDependents(Products $target, string $oldSlug): int
    {
        $newPath = '/producto/' . $target->slug;
        $oldPath = '/producto/' . $oldSlug;

        if ($newPath === $oldPath) {
            return 0;
        }

        $escaped = addcslashes($oldPath, '%_\\');

        $dependents = Products::query()
            ->where('id', '!=', $target->id)
            ->where(function ($q) use ($target, $escaped) {
                $q->where('canonical_product_id', $target->id)
                    ->orWhere('canonical_url', 'like', '%' . $escaped . '%');
            })
            ->get(['id', 'canonical_url', 'canonical_product_id']);

        $updated = 0;

        foreach ($dependents as $product) {
            $linked = (int) $product->canonical_product_id === (int) $target->id;
            $pointsToOld = self::pathOf($product->canonical_url) === $oldPath;

            // Ligado al destino: siempre al día. Texto libre: solo si apuntaba a la URL vieja.
            if (!$linked && !$pointsToOld) {
                continue;
            }

            $new = self::withPath($product->canonical_url, $newPath);
            if ($new !== $product->canonical_url) {
                DB::table('products')->where('id', $product->id)->update(['canonical_url' => $new]);
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Repara datos ya desfasados (cambios de slug hechos antes de este arreglo):
     *  - ligados (canonical_product_id): el path debe ser el del slug ACTUAL del destino.
     *  - de texto libre: si su path es el "old_path" de una redirección hacia un
     *    producto existente, apunta al path nuevo.
     * Con $dryRun solo informa. Devuelve ['linked' => n, 'redirected' => n, 'rows' => [...]].
     */
    public static function repairAll(bool $dryRun = false): array
    {
        $slugById = Products::query()->pluck('slug', 'id');
        $pathExists = Products::query()->pluck('id', 'slug')->mapWithKeys(fn ($id, $slug) => ['/producto/' . $slug => true]);
        $redirects = Redirect::query()->where('old_path', 'like', '/producto/%')->pluck('new_path', 'old_path');

        $linked = 0;
        $redirected = 0;
        $rows = [];

        Products::query()
            ->whereNotNull('canonical_url')
            ->where('canonical_url', '!=', '')
            ->get(['id', 'canonical_url', 'canonical_product_id'])
            ->each(function (Products $product) use ($slugById, $pathExists, $redirects, $dryRun, &$linked, &$redirected, &$rows) {
                $current = self::pathOf($product->canonical_url);
                $target = null;
                $kind = null;

                if ($product->canonical_product_id && isset($slugById[$product->canonical_product_id])) {
                    $expected = '/producto/' . $slugById[$product->canonical_product_id];
                    if ($current !== $expected) {
                        $target = $expected;
                        $kind = 'linked';
                    }
                } elseif ($current !== null) {
                    $new = $redirects[$current] ?? null;
                    $newPath = $new ? '/' . trim($new, '/') : null;
                    if ($newPath && isset($pathExists[$newPath]) && $newPath !== $current && !isset($pathExists[$current])) {
                        $target = $newPath;
                        $kind = 'redirected';
                    }
                }

                if ($target === null) {
                    return;
                }

                $new = self::withPath($product->canonical_url, $target);
                $rows[] = ['id' => $product->id, 'from' => $product->canonical_url, 'to' => $new, 'kind' => $kind];
                $kind === 'linked' ? $linked++ : $redirected++;

                if (!$dryRun) {
                    DB::table('products')->where('id', $product->id)->update(['canonical_url' => $new]);
                }
            });

        return ['linked' => $linked, 'redirected' => $redirected, 'rows' => $rows];
    }
}
