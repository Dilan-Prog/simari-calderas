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
            // FIX BUG 3: tags were captured as visual-only chips in the UI
            // with no backing column at all — nothing was ever persisted.
            $table->json('tags')->nullable()->after('specifications');
            // FIX BUG 5: seo_keywords and Open Graph fields had inputs in
            // the UI (create only, no name= attributes) but no column to
            // save into.
            $table->string('seo_keywords', 255)->nullable()->after('seo_description');
            $table->string('og_title', 255)->nullable()->after('seo_keywords');
            $table->text('og_description')->nullable()->after('og_title');
            $table->string('og_image', 255)->nullable()->after('og_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['tags', 'seo_keywords', 'og_title', 'og_description', 'og_image']);
        });
    }
};
