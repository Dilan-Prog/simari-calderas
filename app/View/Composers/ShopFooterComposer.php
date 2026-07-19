<?php

namespace App\View\Composers;

use App\Models\Menu;
use Illuminate\View\View;

class ShopFooterComposer
{
    public function compose(View $view): void
    {
        $view->with([
            'footerServiceItems' => $this->items('footer-services'),
            'footerProductItems' => $this->items('footer-products'),
            'footerCompanyItems' => $this->items('footer-company'),
        ]);
    }

    protected function items(string $location)
    {
        $menu = Menu::where('location', $location)->where('is_active', true)->first();

        return $menu ? $menu->rootItems()->get() : collect();
    }
}
