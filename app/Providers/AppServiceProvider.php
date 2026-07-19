<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

    // SEO Helper
        require_once app_path('Helpers/SeoHelper.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('frontend.shop.layouts.header', \App\View\Composers\ShopMegaMenuComposer::class);
        View::composer('frontend.shop.layouts.footer', \App\View\Composers\ShopFooterComposer::class);
    }
}
