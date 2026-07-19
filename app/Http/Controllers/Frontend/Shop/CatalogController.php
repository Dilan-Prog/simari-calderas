<?php

namespace App\Http\Controllers\Frontend\Shop;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Products;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function home()
    {
        $sections = HomeSection::where('is_active', true)
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
        $category = Category::where('slug', $categorySlug)->where('is_active', true)->firstOrFail();

        return $this->renderCatalog($request, $category);
    }

    protected function renderCatalog(Request $request, ?Category $category)
    {
        $query = Products::query()
            ->where('is_active', true)
            ->where('publish_on_website', true)
            ->with(['brand', 'category', 'images' => function ($q) {
                $q->orderBy('sort_order')->limit(1);
            }]);

        if ($category) {
            $categoryIds = $category->children()->pluck('id')->push($category->id);
            $query->whereIn('category_id', $categoryIds);
        }

        if ($request->filled('categoria')) {
            $query->whereIn('category_id', (array) $request->input('categoria'));
        }
        if ($request->filled('marca')) {
            $query->whereIn('brand_id', (array) $request->input('marca'));
        }
        if ($request->filled('q')) {
            $term = $request->input('q');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%");
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

        $categoryOptions = Category::where('is_active', true)
            ->withCount(['products' => function ($q) {
                $q->where('is_active', true)->where('publish_on_website', true);
            }])
            ->orderBy('sort_order')
            ->get();

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
