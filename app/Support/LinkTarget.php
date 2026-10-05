<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Products;
use App\Models\ServicePage;

/**
 * Destino de un enlace de bloque (encabezado de sección, banners, fichas).
 *
 * Se guarda como arreglo {type, id, url, new_tab} y se resuelve a URL al
 * RENDERIZAR (no al guardar), para que cambiar el slug de un producto/
 * categoría no rompa los bloques ya armados. Un destino cuya entidad ya no
 * existe (o está inactiva) resuelve a null: el bloque lo trata como enlace
 * roto (en el admin se marca, en la web no se pinta el enlace).
 */
class LinkTarget
{
    public const TYPES = [
        'collection'     => 'Colección',
        'service_page'   => 'Servicio',
        'home'           => 'Inicio',
        'product'        => 'Producto específico',
        'category'       => 'Categoría',
        'subcategory'    => 'Subcategoría',
        'child_category' => 'Categoría hija',
        'custom'         => 'URL personalizada',
    ];

    /** Tipos que apuntan a una entidad por id. */
    private const ENTITY_TYPES = ['collection', 'service_page', 'product', 'category', 'subcategory', 'child_category'];

    /**
     * Limpia lo que llega de un formulario. Devuelve null si no hay destino
     * (sin tipo, o falta id/url según el tipo).
     */
    public static function normalize(?array $raw): ?array
    {
        if (!$raw || empty($raw['type']) || !isset(self::TYPES[$raw['type']])) {
            return null;
        }

        $type = $raw['type'];
        $link = ['type' => $type, 'id' => null, 'url' => null, 'new_tab' => !empty($raw['new_tab'])];

        if (in_array($type, self::ENTITY_TYPES, true)) {
            $link['id'] = isset($raw['id']) && $raw['id'] !== '' ? (int) $raw['id'] : null;

            return $link['id'] ? $link : null;
        }

        if ($type === 'custom') {
            $url = trim((string) ($raw['url'] ?? ''));
            $link['url'] = $url !== '' ? mb_substr($url, 0, 2048) : null;

            return $link['url'] ? $link : null;
        }

        return $link; // home
    }

    /** URL pública del destino, o null si no hay destino o está roto. */
    public static function resolve(?array $link): ?string
    {
        if (!$link || empty($link['type'])) {
            return null;
        }

        $id = $link['id'] ?? null;

        return match ($link['type']) {
            'home'   => route('home'),
            'custom' => self::safeUrl($link['url'] ?? null),
            'product' => ($p = Products::where('is_active', true)->where('publish_on_website', true)->find($id))
                ? route('product.show', $p->slug) : null,
            'collection' => ($c = Collection::where('is_active', true)->find($id))
                ? route('collection.show', $c->slug) : null,
            'category', 'subcategory', 'child_category' => ($c = Category::where('is_active', true)->find($id))
                ? route('catalog.category', $c->slug) : null,
            'service_page' => ($s = ServicePage::with('parent')->where('is_active', true)->find($id))
                ? url($s->publicPath()) : null,
            default => null,
        };
    }

    /** Hay un destino configurado pero ya no se puede resolver. */
    public static function isBroken(?array $link): bool
    {
        return $link && !empty($link['type']) && self::resolve($link) === null;
    }

    /** Etiqueta legible del destino para listados del admin. */
    public static function label(?array $link): ?string
    {
        if (!$link || empty($link['type'])) {
            return null;
        }

        if ($link['type'] === 'home') {
            return 'Inicio';
        }

        $id = $link['id'] ?? null;
        $typeLabel = self::TYPES[$link['type']] ?? $link['type'];

        $name = match ($link['type']) {
            'custom' => $link['url'] ?? null,
            'product' => Products::find($id)?->name,
            'collection' => Collection::find($id)?->name,
            'category', 'subcategory', 'child_category' => Category::find($id)?->name,
            'service_page' => ServicePage::find($id)?->name,
            default => null,
        };

        return $name ? "{$typeLabel}: {$name}" : "{$typeLabel} (no encontrado)";
    }

    /** Solo http(s), rutas relativas y mailto/tel -- nunca javascript:. */
    private static function safeUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        if (preg_match('#^(https?://|/|\#|mailto:|tel:)#i', $url)) {
            return $url;
        }

        return null;
    }
}
