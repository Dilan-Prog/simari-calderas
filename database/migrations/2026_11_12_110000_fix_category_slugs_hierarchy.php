<?php

use App\Services\Catalog\CategorySlugs;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Repara los slugs de las subcategorías que quedaron sin la ruta de su padre ("boiler-de-paso" en vez de
     * "calentadores-de-agua/boiler-de-paso") o con segmentos pegados. Es la URL pública (/catalogo/{slug}), así
     * que antes de cambiar cada slug se registra el redirect 301 de la URL vieja: lo ya indexado sigue llegando.
     * Idempotente: si ya está todo bien, no cambia nada.
     */
    public function up(): void
    {
        CategorySlugs::repairAll();
    }

    public function down(): void
    {
        // No se revierte: las URLs viejas quedan redirigiendo a las nuevas.
    }
};
