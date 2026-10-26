<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Correos enviados desde la bandeja (no solo los que n8n registra desde Enviados):
 * quién los envió, su origen y el hilo al que responden.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_outbound_emails', function (Blueprint $t) {
            // 'external' = lo registró n8n desde la carpeta Enviados; 'platform' = se envió desde la bandeja.
            $t->string('source', 12)->default('external')->after('sent_at');
            $t->unsignedBigInteger('sent_by_user_id')->nullable()->after('source');
            $t->string('in_reply_to', 255)->nullable()->after('sent_by_user_id');

            $t->foreign('sent_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lead_outbound_emails', function (Blueprint $t) {
            $t->dropForeign(['sent_by_user_id']);
            $t->dropColumn(['source', 'sent_by_user_id', 'in_reply_to']);
        });
    }
};
