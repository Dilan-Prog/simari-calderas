<?php

namespace App\Services;

// WorkflowConditionEvaluator y WorkflowActionExecutor viven en este mismo
// namespace (App\Services) -- no requieren `use` explícito.
use App\Models\Workflow;
use App\Models\WorkflowEnrollment;
use App\Models\WorkflowEnrollmentBranch;
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
     * Se persiste tal cual en WorkflowEnrollment::trigger_context. También
     * es donde el motor guarda internamente el contador de vueltas de los
     * steps 'loop' (clave "loop_iterations", ver advanceLoop()/advanceLoopForBranch()).
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

        if ($step->step_type === 'end') {
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
            $branchResult = WorkflowConditionEvaluator::evaluate($enrollment->enrollable, $step->branch_condition ?? []);
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

            $this->advanceOrComplete($enrollment, $step, $next);
            return;
        }

        if ($step->step_type === 'switch') {
            $branchKey = WorkflowConditionEvaluator::evaluateSwitch($enrollment->enrollable, $step->action_config ?? []);

            $next = WorkflowStep::where('parent_step_id', $step->id)
                ->where('branch_key', $branchKey)
                ->orderBy('order')
                ->first();

            if (!$next && $branchKey !== 'default') {
                $next = WorkflowStep::where('parent_step_id', $step->id)
                    ->where('branch_key', 'default')
                    ->orderBy('order')
                    ->first();
            }

            WorkflowEnrollmentLog::create([
                'enrollment_id' => $enrollment->id,
                'step_id'       => $step->id,
                'action_taken'  => 'switch',
                'result'        => $branchKey,
                'logged_at'     => now(),
            ]);

            $this->advanceOrComplete($enrollment, $step, $next);
            return;
        }

        if ($step->step_type === 'parallel') {
            $this->processParallelStep($enrollment, $step);
            return;
        }

        if ($step->step_type === 'join') {
            // El join en sí no ejecuta ninguna acción real -- solo avanza
            // hacia lo que sigue, igual que 'action' pero sin llamar al
            // WorkflowActionExecutor.
            $next = $this->nextSiblingOf($step);
            $this->advanceOrComplete($enrollment, $step, $next);
            return;
        }

        if ($step->step_type === 'loop') {
            $firstChild = WorkflowStep::where('parent_step_id', $step->id)
                ->orderBy('order')
                ->first();

            if (!$firstChild) {
                // Loop sin cuerpo: se trata igual que 'end'/fin de workflow.
                $this->advanceOrComplete($enrollment, $step, null);
                return;
            }

            // Entrar (o re-entrar) al cuerpo del loop NO incrementa el
            // contador de vueltas -- eso solo ocurre al COMPLETAR una vuelta,
            // en advanceLoop().
            $enrollment->current_step_id = $firstChild->id;
            $enrollment->save();

            $this->processStep($enrollment);
            return;
        }

        if ($step->step_type === 'action') {
            app(WorkflowActionExecutor::class)->execute($enrollment, $step);

            $next = $this->nextSiblingOf($step);

            $this->advanceOrComplete($enrollment, $step, $next);

            return;
        }
    }

    /**
     * Encapsula el patrón repetido "si $next existe, avanza current_step_id
     * y recursa processStep(); si no, revisa si el padre de $groupStep es un
     * step_type='loop' (en cuyo caso delega en advanceLoop() para repetir la
     * vuelta o continuar tras el loop agotado) y, si no lo es, completa la
     * inscripción normalmente".
     *
     * Usado por los 3 puntos donde processStep() decide "avanzar o
     * terminar": fin de 'action' (vía nextSiblingOf), fin de 'condition' y
     * fin de 'switch' (ambos vía lookup de branch_key), además de 'join' y
     * de un 'loop' sin cuerpo.
     */
    private function advanceOrComplete(WorkflowEnrollment $enrollment, WorkflowStep $groupStep, ?WorkflowStep $next): void
    {
        $enrollment->current_step_id = $next?->id;

        if ($next) {
            $enrollment->save();
            $this->processStep($enrollment);
            return;
        }

        $enrollment->save();

        $parentStep = $groupStep->parent_step_id
            ? WorkflowStep::find($groupStep->parent_step_id)
            : null;

        if ($parentStep && $parentStep->step_type === 'loop') {
            $this->advanceLoop($enrollment, $parentStep);
            return;
        }

        $enrollment->status = 'completed';
        $enrollment->completed_at = now();
        $enrollment->save();
    }

    /**
     * Se llama cuando el cuerpo de un step 'loop' terminó su cadena (el
     * último step del cuerpo no tiene siguiente). Decide si toca dar otra
     * vuelta (incrementando el contador persistido en
     * $enrollment->trigger_context['loop_iterations'][$loopStep->id]) o si
     * ya se agotaron las vueltas (en cuyo caso continúa tras el propio loop
     * step, vía nextSiblingOf(), o completa la inscripción).
     */
    private function advanceLoop(WorkflowEnrollment $enrollment, WorkflowStep $loopStep): void
    {
        $context = $enrollment->trigger_context ?? [];
        $currentIterations = (int) ($context['loop_iterations'][$loopStep->id] ?? 0);

        // Tope duro absoluto de 50 vueltas -- nunca confiar solo en la
        // validación del request, revalidar siempre aquí también.
        $maxIterations = min((int) ($loopStep->action_config['max_iterations'] ?? 1), 50);

        if ($currentIterations + 1 < $maxIterations) {
            $context['loop_iterations'][$loopStep->id] = $currentIterations + 1;
            $enrollment->trigger_context = $context;

            $firstChild = WorkflowStep::where('parent_step_id', $loopStep->id)
                ->orderBy('order')
                ->first();

            $enrollment->current_step_id = $firstChild?->id;
            $enrollment->save();

            $this->processStep($enrollment);
            return;
        }

        // Vueltas agotadas: continúa como si el propio loop step hubiera
        // "terminado" normalmente.
        $next = $this->nextSiblingOf($loopStep);
        $this->advanceOrComplete($enrollment, $loopStep, $next);
    }

    /**
     * Punto de entrada al procesar un step 'parallel': crea una fila
     * WorkflowEnrollmentBranch por cada rama configurada (con hijo real en
     * el árbol) y procesa cada una independientemente vía processBranch().
     * La inscripción principal queda 'active' con current_step_id apuntando
     * al propio $parallelStep mientras las ramas corren.
     */
    private function processParallelStep(WorkflowEnrollment $enrollment, WorkflowStep $parallelStep): void
    {
        $branches = $parallelStep->action_config['branches'] ?? [];

        if (empty($branches)) {
            WorkflowEnrollmentLog::create([
                'enrollment_id' => $enrollment->id,
                'step_id'       => $parallelStep->id,
                'action_taken'  => 'parallel',
                'result'        => 'skipped',
                'logged_at'     => now(),
            ]);

            $enrollment->status = 'completed';
            $enrollment->completed_at = now();
            $enrollment->save();
            return;
        }

        DB::transaction(function () use ($enrollment, $parallelStep, $branches) {
            $branchRows = [];

            foreach ($branches as $branchDef) {
                $branchKey = $branchDef['branch_key'] ?? null;

                if (!$branchKey) {
                    continue;
                }

                $childStep = WorkflowStep::where('parent_step_id', $parallelStep->id)
                    ->where('branch_key', $branchKey)
                    ->orderBy('order')
                    ->first();

                if (!$childStep) {
                    continue;
                }

                $branchRows[] = WorkflowEnrollmentBranch::create([
                    'enrollment_id'    => $enrollment->id,
                    'parallel_step_id' => $parallelStep->id,
                    'branch_key'       => $branchKey,
                    'current_step_id'  => $childStep->id,
                    'status'           => 'active',
                ]);
            }

            $enrollment->status = 'active';
            $enrollment->save();

            WorkflowEnrollmentLog::create([
                'enrollment_id' => $enrollment->id,
                'step_id'       => $parallelStep->id,
                'action_taken'  => 'parallel',
                'result'        => 'started:' . count($branchRows),
                'logged_at'     => now(),
            ]);

            foreach ($branchRows as $branchRow) {
                $this->processBranch($enrollment, $branchRow);
            }
        });
    }

    /**
     * Espejo de processStep(), pero opera sobre $branch->current_step_id en
     * vez de $enrollment->current_step_id. Reusa la misma lógica de
     * wait/condition/switch/action/loop, con dos diferencias clave:
     *
     *  - Antes de avanzar (vía nextSiblingOf() o lookup de branch_key), si
     *    el step que se acaba de ejecutar tiene joins_into_step_id no nulo,
     *    la rama se marca 'completed' y se dispara checkJoinCompletion() en
     *    vez de seguir avanzando (ver advanceBranchOrComplete()).
     *  - Si el step es 'wait', solo se pausa ESA rama ($branch->status =
     *    'waiting'); el enrollment principal sigue 'active'.
     */
    private function processBranch(WorkflowEnrollment $enrollment, WorkflowEnrollmentBranch $branch): void
    {
        if ($branch->status !== 'active') {
            return;
        }

        $step = $branch->currentStep()->first();

        if (!$step) {
            $branch->status = 'completed';
            $branch->current_step_id = null;
            $branch->save();
            $this->onBranchCompleted($enrollment, $branch);
            return;
        }

        if ($step->step_type === 'end') {
            $branch->status = 'completed';
            $branch->save();
            $this->onBranchCompleted($enrollment, $branch);
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

            $branch->status = 'waiting';
            $branch->resume_at = $resumeAt;
            $branch->save();

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
            $branchResult = WorkflowConditionEvaluator::evaluate($enrollment->enrollable, $step->branch_condition ?? []);
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

            $this->advanceBranchOrComplete($enrollment, $branch, $step, $next);
            return;
        }

        if ($step->step_type === 'switch') {
            $branchKey = WorkflowConditionEvaluator::evaluateSwitch($enrollment->enrollable, $step->action_config ?? []);

            $next = WorkflowStep::where('parent_step_id', $step->id)
                ->where('branch_key', $branchKey)
                ->orderBy('order')
                ->first();

            if (!$next && $branchKey !== 'default') {
                $next = WorkflowStep::where('parent_step_id', $step->id)
                    ->where('branch_key', 'default')
                    ->orderBy('order')
                    ->first();
            }

            WorkflowEnrollmentLog::create([
                'enrollment_id' => $enrollment->id,
                'step_id'       => $step->id,
                'action_taken'  => 'switch',
                'result'        => $branchKey,
                'logged_at'     => now(),
            ]);

            $this->advanceBranchOrComplete($enrollment, $branch, $step, $next);
            return;
        }

        if ($step->step_type === 'join') {
            // Un join anidado dentro de una rama (no es el punto de reunión
            // de ESTE parallel) no ejecuta ninguna acción real, solo avanza.
            $next = $this->nextSiblingOf($step);
            $this->advanceBranchOrComplete($enrollment, $branch, $step, $next);
            return;
        }

        if ($step->step_type === 'loop') {
            $firstChild = WorkflowStep::where('parent_step_id', $step->id)
                ->orderBy('order')
                ->first();

            if (!$firstChild) {
                $this->advanceBranchOrComplete($enrollment, $branch, $step, null);
                return;
            }

            $branch->current_step_id = $firstChild->id;
            $branch->save();

            $this->processBranch($enrollment, $branch);
            return;
        }

        if ($step->step_type === 'action') {
            app(WorkflowActionExecutor::class)->execute($enrollment, $step);

            $next = $this->nextSiblingOf($step);
            $this->advanceBranchOrComplete($enrollment, $branch, $step, $next);
            return;
        }

        // step_type desconocido o no soportado dentro de una rama (ej. un
        // 'parallel' anidado): avanza como si fuera un action sin efecto en
        // vez de dejar la rama bloqueada indefinidamente.
        $next = $this->nextSiblingOf($step);
        $this->advanceBranchOrComplete($enrollment, $branch, $step, $next);
    }

    /**
     * Equivalente de advanceOrComplete() para una rama de un 'parallel'.
     * Primero revisa si $step (el que se acaba de ejecutar) converge a un
     * join explícito (joins_into_step_id); si no, avanza a $next o -- si
     * $next es null y el padre de $step es un 'loop' -- repite la vuelta
     * (advanceLoopForBranch()); si tampoco es un loop, la rama termina
     * "suelta" y se revisa si eso cierra todo el parallel (onBranchCompleted()).
     */
    private function advanceBranchOrComplete(WorkflowEnrollment $enrollment, WorkflowEnrollmentBranch $branch, WorkflowStep $step, ?WorkflowStep $next): void
    {
        if ($step->joins_into_step_id) {
            $branch->status = 'completed';
            $branch->save();
            $this->checkJoinCompletion($enrollment, $branch->parallel_step_id, $step->joins_into_step_id);
            return;
        }

        if ($next) {
            $branch->current_step_id = $next->id;
            $branch->save();
            $this->processBranch($enrollment, $branch);
            return;
        }

        $parentStep = $step->parent_step_id
            ? WorkflowStep::find($step->parent_step_id)
            : null;

        if ($parentStep && $parentStep->step_type === 'loop') {
            $this->advanceLoopForBranch($enrollment, $branch, $parentStep);
            return;
        }

        // Rama terminó "suelta" (sin join) -- ver nota en onBranchCompleted().
        $branch->current_step_id = null;
        $branch->status = 'completed';
        $branch->save();
        $this->onBranchCompleted($enrollment, $branch);
    }

    /**
     * Equivalente de advanceLoop() para una rama: el contador de vueltas se
     * comparte con el flujo principal (se guarda en
     * $enrollment->trigger_context, indexado por id del loop step, sin
     * importar si el loop vive en el flujo principal o dentro de una rama),
     * pero el puntero de "step actual" se persiste en la fila de la rama.
     */
    private function advanceLoopForBranch(WorkflowEnrollment $enrollment, WorkflowEnrollmentBranch $branch, WorkflowStep $loopStep): void
    {
        $context = $enrollment->trigger_context ?? [];
        $currentIterations = (int) ($context['loop_iterations'][$loopStep->id] ?? 0);
        $maxIterations = min((int) ($loopStep->action_config['max_iterations'] ?? 1), 50);

        if ($currentIterations + 1 < $maxIterations) {
            $context['loop_iterations'][$loopStep->id] = $currentIterations + 1;
            $enrollment->trigger_context = $context;
            $enrollment->save();

            $firstChild = WorkflowStep::where('parent_step_id', $loopStep->id)
                ->orderBy('order')
                ->first();

            $branch->current_step_id = $firstChild?->id;
            $branch->save();

            $this->processBranch($enrollment, $branch);
            return;
        }

        $next = $this->nextSiblingOf($loopStep);
        $this->advanceBranchOrComplete($enrollment, $branch, $loopStep, $next);
    }

    /**
     * Llamada cuando una rama termina "suelta" (sin converger a ningún
     * join). Si todavía quedan otras ramas de ese mismo 'parallel' activas o
     * esperando, no hace nada (esas, al completarse, volverán a llamar
     * aquí). Si esta era la última, es un 'parallel' "fire and forget" (sin
     * ningún join configurado) -- completa la inscripción principal
     * directamente y limpia las filas de WorkflowEnrollmentBranch de este
     * parallel (son estado de ejecución transitorio).
     */
    private function onBranchCompleted(WorkflowEnrollment $enrollment, WorkflowEnrollmentBranch $branch): void
    {
        // IMPORTANTE: siempre filtrado también por enrollment_id, no solo
        // por parallel_step_id -- parallel_step_id identifica al WorkflowStep
        // de diseño, compartido por TODAS las inscripciones que pasan por ese
        // mismo 'parallel' (ej. dos Deals distintos corriendo el mismo
        // workflow al mismo tiempo). Sin este filtro, las ramas de una
        // inscripción ajena se cuentan/borran junto con las de esta.
        $stillPending = WorkflowEnrollmentBranch::where('parallel_step_id', $branch->parallel_step_id)
            ->where('enrollment_id', $enrollment->id)
            ->where('status', '!=', 'completed')
            ->exists();

        if ($stillPending) {
            return;
        }

        WorkflowEnrollmentBranch::where('parallel_step_id', $branch->parallel_step_id)
            ->where('enrollment_id', $enrollment->id)
            ->delete();

        if ($enrollment->status !== 'active') {
            return;
        }

        $enrollment->status = 'completed';
        $enrollment->completed_at = now();
        $enrollment->save();
    }

    /**
     * Cuenta cuántas ramas de $parallelStepId siguen sin completarse. Si ya
     * completaron todas, borra las filas de WorkflowEnrollmentBranch de ese
     * parallel (cumplieron su propósito, son estado transitorio) y avanza el
     * enrollment principal al step 'join', procesándolo como un step normal.
     * Si aún quedan ramas pendientes, no hace nada -- otra rama, al
     * completarse después, volverá a llamar aquí y eventualmente el conteo
     * llegará a 0.
     */
    private function checkJoinCompletion(WorkflowEnrollment $enrollment, int $parallelStepId, int $joinStepId): void
    {
        // Ver nota en onBranchCompleted(): siempre filtrado también por
        // enrollment_id -- parallel_step_id por sí solo no distingue entre
        // inscripciones distintas corriendo el mismo 'parallel' del mismo
        // workflow al mismo tiempo.
        $pending = WorkflowEnrollmentBranch::where('parallel_step_id', $parallelStepId)
            ->where('enrollment_id', $enrollment->id)
            ->where('status', '!=', 'completed')
            ->count();

        if ($pending > 0) {
            return;
        }

        WorkflowEnrollmentBranch::where('parallel_step_id', $parallelStepId)
            ->where('enrollment_id', $enrollment->id)
            ->delete();

        $enrollment->current_step_id = $joinStepId;
        $enrollment->save();

        $this->processStep($enrollment);
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
     * Equivalente de resumeWaiting() para una rama de un 'parallel': pasa la
     * rama a 'active', avanza su current_step_id al siguiente hermano del
     * step de wait (mismo nextSiblingOf() genérico, no le importa si el step
     * viene de una rama o del enrollment principal) y continúa procesando
     * esa rama.
     */
    public function resumeWaitingBranch(WorkflowEnrollmentBranch $branch): void
    {
        if ($branch->status !== 'waiting') {
            return;
        }

        $waitStep = $branch->currentStep()->first();

        $branch->status = 'active';
        $branch->resume_at = null;

        if ($waitStep) {
            $next = $this->nextSiblingOf($waitStep);

            $branch->current_step_id = $next?->id;
        }

        $branch->save();

        $this->processBranch($branch->enrollment, $branch);
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
