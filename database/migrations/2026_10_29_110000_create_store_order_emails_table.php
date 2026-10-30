<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bitácora de los correos automáticos de pedidos de la tienda: qué se envió a quién, cuándo y si
 * falló. También sirve para no repetir el mismo aviso de un pedido (un registro `sent` por evento).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_order_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_order_id')->constrained('store_orders')->cascadeOnDelete();
            $table->string('event', 30);
            $table->string('to_email', 190)->nullable();
            $table->string('subject', 255)->nullable();
            $table->string('status', 12); // sent | failed | skipped
            $table->text('error')->nullable();
            $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['store_order_id', 'event', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_order_emails');
    }
};
