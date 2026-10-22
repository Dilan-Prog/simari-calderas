<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La etapa de un lead pasa a ser la del Embudo de Venta (pipeline de WhatsApp):
 *  - leads.pipeline_stage_id guarda la etapa;
 *  - los leads y las conversaciones de WhatsApp que ya existian se acomodan en el embudo para que dejen de
 *    verse vacios (antes ninguna conversacion recibia etapa al llegar).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $t) {
            $t->unsignedBigInteger('pipeline_stage_id')->nullable()->index();
            $t->foreign('pipeline_stage_id')->references('id')->on('pipeline_stages')->nullOnDelete();
        });

        $pipeline = DB::table('pipelines')->where('channel', 'whatsapp')->orderByDesc('is_default')->orderBy('id')->first();
        if (! $pipeline) {
            return;
        }
        $stages = DB::table('pipeline_stages')->where('pipeline_id', $pipeline->id)->orderBy('order')->get()->values();
        if ($stages->isEmpty()) {
            return;
        }

        $open = $stages->where('is_won', false)->where('is_lost', false)->values();
        $first = $open->first() ?? $stages->first();
        $qualified = $open->get(2) ?? $open->last() ?? $first;
        $won = $stages->firstWhere('is_won', true);
        $lost = $stages->firstWhere('is_lost', true);

        $byStatus = [
            'nuevo'      => $first,
            'contactado' => $first,
            'calificado' => $qualified,
            'convertido' => $won ?? $qualified,
            'descartado' => $lost ?? $first,
        ];

        foreach (DB::table('leads')->select('id', 'status', 'whatsapp_conversation_id')->get() as $lead) {
            $stage = $byStatus[$lead->status] ?? $first;
            DB::table('leads')->where('id', $lead->id)->update(['pipeline_stage_id' => $stage->id]);

            if ($lead->whatsapp_conversation_id) {
                DB::table('whatsapp_conversations')
                    ->where('id', $lead->whatsapp_conversation_id)
                    ->whereNull('pipeline_stage_id')
                    ->update(['pipeline_id' => $pipeline->id, 'pipeline_stage_id' => $stage->id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $t) {
            $t->dropForeign(['pipeline_stage_id']);
            $t->dropColumn('pipeline_stage_id');
        });
    }
};
