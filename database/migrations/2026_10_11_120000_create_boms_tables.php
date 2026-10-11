<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(false);
            $table->decimal('yield_quantity', 12, 4)->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'version']);
            $table->index(['product_id', 'is_active']);
        });

        Schema::create('bom_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained('boms')->cascadeOnDelete();
            $table->foreignId('component_product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 12, 4)->default(1);
            $table->string('unit', 30)->nullable();
            $table->decimal('scrap_percent', 5, 2)->default(0);
            $table->string('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('component_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bom_lines');
        Schema::dropIfExists('boms');
    }
};
