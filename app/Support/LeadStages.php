<?php

namespace App\Support;

use App\Models\Lead;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\WhatsappConversation;
use App\Services\LeadService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Etapa de un lead = etapa del Embudo de Venta (pipeline de WhatsApp). Es la misma que se ve en
 * "Embudo de Venta": mover el lead mueve su conversacion y mover la tarjeta en el embudo mueve el lead.
 * El estado interno del lead (nuevo, contactado, calificado, convertido, descartado) se deriva de la etapa
 * para que los indicadores de la bandeja sigan funcionando.
 */
class LeadStages
{
    /** Embudo de WhatsApp predeterminado (o el primero que exista). */
    public static function defaultPipeline(): ?Pipeline
    {
        return Pipeline::query()->whatsapp()->where('is_active', true)->orderByDesc('is_default')->orderBy('id')->first()
            ?? Pipeline::query()->whatsapp()->orderByDesc('is_default')->orderBy('id')->first();
    }

    /** Embudo del lead: el de su etapa actual si ya tiene una, si no el predeterminado. */
    public static function pipelineFor(Lead $lead): ?Pipeline
    {
        if ($lead->pipeline_stage_id) {
            $pipeline = PipelineStage::find($lead->pipeline_stage_id)?->pipeline;
            if ($pipeline && $pipeline->channel === Pipeline::CHANNEL_WHATSAPP) {
                return $pipeline;
            }
        }

        return self::defaultPipeline();
    }

    /** Etapas del embudo del lead, en orden. */
    public static function stages(Lead $lead): Collection
    {
        $pipeline = self::pipelineFor($lead);

        return $pipeline ? $pipeline->stages()->get() : collect();
    }

    /** Etapa actual: la guardada, o la que corresponde a su estado si todavia no tiene una. */
    public static function current(Lead $lead, ?Collection $stages = null): ?PipelineStage
    {
        $stages ??= self::stages($lead);
        if ($lead->pipeline_stage_id && ($stage = $stages->firstWhere('id', $lead->pipeline_stage_id))) {
            return $stage;
        }

        return self::stageForStatus($lead->status, $stages);
    }

    /** Etapa que le corresponde a un estado (para leads que aun no tienen etapa guardada). */
    public static function stageForStatus(?string $status, Collection $stages): ?PipelineStage
    {
        $open = $stages->where('is_won', false)->where('is_lost', false)->values();
        $first = $open->first() ?? $stages->first();

        return match ($status) {
            'calificado' => $open->get(2) ?? $open->last() ?? $first,
            'convertido' => $stages->firstWhere('is_won', true) ?? $open->last() ?? $first,
            'descartado' => $stages->firstWhere('is_lost', true) ?? $first,
            default      => $first,
        };
    }

    /** Estado interno del lead que resulta de moverlo a una etapa. */
    public static function statusFor(PipelineStage $stage, Collection $stages, ?string $currentStatus): string
    {
        if ($stage->is_won) {
            return 'convertido';
        }
        if ($stage->is_lost) {
            return 'descartado';
        }

        $open = $stages->where('is_won', false)->where('is_lost', false)->values();
        $index = $open->search(fn ($s) => $s->id === $stage->id);

        if ($index === 0) {
            // Volver a la primera etapa no borra que ya se le contacto.
            return in_array($currentStatus, ['nuevo', 'contactado'], true) ? $currentStatus : 'contactado';
        }

        return $index === 1 ? 'contactado' : 'calificado';
    }

    /** Mueve el lead a la etapa (y su conversacion de WhatsApp, y su estado interno). */
    public static function move(Lead $lead, PipelineStage $stage): void
    {
        $stages = $stage->pipeline ? $stage->pipeline->stages()->get() : collect([$stage]);

        DB::transaction(function () use ($lead, $stage, $stages) {
            $lead->pipeline_stage_id = $stage->id;
            $status = self::statusFor($stage, $stages, $lead->status);

            if ($status !== $lead->status) {
                app(LeadService::class)->changeStatus($lead, $status);
            } else {
                $lead->save();
            }

            if ($lead->whatsapp_conversation_id) {
                // update() directo: no dispara el observador (que sincroniza en sentido contrario).
                WhatsappConversation::where('id', $lead->whatsapp_conversation_id)
                    ->update(['pipeline_id' => $stage->pipeline_id, 'pipeline_stage_id' => $stage->id]);
            }
        });
    }

    /** Se movio la tarjeta en el Embudo de Venta: el lead de esa conversacion toma la misma etapa. */
    public static function syncLeadFromConversation(WhatsappConversation $conversation): void
    {
        if (! $conversation->pipeline_stage_id) {
            return;
        }
        $stage = PipelineStage::with('pipeline')->find($conversation->pipeline_stage_id);
        if (! $stage || $stage->pipeline?->channel !== Pipeline::CHANNEL_WHATSAPP) {
            return;
        }

        foreach (Lead::where('whatsapp_conversation_id', $conversation->id)->get() as $lead) {
            if ((int) $lead->pipeline_stage_id === (int) $stage->id) {
                continue;
            }
            $stages = $stage->pipeline->stages()->get();
            $lead->pipeline_stage_id = $stage->id;
            $status = self::statusFor($stage, $stages, $lead->status);

            if ($status !== $lead->status) {
                app(LeadService::class)->changeStatus($lead, $status);
            } else {
                $lead->save();
            }
        }
    }

    /** Primera etapa del embudo predeterminado: donde entra toda conversacion nueva. */
    public static function entryStage(): ?PipelineStage
    {
        $pipeline = self::defaultPipeline();

        return $pipeline?->stages()->where('is_won', false)->where('is_lost', false)->first() ?? $pipeline?->stages()->first();
    }
}
