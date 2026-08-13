<?php

namespace App\Services;

use App\Models\Pipeline;
use App\Models\PipelineStage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PipelineService
{
    public function createPipeline(array $data): Pipeline
    {
        return DB::transaction(function () use ($data) {
            $pipeline = Pipeline::create([
                'name'       => $data['name'],
                'channel'    => $data['channel'] ?? Pipeline::CHANNEL_DEALS,
                'is_default' => $data['is_default'] ?? false,
                'is_active'  => $data['is_active'] ?? true,
            ]);

            foreach ($data['stages'] ?? [] as $index => $stage) {
                PipelineStage::create([
                    'pipeline_id'      => $pipeline->id,
                    'name'             => $stage['name'],
                    'slug'             => $stage['slug'] ?? Str::slug($stage['name']),
                    'order'            => $stage['order'] ?? $index,
                    'probability'      => $stage['probability'] ?? 0,
                    'is_won'           => $stage['is_won'] ?? false,
                    'is_lost'          => $stage['is_lost'] ?? false,
                    'required_fields'  => $stage['required_fields'] ?? null,
                ]);
            }

            return $pipeline->fresh('stages');
        });
    }

    public function updatePipeline(Pipeline $pipeline, array $data): Pipeline
    {
        $pipeline->update([
            'name'       => $data['name'] ?? $pipeline->name,
            'is_default' => $data['is_default'] ?? $pipeline->is_default,
            'is_active'  => $data['is_active'] ?? $pipeline->is_active,
        ]);

        return $pipeline;
    }

    /**
     * Actualiza la columna 'order' de las etapas según su posición en
     * $orderedStageIds. Pasa primero por un rango de valores temporales
     * negativos porque (pipeline_id, order) tiene un índice único: escribir
     * los valores finales directamente podría chocar contra el order que
     * todavía tiene otra etapa a mitad de la operación.
     */
    public function reorderStages(Pipeline $pipeline, array $orderedStageIds): void
    {
        DB::transaction(function () use ($pipeline, $orderedStageIds) {
            foreach ($orderedStageIds as $index => $stageId) {
                PipelineStage::where('id', $stageId)
                    ->where('pipeline_id', $pipeline->id)
                    ->update(['order' => -($index + 1)]);
            }

            foreach ($orderedStageIds as $index => $stageId) {
                PipelineStage::where('id', $stageId)
                    ->where('pipeline_id', $pipeline->id)
                    ->update(['order' => $index]);
            }
        });
    }

    /**
     * Borrado seguro de una etapa. Si tiene deals asociados hay que indicar
     * $reassignToStageId; la reasignación es una actualización masiva directa
     * (sin pasar por moveStage()/historial) porque es limpieza administrativa,
     * no un movimiento de venta real.
     */
    public function deleteStage(PipelineStage $stage, ?int $reassignToStageId = null): void
    {
        DB::transaction(function () use ($stage, $reassignToStageId) {
            $dealsCount = $stage->deals()->count();

            if ($dealsCount > 0) {
                if ($reassignToStageId === null) {
                    throw new \RuntimeException(
                        "La etapa '{$stage->name}' tiene {$dealsCount} negocio(s) asociado(s). ".
                        'Reasigna los negocios a otra etapa antes de borrarla.'
                    );
                }

                $stage->deals()->update(['pipeline_stage_id' => $reassignToStageId]);
            }

            $stage->delete();
        });
    }

    public function deletePipeline(Pipeline $pipeline): void
    {
        DB::transaction(function () use ($pipeline) {
            if ($pipeline->deals()->count() > 0) {
                throw new \RuntimeException(
                    "El pipeline '{$pipeline->name}' tiene negocios asociados en sus etapas. ".
                    'Reasigna o elimina esos negocios antes de borrar el pipeline.'
                );
            }

            $pipeline->stages()->delete();
            $pipeline->delete();
        });
    }
}
