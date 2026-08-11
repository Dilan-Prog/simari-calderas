<?php

namespace App\Http\Controllers\Frontend\Shop;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Products;
use App\Models\Redirect;
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
     */
    public function liveSearch(Request $request)
    {
        $term = trim((string) $request->input('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['total' => 0, 'categories' => [], 'brands' => [], 'productsHtml' => '']);
        }

        $baseQuery = Products::where('is_active', true)
            ->where('publish_on_website', true)
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$term}%"));
            });

        $categoryCounts = (clone $baseQuery)
            ->selectRaw('category_id, count(*) as cnt')
            ->groupBy('category_id')
            ->pluck('cnt', 'category_id');

        $categories = Category::whereIn('id', $categoryCounts->keys())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($cat) => ['id' => $cat->id, 'name' => $cat->name, 'count' => $categoryCounts->get($cat->id, 0)])
            ->values();

        $brandCounts = (clone $baseQuery)
            ->whereNotNull('brand_id')
            ->selectRaw('brand_id, count(*) as cnt')
            ->groupBy('brand_id')
            ->pluck('cnt', 'brand_id');

        $brands = Brand::whereIn('id', $brandCounts->keys())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($brand) => ['id' => $brand->id, 'name' => $brand->name, 'count' => $brandCounts->get($brand->id, 0)])
            ->values();

        $productsQuery = clone $baseQuery;

        if ($request->filled('categoria')) {
            $productsQuery->whereIn('category_id', (array) $request->input('categoria'));
        }
        if ($request->filled('marca')) {
            $productsQuery->whereIn('brand_id', (array) $request->input('marca'));
        }

        match ($request->input('orden', 'relevancia')) {
            'precio_asc'  => $productsQuery->orderBy('price', 'asc'),
            'precio_desc' => $productsQuery->orderBy('price', 'desc'),
            'descuento'   => $productsQuery->orderByRaw('(COALESCE(compare_price, 0) - price) DESC'),
            default       => $productsQuery->orderByDesc('is_featured')->orderByDesc('created_at'),
        };

        $total = (clone $productsQuery)->count();

        $products = $productsQuery
            ->with(['images' => fn ($q) => $q->orderBy('sort_order')])
            ->take(12)
            ->get();

        return response()->json([
            'total'        => $total,
            'categories'   => $categories,
            'brands'       => $brands,
            'productsHtml' => view('frontend.shop.partials.search-results-grid', [
                'products' => $products,
                'term'     => $term,
            ])->render(),
        ]);
    }

    protected function renderCatalog(Request $request, ?Category $category)
    {
        $query = Products::query()
            ->where('is_active', true)
            ->where('publish_on_website', true)
            ->with(['brand', 'category', 'images' => function ($q) {
                $q->orderBy('sort_order');
            }]);

        // No se pueden aplicar como dos whereIn() independientes: Eloquent los
        // une con AND sobre la misma columna, y si el checkbox marcado es una
        // subcategoría anidada (nieto+) del $category de la ruta, la
        // intersección quedaba vacía aunque el producto sí perteneciera a
        // ambos alcances lógicamente. Se combinan explícitamente aquí.
        $categoryIds = $category ? $category->idsWithChildren() : null;

        if ($request->filled('categoria')) {
            $requestedIds = array_map('intval', (array) $request->input('categoria'));
            $categoryIds = $categoryIds ? array_values(array_intersect($categoryIds, $requestedIds)) : $requestedIds;
        }

        if ($categoryIds !== null) {
            $query->whereIn('category_id', $categoryIds);
        }
        if ($request->filled('marca')) {
            $query->whereIn('brand_id', (array) $request->input('marca'));
        }
        if ($request->filled('q')) {
            $term = $request->input('q');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$term}%"));
            });
        }
        if ($request->filled('precio_min')) {
            $query->where('price', '>=', (float) $request->input('precio_min'));
        }
        if ($request->filled('precio_max')) {
            $query->where('price', '<=', (float) $request->input('precio_max'));
        }

        match ($request->input('orden', 'relevancia')) {
            'precio_asc'  => $query->orderBy('price', 'asc'),
            'precio_desc' => $query->orderBy('price', 'desc'),
            'descuento'   => $query->orderByRaw('(COALESCE(compare_price, 0) - price) DESC'),
            default       => $query->orderByDesc('is_featured')->orderByDesc('created_at'),
        };

        $products = $query->paginate(24)->withQueryString();

        $allCategories = Category::where('is_active', true)->get(['id', 'parent_id', 'name', 'slug', 'sort_order']);

        $publishedCountsByCategory = Products::where('is_active', true)
            ->where('publish_on_website', true)
            ->selectRaw('category_id, count(*) as cnt')
            ->groupBy('category_id')
            ->pluck('cnt', 'category_id');

        $descendantIds = function (int $categoryId) use ($allCategories, &$descendantIds) {
            return $allCategories->where('parent_id', $categoryId)->pluck('id')
                ->flatMap(fn ($id) => collect([$id])->merge($descendantIds($id)));
        };

        $categoryOptions = $allCategories->sortBy('sort_order')->values()->map(function ($cat) use ($publishedCountsByCategory, $descendantIds) {
            $ids = collect([$cat->id])->merge($descendantIds($cat->id));
            $cat->products_count = $ids->sum(fn ($id) => $publishedCountsByCategory->get($id, 0));

            return $cat;
        });

        $brandOptions = Brand::where('is_active', true)
            ->withCount(['products' => function ($q) {
                $q->where('is_active', true)->where('publish_on_website', true);
            }])
            ->orderBy('name')
            ->get();

        $priceBounds = Products::where('is_active', true)
            ->where('publish_on_website', true)
            ->selectRaw('MIN(price) as min_price, MAX(price) as max_price')
            ->first();

        $productsByCategory = $products->getCollection()->groupBy(function ($product) {
            return $product->category->name ?? 'Otros';
        });

        return view('frontend.shop.catalog.index', compact(
            'products', 'productsByCategory', 'categoryOptions', 'brandOptions', 'priceBounds', 'category'
        ));
    }
}
