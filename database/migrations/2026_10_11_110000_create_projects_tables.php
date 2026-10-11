<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_number', 20)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('company')->nullable();
            $table->foreignId('manager_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('estimated_duration_days')->nullable();
            $table->string('estimated_time')->nullable();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('levantamiento')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('project_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('pendiente');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        Schema::create('project_phase_assignees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phase_id')->constrained('project_phases')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['phase_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_phase_assignees');
        Schema::dropIfExists('project_phases');
        Schema::dropIfExists('projects');
    }
};
