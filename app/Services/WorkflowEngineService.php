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
     * Inscribe un modelo (Deal o Customer) en un workflow. Si el workflow no
     * permite reinscripción, retorna la inscripción activa/waiting existente
     * en lugar de crear una duplicada. Arranca el procesamiento del primer
     * step inmediatamente después de crear la inscripción.
     */
    public function enroll(Workflow $workflow, $target): WorkflowEnrollment
    {
        return DB::transaction(function () use ($workflow, $target) {
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

        $step = $enrollment->currentStep;

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
            $children = $step->children()->get();

            $matched = null;
            $default = null;

            foreach ($children as $child) {
                if (!empty($child->branch_condition)) {
                    if (\App\Services\WorkflowConditionEvaluator::evaluate($enrollment->enrollable, $child->branch_condition)) {
                        $matched = $child;
                        break;
                    }
                } elseif (!$default) {
                    $default = $child;
                }
            }

            $next = $matched ?? $default;

            $enrollment->current_step_id = $next?->id;
            $enrollment->save();

            $this->processStep($enrollment);

            return;
        }

        if ($step->step_type === 'action') {
            app(\App\Services\WorkflowActionExecutor::class)->execute($enrollment, $step);

            $next = WorkflowStep::where('workflow_id', $step->workflow_id)
                ->where('parent_step_id', $step->parent_step_id)
                ->where('order', '>', $step->order)
                ->orderBy('order')
                ->first();

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

        $waitStep = $enrollment->currentStep;

        $enrollment->status = 'active';
        $enrollment->resume_at = null;

        if ($waitStep) {
            $next = WorkflowStep::where('workflow_id', $waitStep->workflow_id)
                ->where('parent_step_id', $waitStep->parent_step_id)
                ->where('order', '>', $waitStep->order)
                ->orderBy('order')
                ->first();

            $enrollment->current_step_id = $next?->id;
        }

        $enrollment->save();

        $this->processStep($enrollment);
    }
}
