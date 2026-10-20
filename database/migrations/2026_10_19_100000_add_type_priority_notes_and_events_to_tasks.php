<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Tareas: tipo, prioridad y referencia libre; notas internas e historial de cambios. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $t) {
            $t->string('type', 30)->nullable();
            $t->string('priority', 10)->nullable();
            $t->string('related_reference', 255)->nullable();
        });

        Schema::create('task_notes', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('task_id')->index();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->text('body');
            $t->timestamps();

            $t->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
            $t->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('task_events', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('task_id')->index();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('type', 30);       // created | status | assigned | deleted
            $t->string('title', 255);
            $t->timestamps();

            $t->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
            $t->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_events');
        Schema::dropIfExists('task_notes');
        Schema::table('tasks', fn (Blueprint $t) => $t->dropColumn(['type', 'priority', 'related_reference']));
    }
};
