<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Colecciones > "Mostrar productos": una colección puede ser solo de contenido (guía, artículo SEO…).
     * Apagado, la página pública oculta el contador "N productos" y la cuadrícula / mensaje de "sin productos".
     * Las colecciones existentes siguen igual (default true).
     */
    public function up(): void
    {
        if (! Schema::hasColumn('collections', 'show_products')) {
            Schema::table('collections', function (Blueprint $table) {
                $table->boolean('show_products')->default(true)->after('is_active');
            });
        }

        Cache::forget('collections.has_show_products');
    }

    public function down(): void
    {
        if (Schema::hasColumn('collections', 'show_products')) {
            Schema::table('collections', function (Blueprint $table) {
                $table->dropColumn('show_products');
            });
        }

        Cache::forget('collections.has_show_products');
    }
};
