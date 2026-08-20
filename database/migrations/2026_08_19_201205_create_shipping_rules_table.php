<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipping_rules', function (Blueprint $table) {
            $table->id();
            // Exactamente uno de los dos debe estar lleno (validado en el
            // controller, no aquí) — una regla es de marca O de categoría,
            // nunca ambas ni ninguna. unique() en MySQL permite múltiples
            // NULL sin choque, así que solo se aplica de verdad al que esté
            // lleno: como mucho una regla por marca y una por categoría.
            $table->foreignId('brand_id')->nullable()->unique()->constrained('brands')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->unique()->constrained('product_categories')->cascadeOnDelete();
            // Igual convención que products.shipping_cost: null/0 = sin
            // cargo especial de esta regla (deja el shipping_cost propio de
            // cada producto, si tiene uno, intacto).
            $table->decimal('shipping_cost', 10, 2)->nullable();
            // Umbral evaluado contra el SUBTOTAL (sin IVA) de SOLO los
            // productos del carrito que caen bajo esta regla (misma marca o
            // categoría) — no el subtotal de todo el carrito.
            $table->decimal('free_shipping_threshold', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_rules');
    }
};
