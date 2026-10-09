<?php

namespace App\Http\Controllers\Frontend\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Redirect;
use App\Services\Catalog\CatalogParams;
use App\Services\Catalog\CatalogQuery;
use Illuminate\Http\Request;

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

    protected function renderCatalog(Request $request, ?Category $category)
    {
        $params = CatalogParams::fromRequest($request, $category);
        $baseUrl = $category ? route('catalog.category', $category->slug) : route('catalog.index');

        $result = app(CatalogQuery::class)->run($params, $category, $baseUrl, 24);

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

        return response()
            ->view('frontend.shop.catalog.index', ['result' => $result, 'category' => $category])
            ->header('Vary', 'Accept');
    }
}
