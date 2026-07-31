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
        Schema::create('image_duplicate_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_id')->nullable()->constrained('image_duplicate_scans')->nullOnDelete();
            $table->string('representative_phash', 16)->nullable();
            $table->unsignedInteger('image_count')->default(0);
            $table->enum('status', ['pending', 'resolved', 'dismissed'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('image_duplicate_groups');
    }
};
