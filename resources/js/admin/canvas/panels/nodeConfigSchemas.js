/**
 * Esquemas declarativos que alimentan el autocompletado "schema-aware" del
 * canvas de Workflows (ver TokenAutocomplete.jsx). Cada entrada describe,
 * por tipo de nodo/acción, qué claves existen y de dónde sacar sugerencias
 * de valor cuando kind === 'enum'.
 *
 * kind:
 *   'enum'         -> sugiere valores reales, resueltos vía `source`
 *   'string_token' -> texto libre que además participa del modo {{ }}
 *   'string'       -> texto libre simple (solo sugerencia de nombre de clave)
 *   'number'       -> numérico (solo sugerencia de nombre de clave)
 *   'date'         -> fecha (solo sugerencia de nombre de clave)
 *   'array'        -> array simple (solo sugerencia de nombre de clave)
 *   'array_of_object' -> array de objetos con su propio itemSchema anidado
 *
 * source (solo relevante cuando kind === 'enum'):
 *   'trigger_event'         -> valores fijos created/updated/deleted/stale
 *   'trigger_operator'      -> valores fijos eq/neq/gt/gte/lt/lte
 *   'module_fields'         -> catalog.modules[moduleType].fields
 *   'field_value:<campo>'   -> catalog.field_value_sources[<campo>]
 *   'field_value:dynamic'   -> como arriba, pero <campo> se resuelve leyendo
 *                              la clave "field" ya escrita en el mismo JSON
 *   'catalog:<clave>'       -> catalog.action_value_sources[<clave>]
 *   'local:<clave>'         -> datos ya cargados en memoria por NodeInspector
 *                              (ej. lista de credenciales de BD), pasados
 *                              como prop localValueSources
 *   'static:<clave>'        -> listas fijas definidas en TokenAutocomplete.jsx
 */

// Fuente de verdad de cada esquema: el método PHP correspondiente en
// app/Services/WorkflowActionExecutor.php (createTask(), updateProperty(),
// moveDealStage(), enrollInWorkflow(), sendEmail(), sendWhatsappMessage()).
// Si esos métodos cambian de forma, actualizar aquí también.

export const TRIGGER_SCHEMA = {
    keys: [
        { key: 'event', kind: 'enum', source: 'trigger_event', hint: 'evento que dispara (created/updated/deleted/stale)' },
        { key: 'field', kind: 'enum', source: 'module_fields', hint: 'campo del módulo activo' },
        { key: 'operator', kind: 'enum', source: 'trigger_operator', hint: 'comparador (eq/neq/gt/gte/lt/lte)' },
        { key: 'value', kind: 'enum', source: 'field_value:dynamic', hint: 'valor a comparar' },
        { key: 'from_value', kind: 'string', hint: 'valor anterior requerido (opcional, transición A→B)' },
        { key: 'hours', kind: 'number', hint: 'horas sin actividad (solo event=stale)' },
    ],
};

export const ACTION_CONFIG_SCHEMAS = {
    create_task: {
        keys: [
            { key: 'title', kind: 'string_token', hint: 'título de la tarea (soporta {{ }})' },
            { key: 'description', kind: 'string_token', hint: 'descripción (soporta {{ }})' },
            { key: 'due_at', kind: 'date', hint: 'fecha límite (YYYY-MM-DD)' },
            { key: 'assigned_to', kind: 'number', hint: 'id del usuario asignado' },
        ],
    },
    update_property: {
        keys: [
            { key: 'field', kind: 'enum', source: 'module_fields', hint: 'campo del módulo activo' },
            { key: 'value', kind: 'string_token', hint: 'nuevo valor (soporta {{ }})' },
        ],
    },
    move_deal_stage: {
        keys: [
            { key: 'stage_id', kind: 'enum', source: 'field_value:pipeline_stage_id', hint: 'etapa destino' },
        ],
    },
    enroll_in_workflow: {
        keys: [
            { key: 'workflow_id', kind: 'enum', source: 'catalog:workflows', hint: 'workflow a inscribir' },
        ],
    },
    send_email: {
        keys: [
            { key: 'template_id', kind: 'enum', source: 'catalog:email_templates', hint: 'plantilla a enviar' },
        ],
    },
    send_whatsapp_message: {
        keys: [
            { key: 'text', kind: 'string_token', hint: 'texto libre, solo dentro de ventana 24h (soporta {{ }})' },
            { key: 'template_name', kind: 'string', hint: 'nombre de plantilla aprobada de WhatsApp' },
            { key: 'params', kind: 'array', hint: 'parámetros posicionales de la plantilla' },
        ],
    },
};

