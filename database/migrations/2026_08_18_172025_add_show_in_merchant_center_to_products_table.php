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
            // Default true: al activar el feed, todo el catálogo público
            // (is_active + publish_on_website) ya se incluye sin trabajo
            // manual — el admin apaga excepciones puntuales (servicios,
            // productos incompletos) en vez de tener que prender uno por uno.
            $table->boolean('show_in_merchant_center')->default(true)->after('publish_on_website');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('show_in_merchant_center');
        });
    }
};
