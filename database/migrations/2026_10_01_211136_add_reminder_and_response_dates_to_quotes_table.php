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
        Schema::table('quotes', function (Blueprint $table) {
            // Para el seguimiento automatico (recordatorios N8N): cuando fue
            // el ultimo recordatorio mandado, para no reenviarlo dos veces en
            // la siguiente corrida del flujo.
            $table->timestamp('last_reminder_sent_at')->nullable()->after('sent_at');
            // Fecha exacta del cambio de estatus a aceptada/rechazada -- status
            // ya dice CUAL es el estatus actual, pero no CUANDO cambio a ese
            // estatus (sent_at solo cubre el envio). Nullable porque la
            // mayoria de cotizaciones historicas nunca tendran estos datos.
            $table->timestamp('accepted_at')->nullable()->after('last_reminder_sent_at');
            $table->timestamp('rejected_at')->nullable()->after('accepted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['last_reminder_sent_at', 'accepted_at', 'rejected_at']);
        });
    }
};
