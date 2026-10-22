<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ad_events', function (Blueprint $table) {
            // Que boton/elemento disparo el evento (flotante, producto, tarjeta...).
            $table->string('element', 30)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ad_events', function (Blueprint $table) {
            $table->dropColumn('element');
        });
    }
};
