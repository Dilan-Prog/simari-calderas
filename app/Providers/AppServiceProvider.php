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

        // Fase 16: reemplaza los Model::observe() bespoke (DealObserver,
        // WhatsappConversationObserver) por un bucle sobre el registro
        // central de módulos automatizables. Cada modelo declarado en
        // config/automatable_modules.php (incluyendo Deal y
        // WhatsappConversation, que también son entradas del registro) se
        // registra sobre el mismo AutomatableModelObserver genérico.
        // DealObserver/WhatsappConversationObserver se retiran (Fase 20)
        // solo una vez confirmado por tests de regresión que el observer
        // genérico reproduce su comportamiento exacto.
        $registry = app(\App\Services\AutomatableModuleRegistry::class);
        foreach ($registry->all() as $type => $entry) {
            if (!empty($entry['model']) && class_exists($entry['model'])) {
                $entry['model']::observe(\App\Observers\AutomatableModelObserver::class);
            }
        }
    }
}
