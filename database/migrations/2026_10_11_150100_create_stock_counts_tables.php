<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_counts', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->unsignedBigInteger('warehouse_id');
            $table->string('status', 20)->default('abierto'); // abierto|en_conteo|cerrado|ajustado
            $table->timestamp('started_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('adjusted_at')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('closed_by_user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
            $table->index('status');
        });

        Schema::create('stock_count_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('count_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('system_quantity')->default(0);
            $table->integer('counted_quantity')->nullable();
            $table->integer('difference')->nullable();
            $table->timestamps();

            $table->foreign('count_id')->references('id')->on('stock_counts')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
            $table->unique(['count_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_count_lines');
        Schema::dropIfExists('stock_counts');
    }
};
