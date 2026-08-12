<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_sequence_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained('email_sequences')->cascadeOnDelete();
            $table->unsignedInteger('order');
            $table->foreignId('template_id')->constrained('email_templates')->cascadeOnDelete();
            $table->unsignedInteger('delay_days')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_sequence_steps');
    }
};
