<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\Workflow;

class WorkflowTriggerService
{
    /**
     * Evalúa los Workflows activos de tipo 'deal' contra un evento del ciclo
     * de vida de un Deal y matricula el Deal en los workflows cuyo
     * enrollment_trigger coincida.
     *
     * IMPORTANTE: cuando $eventType === 'updated', este método depende de
     * Deal::wasChanged() para saber qué campos cambiaron. wasChanged() solo
     * es confiable inmediatamente después de save(), dentro del mismo ciclo
     * de vida del modelo (p. ej. en el evento 'updated' del Observer, antes
     * de que el modelo se recargue o se vuelva a consultar desde la BD). Por
     * eso este método debe invocarse desde DealObserver::updated() con la
     * misma instancia de $deal que Eloquent acaba de guardar -- si el $deal
     * se recarga (fresh()/refresh()) o se obtiene con una nueva consulta
     * antes de llamar a este método, wasChanged() ya no reflejará los
     * cambios de ese guardado y los triggers basados en 'field' dejarán de
     * dispararse correctamente.
     *
     * Formas soportadas de enrollment_trigger (json):
     *   {"event": "created"}
     *   {"event": "updated", "field": "pipeline_stage_id"}
     *   {"event": "updated", "field": "pipeline_stage_id", "to_stage_id": 5}
     *
     * @param  \App\Models\Deal $deal
     * @param  string $eventType 'created' | 'updated'
     */
    public function handleDealEvent(Deal $deal, string $eventType): void
    {
        $workflows = Workflow::query()
            ->where('is_active', true)
            ->where('type', 'deal')
            ->get();

        foreach ($workflows as $workflow) {
            if ($this->triggerMatches($workflow, $deal, $eventType)) {
                app(WorkflowEngineService::class)->enroll($workflow, $deal);
            }
        }
    }

    protected function triggerMatches(Workflow $workflow, Deal $deal, string $eventType): bool
    {
        $trigger = $workflow->enrollment_trigger;

        if (!is_array($trigger) || !isset($trigger['event'])) {
            return false;
        }

        if ($trigger['event'] !== $eventType) {
            return false;
        }

        if ($eventType === 'updated' && isset($trigger['field'])) {
            $field = $trigger['field'];

            if (!$deal->wasChanged($field)) {
                return false;
            }

            if (isset($trigger['to_stage_id']) && (string) $deal->{$field} !== (string) $trigger['to_stage_id']) {
                return false;
            }
        }

        return true;
    }
}
