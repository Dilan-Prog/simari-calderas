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
     *   {"event": "deleted"}
     *   {"event": "updated", "field": "pipeline_stage_id"}
     *   {"event": "updated", "field": "pipeline_stage_id", "to_stage_id": 5}
     *   {"event": "updated", "field": "stock", "operator": "lt", "value": 10}
     *   {"event": "updated", "field": "status", "from_value": "pending", "value": "shipped"}
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param  string $eventType 'created' | 'updated' | 'deleted'
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
                $context = $this->buildTriggerContext($workflow, $model, $eventType);
                app(WorkflowEngineService::class)->enroll($workflow, $model, $context);
            }
        }
    }

    /**
     * Arma el $context (Fase 24) que se persiste en
     * WorkflowEnrollment::trigger_context al inscribir: el valor ANTERIOR
     * del campo vigilado por el trigger (solo aplica para 'updated' con
     * 'field' configurado -- se captura AQUÍ, en el momento del evento,
     * porque para cuando una acción se ejecuta (posiblemente tras un 'wait',
     * en un job en cola) $model->getOriginal() ya no refleja este cambio) y
     * el usuario autenticado en el momento del evento (auth()->id(), que
     * naturalmente es null fuera de un ciclo de request -- ej. cuando este
     * método se invoca desde un comando de scheduler -- sin necesidad de
     * distinguir el caso a mano).
     */
    private function buildTriggerContext(Workflow $workflow, Model $model, string $eventType): array
    {
        $context = ['actor_user_id' => auth()->id()];

        if ($eventType === 'updated') {
            $trigger = $workflow->enrollment_trigger;
            $field = is_array($trigger) ? ($trigger['field'] ?? null) : null;

            if ($field !== null) {
                $context['previous'] = [$field => $model->getOriginal($field)];
            }
        }

        return $context;
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

        // Fase 23: el evento 'deleted' matchea con solo comparar 'event' --
        // no tiene sentido revisar 'field'/wasChanged() sobre un modelo que
        // se está borrando (wasChanged() tampoco sería confiable aquí).
        if ($eventType === 'deleted') {
            return true;
        }

        if ($eventType === 'updated' && isset($trigger['field'])) {
            $field = $trigger['field'];

            if (!$model->wasChanged($field)) {
                return false;
            }

            // 'value' es el nombre genérico (Fase 23); 'to_stage_id' se
            // conserva como alias legado retrocompatible permanente -- los
            // triggers ya guardados en producción con solo 'to_stage_id'
            // deben seguir funcionando exactamente igual.
            $target = $trigger['value'] ?? $trigger['to_stage_id'] ?? null;

            if ($target !== null) {
                $operator = $trigger['operator'] ?? 'eq';

                if (!$this->compareValues($model->{$field}, $operator, $target)) {
                    return false;
                }
            }

            // 'from_value' (opcional, Fase 23): exige que el valor ANTERIOR
            // del campo coincida, además de que el valor nuevo cumpla
            // operator/value -- permite expresar una transición A->B
            // explícita, siempre por igualdad exacta (no tiene operador
            // propio).
            if (array_key_exists('from_value', $trigger)) {
                if ((string) $model->getOriginal($field) !== (string) $trigger['from_value']) {
                    return false;
                }
            }
        }

        // Mismo filtrado de field/operator/value que 'updated', pero para
        // 'created': aquí NO tiene sentido wasChanged() (todo campo es
        // "nuevo" por definición en un registro recién creado) ni
        // 'from_value' (implica "valor anterior", que no existe en un
        // created). Se compara directamente el valor actual del campo
        // contra operator/value reusando compareValues(). Necesario para
        // triggers como "Mensaje de WhatsApp recibido":
        // {"event": "created", "field": "sender_type", "value": "contact"}.
        if ($eventType === 'created' && isset($trigger['field'])) {
            $field = $trigger['field'];
            $target = $trigger['value'] ?? $trigger['to_stage_id'] ?? null;

            if ($target !== null) {
                $operator = $trigger['operator'] ?? 'eq';

                if (!$this->compareValues($model->{$field}, $operator, $target)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Compara $current contra $target según $operator (Fase 23).
     * gt/gte/lt/lte: cast numérico -- si alguno de los dos valores no es
     * numérico, no se puede comparar y se resuelve a false (nunca lanza,
     * nunca hace un cast forzado que rompa la semántica). eq/neq (y
     * cualquier operador desconocido, que cae al mismo default que 'eq'):
     * comparación de string, mismo comportamiento `(string) === (string)`
     * que ya existía antes de esta fase.
     */
    private function compareValues($current, string $operator, $target): bool
    {
        if (in_array($operator, ['gt', 'gte', 'lt', 'lte'], true)) {
            if (!is_numeric($current) || !is_numeric($target)) {
                return false;
            }

            $current = (float) $current;
            $target = (float) $target;

            return match ($operator) {
                'gt'    => $current > $target,
                'gte'   => $current >= $target,
                'lt'    => $current < $target,
                default => $current <= $target, // 'lte'
            };
        }

        if ($operator === 'neq') {
            return (string) $current !== (string) $target;
        }

        // 'eq' (default) y cualquier operador desconocido.
        return (string) $current === (string) $target;
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

            // Cada workflow puede vigilar su propia columna de fecha en vez de
            // la que trae por default el tipo de módulo (config/automatable_
            // modules.php) -- ej. dos workflows de tipo "quote" pueden existir
            // a la vez, uno viendo sent_at (seguimiento) y otro valid_until
            // (vencimiento), sin pisarse entre sí.
            $field = $workflow->enrollment_trigger['field'] ?? $staleField;

            $modelClass::where($field, '<', now()->subHours($hours))
                ->get()
                ->each(function (Model $model) use ($workflow) {
                    app(WorkflowEngineService::class)->enroll($workflow, $model);
                });
        }
    }

    /**
     * Trigger "Negocio estancado en su etapa actual N días" -- reusa tal
     * cual Deal::scopeStalled() (que ya filtra deals abiertos cuya última
     * fila de deal_stage_history es más vieja que N días), no reimplementa
     * su lógica SQL. Patrón de invocación idéntico a handleModelStale():
     * busca workflows activos del $workflowType con enrollment_trigger.event
     * === 'stage_stagnant', lee 'days' (default $defaultDays) e inscribe
     * cada Deal encontrado.
     */
    public function handleModelStageStagnant(string $workflowType, int $defaultDays = 3): void
    {
        $workflows = Workflow::query()
            ->where('is_active', true)
            ->where('type', $workflowType)
            ->get()
            ->filter(function (Workflow $workflow) {
                $trigger = $workflow->enrollment_trigger;

                return is_array($trigger) && ($trigger['event'] ?? null) === 'stage_stagnant';
            });

        foreach ($workflows as $workflow) {
            $days = (int) ($workflow->enrollment_trigger['days'] ?? $defaultDays);
            $days = $days > 0 ? $days : $defaultDays;

            Deal::stalled($days)->get()->each(function (Deal $deal) use ($workflow) {
                app(WorkflowEngineService::class)->enroll($workflow, $deal);
            });
        }
    }

    /**
     * Trigger "fecha de $dateField próxima en los siguientes N días" --
     * patrón inverso a handleModelStale(): en vez de buscar registros cuya
     * fecha ya pasó hace más de N horas, busca registros cuya fecha caiga
     * en la ventana [ahora, ahora + N días]. Resuelve $modelClass vía
     * AutomatableModuleRegistry (mismo patrón que handleModelStale usa para
     * resolver el modelo). Busca workflows activos del $workflowType con
     * enrollment_trigger.event === 'upcoming'.
     */
    public function handleModelUpcoming(string $workflowType, string $dateField, int $defaultDays = 7): void
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

                return is_array($trigger) && ($trigger['event'] ?? null) === 'upcoming';
            });

        foreach ($workflows as $workflow) {
            $days = (int) ($workflow->enrollment_trigger['days'] ?? $defaultDays);
            $days = $days > 0 ? $days : $defaultDays;

            $modelClass::whereBetween($dateField, [now(), now()->addDays($days)])
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
