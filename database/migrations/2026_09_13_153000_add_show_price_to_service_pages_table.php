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
        Schema::table('service_pages', function (Blueprint $table) {
            // Independiente de is_active (que controla si la página entera
            // se publica): permite publicar el servicio pero cotizar el
            // precio en privado en vez de mostrarlo en el sitio. Default
            // true para que ningún servicio existente pierda su precio
            // visible al desplegar esta migración.
            $table->boolean('show_price')->default(true)->after('currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_pages', function (Blueprint $table) {
            $table->dropColumn('show_price');
        });
    }
};
