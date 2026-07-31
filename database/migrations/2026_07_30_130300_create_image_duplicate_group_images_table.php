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
        Schema::create('image_duplicate_group_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('image_duplicate_groups')->cascadeOnDelete();
            $table->string('image_url', 255);
            $table->string('phash', 16)->nullable();
            $table->timestamps();
            $table->unique(['group_id', 'image_url']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('image_duplicate_group_images');
    }
};
