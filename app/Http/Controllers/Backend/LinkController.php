<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Products;
use Illuminate\Http\Request;

/**
 * Resultados para el picker de enlaces genérico del admin (producto,
 * colección, categoría, marca, página estática) — el frontend ya arma
 * [texto](url) o similar con lo que regresa aquí, sin tener que conocer las
 * rutas de cada tipo. Generalización de la antigua
 * ProductController::faqLinkSearch() (que solo cubría product/collection/
 * category para el picker de enlaces de FAQ de Productos); ahora también la
 * consumen otras pantallas del admin vía resources/js/admin/link-picker.js.
 */
class LinkController extends Controller
{
    public function search(Request $request)
    {
        $type = $request->input('type');
        $term = trim((string) $request->input('q', ''));

        $results = match ($type) {
            'product' => Products::query()
                ->where('publish_on_website', true)
                ->when($term !== '', fn ($q) => $q->where(function ($q2) use ($term) {
                    $q2->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%");
                }))
                ->orderBy('name')
                ->limit(10)
                ->get(['name', 'slug'])
                ->map(fn ($p) => ['label' => $p->name, 'url' => route('product.show', $p->slug)]),

            'collection' => Collection::query()
                ->where('is_active', true)
                ->when($term !== '', fn ($q) => $q->where('name', 'like', "%{$term}%"))
                ->orderBy('name')
                ->limit(10)
                ->get(['name', 'slug'])
                ->map(fn ($c) => ['label' => $c->name, 'url' => route('collection.show', $c->slug)]),

            'category' => Category::query()
                ->where('is_active', true)
                ->when($term !== '', fn ($q) => $q->where('name', 'like', "%{$term}%"))
                ->orderBy('name')
                ->limit(10)
                ->get(['name', 'slug'])
                ->map(fn ($c) => ['label' => $c->name, 'url' => route('catalog.category', $c->slug)]),

            'brand' => Brand::query()
                ->where('is_active', true)
                ->when($term !== '', fn ($q) => $q->where('name', 'like', "%{$term}%"))
                ->orderBy('name')
                ->limit(10)
                ->get(['id', 'name'])
                ->map(fn ($b) => ['label' => $b->name, 'url' => route('catalog.index', ['marca' => [$b->id]])]),

            'static_page' => collect(config('link_picker.static_pages', []))
                ->filter(fn ($page) => $term === '' || str_contains(mb_strtolower($page['label']), mb_strtolower($term)))
                ->map(fn ($page) => [
                    'label' => $page['label'],
                    'url' => $page['url'] ?? route($page['route']),
                ])
                ->values(),

            default => collect(),
        };

        return response()->json($results->values());
    }
}
