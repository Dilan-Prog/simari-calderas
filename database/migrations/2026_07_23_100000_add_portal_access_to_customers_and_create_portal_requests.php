<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // El portal /customer (reportes de servicio) es un privilegio que
            // el admin activa por cliente desde el candado de Clientes.
            $table->boolean('portal_access')->default(false)->index()->after('status');
        });

        // Solicitud de acceso al portal: una fila por cliente que se va
        // llenando conforme el wizard guarda cada respuesta (autosave).
        Schema::create('customer_portal_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->unique();
            $table->string('purchase_frequency', 20)->nullable();   // mensual|trimestral|semestral
            $table->string('purchase_amount', 30)->nullable();      // rango de monto
            $table->string('reason', 500)->nullable();
            $table->string('responsible_name', 150)->nullable();
            $table->string('rfc', 20)->nullable();
            $table->string('tax_regime', 150)->nullable();
            $table->string('contact_email', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('fiscal_certificate_path', 255)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_portal_requests');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('portal_access');
        });
    }
};
