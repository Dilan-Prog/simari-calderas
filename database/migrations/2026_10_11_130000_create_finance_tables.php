<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 3 — Finanzas (gestión interna): cuentas por cobrar, por pagar, pagos y
 * aplicaciones de pago. Sin contabilidad general ni timbrado CFDI.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receivables', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            // Origen manual polimórfico (sales_order|quote|project|store_order).
            $table->string('source_type', 30)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            // Sin FK dura: el módulo Proyectos puede no existir aún.
            $table->unsignedBigInteger('project_id')->nullable();
            $table->date('issue_date');
            $table->date('due_date');
            $table->unsignedSmallInteger('credit_days')->default(0);
            $table->string('currency', 3)->default('MXN');
            $table->decimal('exchange_rate', 12, 4)->default(1);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('balance', 14, 2)->default(0);
            $table->string('status', 20)->default('pendiente');
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('due_date');
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('payables', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->string('supplier_invoice', 60)->nullable();
            $table->date('issue_date');
            $table->date('due_date');
            $table->unsignedSmallInteger('credit_days')->default(0);
            $table->string('currency', 3)->default('MXN');
            $table->decimal('exchange_rate', 12, 4)->default(1);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('balance', 14, 2)->default(0);
            $table->string('status', 20)->default('pendiente');
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('due_date');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->string('direction', 3); // in = cobro a cliente, out = pago a proveedor
            $table->foreignId('customer_id')->nullable()->constrained('customers')->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->restrictOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 14, 2);
            $table->decimal('applied_amount', 14, 2)->default(0);
            $table->string('currency', 3)->default('MXN');
            $table->decimal('exchange_rate', 12, 4)->default(1);
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->string('reference', 120)->nullable();
            $table->string('proof_path')->nullable();
            $table->string('status', 20)->default('pendiente');
            $table->timestamp('reconciled_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index(['direction', 'payment_date']);
        });

        Schema::create('payment_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->string('applicable_type', 20); // receivable | payable
            $table->unsignedBigInteger('applicable_id');
            // En la moneda del documento.
            $table->decimal('amount_applied', 14, 2);
            // En la moneda del pago (lo que consume del monto del pago).
            $table->decimal('amount_payment', 14, 2);
            $table->timestamps();

            $table->unique(['payment_id', 'applicable_type', 'applicable_id'], 'payment_app_unique');
            $table->index(['applicable_type', 'applicable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_applications');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('payables');
        Schema::dropIfExists('receivables');
    }
};
