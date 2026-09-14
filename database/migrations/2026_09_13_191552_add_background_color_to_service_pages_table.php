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
            // Color de fondo de la página completa (hex), editable desde
            // "Información general" en el editor en vivo. NULL = fondo
            // blanco por defecto (comportamiento actual, sin cambios).
            $table->string('background_color', 7)->nullable()->after('cover_image_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_pages', function (Blueprint $table) {
            $table->dropColumn('background_color');
        });
    }
};
