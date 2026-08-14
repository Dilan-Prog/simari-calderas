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
        // Doctrine/DBAL no trae soporte nativo para el tipo MySQL "enum".
        // Cuando una migración hace ->enum(...)->change() (ej.
        // database/migrations/2026_06_10_061827_update_services_table.php),
        // Laravel necesita DOS cosas de Doctrine, no solo una:
        //   1. Un mapeo de "cómo se llama en la BD" -> "tipo Doctrine" para
        //      INTROSPECCIONAR la columna existente. Laravel ya registra
        //      esto solo (ChangeColumn::compile() llama
        //      $platform->registerDoctrineTypeMapping('enum', 'string')).
        //   2. Que el tipo Doctrine "enum" (el que declara el propio
        //      Blueprint::enum() de Laravel como $fluent['type']) exista en
        //      el registro GLOBAL Doctrine\DBAL\Types\Type -- esto Laravel
        //      NO lo registra, y sin él CUALQUIER ->enum(...)->change()
        //      revienta con "Unknown column type enum" (ChangeColumn.php
        //      línea 155, vía Type::getType('enum')), tumbando TODO
        //      `php artisan migrate`/`migrate:fresh` posterior a esa
        //      migración -- incluye el migrate:fresh que corre
        //      RefreshDatabase en CADA suite de tests del proyecto.
        // Registrar "enum" como alias de StringType (mismo mapeo que ya usa
        // Laravel para introspección) es el workaround estándar; no cambia
        // el tipo real de la columna en MySQL.
        try {
            if (! \Doctrine\DBAL\Types\Type::hasType('enum')) {
                \Doctrine\DBAL\Types\Type::addType('enum', \Doctrine\DBAL\Types\StringType::class);
            }

            $connection = \Illuminate\Support\Facades\Schema::getConnection();
            if ($connection->getDriverName() === 'mysql') {
                $connection->getDoctrineSchemaManager()
                    ->getDatabasePlatform()
                    ->registerDoctrineTypeMapping('enum', 'string');
            }
        } catch (\Throwable $e) {
            // No debe impedir el boot de la app si Doctrine/DBAL no está
            // disponible por cualquier razón.
        }

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
