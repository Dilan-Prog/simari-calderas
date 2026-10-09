<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            // A dónde lleva el logo de la marca en el carrusel de marcas
            // (bloque brand_carousel). Opcional: sin URL, el logo no es enlace.
            $table->string('redirect_url', 500)->nullable()->after('logo_url');
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn('redirect_url');
        });
    }
};
