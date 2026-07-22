<?php

namespace App\View\Composers;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Products;
use Illuminate\View\View;

class ShopMegaMenuComposer
{
    public function compose(View $view): void
    {
        $categories = Category::where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->with(['children' => function ($q2) {
                        $q2->where('is_active', true)
                            ->orderBy('sort_order')
                            ->orderBy('name');
                    }]);
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $publishedProducts = Products::query()
            ->where('is_active', true)
            ->where('publish_on_website', true)
            ->with(['images' => fn ($q) => $q->orderBy('sort_order')])
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->get();

        $categoryProducts = $publishedProducts->groupBy('category_id')
            ->map(fn ($items) => $items->take(4));

        $headerMain = Menu::where('location', 'header-main')->where('is_active', true)->first();
        $headerServicios = Menu::where('location', 'header-servicios')->where('is_active', true)->first();

        $view->with([
            'megaMenuCategories'       => $categories,
            'megaMenuCategoryProducts' => $categoryProducts,
            'headerMainItems'          => $headerMain ? $headerMain->rootItems()->get() : collect(),
            'headerServiciosItems'     => $headerServicios ? $headerServicios->rootItems()->get() : collect(),
        ]);
    }
}
