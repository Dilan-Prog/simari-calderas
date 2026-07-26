<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            // Página pública /coleccion/{slug}: metadatos SEO + FAQs propias
            // (misma estructura [{question, answer}] que products.faqs).
            $table->string('seo_title', 160)->nullable()->after('image_url');
            $table->string('seo_description', 500)->nullable()->after('seo_title');
            $table->string('og_image_url', 255)->nullable()->after('seo_description');
            $table->json('faqs')->nullable()->after('og_image_url');
        });
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn(['seo_title', 'seo_description', 'og_image_url', 'faqs']);
        });
    }
};
