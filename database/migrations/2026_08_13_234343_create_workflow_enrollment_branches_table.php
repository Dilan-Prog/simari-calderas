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
        Schema::create('workflow_enrollment_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('workflow_enrollments')->cascadeOnDelete();
            $table->foreignId('parallel_step_id')->constrained('workflow_steps')->cascadeOnDelete();
            $table->string('branch_key', 20);
            $table->foreignId('current_step_id')->nullable()->constrained('workflow_steps')->nullOnDelete();
            $table->string('status')->default('active');
            $table->dateTime('resume_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_enrollment_branches');
    }
};
