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
                    'wip_limit'        => $stage['wip_limit'] ?? null,
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
        DB::transaction(function () use ($pipeline, $data) {
            $pipeline->update([
                'name'       => $data['name'] ?? $pipeline->name,
                'is_default' => $data['is_default'] ?? $pipeline->is_default,
                'is_active'  => $data['is_active'] ?? $pipeline->is_active,
            ]);

            // OJO: se compara con array_key_exists (no isset/??) porque
            // 'stages' => [] es una instrucción válida del usuario ("quité
            // todas las etapas"), distinta de "no mandé stages" (no tocar).
            if (array_key_exists('stages', $data)) {
                $this->syncStages($pipeline, $data['stages'] ?? []);
            }
        });

        return $pipeline;
    }

    /**
     * Sincroniza las etapas de un pipeline contra el array recibido del
     * editor: etapas con 'id' existente se actualizan, sin 'id' se crean,
     * y las que existían en BD pero ya no aparecen en el array se borran
     * (o abortan la operación completa si tienen negocios/conversaciones
     * asociados). El ORDEN final es el índice de aparición en el array.
     *
     * Se asume invocado dentro de una transacción ya abierta (updatePipeline).
     */
    public function syncStages(Pipeline $pipeline, array $stagesInput): void
    {
        $existingStages = $pipeline->stages()->get()->keyBy('id');
        $incomingIds = collect($stagesInput)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        // Primero se valida TODO (sin escribir nada) para poder abortar la
        // operación completa si alguna etapa quitada tiene negocios o
        // conversaciones asociados.
        $stagesToDelete = $existingStages->reject(fn ($stage) => in_array($stage->id, $incomingIds, true));

        foreach ($stagesToDelete as $stage) {
            $count = $stage->deals()->count() + $stage->whatsappConversations()->count();

            if ($count > 0) {
                throw new \RuntimeException(
                    "La etapa '{$stage->name}' tiene {$count} negocio(s)/conversación(es) asociado(s). ".
                    'Reasígnalos a otra etapa antes de quitarla del pipeline.'
                );
            }
        }

        // Fase 1: valores temporales para no violar el índice único
        // (pipeline_id, order) mientras se reordena (mismo patrón que
        // reorderStages()). La columna 'order' es unsignedInteger, así que
        // los temporales deben ser positivos (no negativos como en un
        // esquema con enteros con signo) — se usa un offset grande basado
        // en el id, que nunca choca con los order finales (pequeños,
        // basados en el índice del array de entrada).
        foreach ($existingStages as $stage) {
            $stage->update(['order' => 1000000 + $stage->id]);
        }

        // Fase 2: escribir/crear cada etapa con su order final = índice en
        // el array de entrada.
        foreach ($stagesInput as $index => $stageData) {
            $id = isset($stageData['id']) ? (int) $stageData['id'] : null;
            $existingStage = $id ? $existingStages->get($id) : null;

            if ($existingStage) {
                $name = $stageData['name'];
                $existingStage->update([
                    'name'        => $name,
                    'slug'        => $name !== $existingStage->name ? Str::slug($name) : $existingStage->slug,
                    'order'       => $index,
                    'probability' => $stageData['probability'] ?? 0,
                    'is_won'      => $stageData['is_won'] ?? false,
                    'is_lost'     => $stageData['is_lost'] ?? false,
                    'wip_limit'   => $stageData['wip_limit'] ?? null,
                ]);
            } else {
                PipelineStage::create([
                    'pipeline_id' => $pipeline->id,
                    'name'        => $stageData['name'],
                    'slug'        => Str::slug($stageData['name']),
                    'order'       => $index,
                    'probability' => $stageData['probability'] ?? 0,
                    'is_won'      => $stageData['is_won'] ?? false,
                    'is_lost'     => $stageData['is_lost'] ?? false,
                    'wip_limit'   => $stageData['wip_limit'] ?? null,
                ]);
            }
        }

        // Fase 3: borrar las etapas quitadas (ya validado que no tienen
        // negocios/conversaciones asociados).
        foreach ($stagesToDelete as $stage) {
            $stage->delete();
        }
    }

    /**
     * Actualiza la columna 'order' de las etapas según su posición en
     * $orderedStageIds. Pasa primero por un rango de valores temporales
     * porque (pipeline_id, order) tiene un índice único: escribir los
     * valores finales directamente podría chocar contra el order que
     * todavía tiene otra etapa a mitad de la operación. 'order' es
     * unsignedInteger, así que los temporales deben ser positivos (un
     * offset grande que nunca choca con los order finales, pequeños).
     */
    public function reorderStages(Pipeline $pipeline, array $orderedStageIds): void
    {
        DB::transaction(function () use ($pipeline, $orderedStageIds) {
            foreach ($orderedStageIds as $index => $stageId) {
                PipelineStage::where('id', $stageId)
                    ->where('pipeline_id', $pipeline->id)
                    ->update(['order' => 1000000 + $index]);
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

    /**
     * Duplica un pipeline completo (todas sus etapas, incluido wip_limit).
     * El clon nace inactivo y no-default para que el usuario lo revise antes
     * de ponerlo en producción.
     */
    public function duplicatePipeline(Pipeline $pipeline): Pipeline
    {
        return DB::transaction(function () use ($pipeline) {
            $clone = Pipeline::create([
                'name'       => $pipeline->name.' (copia)',
                'channel'    => $pipeline->channel,
                'is_default' => false,
                'is_active'  => false,
            ]);

            foreach ($pipeline->stages()->get() as $stage) {
                PipelineStage::create([
                    'pipeline_id'      => $clone->id,
                    'name'             => $stage->name,
                    'slug'             => $stage->slug,
                    'order'            => $stage->order,
                    'probability'      => $stage->probability,
                    'wip_limit'        => $stage->wip_limit,
                    'is_won'           => $stage->is_won,
                    'is_lost'          => $stage->is_lost,
                    'required_fields'  => $stage->required_fields,
                ]);
            }

            return $clone->fresh('stages');
        });
    }

    /**
     * Borra un pipeline. Si tiene negocios/conversaciones asociados (según
     * su channel) hay que indicar $reassignToPipelineId (mismo channel, con
     * al menos una etapa) para reasignarlos en bloque antes de borrar —
     * reasignación administrativa directa, sin pasar por DealService::
     * moveStage() ni por historial.
     */
    public function deletePipeline(Pipeline $pipeline, ?int $reassignToPipelineId = null): void
    {
        DB::transaction(function () use ($pipeline, $reassignToPipelineId) {
            $isWhatsapp = $pipeline->channel === Pipeline::CHANNEL_WHATSAPP;
            $count = $isWhatsapp
                ? $pipeline->whatsappConversations()->count()
                : $pipeline->deals()->count();

            if ($count > 0) {
                if ($reassignToPipelineId === null) {
                    throw new \RuntimeException(
                        "El pipeline '{$pipeline->name}' tiene negocios asociados en sus etapas. ".
                        'Reasigna o elimina esos negocios antes de borrar el pipeline.'
                    );
                }

                $targetPipeline = Pipeline::findOrFail($reassignToPipelineId);

                if ($targetPipeline->id === $pipeline->id) {
                    throw new \RuntimeException('No puedes reasignar un pipeline a sí mismo.');
                }

                if ($targetPipeline->channel !== $pipeline->channel) {
                    throw new \RuntimeException('No puedes reasignar a un pipeline de un canal distinto.');
                }

                $targetStage = $targetPipeline->stages()->orderBy('order')->first();

                if (! $targetStage) {
                    throw new \RuntimeException(
                        "El pipeline destino '{$targetPipeline->name}' no tiene etapas configuradas."
                    );
                }

                $stageIds = $pipeline->stages()->pluck('id');

                if ($isWhatsapp) {
                    \App\Models\WhatsappConversation::whereIn('pipeline_stage_id', $stageIds)
                        ->update(['pipeline_stage_id' => $targetStage->id, 'pipeline_id' => $targetPipeline->id]);
                } else {
                    \App\Models\Deal::whereIn('pipeline_stage_id', $stageIds)
                        ->update(['pipeline_stage_id' => $targetStage->id, 'pipeline_id' => $targetPipeline->id]);
                }
            }

            $pipeline->stages()->delete();
            $pipeline->delete();
        });
    }
}
