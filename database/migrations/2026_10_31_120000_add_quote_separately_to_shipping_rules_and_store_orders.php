<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // "Envío se cotiza aparte" por Regla de Envío (marca/categoría): el
    // envío de esos productos no se cobra en línea, se cotiza a la
    // dirección del cliente después de la compra -- salvo que el subtotal
    // de esa marca/categoría alcance free_shipping_threshold (gratis).
    // store_orders.shipping_pending_quote marca el pedido cuyo envío
    // quedó pendiente de cotizar (shipping_total no lo incluye).
    public function up(): void
    {
        Schema::table('shipping_rules', function (Blueprint $table) {
            $table->boolean('quote_separately')->default(false)->after('free_shipping_threshold');
        });

        Schema::table('store_orders', function (Blueprint $table) {
            $table->boolean('shipping_pending_quote')->default(false)->after('shipping_total');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_rules', function (Blueprint $table) {
            $table->dropColumn('quote_separately');
        });

        Schema::table('store_orders', function (Blueprint $table) {
            $table->dropColumn('shipping_pending_quote');
        });
    }
};
