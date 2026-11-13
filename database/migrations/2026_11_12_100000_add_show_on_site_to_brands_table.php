<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marcas > "Mostrar en sitio web": una marca puede estar Activa (se usa en productos, cotizaciones,
     * colecciones…) y aun así no mostrarse en la tienda pública (carrusel de marcas y filtro de marcas del
     * catálogo). Todas las marcas existentes siguen mostrándose (default true): no cambia nada hasta que se apague.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('brands', 'show_on_site')) {
            Schema::table('brands', function (Blueprint $table) {
                $table->boolean('show_on_site')->default(true)->after('is_active');
            });
        }

        Cache::forget('brands.has_show_on_site');
    }

    public function down(): void
    {
        if (Schema::hasColumn('brands', 'show_on_site')) {
            Schema::table('brands', function (Blueprint $table) {
                $table->dropColumn('show_on_site');
            });
        }

        Cache::forget('brands.has_show_on_site');
    }
};
