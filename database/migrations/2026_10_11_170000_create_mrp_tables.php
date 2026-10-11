<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('material_requirements')) {
            Schema::create('material_requirements', function (Blueprint $table) {
                $table->id();
                $table->string('number', 20)->unique();
                $table->string('origin_type', 30)->default('manual')->index(); // sales_order|project|technical_service|production_order|manual
                $table->unsignedBigInteger('origin_id')->nullable();
                // Sin FK a propósito: el módulo Proyectos es independiente.
                $table->unsignedBigInteger('project_id')->nullable()->index();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->unsignedBigInteger('warehouse_id')->nullable();
                $table->date('needed_by_date')->nullable();
                $table->string('status', 20)->default('borrador')->index(); // borrador|calculado|en_compra|cubierto|cancelado
                $table->boolean('include_chemicals')->default(false);
                $table->text('notes')->nullable();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['origin_type', 'origin_id']);
            });
        }

        if (!Schema::hasTable('material_requirement_lines')) {
            Schema::create('material_requirement_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('requirement_id')->constrained('material_requirements')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->decimal('required_quantity', 14, 4)->default(0);
                $table->decimal('on_hand', 14, 4)->default(0);
                $table->decimal('reserved', 14, 4)->default(0);       // reservado por OTROS requerimientos/proyectos
                $table->decimal('own_reserved', 14, 4)->default(0);   // reservado para este requerimiento
                $table->decimal('shortfall', 14, 4)->default(0);
                $table->string('unit', 30)->nullable();
                $table->string('source', 20)->default('manual');      // componente_bom|linea_pedido|servicio|quimicos|manual
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
                $table->decimal('suggested_price', 12, 2)->nullable();
                $table->string('suggested_currency', 3)->nullable();
                $table->unsignedBigInteger('purchase_order_id')->nullable()->index();
                $table->string('status', 20)->default('pendiente');  // pendiente|reservado|en_compra|cubierto
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['requirement_id', 'product_id']);
            });
        }

        if (!Schema::hasTable('stock_reservations')) {
            Schema::create('stock_reservations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->unsignedBigInteger('warehouse_id')->nullable();
                $table->decimal('quantity', 14, 4)->default(0);
                $table->unsignedBigInteger('project_id')->nullable()->index();
                $table->unsignedBigInteger('requirement_id')->nullable()->index();
                $table->string('source_type', 40)->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->string('status', 20)->default('activa'); // activa|liberada|consumida
                $table->unsignedBigInteger('created_by_user_id')->nullable();
                $table->timestamps();

                $table->index(['product_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('material_requirement_lines');
        Schema::dropIfExists('material_requirements');
        // stock_reservations puede ser usada por Almacén/Producción: no se elimina aquí.
    }
};
