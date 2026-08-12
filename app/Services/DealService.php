<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\DealStageHistory;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DealService
{
    /**
     * Crea un Deal. Si no viene pipeline_stage_id pero sí pipeline_id, usa
     * la primera etapa (order más bajo) del pipeline como default. Escribe
     * también el registro inicial en deal_stage_history.
     */
    public function create(array $data): Deal
    {
        return DB::transaction(function () use ($data) {
            if (empty($data['pipeline_stage_id']) && !empty($data['pipeline_id'])) {
                $firstStage = PipelineStage::where('pipeline_id', $data['pipeline_id'])
                    ->orderBy('order')
                    ->first();

                if ($firstStage) {
                    $data['pipeline_stage_id'] = $firstStage->id;
                }
            }

            $deal = Deal::create($data);

            DealStageHistory::create([
                'deal_id'       => $deal->id,
                'from_stage_id' => null,
                'to_stage_id'   => $deal->pipeline_stage_id,
                'moved_by'      => auth()->id(),
                'moved_at'      => now(),
            ]);

            return $deal;
        });
    }

    /**
     * Actualiza campos del deal. No permite cambiar pipeline_stage_id aquí
     * — eso es responsabilidad de moveStage().
     */
    public function update(Deal $deal, array $data): Deal
    {
        unset($data['pipeline_stage_id']);

        $deal->update($data);

        return $deal;
    }

    /**
     * Mueve el deal a una nueva etapa, validando required_fields, calculando
     * la duración en la etapa anterior y registrando el historial.
     */
    public function moveStage(Deal $deal, PipelineStage $toStage, ?User $user = null): Deal
    {
        return DB::transaction(function () use ($deal, $toStage, $user) {
            if (!empty($toStage->required_fields)) {
                $missing = [];

                foreach ($toStage->required_fields as $field) {
                    if (!array_key_exists($field, $deal->getAttributes()) || is_null($deal->{$field})) {
                        $missing[] = $field;
                    }
                }

                if (!empty($missing)) {
                    throw ValidationException::withMessages([
                        'pipeline_stage_id' => 'Faltan los siguientes campos antes de mover a esta etapa: ' . implode(', ', $missing) . '.',
                    ]);
                }
            }

            $fromStageId = $deal->pipeline_stage_id;

            $lastHistory = DealStageHistory::where('deal_id', $deal->id)
                ->orderByDesc('moved_at')
                ->first();

            $durationInPreviousStageSeconds = null;
            if ($lastHistory) {
                $durationInPreviousStageSeconds = now()->diffInSeconds($lastHistory->moved_at);
            }

            DealStageHistory::create([
                'deal_id'                              => $deal->id,
                'from_stage_id'                         => $fromStageId,
                'to_stage_id'                            => $toStage->id,
                'moved_by'                               => $user?->id,
                'moved_at'                               => now(),
                'duration_in_previous_stage_seconds'    => $durationInPreviousStageSeconds,
            ]);

            $deal->pipeline_stage_id = $toStage->id;

            if ($toStage->is_won) {
                $deal->status = 'won';
                $deal->closed_at = now();
            } elseif ($toStage->is_lost) {
                $deal->status = 'lost';
                $deal->closed_at = now();
            }

            // TODO Fase 2: aquí se disparará el trigger de automatizaciones
            // (workflow events) asociado al cambio de etapa.

            $deal->save();

            return $deal;
        });
    }
}
