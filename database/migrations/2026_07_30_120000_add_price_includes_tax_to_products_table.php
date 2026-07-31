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
            // Default false a propósito: los 341 productos existentes hoy
            // están capturados sin IVA (confirmado con el usuario) — al no
            // existir la columna antes, todos quedan en false sin necesidad
            // de un backfill aparte.
            $table->boolean('price_includes_tax')->default(false)->after('compare_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('price_includes_tax');
        });
    }
};
