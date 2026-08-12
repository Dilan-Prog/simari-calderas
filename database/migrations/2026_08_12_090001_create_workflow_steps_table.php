<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflows')->cascadeOnDelete();
            $table->foreignId('parent_step_id')->nullable()->constrained('workflow_steps')->cascadeOnDelete();
            $table->json('branch_condition')->nullable();
            $table->unsignedInteger('order');
            $table->string('step_type');
            $table->string('action_type')->nullable();
            $table->json('action_config')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_steps');
    }
};
