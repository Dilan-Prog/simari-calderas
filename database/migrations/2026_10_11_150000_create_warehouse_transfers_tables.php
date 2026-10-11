<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->unsignedBigInteger('from_warehouse_id');
            $table->unsignedBigInteger('to_warehouse_id');
            $table->string('status', 20)->default('borrador'); // borrador|en_transito|recibido|cancelado
            $table->text('notes')->nullable();
            $table->text('receipt_notes')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->unsignedBigInteger('requested_by_user_id')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->unsignedBigInteger('shipped_by_user_id')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->unsignedBigInteger('received_by_user_id')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('from_warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
            $table->foreign('to_warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
            $table->index('status');
        });

        Schema::create('warehouse_transfer_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transfer_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity');
            $table->integer('received_quantity')->nullable();
            $table->timestamps();

            $table->foreign('transfer_id')->references('id')->on('warehouse_transfers')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_transfer_lines');
        Schema::dropIfExists('warehouse_transfers');
    }
};
