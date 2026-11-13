<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Models\Redirect;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Slug jerárquico de las categorías: el slug guardado es la RUTA completa, con todos sus ancestros
 * (p. ej. "calentadores-de-agua/boiler-de-paso"), porque /catalogo/{slug} es la URL pública y la que
 * indexa Google. Una subcategoría nunca debe quedar con solo su propio segmento ("boiler-de-paso").
 *
 * Lo usan el admin de Categorías (al crear/editar siempre se arma desde el padre, sin importar lo que se
 * haya escrito) y la reparación de datos existentes (migración y comandos categories:*), que registra el
 * 301 de la URL vieja antes de cambiar cada slug.
 */
class CategorySlugs
{
    public const MAX_LENGTH = 150;

    /** Cada segmento "/"-separado se normaliza por su cuenta (Str::slug('a/b') quitaría la "/"). */
    public static function slugifyPath(string $raw): string
    {
        return collect(explode('/', $raw))
            ->filter(fn ($seg) => $seg !== '')
            ->map(fn ($seg) => Str::slug($seg))
            ->filter(fn ($seg) => $seg !== '')
            ->implode('/');
    }

    /** Último segmento de lo escrito (lo propio de la categoría), normalizado. */
    public static function ownSegment(string $raw): string
    {
        return Str::slug(Str::afterLast(trim($raw, '/'), '/'));
    }

    /** Slug completo de una categoría con ese padre (o raíz si no hay padre). */
    public static function build(?Category $parent, string $raw): string
    {
        $own = self::ownSegment($raw);

        if ($own === '') {
            return '';
        }

        return $parent ? $parent->slug . '/' . $own : $own;
    }

    /** ¿$ancestorId es la propia categoría o alguno de sus descendientes? (evita ciclos al elegir padre) */
    public static function isSelfOrDescendant(int $categoryId, int $candidateId): bool
    {
        if ($candidateId === $categoryId) {
            return true;
        }

        $seen = [];
        $node = Category::find($candidateId);
        while ($node && $node->parent_id && ! isset($seen[$node->id])) {
            $seen[$node->id] = true;
            if ((int) $node->parent_id === $categoryId) {
                return true;
            }
            $node = Category::find($node->parent_id);
        }

        return false;
    }

    /**
     * Slug esperado de TODAS las categorías según su jerarquía real, padres primero.
     *
     * Lo propio de cada una sale de su slug guardado; salvo el caso de un bug viejo que pegó el slug del
     * padre con el nombre sin "/" ("calentadores-masstercal" + "calentador-servicios-generales"), donde se
     * usa el nombre. Si el slug esperado ya lo tiene otra categoría, no se propone (queda como conflicto).
     *
     * @param  Collection<int, Category>  $all  keyBy id
     * @return array{expected: array<int,string>, conflicts: array<int,string>}
     */
    public static function expectedForAll(Collection $all): array
    {
        $expected = [];
        $conflicts = [];
        $depth = function (Category $c) use ($all, &$depth): int {
            return ($c->parent_id && $all->has($c->parent_id)) ? 1 + $depth($all->get($c->parent_id)) : 0;
        };

        // Slugs que ya están en uso y no cambian (para detectar choques).
        $taken = $all->pluck('slug', 'id')->all();

        foreach ($all->sortBy($depth)->values() as $cat) {
            $parent = ($cat->parent_id && $all->has($cat->parent_id)) ? $all->get($cat->parent_id) : null;
            $parentSlug = $parent ? ($expected[$parent->id] ?? $parent->slug) : null;

            // Las categorías principales no se tocan (como en la auditoría original): su slug es el que se eligió.
            if (! $parent) {
                $expected[$cat->id] = $cat->slug;
                continue;
            }

            $own = self::ownSegment($cat->slug);
            if ($parent) {
                foreach (array_unique([$parent->slug, str_replace('/', '', $parent->slug), $parentSlug]) as $prefix) {
                    $nameSlug = Str::slug($cat->name);
                    if ($prefix && $nameSlug !== '' && $own === Str::slug($prefix . $nameSlug)) {
                        $own = $nameSlug;
                        break;
                    }
                }
            }
            if ($own === '') {
                $own = Str::slug($cat->name);
            }

            $wanted = $parentSlug ? $parentSlug . '/' . $own : $own;

            if ($wanted === $cat->slug) {
                $expected[$cat->id] = $cat->slug;
                continue;
            }

            $owner = array_search($wanted, $taken, true);
            if ($owner !== false && (int) $owner !== (int) $cat->id) {
                $conflicts[$cat->id] = $wanted;
                $expected[$cat->id] = $cat->slug; // se queda como está; sus hijos cuelgan de este slug
                continue;
            }

            $taken[$cat->id] = $wanted;
            $expected[$cat->id] = $wanted;
        }

        return ['expected' => $expected, 'conflicts' => $conflicts];
    }

    /**
     * Compara los slugs guardados con los esperados. No escribe nada.
     *
     * @return array<int, array{id:int,name:string,from:string,to:string}>
     */
    public static function plan(): array
    {
        $all = Category::orderBy('sort_order')->orderBy('name')->get()->keyBy('id');
        ['expected' => $expected] = self::expectedForAll($all);

        $changes = [];
        foreach ($expected as $id => $slug) {
            $cat = $all->get($id);
            if ($cat && $cat->slug !== $slug) {
                $changes[] = ['id' => $id, 'name' => $cat->name, 'from' => $cat->slug, 'to' => $slug];
            }
        }

        return $changes;
    }

    /**
     * Corrige los slugs desalineados. Antes de cambiar cada uno registra el redirect 301 de su URL vieja
     * (en la misma transacción); padres antes que hijos. Idempotente.
     *
     * @return array{changed: array<int, array{id:int,name:string,from:string,to:string}>, conflicts: array<int,string>}
     */
    public static function repairAll(bool $dryRun = false): array
    {
        $all = Category::orderBy('sort_order')->orderBy('name')->get()->keyBy('id');
        ['expected' => $expected, 'conflicts' => $conflicts] = self::expectedForAll($all);

        $depth = function (Category $c) use ($all, &$depth): int {
            return ($c->parent_id && $all->has($c->parent_id)) ? 1 + $depth($all->get($c->parent_id)) : 0;
        };

        $changed = [];
        foreach ($all->sortBy($depth) as $cat) {
            $target = $expected[$cat->id] ?? $cat->slug;
            // Modelo fresco: al corregir a un padre, el cascade de Category ya pudo reescribir a sus hijos.
            $current = Category::find($cat->id);
            if (! $current || $current->slug === $target) {
                continue;
            }

            $changed[] = ['id' => $cat->id, 'name' => $cat->name, 'from' => $current->slug, 'to' => $target];

            if ($dryRun) {
                continue;
            }

            DB::transaction(function () use ($current, $target) {
                Redirect::record('/catalogo/' . $current->slug, '/catalogo/' . $target);
                $current->slug = $target;
                $current->save();
            });
        }

        return ['changed' => $changed, 'conflicts' => $conflicts];
    }
}
