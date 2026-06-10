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
        Schema::table('product_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('product_categories', 'seo_title')) {
                $table->string('seo_title', 60)->nullable()->after('sort_order');
            }
            if (!Schema::hasColumn('product_categories', 'seo_description')) {
                $table->string('seo_description', 160)->nullable()->after('seo_title');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            //
        });
    }
};
