<?php

namespace App\Services;

use App\Models\Workflow;
use App\Models\WorkflowEnrollment;
use App\Models\WorkflowEnrollmentLog;
use App\Models\WorkflowStep;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WorkflowEngineService
{
    /**
     * Inscribe un modelo (Deal, Customer, o cualquier otro modelo
     * automatizable) en un workflow. Si el workflow no permite reinscripción,
     * retorna la inscripción activa/waiting existente en lugar de crear una
     * duplicada. Arranca el procesamiento del primer step inmediatamente
     * después de crear la inscripción.
     *
     * $context (Fase 24, opcional y retrocompatible -- todos los call-sites
     * previos a esta fase siguen funcionando sin pasarlo): datos de contexto
     * de ejecución capturados en el momento del EVENTO que originó la
     * inscripción (no en el momento en que una acción se ejecuta más tarde,
     * posiblemente en un job en cola), ej. {"previous": {...}, "actor_user_id": ...}.
     * Se persiste tal cual en WorkflowEnrollment::trigger_context.
     */
    public function enroll(Workflow $workflow, $target, array $context = []): WorkflowEnrollment
    {
        return DB::transaction(function () use ($workflow, $target, $context) {
            if (!$workflow->reenrollment_allowed) {
                $existing = WorkflowEnrollment::where('workflow_id', $workflow->id)
                    ->where('enrollable_type', $target->getMorphClass())
                    ->where('enrollable_id', $target->getKey())
                    ->whereIn('status', ['active', 'waiting'])
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            $firstStep = WorkflowStep::where('workflow_id', $workflow->id)
                ->whereNull('parent_step_id')
                ->orderBy('order')
                ->first();

            $enrollment = WorkflowEnrollment::create([
                'workflow_id'      => $workflow->id,
                'enrollable_type'  => $target->getMorphClass(),
                'enrollable_id'    => $target->getKey(),
                'trigger_context'  => $context ?: null,
                'current_step_id'  => $firstStep?->id,
                'status'           => 'active',
                'enrolled_at'      => now(),
            ]);

            $this->processStep($enrollment);

            return $enrollment;
        });
    }

    /**
     * Ejecuta el step actual de la inscripción y avanza recursivamente hasta
     * llegar a un step de tipo 'wait' (donde se detiene) o al final del
     * workflow (donde marca la inscripción como completada).
     */
    public function processStep(WorkflowEnrollment $enrollment): void
    {
        if ($enrollment->status !== 'active') {
            return;
        }

        // IMPORTANTE: se usa currentStep() (llamada al método de relación,
        // que ejecuta una consulta fresca) en vez del accessor de propiedad
        // mágico $enrollment->currentStep. processStep() se recursa sobre la
        // MISMA instancia de $enrollment (mismo objeto PHP) al avanzar de un
        // step a otro; el accessor de propiedad cachea la relación cargada
        // la primera vez que se accede y Eloquent no invalida ese caché solo
        // porque cambiemos current_step_id y guardemos. Si se usara el
        // accessor aquí, cada llamada recursiva volvería a ver el PRIMER
        // step cargado (nunca el step al que acabamos de avanzar), lo que
        // provoca recursión infinita reprocesando siempre el mismo step.
        $step = $enrollment->currentStep()->first();

        if (!$step) {
            $enrollment->status = 'completed';
            $enrollment->completed_at = now();
            $enrollment->save();

            return;
        }

        if ($step->step_type === 'wait') {
            $unit = $step->action_config['unit'] ?? 'minutes';
            $amount = (int) ($step->action_config['amount'] ?? 0);

            $resumeAt = now();
            match ($unit) {
                'hours' => $resumeAt = $resumeAt->addHours($amount),
                'days'  => $resumeAt = $resumeAt->addDays($amount),
                default => $resumeAt = $resumeAt->addMinutes($amount),
            };

            $enrollment->status = 'waiting';
            $enrollment->resume_at = $resumeAt;
            $enrollment->save();

            WorkflowEnrollmentLog::create([
                'enrollment_id' => $enrollment->id,
                'step_id'       => $step->id,
                'action_taken'  => 'wait',
                'result'        => 'scheduled',
                'logged_at'     => now(),
            ]);

            return;
        }

        if ($step->step_type === 'condition') {
            $branchResult = \App\Services\WorkflowConditionEvaluator::evaluate($enrollment->enrollable, $step->branch_condition ?? []);
            $branchKey = $branchResult ? 'yes' : 'no';

            $next = WorkflowStep::where('parent_step_id', $step->id)
                ->where('branch_key', $branchKey)
                ->orderBy('order')
                ->first();

            WorkflowEnrollmentLog::create([
                'enrollment_id' => $enrollment->id,
                'step_id'       => $step->id,
                'action_taken'  => 'condition',
                'result'        => $branchKey,
                'logged_at'     => now(),
            ]);

            $enrollment->current_step_id = $next?->id;
            $enrollment->save();

            if (!$next) {
                $enrollment->status = 'completed';
                $enrollment->completed_at = now();
                $enrollment->save();
                return;
            }

            $this->processStep($enrollment);
            return;
        }

        if ($step->step_type === 'action') {
            app(\App\Services\WorkflowActionExecutor::class)->execute($enrollment, $step);

            $next = $this->nextSiblingOf($step);

            $enrollment->current_step_id = $next?->id;

            if (!$next) {
                $enrollment->status = 'completed';
                $enrollment->completed_at = now();
                $enrollment->save();

                return;
            }

            $enrollment->save();

            // Recursamos siempre: si el nuevo step es 'wait', la propia
            // rama 'wait' de processStep() deja el enrollment en estado
            // waiting y retorna sin seguir recursando, por lo que no hace
            // falta (ni conviene) duplicar esa condición aquí.
            $this->processStep($enrollment);

            return;
        }
    }

    /**
     * Reactiva una inscripción en estado 'waiting' cuyo resume_at ya venció:
     * la pasa a 'active', avanza al step siguiente al wait actual y continúa
     * el procesamiento.
     */
    public function resumeWaiting(WorkflowEnrollment $enrollment): void
    {
        if ($enrollment->status !== 'waiting') {
            return;
        }

        $waitStep = $enrollment->currentStep()->first();

        $enrollment->status = 'active';
        $enrollment->resume_at = null;

        if ($waitStep) {
            $next = $this->nextSiblingOf($waitStep);

            $enrollment->current_step_id = $next?->id;
        }

        $enrollment->save();

        $this->processStep($enrollment);
    }

    /**
     * Encuentra el siguiente step hermano de $step: mismo workflow, mismo
     * parent_step_id, mismo branch_key (o ambos null si $step no está en
     * una rama), con order mayor, ordenado por order.
     *
     * ============================================================================
     *  ESPEJADO MANUALMENTE EN resources/js/admin/canvas/graph/toFlow.js
     * ============================================================================
     * El canvas de React Flow (toFlow.js::buildPredecessorMap()) reproduce esta
     * misma semántica en JS (en sentido inverso, buscando el predecesor de cada
     * step) para dibujar las conexiones del editor visual. Si esta lógica
     * cambia, toFlow.js debe actualizarse igual o el canvas mostrará conexiones
     * que no coinciden con la ruta que el motor realmente ejecuta.
     *
     * Fase 10, punto 2: la paridad entre ambas implementaciones está cubierta
     * por tests/Feature/Workflows/CanvasEngineParityTest.php, que ejercita el
     * comando `workflows:debug-next-steps` (wrapper de solo lectura sobre este
     * método vía nextStepSequence()) contra 3 árboles representativos (lineal,
     * condición de dos ramas, ramas de longitud desigual) y compara la
     * secuencia resultante contra el valor esperado. Si se toca esta función,
     * correr ese test.
     * ============================================================================
     */
    private function nextSiblingOf(WorkflowStep $step): ?WorkflowStep
    {
        $query = WorkflowStep::where('workflow_id', $step->workflow_id)
            ->where('parent_step_id', $step->parent_step_id)
            ->where('order', '>', $step->order)
            ->orderBy('order');

        if ($step->branch_key === null) {
            $query->whereNull('branch_key');
        } else {
            $query->where('branch_key', $step->branch_key);
        }

        return $query->first();
    }

    /**
     * Expone, sin cambiar ninguna lógica de negocio, la secuencia completa de
     * "siguiente step" que nextSiblingOf() produce para cada step de un
     * workflow. Es un wrapper de solo lectura pensado para depuración/tests
     * de paridad contra el canvas de React (ver comentario en nextSiblingOf()
     * y tests/Feature/Workflows/CanvasEngineParityTest.php) — nunca se usa en
     * el flujo real de ejecución (enroll()/processStep()/resumeWaiting()).
     *
     * @param int $workflowId
     * @return array<int, array{step_id:int, next_step_id:?int}> ordenado por
     *   (parent_step_id, branch_key, order) para que la salida sea determinista.
     */
    public function nextStepSequence(int $workflowId): array
    {
        $steps = WorkflowStep::where('workflow_id', $workflowId)
            ->orderBy('parent_step_id')
            ->orderBy('branch_key')
            ->orderBy('order')
            ->get();

        return $steps->map(function (WorkflowStep $step) {
            return [
                'step_id'      => $step->id,
                'next_step_id' => $this->nextSiblingOf($step)?->id,
            ];
        })->all();
    }
}
