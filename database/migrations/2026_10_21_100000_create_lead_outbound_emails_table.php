<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Correos que administracion@ le envia a un cliente que ya tiene tarea (los registra n8n desde la carpeta Enviados). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_outbound_emails', function (Blueprint $t) {
            $t->id();
            $t->string('message_id', 255)->unique();
            $t->unsignedBigInteger('lead_id')->index();
            $t->unsignedBigInteger('task_id')->nullable()->index();
            $t->string('from_email', 190)->nullable();
            $t->string('to_email', 190)->nullable()->index();   // destinatario que coincidio con el lead
            $t->json('recipients')->nullable();                 // to + cc
            $t->string('subject', 255)->nullable();
            $t->longText('body')->nullable();
            $t->json('attachments')->nullable();                // solo nombres
            $t->timestamp('sent_at')->nullable();
            $t->timestamps();

            $t->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
            $t->foreign('task_id')->references('id')->on('tasks')->nullOnDelete();
        });

        Schema::table('task_events', function (Blueprint $t) {
            $t->unsignedBigInteger('outbound_email_id')->nullable()->after('title');
            $t->foreign('outbound_email_id')->references('id')->on('lead_outbound_emails')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('task_events', function (Blueprint $t) {
            $t->dropForeign(['outbound_email_id']);
            $t->dropColumn('outbound_email_id');
        });
        Schema::dropIfExists('lead_outbound_emails');
    }
};