// Consumido por el nuevo componente FieldAutocompleteInput.jsx (paso
// Condición) — no por TokenAutocompleteTextarea, cuyo mecanismo de
// posición-de-cursor no aplica a un <input> de una sola línea.
export const CONDITION_SCHEMA = {
    field: { kind: 'enum', source: 'module_fields' },
    value: { kind: 'enum', source: 'field_value:dynamic' },
};

export const EXTERNAL_DB_SCHEMA = {
    keys: [
        { key: 'credential_id', kind: 'enum', source: 'local:db_credentials', hint: 'credencial de BD' },
        { key: 'table', kind: 'string', hint: 'nombre de la tabla' },
        { key: 'operation', kind: 'enum', source: 'static:db_operations', hint: 'select | insert | update | delete' },
        { key: 'output_variable', kind: 'string', hint: 'variable de salida (solo select)' },
        { key: 'limit', kind: 'number', hint: 'máx. 500 (solo select)' },
        {
            key: 'columns',
            kind: 'array_of_object',
            hint: 'columnas a insertar/actualizar',
            itemSchema: {
                keys: [
                    { key: 'column', kind: 'string', hint: 'nombre de columna' },
                    { key: 'value_source', kind: 'enum', source: 'static:value_sources', hint: 'literal | variable' },
                    { key: 'value', kind: 'string_token', hint: 'valor o nombre de variable' },
                ],
            },
        },
        {
            key: 'where',
            kind: 'array_of_object',
            hint: 'condiciones WHERE',
            itemSchema: {
                keys: [
                    { key: 'column', kind: 'string', hint: 'nombre de columna' },
                    { key: 'operator', kind: 'enum', source: 'static:where_operators', hint: '= != > < >= <= like' },
                    { key: 'value_source', kind: 'enum', source: 'static:value_sources', hint: 'literal | variable' },
                    { key: 'value', kind: 'string_token', hint: 'valor o nombre de variable' },
                ],
            },
        },
    ],
};

// Esquema del nodo de acción 'call_webhook' (HTTP Request), en el mismo
// patrón que EXTERNAL_DB_SCHEMA -- consumido por el modo "Editar como JSON"
// del formulario estructurado del inspector (ver NodeInspector.jsx). Fuente
// de verdad: WorkflowActionExecutor::callWebhook() (backend, Fase paralela).
export const WEBHOOK_SCHEMA = {
    keys: [
        { key: 'url', kind: 'string_token', hint: 'URL destino (soporta {{ }})' },
        { key: 'method', kind: 'enum', source: 'static:http_methods', hint: 'GET | POST | PUT | PATCH | DELETE' },
        { key: 'credential_id', kind: 'enum', source: 'local:webhook_credentials', hint: 'credencial de webhook (opcional)' },
        { key: 'webhook_id', kind: 'enum', source: 'local:registered_webhooks', hint: 'webhook registrado a usar' },
        {
            key: 'headers',
            kind: 'array_of_object',
            hint: 'encabezados HTTP adicionales',
            itemSchema: {
                keys: [
                    { key: 'key', kind: 'string', hint: 'nombre del header' },
                    { key: 'value', kind: 'string_token', hint: 'valor (soporta {{ }})' },
                ],
            },
        },
        {
            key: 'body',
            kind: 'array_of_object',
            hint: 'campos del cuerpo de la petición',
            itemSchema: {
                keys: [
                    { key: 'key', kind: 'string', hint: 'nombre del campo' },
                    { key: 'value', kind: 'string_token', hint: 'valor (soporta {{ }})' },
                ],
            },
        },
    ],
};
