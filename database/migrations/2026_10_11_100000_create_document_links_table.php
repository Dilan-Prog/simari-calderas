<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_links', function (Blueprint $table) {
            $table->id();
            $table->string('from_type', 120);
            $table->unsignedBigInteger('from_id');
            $table->string('to_type', 120);
            $table->unsignedBigInteger('to_id');
            $table->string('relation', 40)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['from_type', 'from_id', 'to_type', 'to_id'], 'document_links_unique');
            $table->index(['to_type', 'to_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_links');
    }
};
