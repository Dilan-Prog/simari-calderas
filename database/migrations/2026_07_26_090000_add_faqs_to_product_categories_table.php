<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            // Preguntas frecuentes personalizadas por categoría:
            // [{question, answer}, ...]. Mismo patrón que products.faqs y
            // collections.faqs; alimentan el bloque FAQ visible del catálogo
            // + el JSON-LD FAQPage.
            $table->json('faqs')->nullable()->after('seo_description');
        });
    }

    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropColumn('faqs');
        });
    }
};
