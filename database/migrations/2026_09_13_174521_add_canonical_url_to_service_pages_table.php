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
            // Mismo criterio que products.canonical_url: solo afecta el
            // <link rel="canonical"> cuando esta página es muy parecida a
            // otra y se quiere que Google indexe esa otra en su lugar. Vacío
            // (default) = esta misma página es su propia canónica.
            $table->string('canonical_url', 255)->nullable()->after('seo_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_pages', function (Blueprint $table) {
            $table->dropColumn('canonical_url');
        });
    }
};
