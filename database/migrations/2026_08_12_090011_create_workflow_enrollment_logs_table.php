<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_enrollment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('workflow_enrollments')->cascadeOnDelete();
            $table->foreignId('step_id')->nullable()->constrained('workflow_steps')->nullOnDelete();
            $table->string('action_taken');
            $table->string('result');
            $table->text('message')->nullable();
            $table->dateTime('logged_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_enrollment_logs');
    }
};
