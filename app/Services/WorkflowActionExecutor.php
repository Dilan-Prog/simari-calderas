<?php

namespace App\Services;

use App\Jobs\SendMarketingEmailJob;
use App\Models\Credential;
use App\Models\Customer;
use App\Models\Deal;
use App\Models\EmailSend;
use App\Models\EmailTemplate;
use App\Models\PipelineStage;
use App\Models\Workflow;
use App\Models\WorkflowEnrollment;
use App\Models\WorkflowEnrollmentLog;
use App\Models\WorkflowStep;
use App\Models\WorkflowVariable;
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
            'external_db_query' => $this->externalDbQuery($enrollment, $step),
            default             => $this->logResult($enrollment, $step, $step->action_type, 'skipped', "Tipo de acción desconocido: {$step->action_type}"),
        };
    }

    /**
     * Lista de action_type soportados por execute()/match(), con un label
     * para UI y un ejemplo de la forma real de action_config esperada por
     * cada método privado correspondiente.
     */
    public static function supportedActions(): array
    {
        return [
            'create_task' => [
                'label' => 'Crear tarea',
                'example_config' => ['title' => 'Dar seguimiento', 'description' => null, 'due_at' => null, 'assigned_to' => null],
            ],
            // Opción A (QA Fase 10, punto 4): notify_rep se mantiene en el catálogo
            // pero marcado 'coming_soon' porque notifyRep() es un no-op (solo registra
            // 'skipped' en el log, no envía notificación real). Se trata igual que las
            // categorías Integraciones/IA/Datos y HTTP en el canvas: visible pero
            // deshabilitado ("Próximamente") en vez de ocultarlo, para comunicar el
            // roadmap sin prometer una funcionalidad que aún no existe (Opción B,
            // quitarlo del catálogo, se descartó por perder esa visibilidad).
            'notify_rep' => [
                'label' => 'Notificar al vendedor',
                'example_config' => [],
                'coming_soon' => true,
            ],
            'update_property' => [
                'label' => 'Actualizar propiedad del negocio',
                'example_config' => ['field' => 'notes', 'value' => ''],
            ],
            'move_deal_stage' => [
                'label' => 'Mover etapa del negocio',
                'example_config' => ['stage_id' => null],
            ],
            'enroll_in_workflow' => [
                'label' => 'Inscribir en otro workflow',
                'example_config' => ['workflow_id' => null],
            ],
            'send_email' => [
                'label' => 'Enviar correo',
                'example_config' => ['template_id' => null],
            ],
            'external_db_query' => [
                'label' => 'Base de datos externa (MySQL/MariaDB)',
                'example_config' => [
                    'credential_id' => null,
                    'table' => '',
                    'operation' => 'select',
                    'columns' => [
                        ['column' => '', 'value_source' => 'literal', 'value' => ''],
                    ],
                    'where' => [
                        ['column' => '', 'operator' => '=', 'value_source' => 'literal', 'value' => ''],
                    ],
                    'output_variable' => null,
                    'limit' => 50,
                ],
            ],
        ];
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

    /**
     * Tope máximo absoluto de filas para una operación `select`, sin importar
     * lo que pida `action_config['limit']` — mismo tope que
     * DevOpsController::MAX_RESULT_ROWS para la consola SQL admin-only.
     */
    private const EXTERNAL_DB_MAX_ROWS = 500;

    private function externalDbQuery(WorkflowEnrollment $enrollment, WorkflowStep $step): void
    {
        $config = $step->action_config ?? [];

        $credentialId = $config['credential_id'] ?? null;
        $credential   = $credentialId ? Credential::find($credentialId) : null;

        if (!$credential || $credential->type !== 'mysql') {
            $this->logResult($enrollment, $step, 'external_db_query', 'skipped', "No se encontró una credencial mysql válida (credential_id={$credentialId}).");
            return;
        }

        $table     = (string) ($config['table'] ?? '');
        $operation = (string) ($config['operation'] ?? '');

        if ($table === '' || !in_array($operation, ['select', 'insert', 'update', 'delete'], true)) {
            $this->logResult($enrollment, $step, 'external_db_query', 'skipped', 'Configuración incompleta: falta tabla u operación válida.');
            return;
        }

        $connectionService = app(ExternalDatabaseConnectionService::class);

        try {
            // Re-validación de esquema contra la BD real en el momento de
            // ejecutar (nunca se confía en lo que quedó guardado en
            // action_config al diseñar el paso — el esquema pudo cambiar).
            $realTables = $connectionService->listTables($credential);

            if (!in_array($table, $realTables, true)) {
                $this->logResult($enrollment, $step, 'external_db_query', 'failed', "La tabla '{$table}' ya no existe en la base de datos externa.");
                return;
            }

            $realColumns = $connectionService->listColumns($credential, $table);

            $columns = collect($config['columns'] ?? [])
                ->filter(fn ($c) => filled($c['column'] ?? null))
                ->map(function ($c) use ($enrollment, $realColumns, $step) {
                    if (!in_array($c['column'], $realColumns, true)) {
                        throw new \RuntimeException("La columna '{$c['column']}' ya no existe en la tabla.");
                    }

                    return [
                        'column' => $c['column'],
                        'value'  => $this->resolveConfiguredValue($c, $enrollment->workflow_id),
                    ];
                })
                ->values();

            $whereClauses = collect($config['where'] ?? [])
                ->filter(fn ($w) => filled($w['column'] ?? null))
                ->map(function ($w) use ($realColumns, $enrollment) {
                    if (!in_array($w['column'], $realColumns, true)) {
                        throw new \RuntimeException("La columna '{$w['column']}' ya no existe en la tabla.");
                    }

                    return [
                        'column'   => $w['column'],
                        'operator' => $w['operator'] ?? '=',
                        'value'    => $this->resolveConfiguredValue($w, $enrollment->workflow_id),
                    ];
                })
                ->values();

            // Guarda dura: nunca un UPDATE/DELETE sin condición WHERE.
            if (in_array($operation, ['update', 'delete'], true) && $whereClauses->isEmpty()) {
                $this->logResult($enrollment, $step, 'external_db_query', 'failed', "Operación '{$operation}' rechazada: no se especificó ningún WHERE.");
                return;
            }

            $connection = $connectionService->connectionFor($credential);
            $query      = $connection->table($table);

            foreach ($whereClauses as $clause) {
                $query->where($clause['column'], $clause['operator'], $clause['value']);
            }

            $affected = null;
            $resultForVariable = null;

            switch ($operation) {
                case 'select':
                    $limit = min((int) ($config['limit'] ?? 50), self::EXTERNAL_DB_MAX_ROWS);
                    $rows  = $query->limit(max($limit, 1))->get();
                    $resultForVariable = $limit === 1 ? ($rows->first() ? (array) $rows->first() : null) : $rows->map(fn ($row) => (array) $row)->all();
                    $affected = $rows->count();
                    break;

                case 'insert':
                    $insertData = $columns->pluck('value', 'column')->all();

                    if (empty($insertData)) {
                        $this->logResult($enrollment, $step, 'external_db_query', 'failed', 'No se especificaron columnas para el insert.');
                        return;
                    }

                    $query->insert($insertData);
                    $affected = 1;
                    break;

                case 'update':
                    $updateData = $columns->pluck('value', 'column')->all();

                    if (empty($updateData)) {
                        $this->logResult($enrollment, $step, 'external_db_query', 'failed', 'No se especificaron columnas para el update.');
                        return;
                    }

                    $affected = $query->update($updateData);
                    break;

                case 'delete':
                    $affected = $query->delete();
                    break;
            }

            if ($operation === 'select' && !empty($config['output_variable'])) {
                $this->storeOutputVariable($enrollment->workflow_id, (string) $config['output_variable'], $resultForVariable);
            }

            $this->logResult($enrollment, $step, 'external_db_query', 'success', "Operación '{$operation}' sobre '{$table}' completada ({$affected} fila(s)).");
        } catch (Throwable $e) {
            $this->logResult($enrollment, $step, 'external_db_query', 'failed', $e->getMessage());
        }
    }

    /**
     * Resuelve un valor configurado ({value_source: literal|variable, value})
     * contra WorkflowVariable cuando corresponde. $workflowId null se usa
     * para condiciones WHERE que no necesitan quedar ligadas a un workflow
     * específico distinto del ya resuelto por la variable misma.
     */
    private function resolveConfiguredValue(array $entry, ?int $workflowId)
    {
        $source = $entry['value_source'] ?? 'literal';

        if ($source === 'variable') {
            return WorkflowVariable::resolveValue((string) ($entry['value'] ?? ''), $workflowId);
        }

        return $entry['value'] ?? null;
    }

    private function storeOutputVariable(int $workflowId, string $name, $value): void
    {
        WorkflowVariable::updateOrCreate(
            ['workflow_id' => $workflowId, 'name' => $name],
            ['type' => 'json', 'value' => is_scalar($value) || $value === null ? $value : json_encode($value)]
        );
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
