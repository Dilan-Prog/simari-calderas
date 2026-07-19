<?php

namespace App\View\Composers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Products;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ShopMegaMenuComposer
{
    public function compose(View $view): void
    {
        $categories = Category::where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $brandRows = Products::query()
            ->where('is_active', true)
            ->where('publish_on_website', true)
            ->whereNotNull('brand_id')
            ->select('category_id', 'brand_id', DB::raw('count(*) as cnt'))
            ->groupBy('category_id', 'brand_id')
            ->get();

        $brandsById = Brand::where('is_active', true)->get()->keyBy('id');

        $categoryBrands = $brandRows->groupBy('category_id')->map(function ($rows) use ($brandsById) {
            return $rows->map(function ($row) use ($brandsById) {
                return [
                    'brand' => $brandsById->get($row->brand_id),
                    'count' => $row->cnt,
                ];
            })->filter(fn ($row) => $row['brand'])->sortByDesc('count')->values();
        });

        $publishedProducts = Products::query()
            ->where('is_active', true)
            ->where('publish_on_website', true)
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->get();

        $categoryProducts = $publishedProducts->groupBy('category_id')
            ->map(fn ($items) => $items->take(4));

        $categoryBrandProducts = $publishedProducts->whereNotNull('brand_id')
            ->groupBy(fn ($product) => $product->category_id . '-' . $product->brand_id)
            ->map(fn ($items) => $items->take(6));

        $headerMain = Menu::where('location', 'header-main')->where('is_active', true)->first();
        $headerServicios = Menu::where('location', 'header-servicios')->where('is_active', true)->first();

        $view->with([
            'megaMenuCategories'       => $categories,
            'megaMenuCategoryBrands'   => $categoryBrands,
            'megaMenuCategoryProducts' => $categoryProducts,
            'megaMenuBrandProducts'    => $categoryBrandProducts,
            'headerMainItems'          => $headerMain ? $headerMain->rootItems()->get() : collect(),
            'headerServiciosItems'     => $headerServicios ? $headerServicios->rootItems()->get() : collect(),
        ]);
    }
}
