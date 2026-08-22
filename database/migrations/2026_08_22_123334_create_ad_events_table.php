<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_visit_id')->constrained('ad_visits')->cascadeOnDelete();
            // Whitelist validada a nivel app (Rule::in), no un enum de BD,
            // para poder agregar tipos de evento nuevos sin migración:
            // page_view | whatsapp_click | add_to_cart | cart_abandoned |
            // quote_start | checkout_start
            $table->string('event_type');
            $table->string('url', 2048);
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            // Asignado SIEMPRE por el servidor (now()) al insertar -- nunca
            // se confía en un timestamp mandado por el cliente.
            $table->timestamp('occurred_at');

            $table->index(['ad_visit_id', 'event_type', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_events');
    }
};
