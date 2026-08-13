<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\Workflow;
use App\Models\WhatsappConversation;
use Illuminate\Database\Eloquent\Model;

class WorkflowTriggerService
{
    /**
     * Evalúa los Workflows activos de un `$workflowType` dado contra un
     * evento del ciclo de vida de cualquier modelo automatizable (Fase 17 --
     * generaliza handleDealEvent()/handleConversationEvent(), que ahora
     * delegan aquí) y matricula el modelo en los workflows cuyo
     * enrollment_trigger coincida.
     *
     * IMPORTANTE: cuando $eventType === 'updated', triggerMatches() depende
     * de Model::wasChanged() para saber qué campos cambiaron. wasChanged()
     * solo es confiable inmediatamente después de save(), dentro del mismo
     * ciclo de vida del modelo (p. ej. en el evento 'updated' del Observer,
     * antes de que el modelo se recargue o se vuelva a consultar desde la
     * BD). Por eso este método debe invocarse desde el Observer con la MISMA
     * instancia de $model que Eloquent acaba de guardar -- si el $model se
     * recarga (fresh()/refresh()) o se obtiene con una nueva consulta antes
     * de llamar a este método, wasChanged() ya no reflejará los cambios de
     * ese guardado y los triggers basados en 'field' dejarán de dispararse
     * correctamente.
     *
     * Formas soportadas de enrollment_trigger (json):
     *   {"event": "created"}
     *   {"event": "updated", "field": "pipeline_stage_id"}
     *   {"event": "updated", "field": "pipeline_stage_id", "to_stage_id": 5}
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param  string $eventType 'created' | 'updated'
     * @param  string $workflowType  valor de Workflow.type a evaluar (ej. 'deal', 'store_order')
     */
    public function handleModelEvent(Model $model, string $eventType, string $workflowType): void
    {
        $workflows = Workflow::query()
            ->where('is_active', true)
            ->where('type', $workflowType)
            ->get();

        foreach ($workflows as $workflow) {
            if ($this->triggerMatches($workflow, $model, $eventType)) {
                app(WorkflowEngineService::class)->enroll($workflow, $model);
            }
        }
    }

    /**
     * Lógica de matching de enrollment_trigger, genérica desde su origen
     * (sin ningún campo de Deal hardcodeado): solo depende de
     * wasChanged()/acceso dinámico de propiedad, ambos disponibles en
     * cualquier Eloquent Model. Compartida por handleModelEvent() y, vía
     * los métodos legacy handleDealEvent()/handleConversationEvent() de
     * abajo, por el código que aún los invoque directamente.
     */
    protected function triggerMatches(Workflow $workflow, Model $model, string $eventType): bool
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

            if (!$model->wasChanged($field)) {
                return false;
            }

            if (isset($trigger['to_stage_id']) && (string) $model->{$field} !== (string) $trigger['to_stage_id']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Trigger de "sin actividad en N horas" genérico (Fase 17 -- generaliza
     * handleConversationStale(), que ahora delega aquí). A diferencia del
     * scope stale() propio de WhatsappConversation (que además filtraba por
     * status='open' e incluía filas con $staleField NULL como obsoletas),
     * usa una query genérica `$model::where($staleField, '<', now()->subHours($hours))`
     * válida para cualquier modelo automatizable con una columna datetime de
     * "última actividad" -- decisión de diseño explícita del plan para no
     * tener que declarar un scope por módulo.
     *
     * Se invoca periódicamente (comando automatable-modules:tick) por cada
     * entrada del registro con supports_stale=true.
     */
    public function handleModelStale(string $workflowType, string $staleField, int $defaultHours = 24): void
    {
        $entry = app(AutomatableModuleRegistry::class)->entry($workflowType);
        $modelClass = is_array($entry) ? ($entry['model'] ?? null) : null;

        if (!$modelClass || !class_exists($modelClass)) {
            return;
        }

        $workflows = Workflow::query()
            ->where('is_active', true)
            ->where('type', $workflowType)
            ->get()
            ->filter(function (Workflow $workflow) {
                $trigger = $workflow->enrollment_trigger;

                return is_array($trigger) && ($trigger['event'] ?? null) === 'stale';
            });

        foreach ($workflows as $workflow) {
            $hours = (int) ($workflow->enrollment_trigger['hours'] ?? $defaultHours);
            $hours = $hours > 0 ? $hours : $defaultHours;

            $modelClass::where($staleField, '<', now()->subHours($hours))
                ->get()
                ->each(function (Model $model) use ($workflow) {
                    app(WorkflowEngineService::class)->enroll($workflow, $model);
                });
        }
    }

    /**
     * @deprecated Fase 17 -- DealObserver.php fue retirado por completo en
     * Fase 20 (ya no existe ni se registra; AutomatableModelObserver lo
     * sustituye). Este método se conserva solo como wrapper de compatibilidad
     * para quien lo invoque directamente (ej. tests de regresión); delega en
     * handleModelEvent(), no agrega lógica propia.
     *
     * @param  \App\Models\Deal $deal
     * @param  string $eventType 'created' | 'updated'
     */
    public function handleDealEvent(Deal $deal, string $eventType): void
    {
        $this->handleModelEvent($deal, $eventType, 'deal');
    }

    /**
     * @deprecated Fase 17 -- WhatsappConversationObserver.php fue retirado
     * por completo en Fase 20 (ya no existe ni se registra;
     * AutomatableModelObserver lo sustituye). Este método se conserva solo
     * como wrapper de compatibilidad para quien lo invoque directamente (ej.
     * tests de regresión); delega en handleModelEvent(), no agrega lógica
     * propia.
     *
     * @param  \App\Models\WhatsappConversation $conversation
     * @param  string $eventType 'created' | 'updated'
     */
    public function handleConversationEvent(WhatsappConversation $conversation, string $eventType): void
    {
        $this->handleModelEvent($conversation, $eventType, 'whatsapp_conversation');
    }

    /**
     * @deprecated Fase 17 -- conservado porque el test de regresión de
     * Fase 15 aún lo invoca directamente (whatsapp-conversations:tick, el
     * comando legacy, ya no está registrado en Kernel::schedule() -- ver
     * automatable-modules:tick). A diferencia de handleModelStale()
     * genérico, usa el scope stale() propio de WhatsappConversation (incluye
     * status='open' y trata NULL como obsoleto) -- comportamiento más
     * específico que el genérico no reproduce a propósito (ver comentario de
     * handleModelStale()).
     */
    public function handleConversationStale(int $defaultHours = 24): void
    {
        $workflows = Workflow::query()
            ->where('is_active', true)
            ->where('type', 'whatsapp_conversation')
            ->get()
            ->filter(function (Workflow $workflow) {
                $trigger = $workflow->enrollment_trigger;

                return is_array($trigger) && ($trigger['event'] ?? null) === 'stale';
            });

        foreach ($workflows as $workflow) {
            $hours = (int) ($workflow->enrollment_trigger['hours'] ?? $defaultHours);
            $hours = $hours > 0 ? $hours : $defaultHours;

            WhatsappConversation::query()->stale($hours)->get()->each(function (WhatsappConversation $conversation) use ($workflow) {
                app(WorkflowEngineService::class)->enroll($workflow, $conversation);
            });
        }
    }
}
