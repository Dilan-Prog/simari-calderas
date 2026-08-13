<?php

namespace App\Services;

use App\Models\WorkflowVariable;

/**
 * Primer sistema de tokens `{{ }}` real del motor de workflows (Fase 17).
 *
 * resolveTokens() reemplaza cada `{{ path }}` encontrado en un texto libre
 * (título/descripción de una tarea, valor de update_property, texto de
 * WhatsApp, etc.) por su valor resuelto:
 *
 *   - Si `path` contiene un punto ("customer.email"), se resuelve vía
 *     data_get($enrollable, $path) -- mismo mecanismo ya probado en
 *     WorkflowConditionEvaluator::evaluate() para condiciones de branching,
 *     ahora reutilizado también para texto libre. Soporta relaciones
 *     Eloquent anidadas (data_get atraviesa el __get() mágico del Model).
 *   - Si `path` NO contiene un punto ("first_name"), primero se intenta
 *     WorkflowVariable::resolveValue($path, $workflowId) (variable nombrada
 *     ya existente, con scope de workflow o global); si no existe ninguna
 *     variable con ese nombre, cae a data_get($enrollable, $path) como
 *     campo plano del enrollable.
 *
 * Un token que no resuelve a nada (ruta inexistente, variable inexistente)
 * se reemplaza por cadena vacía -- nunca deja el `{{ }}` literal en el
 * texto final ni lanza excepción, para que un JSON/texto de acción mal
 * configurado degrade limpio en vez de romper la ejecución del workflow.
 */
class WorkflowTokenResolver
{
    private const TOKEN_PATTERN = '/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/';

    public function resolveTokens(?string $text, $enrollable, ?int $workflowId = null): ?string
    {
        if ($text === null || $text === '' || !str_contains($text, '{{')) {
            return $text;
        }

        return preg_replace_callback(self::TOKEN_PATTERN, function (array $matches) use ($enrollable, $workflowId) {
            $path = $matches[1];

            $value = str_contains($path, '.')
                ? data_get($enrollable, $path)
                : $this->resolveFlatPath($path, $enrollable, $workflowId);

            return $this->stringify($value);
        }, $text);
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
