<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Liga `whatsapp_conversations` al resto del CRM (Fase 11-13 del plan):
 * puede vivir dentro de un Pipeline `channel='whatsapp'` (el "Embudo de
 * Venta") y opcionalmente apuntar a un Deal creado desde el chat — sin que
 * mover la conversación de etapa mueva el Deal (decisión confirmada, ver
 * WhatsappFunnelController de Fase 13). `updated_at` no existía en el
 * esquema original (solo `created_at`); hace falta para poder ordenar por
 * actividad y para que Eloquent `touch()` funcione.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            $table->foreignId('pipeline_id')->nullable()->after('account_id')
                ->constrained('pipelines')->nullOnDelete();
            $table->foreignId('pipeline_stage_id')->nullable()->after('pipeline_id')
                ->constrained('pipeline_stages')->nullOnDelete();
            $table->foreignId('deal_id')->nullable()->after('pipeline_stage_id')
                ->constrained('deals')->nullOnDelete();
            $table->unsignedInteger('unread_count')->default(0)->after('last_message_at');
            $table->timestamp('updated_at')->nullable()->after('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pipeline_id');
            $table->dropConstrainedForeignId('pipeline_stage_id');
            $table->dropConstrainedForeignId('deal_id');
            $table->dropColumn(['unread_count', 'updated_at']);
        });
    }
};
