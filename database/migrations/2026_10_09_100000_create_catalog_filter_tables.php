<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo estilo Mercado Libre:
     *  - category_filter_groups / category_filter_options: filtros técnicos por
     *    categoría. Cada opción apunta a una ETIQUETA de producto (products.tags);
     *    `tag_normalized` (minúsculas/ASCII/espacios colapsados, ver
     *    App\Services\Catalog\TagNormalizer) es contra lo que se compara.
     *  - product_sales_stats: unidades vendidas en la ventana configurada
     *    (catalog.best_seller_days), refrescadas por `catalog:refresh-sales`.
     */
    public function up(): void
    {
        Schema::create('category_filter_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('product_categories')->cascadeOnDelete();
            $table->string('name', 60);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category_id', 'sort_order']);
        });

        Schema::create('category_filter_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('category_filter_groups')->cascadeOnDelete();
            $table->string('label', 80);
            // Etiqueta tal cual se escribe en el producto, y su forma normalizada.
            $table->string('tag', 80);
            $table->string('tag_normalized', 80)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['group_id', 'tag_normalized']);
        });

        Schema::create('product_sales_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('units')->default(0);
            $table->timestamp('computed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_sales_stats');
        Schema::dropIfExists('category_filter_options');
        Schema::dropIfExists('category_filter_groups');
    }
};
