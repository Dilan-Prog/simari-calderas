<?php

namespace App\Services;

use App\Jobs\SendMarketingEmailJob;
use App\Models\Customer;
use App\Models\Deal;
use App\Models\EmailSend;
use App\Models\EmailTemplate;
use App\Models\PipelineStage;
use App\Models\Workflow;
use App\Models\WorkflowEnrollment;
use App\Models\WorkflowEnrollmentLog;
use App\Models\WorkflowStep;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Ejecuta la acción de un WorkflowStep sobre un WorkflowEnrollment.
 * Toda ejecución (exitosa, fallida o omitida) queda registrada en
 * WorkflowEnrollmentLog.
 */
class WorkflowActionExecutor
{
    public function execute(WorkflowEnrollment $enrollment, WorkflowStep $step): void
    {
        match ($step->action_type) {
            'create_task'       => $this->createTask($enrollment, $step),
            'notify_rep'        => $this->notifyRep($enrollment, $step),
            'update_property'   => $this->updateProperty($enrollment, $step),
            'move_deal_stage'   => $this->moveDealStage($enrollment, $step),
            'enroll_in_workflow' => $this->enrollInWorkflow($enrollment, $step),
            'send_email'        => $this->sendEmail($enrollment, $step),
            default             => $this->logResult($enrollment, $step, $step->action_type, 'skipped', "Tipo de acción desconocido: {$step->action_type}"),
        };
    }

    private function createTask(WorkflowEnrollment $enrollment, WorkflowStep $step): void
    {
        if (!class_exists(\App\Models\Task::class)) {
            $this->logResult($enrollment, $step, 'create_task', 'skipped', 'El modelo Task no existe todavía.');
            return;
        }

        $config = $step->action_config ?? [];

        $task = new \App\Models\Task([
            'title'                   => $config['title'] ?? null,
            'description'             => $config['description'] ?? null,
            'due_at'                  => $config['due_at'] ?? null,
            'assigned_to'             => $config['assigned_to'] ?? null,
            'created_by_workflow_id'  => $enrollment->workflow_id,
        ]);

        $task->taskable()->associate($enrollment->enrollable);
        $task->save();

        $this->logResult($enrollment, $step, 'create_task', 'success', "Tarea #{$task->id} creada.");
    }

    private function notifyRep(WorkflowEnrollment $enrollment, WorkflowStep $step): void
    {
        // Placeholder: la notificación in-app aún no está implementada.
        $this->logResult(
            $enrollment,
            $step,
            'notify_rep',
            'skipped',
            'Notificación in-app no implementada, solo email (fuera de alcance de este momento)'
        );
    }

    private function updateProperty(WorkflowEnrollment $enrollment, WorkflowStep $step): void
    {
        $config = $step->action_config ?? [];
        $field  = $config['field'] ?? null;
        $value  = $config['value'] ?? null;

        if (!$enrollment->enrollable instanceof Deal) {
            $this->logResult($enrollment, $step, 'update_property', 'skipped', 'El enrollable no es un Deal.');
            return;
        }

        if (empty($field)) {
            $this->logResult($enrollment, $step, 'update_property', 'skipped', 'No se especificó el campo a actualizar.');
            return;
        }

        $enrollment->enrollable->update([$field => $value]);

        $this->logResult($enrollment, $step, 'update_property', 'success', "Campo '{$field}' actualizado.");
    }

    private function moveDealStage(WorkflowEnrollment $enrollment, WorkflowStep $step): void
    {
        if (!$enrollment->enrollable instanceof Deal) {
            $this->logResult($enrollment, $step, 'move_deal_stage', 'skipped', 'El enrollable no es un Deal.');
            return;
        }

        $config  = $step->action_config ?? [];
        $stageId = $config['stage_id'] ?? null;
        $stage   = $stageId ? PipelineStage::find($stageId) : null;

        if (!$stage) {
            $this->logResult($enrollment, $step, 'move_deal_stage', 'failed', "No se encontró la etapa (stage_id={$stageId}).");
            return;
        }

        try {
            app(DealService::class)->moveStage($enrollment->enrollable, $stage, null);

            $this->logResult($enrollment, $step, 'move_deal_stage', 'success', "Deal movido a la etapa '{$stage->name}'.");
        } catch (ValidationException|Throwable $e) {
            $this->logResult($enrollment, $step, 'move_deal_stage', 'failed', $e->getMessage());
        }
    }

    private function enrollInWorkflow(WorkflowEnrollment $enrollment, WorkflowStep $step): void
    {
        $config       = $step->action_config ?? [];
        $workflowId   = $config['workflow_id'] ?? null;
        $targetWorkflow = $workflowId ? Workflow::find($workflowId) : null;

        if (!$targetWorkflow) {
            $this->logResult($enrollment, $step, 'enroll_in_workflow', 'failed', "No se encontró el workflow destino (workflow_id={$workflowId}).");
            return;
        }

        app(WorkflowEngineService::class)->enroll($targetWorkflow, $enrollment->enrollable);

        $this->logResult($enrollment, $step, 'enroll_in_workflow', 'success', "Inscrito en el workflow '{$targetWorkflow->name}'.");
    }

    private function sendEmail(WorkflowEnrollment $enrollment, WorkflowStep $step): void
    {
        $config = $step->action_config ?? [];
        $templateId = $config['template_id'] ?? null;

        $template = $templateId ? EmailTemplate::find($templateId) : null;

        if (!$template) {
            $this->logResult($enrollment, $step, 'send_email', 'failed', "No se encontró la plantilla de email (template_id={$templateId}).");
            return;
        }

        $customer = null;

        if ($enrollment->enrollable instanceof Deal) {
            $customer = $enrollment->enrollable->customer;

            if (!$customer) {
                $this->logResult($enrollment, $step, 'send_email', 'skipped', 'Deal sin Customer, no se puede enviar email');
                return;
            }
        } elseif ($enrollment->enrollable instanceof Customer) {
            $customer = $enrollment->enrollable;
        }

        if (!$customer) {
            $this->logResult($enrollment, $step, 'send_email', 'skipped', 'El enrollable no tiene un Customer asociado, no se puede enviar email');
            return;
        }

        $send = EmailSend::create([
            'workflow_step_id' => $step->id,
            'customer_id'      => $customer->id,
        ]);

        SendMarketingEmailJob::dispatch($send);

        $this->logResult($enrollment, $step, 'send_email', 'success', "Email \"{$template->name}\" encolado para {$customer->email}.");
    }

    private function logResult(WorkflowEnrollment $enrollment, WorkflowStep $step, string $actionTaken, string $result, ?string $message = null): void
    {
        WorkflowEnrollmentLog::create([
            'enrollment_id' => $enrollment->id,
            'step_id'       => $step->id,
            'action_taken'  => $actionTaken,
            'result'        => $result,
            'message'       => $message,
            'logged_at'     => now(),
        ]);
    }
}
