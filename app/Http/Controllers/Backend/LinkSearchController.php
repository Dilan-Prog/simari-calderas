<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Products;
use App\Models\ServicePage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Buscador unificado de destinos internos para el selector de enlaces de
 * los editores en vivo (Servicios y Colecciones). Solo contenido público
 * (activo/publicado) y siempre rutas relativas.
 */
class LinkSearchController extends Controller
{
    private const PER_TYPE = 6;
    private const MAX_RESULTS = 20;

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $all = ['producto', 'coleccion', 'servicio', 'categoria'];
        $types = array_filter(array_map('trim', explode(',', (string) $request->query('types', ''))));
        $types = $types ? array_values(array_intersect($all, $types)) : $all;

        $bs = chr(92);
        $like = '%' . str_replace([$bs, '%', '_'], [$bs . $bs, $bs . '%', $bs . '_'], $q) . '%';
        $results = [];

        if (in_array('producto', $types, true)) {
            Products::query()
                ->where('is_active', true)
                ->where('publish_on_website', true)
                ->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('sku', 'like', $like))
                ->orderBy('name')
                ->limit(self::PER_TYPE)
                ->get(['id', 'name', 'slug', 'sku'])
                ->each(function ($p) use (&$results) {
                    $results[] = $this->row('producto', $p->name, route('product.show', $p->slug), $p->sku);
                });
        }

        if (in_array('coleccion', $types, true)) {
            Collection::query()
                ->where('is_active', true)
                ->where('name', 'like', $like)
                ->orderBy('name')
                ->limit(self::PER_TYPE)
                ->get(['id', 'name', 'slug'])
                ->each(function ($c) use (&$results) {
                    $results[] = $this->row('coleccion', $c->name, route('collection.show', $c->slug), 'Colección');
                });
        }

        if (in_array('servicio', $types, true)) {
            ServicePage::query()
                ->with('parent')
                ->where('is_active', true)
                ->where('name', 'like', $like)
                ->orderBy('name')
                ->limit(self::PER_TYPE)
                ->get()
                ->each(function ($s) use (&$results) {
                    $results[] = $this->row('servicio', $s->name, $s->publicPath(), $s->parent?->name ?? 'Servicio', false);
                });
        }

        if (in_array('categoria', $types, true)) {
            Category::query()
                ->with('parent')
                ->where('is_active', true)
                ->where('name', 'like', $like)
                ->orderBy('name')
                ->limit(self::PER_TYPE)
                ->get()
                ->each(function ($c) use (&$results) {
                    $results[] = $this->row('categoria', $c->name, route('catalog.category', $c->slug), $c->parent?->name ?? 'Categoría');
                });
        }

        return response()->json(array_slice($results, 0, self::MAX_RESULTS));
    }

    private function row(string $type, ?string $label, string $url, ?string $hint, bool $strip = true): array
    {
        return [
            'type'  => $type,
            'label' => (string) $label,
            'url'   => $strip ? (parse_url($url, PHP_URL_PATH) ?: '/') : $url,
            'hint'  => (string) $hint,
        ];
    }
}
