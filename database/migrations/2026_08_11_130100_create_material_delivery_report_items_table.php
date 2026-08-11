<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_delivery_report_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_delivery_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_order_item_id')->constrained('sales_order_items')->restrictOnDelete();
            $table->string('product_name', 180);
            $table->string('product_sku', 80)->nullable();
            $table->string('unit', 30)->nullable();
            $table->integer('quantity_delivered_in_event');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_delivery_report_items');
    }
};
