<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            // Tipo de cambio USD→MXN vigente al momento de guardar esta
            // cotización — editable por documento (no un snapshot congelado:
            // se re-guarda tal cual el campo del formulario en cada save).
            // Nullable porque cotizaciones anteriores a esta fase no lo tienen.
            $table->decimal('exchange_rate', 10, 4)->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('exchange_rate');
        });
    }
};
