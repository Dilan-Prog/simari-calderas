<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Default false: a diferencia de show_in_merchant_center, un
            // producto nuevo NO acepta meses sin intereses hasta que un
            // admin lo marque explícitamente — Mercado Pago aplica cuotas
            // sobre el monto total de un cobro, así que activarlo sin
            // querer en un producto equivocado tendría impacto financiero
            // real (aceptar MSI implica absorber el costo de la cuota).
            $table->boolean('accepts_msi')->default(false)->after('show_in_merchant_center');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('accepts_msi');
        });
    }
};
