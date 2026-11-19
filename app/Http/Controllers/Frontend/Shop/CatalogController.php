<?php

namespace App\Http\Controllers\Frontend\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategorySection;
use App\Models\HomeSection;
use App\Models\Redirect;
use App\Services\Catalog\CatalogParams;
use App\Services\Catalog\CatalogQuery;
use App\Services\Catalog\CatalogResult;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;

class CatalogController extends Controller
{
    public function home()
    {
        $sections = HomeSection::where('page', 'home')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->with('slides')
            ->get();

        return view('frontend.shop.home.index', compact('sections'));
    }

    public function index(Request $request)
    {
        return $this->renderCatalog($request, null);
    }

    public function category(Request $request, string $categorySlug)
    {
        $category = Category::where('slug', $categorySlug)->where('is_active', true)->first();

        if (!$category) {
            // FIX (SEO redirects): the route pattern still matches an old
            // bare slug syntactically (now that categorySlug accepts "/"),
            // so a rename shows up here as a DB miss, not a route miss —
            // Route::fallback() never sees this case.
            if ($redirect = Redirect::resolve($request->path())) {
                return redirect($redirect->new_path, $redirect->status_code);
            }
            abort(404);
        }

        return $this->renderCatalog($request, $category);
    }

    /**
     * Powers the live search overlay in the header: as the user types (or
     * changes a filter/sort inside the overlay), returns matching products
     * pre-rendered as HTML (reusing <x-frontend.shop.product-card>, so the
     * overlay always looks identical to every other product grid) plus the
     * category/brand facets for that search term with their own counts.
     *
     * Usa el mismo servicio que el catálogo (CatalogParams + CatalogQuery); el
     * JSON conserva la forma que espera shared.js (searchOverlay).
     */
    public function liveSearch(Request $request)
    {
        $params = CatalogParams::fromRequest($request, null);

        if ($params->q === '') {
            return response()->json(['total' => 0, 'categories' => [], 'brands' => [], 'productsHtml' => '']);
        }

        $found = app(CatalogQuery::class)->search($params, 12);

        return response()->json([
            'total'        => $found['total'],
            'categories'   => $found['categories'],
            'brands'       => $found['brands'],
            'productsHtml' => view('frontend.shop.partials.search-results-grid', [
                'products' => $found['products'],
                'term'     => $params->q,
            ])->render(),
        ]);
    }

    /**
     * Resultado del catálogo (productos de la página, facetas, chips, etc.)
     * para una petición. Público para que el editor en vivo de Categorías
     * (Backend\CategoryLiveEditorController::preview) pinte exactamente lo
     * mismo que la página pública.
     */
    public function catalogResult(Request $request, ?Category $category): CatalogResult
    {
        $params = CatalogParams::fromRequest($request, $category);
        $baseUrl = $category ? route('catalog.category', $category->slug) : route('catalog.index');

        return app(CatalogQuery::class)->run($params, $category, $baseUrl, 24);
    }

    /**
     * Separa los bloques por zona pública (config.position): 'top' (por
     * defecto, bajo las migas) y 'bottom' (tras la paginación, antes de FAQ).
     *
     * @return array{sectionsTop: SupportCollection, sectionsBottom: SupportCollection}
     */
    public static function splitSections(SupportCollection $sections): array
    {
        [$bottom, $top] = $sections->values()->partition(
            fn (CategorySection $s) => $s->position() === CategorySection::POSITION_BOTTOM
        );

        return ['sectionsTop' => $top->values(), 'sectionsBottom' => $bottom->values()];
    }

    protected function renderCatalog(Request $request, ?Category $category)
    {
        // Página pedida ANTES de que el servicio la ajuste al rango válido:
        // los bloques solo van en la página 1 (no en ?page=2+).
        $requestedPage = (int) $request->input('page', 1);
        $result = $this->catalogResult($request, $category);

        if ($request->wantsJson()) {
            $data = ['result' => $result, 'category' => $category];

            return response()->json([
                'ok'             => true,
                'total'          => $result->total,
                'liveMessage'    => $result->meta['liveMessage'],
                'title'          => ($category->name ?? 'Catálogo') . ' — Equiterm Industries',
                'url'            => $result->meta['stateUrl'],
                'page'           => $result->meta['page'],
                'pages'          => $result->meta['pages'],
                'activeCount'    => $result->meta['activeCount'],
                'noindex'        => $result->meta['noindex'],
                'sidebarHtml'    => view('frontend.shop.catalog.partials.sidebar', $data)->render(),
                'chipsHtml'      => view('frontend.shop.catalog.partials.chips', $data)->render(),
                'toolbarHtml'    => view('frontend.shop.catalog.partials.toolbar', $data)->render(),
                'productsHtml'   => view('frontend.shop.catalog.partials.grid', $data)->render(),
                'paginationHtml' => (string) $result->paginator->links('frontend.shop.partials.pagination'),
            ])->withHeaders(['Vary' => 'Accept', 'Cache-Control' => 'private, no-cache']);
        }

        // Bloques del editor en vivo (propios o heredados del ancestro), solo
        // en la carga completa de la página 1 de una categoría; nunca en las
        // respuestas JSON de filtros/paginación (arriba).
        $sections = ($category && $requestedPage <= 1 && (int) ($result->meta['page'] ?? 1) === 1)
            ? $category->effectiveSections(true)['sections']
            : collect();

        return response()
            ->view('frontend.shop.catalog.index', ['result' => $result, 'category' => $category] + self::splitSections($sections))
            ->header('Vary', 'Accept');
    }
}
