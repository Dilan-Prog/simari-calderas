<?php

namespace App\View\Composers;

use App\Models\Menu;
use Illuminate\View\View;

class ShopFooterComposer
{
    public function compose(View $view): void
    {
        $view->with([
            'footerMenus' => Menu::where('location', 'footer')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Menu $menu) => ['menu' => $menu, 'items' => $menu->rootItems()->get()]),
        ]);
    }
}
