<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 30); // lead_incoming | lead_reply | task
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('url', 500);
            $table->string('subject_type', 20)->nullable(); // lead | task
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'id']);
            $table->index(['subject_type', 'subject_id']);
        });

        // "Notificar también a estos usuarios": cuando user_id recibe una notificación, copy_user_id la recibe también.
        Schema::create('user_notification_copies', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('copy_user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['user_id', 'copy_user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            // Segundos que dura el aviso emergente (5-300; 0 = no se cierra solo).
            $table->unsignedSmallInteger('notification_toast_seconds')->default(30);
        });

        Schema::table('leads', function (Blueprint $table) {
            // Mensaje entrante sin responder (resaltado en la bandeja); se limpia al responder o abrir el lead.
            $table->timestamp('reply_pending_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('leads', fn (Blueprint $t) => $t->dropColumn('reply_pending_at'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('notification_toast_seconds'));
        Schema::dropIfExists('user_notification_copies');
        Schema::dropIfExists('admin_notifications');
    }
};
