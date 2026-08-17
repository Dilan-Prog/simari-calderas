<?php

namespace App\Services;

use App\Jobs\SendMarketingEmailJob;
use App\Models\Credential;
use App\Models\Customer;
use App\Models\Deal;
use App\Models\EmailSend;
use App\Models\EmailTemplate;
use App\Models\PipelineStage;
use App\Models\Task;
use App\Models\Workflow;
use App\Models\WorkflowEnrollment;
use App\Models\WorkflowEnrollmentLog;
use App\Models\WorkflowStep;
use App\Models\WorkflowVariable;
use App\Models\WhatsappConversation;
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
            'send_whatsapp_message' => $this->sendWhatsappMessage($enrollment, $step),
            'flag_missing_contact' => $this->flagMissingContact($enrollment, $step),
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
            'send_whatsapp_message' => [
                'label' => 'Enviar mensaje de WhatsApp',
                'example_config' => ['template_name' => null, 'params' => [], 'text' => null],
            ],
            'flag_missing_contact' => [
                'label' => 'Avisar si falta contacto',
                'example_config' => ['channel' => 'email'],
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
        $resolver = app(WorkflowTokenResolver::class);

        $task = new \App\Models\Task([
            'title'                   => $resolver->resolveTokens($config['title'] ?? null, $enrollment),
            'description'             => $resolver->resolveTokens($config['description'] ?? null, $enrollment),
            'due_at'                  => $config['due_at'] ?? null,
            'assigned_to'             => $config['assigned_to'] ?? null,
            'created_by_workflow_id'  => $enrollment->workflow_id,
        ]);

        $task->taskable()->associate($enrollment->enrollable);
        $task->save();

        $this->logResult($enrollment, $step, 'create_task', 'success', "Tarea #{$task->id} creada.");
    }

    /**
     * Acción explícita y visible en el lienzo (catálogo: "Avisar si falta
     * contacto"). Antes esto se disparaba automáticamente y en silencio
     * desde dentro de sendEmail()/sendWhatsappMessage() cuando se omitían
     * por falta de dato de contacto; a partir de aquí es opt-in: cada
     * workflow decide si le importa este aviso agregando (o no) este nodo
     * después de su paso de envío correspondiente.
     *
     * `action_config.channel` ('email'|'whatsapp') determina qué se checa:
     * mismo criterio exacto que usan sendEmail()/sendWhatsappMessage() para
     * decidir si omiten el envío (resolveEmailRecipient()/guest_email/
     * contact_email para email; resolveWhatsappConversation() para
     * whatsapp), así que este step siempre coincide con lo que de verdad
     * pasó en el paso de envío anterior.
     */
    private function flagMissingContact(WorkflowEnrollment $enrollment, WorkflowStep $step): void
    {
        $config = $step->action_config ?? [];
        $channel = $config['channel'] ?? 'email';
        $enrollable = $enrollment->enrollable;

        if (!$enrollable) {
            $this->logResult($enrollment, $step, 'flag_missing_contact', 'skipped', 'No hay enrollable.');
            return;
        }

        $hasContact = $channel === 'whatsapp'
            ? (bool) $this->resolveWhatsappConversation($enrollable)
            : (bool) ($this->resolveEmailRecipient($enrollable) || !empty($enrollable->guest_email) || !empty($enrollable->contact_email));

        if ($hasContact) {
            $this->logResult($enrollment, $step, 'flag_missing_contact', 'skipped', 'Sí tiene ese dato de contacto, no hace falta avisar.');
            return;
        }

        $label = $channel === 'whatsapp' ? 'WhatsApp' : 'correo';
        $identifier = $enrollable->quote_number ?? $enrollable->name ?? ('#' . $enrollable->getKey());
        $title = "Sin {$label}: {$identifier}";
        $description = "El seguimiento automático no pudo enviarse por {$label}: falta ese dato de contacto. Revisa los datos del cliente o contáctalo manualmente.";

        // Para WhatsApp, distingue "no tiene teléfono" de "tiene teléfono
        // pero no hay conversación real" (típicamente porque todavía no se
        // conectan las credenciales de Meta/WhatsApp Business) -- son dos
        // causas distintas y le ahorran a la persona que revisa la tarea
        // tener que adivinar cuál aplica.
        if ($channel === 'whatsapp') {
            $hasPhone = method_exists($enrollable, 'getAttribute') ? (bool) $enrollable->has_whatsapp : false;

            if ($hasPhone) {
                $title = "WhatsApp no enviado: {$identifier}";
                $description = 'El cliente sí tiene un número registrado, pero no hay una conversación de WhatsApp activa -- probablemente porque todavía no se conectan las credenciales de Meta/WhatsApp Business en el sistema. Envía el mensaje manualmente mientras tanto.';
            } else {
                $description = 'El seguimiento automático no pudo enviarse por WhatsApp: el cliente no tiene ningún número registrado. Revisa sus datos o contáctalo por otro medio.';
            }
        }

        $alreadyOpen = Task::query()
            ->where('taskable_type', $enrollable->getMorphClass())
            ->where('taskable_id', $enrollable->getKey())
            ->where('title', $title)
            ->where('status', 'open')
            ->exists();

        if ($alreadyOpen) {
            $this->logResult($enrollment, $step, 'flag_missing_contact', 'skipped', "Ya existe una tarea abierta \"{$title}\".");
            return;
        }

        $task = new Task([
            'title'                  => $title,
            'description'            => $description,
            'created_by_workflow_id' => $enrollment->workflow_id,
        ]);

        $task->taskable()->associate($enrollable);
        $task->save();

        $this->logResult($enrollment, $step, 'flag_missing_contact', 'success', "Tarea #{$task->id} creada: \"{$title}\".");
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

    /**
     * De-hardcodeada en Fase 17: ya no exige `instanceof Deal` -- actualiza
     * genéricamente el `enrollable` de cualquier módulo automatizable vía
     * update() de Eloquent. isFillable() reemplaza al chequeo de tipo fijo:
     * si el campo configurado no es mass-assignable en el modelo real
     * (fillable no lo declara), se registra 'skipped' con mensaje claro en
     * vez de que Eloquent lo descarte en silencio (el proyecto no tiene
     * preventSilentlyDiscardingAttributes() activado).
     */
    private function updateProperty(WorkflowEnrollment $enrollment, WorkflowStep $step): void
    {
        $config = $step->action_config ?? [];
        $field  = $config['field'] ?? null;
        $value  = $config['value'] ?? null;

        if (empty($field)) {
            $this->logResult($enrollment, $step, 'update_property', 'skipped', 'No se especificó el campo a actualizar.');
            return;
        }

        $enrollable = $enrollment->enrollable;

        if (!$enrollable || !$enrollable->isFillable($field)) {
            $this->logResult($enrollment, $step, 'update_property', 'skipped', "El campo '{$field}' no es editable en este módulo.");
            return;
        }

        $resolvedValue = is_string($value)
            ? app(WorkflowTokenResolver::class)->resolveTokens($value, $enrollment)
            : $value;

        $enrollable->update([$field => $resolvedValue]);

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

    /**
     * Generalizada en Fase 17: el if/elseif fijo a Deal/Customer se
     * reemplaza por lectura de `customer_relation` desde
     * AutomatableModuleRegistry -- cualquier módulo que declare esa
     * relación en config/automatable_modules.php puede enviar email
     * genéricamente, sin código nuevo por modelo.
     */
    private function sendEmail(WorkflowEnrollment $enrollment, WorkflowStep $step): void
    {
        $config = $step->action_config ?? [];
        $templateId = $config['template_id'] ?? null;
        $attachSource = $config['attach_source'] ?? null;

        $template = $templateId ? EmailTemplate::find($templateId) : null;

        if (!$template) {
            $this->logResult($enrollment, $step, 'send_email', 'failed', "No se encontró la plantilla de email (template_id={$templateId}).");
            return;
        }

        $enrollable = $enrollment->enrollable;
        $customer = $this->resolveEmailRecipient($enrollable);

        $customerId = null;
        $guestEmail = null;
        $guestName  = null;
        $recipientEmail = null;

        if ($customer) {
            $customerId     = $customer->id;
            $recipientEmail = $customer->email;
        } elseif ($enrollable && !empty($enrollable->guest_email)) {
            $guestEmail     = $enrollable->guest_email;
            $guestName      = $enrollable->guest_name ?? null;
            $recipientEmail = $guestEmail;
        } elseif ($enrollable && !empty($enrollable->contact_email)) {
            // Fallback de invitado con otro nombre de campo: Cart (carrito
            // abandonado) no usa guest_email/guest_name como Quote, usa
            // contact_email/contact_name (mismo patrón de invitado, campos
            // distintos) -- se acepta genéricamente aquí para no tener que
            // repetir esta rama por cada modelo con su propia convención.
            $guestEmail     = $enrollable->contact_email;
            $guestName      = $enrollable->contact_name ?? null;
            $recipientEmail = $guestEmail;
        } else {
            $this->logResult($enrollment, $step, 'send_email', 'skipped', 'El enrollable no tiene un Customer asociado, no se puede enviar email');
            return;
        }

        $send = EmailSend::create([
            'workflow_step_id'        => $step->id,
            'workflow_enrollment_id'  => $enrollment->id,
            'customer_id'             => $customerId,
            'guest_email'             => $guestEmail,
            'guest_name'              => $guestName,
            'attach_source'           => $attachSource,
        ]);

        SendMarketingEmailJob::dispatch($send);

        $this->logResult($enrollment, $step, 'send_email', 'success', "Email \"{$template->name}\" encolado para {$recipientEmail}.");
    }

    /**
     * Envía un mensaje de WhatsApp (Fase 15). `action_config` acepta
     * `{text}` (texto libre, solo válido dentro de la ventana de 24h desde
     * el último mensaje del contacto) y/o `{template_name, params}`
     * (plantilla aprobada, obligatoria fuera de la ventana). Si hay `text`
     * configurado y la ventana está abierta, se prefiere texto libre; si no,
     * cae a la plantilla configurada. Reutiliza
     * WhatsappService::isWithin24hWindow()/sendTextMessage()/sendTemplateMessage()
     * de la Fase 11 tal cual, sin reimplementar la regla de la ventana aquí.
     */
    private function sendWhatsappMessage(WorkflowEnrollment $enrollment, WorkflowStep $step): void
    {
        $conversation = $this->resolveWhatsappConversation($enrollment->enrollable);

        if (!$conversation) {
            $this->logResult($enrollment, $step, 'send_whatsapp_message', 'skipped', 'El enrollable no tiene una conversación de WhatsApp asociada.');
            return;
        }

        $config = $step->action_config ?? [];
        $whatsapp = app(WhatsappService::class);
        $resolvedText = app(WorkflowTokenResolver::class)->resolveTokens($config['text'] ?? null, $enrollment);

        try {
            if (filled($resolvedText) && $whatsapp->isWithin24hWindow($conversation)) {
                $whatsapp->sendTextMessage($conversation, (string) $resolvedText);
                $this->logResult($enrollment, $step, 'send_whatsapp_message', 'success', 'Mensaje de texto libre enviado.');
                return;
            }

            $templateName = $config['template_name'] ?? null;

            if (!$templateName) {
                $this->logResult($enrollment, $step, 'send_whatsapp_message', 'failed', 'La conversación está fuera de la ventana de 24h y no se configuró una plantilla aprobada (template_name).');
                return;
            }

            $whatsapp->sendTemplateMessage($conversation, $templateName, $config['params'] ?? []);

            $this->logResult($enrollment, $step, 'send_whatsapp_message', 'success', "Plantilla \"{$templateName}\" enviada.");
        } catch (Throwable $e) {
            $this->logResult($enrollment, $step, 'send_whatsapp_message', 'failed', $e->getMessage());
        }
    }

    /**
     * Resuelve la WhatsappConversation sobre la que operar según el tipo de
     * enrollable: directa si el workflow es tipo 'whatsapp_conversation', o
     * la conversación vinculada (deal_id) si es un Deal con chat asociado
     * (botón "WhatsApp" de la tarjeta de Deal, Fase 14) — cualquier otro
     * tipo de enrollable no tiene conversación posible.
     */
    /**
     * Resuelve el Customer destinatario de send_email para cualquier
     * enrollable: directo si ya es un Customer, o vía la relación declarada
     * en `customer_relation` del registro (AutomatableModuleRegistry) para
     * cualquier otro módulo. Retorna null si el enrollable es un Customer
     * pero no tiene relación declarada o la relación no resuelve nada
     * (p. ej. Deal sin customer_id) -- la única acción posible del llamador
     * en ambos casos es 'skipped', así que no hace falta distinguir el
     * motivo aquí.
     */
    private function resolveEmailRecipient($enrollable): ?Customer
    {
        if ($enrollable instanceof Customer) {
            return $enrollable;
        }

        if (!$enrollable) {
            return null;
        }

        $registry = app(AutomatableModuleRegistry::class);
        $type = $registry->typeForModel($enrollable);
        $relation = $type ? ($registry->entry($type)['customer_relation'] ?? null) : null;

        if (!$relation || !method_exists($enrollable, $relation)) {
            return null;
        }

        $related = $enrollable->{$relation};

        return $related instanceof Customer ? $related : null;
    }

    private function resolveWhatsappConversation($enrollable): ?WhatsappConversation
    {
        if ($enrollable instanceof WhatsappConversation) {
            return $enrollable;
        }

        if ($enrollable instanceof Deal) {
            return WhatsappConversation::where('deal_id', $enrollable->getKey())->latest('id')->first();
        }

        // Fallback genérico para cualquier otro enrollable (p. ej. Quote) que
        // resuelva a un Customer real: si ese cliente ya tiene una
        // conversación de WhatsApp (abierta por cualquier vía, no solo por un
        // Deal), se usa esa. Si no tiene ninguna, se sigue quedando en null
        // (logResult 'skipped', no falla el step ni el workflow).
        $customer = $this->resolveEmailRecipient($enrollable);

        if ($customer !== null) {
            return WhatsappConversation::where('customer_id', $customer->getKey())->latest('id')->first();
        }

        return null;
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
