<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use App\Models\WorkflowEnrollment;
use App\Models\WorkflowVariable;

/**
 * Primer sistema de tokens `{{ }}` real del motor de workflows (Fase 17).
 *
 * resolveTokens() reemplaza cada `{{ path }}` encontrado en un texto libre
 * (título/descripción de una tarea, valor de update_property, texto de
 * WhatsApp, etc.) por su valor resuelto. Fase 24 amplió la firma para
 * recibir el WorkflowEnrollment completo (en vez de $enrollable/$workflowId
 * sueltos) porque los prefijos reservados nuevos (`_previous.*`, `actor.*`)
 * necesitan leer WorkflowEnrollment::trigger_context.
 *
 * Orden de resolución para cada `{{ path }}`:
 *   1. Prefijos reservados (Fase 24), evaluados ANTES que cualquier otra
 *      cosa -- si el path coincide con uno de estos, nunca cae al
 *      data_get()/WorkflowVariable de siempre, sin importar si el
 *      enrollable también tuviera un campo/relación con ese mismo nombre:
 *        - `hoy`            -> now()->toDateString() (resuelto EN VIVO, al
 *                              momento de EJECUTAR la acción, no al inscribir).
 *        - `ahora`          -> now()->toDateTimeString() (idem, en vivo).
 *        - `empresa.nombre` -> config('app.name').
 *        - `settings.<key>` -> Setting::get('<key>') -- <key> es el resto
 *                              del path TAL CUAL después del prefijo, sin
 *                              volver a partir por punto (puede contener
 *                              puntos, ej. `settings.ecommerce.iva_rate`).
 *        - `_previous.<campo>` -> $enrollment->trigger_context['previous']['<campo>']
 *                              ?? null -- el valor ANTERIOR al cambio que
 *                              originó la inscripción, capturado en el
 *                              momento del evento por
 *                              WorkflowTriggerService::buildTriggerContext().
 *        - `actor.nombre` / `actor.email` -> datos del usuario autenticado
 *                              en el momento del evento que disparó la
 *                              inscripción ($enrollment->trigger_context['actor_user_id']),
 *                              null seguro si no había usuario autenticado
 *                              (jobs en cola, comandos, otro workflow).
 *   2. Si `path` contiene un punto ("customer.email"), se resuelve vía
 *      data_get($enrollable, $path) -- mismo mecanismo ya probado en
 *      WorkflowConditionEvaluator::evaluate() para condiciones de branching,
 *      ahora reutilizado también para texto libre. Soporta relaciones
 *      Eloquent anidadas (data_get atraviesa el __get() mágico del Model).
 *   3. Si `path` NO contiene un punto ("first_name"), primero se intenta
 *      WorkflowVariable::resolveValue($path, $workflowId) (variable nombrada
 *      ya existente, con scope de workflow o global); si no existe ninguna
 *      variable con ese nombre, cae a data_get($enrollable, $path) como
 *      campo plano del enrollable.
 *
 * Un token que no resuelve a nada (ruta inexistente, variable inexistente)
 * se reemplaza por cadena vacía -- nunca deja el `{{ }}` literal en el
 * texto final ni lanza excepción, para que un JSON/texto de acción mal
 * configurado degrade limpio en vez de romper la ejecución del workflow.
 */
class WorkflowTokenResolver
{
    private const TOKEN_PATTERN = '/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/';

    public function resolveTokens(?string $text, WorkflowEnrollment $enrollment): ?string
    {
        if ($text === null || $text === '' || !str_contains($text, '{{')) {
            return $text;
        }

        $enrollable = $enrollment->enrollable;
        $workflowId = $enrollment->workflow_id;

        return preg_replace_callback(self::TOKEN_PATTERN, function (array $matches) use ($enrollment, $enrollable, $workflowId) {
            $path = $matches[1];

            $value = $this->resolveReservedPrefix($path, $enrollment);

            if ($value === self::NOT_RESERVED) {
                $value = str_contains($path, '.')
                    ? data_get($enrollable, $path)
                    : $this->resolveFlatPath($path, $enrollable, $workflowId);
            }

            return $this->stringify($value);
        }, $text);
    }

    /** Sentinela interno para distinguir "el prefijo reservado resolvió a null" de "no es un prefijo reservado". */
    private const NOT_RESERVED = "\0__not_reserved__\0";

    private function resolveReservedPrefix(string $path, WorkflowEnrollment $enrollment)
    {
        if ($path === 'hoy') {
            return now()->toDateString();
        }

        if ($path === 'ahora') {
            return now()->toDateTimeString();
        }

        if ($path === 'empresa.nombre') {
            return config('app.name');
        }

        if (str_starts_with($path, 'settings.')) {
            $key = substr($path, strlen('settings.'));

            return Setting::get($key);
        }

        if (str_starts_with($path, '_previous.')) {
            $field = substr($path, strlen('_previous.'));

            return $enrollment->trigger_context['previous'][$field] ?? null;
        }

        if ($path === 'actor.nombre' || $path === 'actor.email') {
            $actorId = $enrollment->trigger_context['actor_user_id'] ?? null;
            $actor = $actorId ? User::find($actorId) : null;

            if (!$actor) {
                return null;
            }

            return $path === 'actor.nombre' ? $actor->full_name : $actor->email;
        }

        return self::NOT_RESERVED;
    }

    private function resolveFlatPath(string $path, $enrollable, ?int $workflowId)
    {
        $value = WorkflowVariable::resolveValue($path, $workflowId);

        if ($value !== null) {
            return $value;
        }

        return data_get($enrollable, $path);
    }

    private function stringify($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return (string) json_encode($value);
    }
}
