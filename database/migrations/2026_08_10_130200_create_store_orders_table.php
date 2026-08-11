<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NOTA: se llama "store_orders" (no "orders") a propósito — ya existe una
     * tabla "orders" huérfana de un batch de migraciones legado
     * (2026_04_19_*), sin modelo/controller/ruta, de otro subsistema muerto.
     * No la tocamos; esta es una tabla nueva e independiente.
     */
    public function up(): void
    {
        Schema::create('store_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('contact_name', 150);
            $table->string('contact_email', 150);
            $table->string('contact_phone', 30);
            $table->string('shipping_address_line1', 255);
            $table->string('shipping_address_line2', 255)->nullable();
            $table->string('shipping_city', 100);
            $table->string('shipping_state', 100);
            $table->string('shipping_postal_code', 20);
            $table->string('shipping_country', 2)->default('MX');
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('shipping_total', 10, 2)->default(0);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('tax_total', 10, 2);
            $table->decimal('total', 10, 2);
            $table->string('currency', 3)->default('MXN');
            $table->string('status', 30)->default('pendiente_pago');
            $table->timestamp('terms_accepted_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_orders');
    }
};
