<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('production_orders')) {
            Schema::create('production_orders', function (Blueprint $table) {
                $table->id();
                $table->string('number', 20)->unique(); // PRD-AAAA-NNNN
                $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
                $table->unsignedBigInteger('bom_id')->nullable();
                $table->decimal('quantity_planned', 14, 4)->default(1);
                $table->decimal('quantity_produced', 14, 4)->default(0);
                $table->unsignedBigInteger('warehouse_id')->nullable();
                // Sin FK a propósito: Proyectos / Pedidos / MRP son módulos independientes.
                $table->unsignedBigInteger('project_id')->nullable()->index();
                $table->unsignedBigInteger('sales_order_id')->nullable()->index();
                $table->unsignedBigInteger('material_requirement_id')->nullable()->index();
                $table->date('due_date')->nullable()->index();
                $table->date('start_date')->nullable();
                $table->string('status', 20)->default('borrador')->index(); // borrador|liberada|en_proceso|terminada|cancelada
                $table->unsignedTinyInteger('progress')->default(0);
                $table->decimal('standard_cost', 16, 4)->default(0);
                $table->decimal('actual_cost', 16, 4)->default(0);
                $table->text('notes')->nullable();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('production_order_components')) {
            Schema::create('production_order_components', function (Blueprint $table) {
                $table->id();
                $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
                $table->foreignId('component_product_id')->constrained('products')->restrictOnDelete();
                $table->decimal('required_quantity', 14, 4)->default(0);
                $table->decimal('reserved_quantity', 14, 4)->default(0);
                $table->decimal('consumed_quantity', 14, 4)->default(0);
                $table->string('unit', 30)->nullable();
                $table->decimal('unit_cost', 14, 4)->default(0);
                $table->timestamps();

                $table->index(['production_order_id', 'component_product_id'], 'prd_comp_order_product_idx');
            });
        }

        if (!Schema::hasTable('production_order_logs')) {
            Schema::create('production_order_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 30);
                $table->string('from_status', 20)->nullable();
                $table->string('to_status', 20)->nullable();
                $table->unsignedTinyInteger('progress')->nullable();
                $table->text('note')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('production_order_logs');
        Schema::dropIfExists('production_order_components');
        Schema::dropIfExists('production_orders');
    }
};
