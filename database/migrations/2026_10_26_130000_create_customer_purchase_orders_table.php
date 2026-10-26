<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Órdenes de compra del CLIENTE (PDF que el cliente sube desde el portal /customer).
 * No confundir con `purchase_orders` (órdenes de compra de Equiterm a sus proveedores).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('po_number', 60)->nullable();          // folio propio de la OC del cliente
            $table->date('po_date')->nullable();
            $table->foreignId('quote_id')->nullable()->constrained('quotes')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->string('file_path');                           // disco privado `local`
            $table->string('original_name');
            $table->string('mime', 100)->default('application/pdf');
            $table->unsignedInteger('size')->default(0);
            // Usuario del equipo del portal que la subió (null = titular). Sin FK a propósito.
            $table->unsignedBigInteger('uploaded_by_member_id')->nullable()->index();
            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_purchase_orders');
    }
};
