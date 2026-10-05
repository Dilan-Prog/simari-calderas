<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Products;
use App\Models\ServicePage;
use App\Support\LinkTarget;
use Illuminate\Http\Request;

/**
 * Resultados para el picker de enlaces genérico del admin (producto,
 * colección, categoría, subcategoría, categoría hija, servicio, marca, página
 * estática) — el frontend ya arma [texto](url) o similar con lo que regresa
 * aquí, sin tener que conocer las rutas de cada tipo. Generalización de la
 * antigua ProductController::faqLinkSearch() (que solo cubría product/
 * collection/category para el picker de enlaces de FAQ de Productos); ahora
 * también la consumen otras pantallas del admin vía
 * resources/js/admin/link-picker.js.
 *
 * Cada resultado trae {id, label, url}: `label`/`url` son lo que consume
 * variable-picker.js (FAQ de Productos, no romper) e `id` es lo que guardan
 * los destinos de bloque (App\Support\LinkTarget) vía
 * window.LinkPicker.mountField().
 *
 * Modo "lookup": con `id=` se devuelve solo esa entidad (sin filtrar por
 * activa) más `broken` (LinkTarget::isBroken), para pintar la etiqueta de un
 * destino ya guardado.
 */
class LinkController extends Controller
{
    public function search(Request $request)
    {
        $type = (string) $request->input('type');
        $term = trim((string) $request->input('q', ''));
        $lookupId = $request->filled('id') ? (int) $request->input('id') : null;

        if ($lookupId !== null) {
            return response()->json($this->lookup($type, $lookupId));
        }

        $results = match ($type) {
            'product' => Products::query()
                ->where('publish_on_website', true)
                ->when($term !== '', fn ($q) => $q->where(function ($q2) use ($term) {
                    $q2->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%");
                }))
                ->orderBy('name')
                ->limit(10)
                ->get(['id', 'name', 'slug'])
                ->map(fn ($p) => ['id' => $p->id, 'label' => $p->name, 'url' => route('product.show', $p->slug)]),

            'collection' => Collection::query()
                ->where('is_active', true)
                ->when($term !== '', fn ($q) => $q->where('name', 'like', "%{$term}%"))
                ->orderBy('name')
                ->limit(10)
                ->get(['id', 'name', 'slug'])
                ->map(fn ($c) => ['id' => $c->id, 'label' => $c->name, 'url' => route('collection.show', $c->slug)]),

            // Categoría = solo raíz; subcategoría = hija de una raíz;
            // categoría hija = hija de una subcategoría (3 niveles máx.).
            'category' => $this->categoryResults($term, fn ($q) => $q->whereNull('parent_id')),

            'subcategory' => $this->categoryResults($term, fn ($q) => $q->whereHas(
                'parent',
                fn ($p) => $p->whereNull('parent_id')
            )),

            'child_category' => $this->categoryResults($term, fn ($q) => $q->whereHas(
                'parent',
                fn ($p) => $p->whereNotNull('parent_id')
            )),

            'service_page' => ServicePage::query()
                ->with('parent')
                ->where('is_active', true)
                ->when($term !== '', fn ($q) => $q->where('name', 'like', "%{$term}%"))
                ->orderBy('name')
                ->limit(20)
                ->get()
                ->map(fn ($s) => [
                    'id'    => $s->id,
                    'label' => $this->servicePageLabel($s),
                    'url'   => url($s->publicPath()),
                ]),

            'brand' => Brand::query()
                ->where('is_active', true)
                ->when($term !== '', fn ($q) => $q->where('name', 'like', "%{$term}%"))
                ->orderBy('name')
                ->limit(10)
                ->get(['id', 'name'])
                ->map(fn ($b) => ['id' => $b->id, 'label' => $b->name, 'url' => route('catalog.index', ['marca' => [$b->id]])]),

            'static_page' => collect(config('link_picker.static_pages', []))
                ->filter(fn ($page) => $term === '' || str_contains(mb_strtolower($page['label']), mb_strtolower($term)))
                ->map(fn ($page) => [
                    'id'    => null,
                    'label' => $page['label'],
                    'url'   => $page['url'] ?? route($page['route']),
                ])
                ->values(),

            default => collect(),
        };

        return response()->json($results->values());
    }

    /** Categorías activas con etiqueta "Padre › Nombre" (útil en subniveles). */
    private function categoryResults(string $term, callable $scope)
    {
        return Category::query()
            ->with('parent')
            ->where('is_active', true)
            ->tap($scope)
            ->when($term !== '', fn ($q) => $q->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn ($c) => [
                'id'    => $c->id,
                'label' => $c->parent ? $c->parent->name . ' › ' . $c->name : $c->name,
                'url'   => route('catalog.category', $c->slug),
            ]);
    }

    /** Servicio hoja con prefijo del padre ("Calderas › Diagnóstico"). */
    private function servicePageLabel(ServicePage $page): string
    {
        return $page->parent ? $page->parent->name . ' › ' . $page->name : $page->name;
    }

    /**
     * Una sola entidad por id (sin filtrar por activa) para repintar un
     * destino guardado. `broken` marca destinos inactivos/inexistentes.
     */
    private function lookup(string $type, int $id): array
    {
        $broken = LinkTarget::isBroken(['type' => $type, 'id' => $id]);

        $row = match ($type) {
            'product' => ($p = Products::find($id))
                ? ['id' => $p->id, 'label' => $p->name, 'url' => route('product.show', $p->slug)] : null,
            'collection' => ($c = Collection::find($id))
                ? ['id' => $c->id, 'label' => $c->name, 'url' => route('collection.show', $c->slug)] : null,
            'category', 'subcategory', 'child_category' => ($c = Category::with('parent')->find($id))
                ? [
                    'id'    => $c->id,
                    'label' => $c->parent ? $c->parent->name . ' › ' . $c->name : $c->name,
                    'url'   => route('catalog.category', $c->slug),
                ] : null,
            'service_page' => ($s = ServicePage::with('parent')->find($id))
                ? ['id' => $s->id, 'label' => $this->servicePageLabel($s), 'url' => url($s->publicPath())] : null,
            'brand' => ($b = Brand::find($id))
                ? ['id' => $b->id, 'label' => $b->name, 'url' => route('catalog.index', ['marca' => [$b->id]])] : null,
            default => null,
        };

        if (!$row) {
            return [];
        }

        return [$row + ['broken' => $broken]];
    }
}
