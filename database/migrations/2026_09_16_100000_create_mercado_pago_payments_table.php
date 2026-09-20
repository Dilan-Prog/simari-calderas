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
        Schema::create('mercado_pago_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_order_id')->constrained('store_orders'); // sin cascadeOnDelete — un pedido no se borra duro
            $table->unsignedTinyInteger('charge_group'); // 1 o 2
            $table->boolean('includes_msi')->default(false);
            $table->json('store_order_item_ids'); // IDs de StoreOrderItem cubiertos por este cobro
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('MXN');
            $table->unsignedTinyInteger('installments')->nullable();
            $table->string('mp_payment_id')->nullable()->unique();
            $table->string('mp_preference_id')->nullable();
            $table->string('mp_payment_method')->nullable();
            $table->string('mp_payment_type')->nullable();
            $table->string('status', 30)->default('pending');
            $table->string('status_detail')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->unsignedTinyInteger('attempts')->default(1);
            $table->timestamp('paid_at')->nullable();
            $table->json('raw_last_response')->nullable(); // NUNCA debe contener datos de tarjeta, solo la respuesta de MP (que ya no trae PAN completo)
            $table->timestamps();
            $table->index(['store_order_id', 'charge_group']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mercado_pago_payments');
    }
};
