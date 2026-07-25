<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Preguntas frecuentes personalizadas por producto:
            // [{question, answer}, ...]. Se capturan en el modal SEO del
            // formulario de producto y alimentan la sección FAQ de la ficha
            // pública + el JSON-LD FAQPage.
            $table->json('faqs')->nullable()->after('specifications');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('faqs');
        });
    }
};
