<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Historial de correos entrantes procesados por n8n (también evita duplicados por Message-ID). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_inbound_emails', function (Blueprint $t) {
            $t->id();
            $t->string('message_id', 255)->unique();
            $t->unsignedBigInteger('lead_id')->nullable()->index();
            $t->string('from_email', 190)->nullable()->index();
            $t->string('from_name', 190)->nullable();
            $t->string('subject', 255)->nullable();
            $t->text('excerpt')->nullable();
            $t->timestamp('received_at')->nullable();
            $t->unsignedBigInteger('task_id')->nullable();
            $t->boolean('is_new_lead')->default(false);
            $t->timestamps();

            $t->foreign('lead_id')->references('id')->on('leads')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_inbound_emails');
    }
};
