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
        Schema::create('image_hashes', function (Blueprint $table) {
            $table->id();
            // Clave = la ruta guardada (products/abc.jpg, uploads/xyz.png, ...)
            // — una misma ruta física se hashea una sola vez sin importar
            // cuántos registros la referencien.
            $table->string('image_url', 255)->unique();
            $table->string('phash', 16);
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedBigInteger('file_mtime')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('image_hashes');
    }
};
