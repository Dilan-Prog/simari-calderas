<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_sequence_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained('email_sequences')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->unsignedInteger('current_step')->default(0);
            $table->string('status')->default('active');
            $table->dateTime('next_send_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_sequence_enrollments');
    }
};
