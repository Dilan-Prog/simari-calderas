<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bloques del editor en vivo de Categorías (mismo esquema que
     * collection_sections). Una subcategoría sin bloques propios hereda los
     * del ancestro más cercano que sí tenga (ver Category::effectiveSections()).
     */
    public function up(): void
    {
        Schema::create('category_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('product_categories')->cascadeOnDelete();
            $table->string('type', 40);
            $table->json('config')->nullable();
            $table->string('title', 255)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_sections');
    }
};
