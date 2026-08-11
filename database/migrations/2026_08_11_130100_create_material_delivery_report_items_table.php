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
            $table->foreignId('material_delivery_report_id');
            $table->foreignId('sales_order_item_id');
            $table->string('product_name', 180);
            $table->string('product_sku', 80)->nullable();
            $table->string('unit', 30)->nullable();
            $table->integer('quantity_delivered_in_event');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Nombres cortos explícitos: los autogenerados por Laravel para
            // estas columnas exceden el límite de 64 caracteres de MySQL
            // para identificadores (mismo problema ya corregido en la
            // migración de material_delivery_report_images).
            $table->foreign('material_delivery_report_id', 'mdr_items_report_id_fk')
                ->references('id')->on('material_delivery_reports')
                ->cascadeOnDelete();
            $table->foreign('sales_order_item_id', 'mdr_items_so_item_id_fk')
                ->references('id')->on('sales_order_items')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_delivery_report_items');
    }
};
