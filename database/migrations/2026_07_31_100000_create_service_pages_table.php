<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_pages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 180);
            $table->string('slug', 255)->unique();
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 10)->default('MXN');
            $table->string('cover_image_url', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->string('seo_title', 160)->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->string('og_image_url', 255)->nullable();
            $table->json('faqs')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_pages');
    }
};
