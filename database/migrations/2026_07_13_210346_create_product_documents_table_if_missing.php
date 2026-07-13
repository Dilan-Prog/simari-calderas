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
        // FIX (Documentación feature): product_documents already exists in
        // this environment (created outside Laravel's migration system —
        // no prior migration tracked it), so this is guarded with
        // hasTable() to stay a no-op here while still creating the table
        // correctly on a fresh environment (e.g. migrate:fresh).
        if (Schema::hasTable('product_documents')) {
            return;
        }

        Schema::create('product_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->enum('type', ['ficha', 'manual', 'catalogo', 'certificacion', 'garantia', 'otro']);
            $table->string('path', 255);
            $table->string('original_name', 255);
            $table->timestamp('created_at')->nullable();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_documents');
    }
};
