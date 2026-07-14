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
            // FIX BUG 9: 'Moneda' and 'Unidad de Medida' selects existed in
            // the UI with no name= attribute at all and no backing column —
            // decorative in both create and edit.
            $table->string('currency', 3)->default('MXN')->after('compare_price');
            $table->string('stock_unit', 20)->default('pieza')->after('stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['currency', 'stock_unit']);
        });
    }
};
