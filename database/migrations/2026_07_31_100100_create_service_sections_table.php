<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_page_id')->constrained('service_pages')->cascadeOnDelete();
            $table->string('type', 40);
            $table->json('config')->nullable();
            $table->string('title', 255)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_sections');
    }
};
