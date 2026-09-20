<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Separa "Colonia" (ya vivía en shipping_address_line2) de "Referencias
    // de entrega" -- hoy comparten un solo campo libre, mezclando 2
    // conceptos distintos del formulario de envío del checkout.
    // customer_addresses.reference YA EXISTE (migración
    // 2026_04_19_172737_create_customer_addresses_table.php) y ya está en
    // CustomerAddress::$fillable, sin usarse -- store_orders no tenía
    // equivalente hasta ahora.
    public function up(): void
    {
        Schema::table('store_orders', function (Blueprint $table) {
            $table->string('shipping_reference', 255)->nullable()->after('shipping_address_line2');
        });
    }

    public function down(): void
    {
        Schema::table('store_orders', function (Blueprint $table) {
            $table->dropColumn('shipping_reference');
        });
    }
};
