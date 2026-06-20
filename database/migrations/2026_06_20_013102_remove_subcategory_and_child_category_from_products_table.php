<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['subcategory_id']);
            $table->dropForeign(['child_category_id']);
            $table->dropColumn(['subcategory_id', 'child_category_id']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('subcategory_id')->nullable()->after('category_id');
            $table->unsignedBigInteger('child_category_id')->nullable()->after('subcategory_id');
            $table->foreign('subcategory_id')->references('id')->on('product_categories')->nullOnDelete();
            $table->foreign('child_category_id')->references('id')->on('product_categories')->nullOnDelete();
        });
    }
};
