<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

/**
 * Evalúa condiciones de branching de un WorkflowStep contra un modelo
 * (típicamente el `enrollable` de un WorkflowEnrollment, ej. Deal).
 *
 * Forma esperada de $condition:
 * ["field" => "status", "operator" => "equals", "value" => "open"]
 */
class WorkflowConditionEvaluator
{
    /**
     * @param Model $model      Modelo contra el que se evalúa la condición.
     * @param array $condition  ["field" => ..., "operator" => ..., "value" => ...]
     */
    public static function evaluate(Model $model, array $condition): bool
    {
        // Sin condición => se considera "default" / siempre verdadero.
        if (empty($condition)) {
            return true;
        }

        $field    = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? null;
        $expected = $condition['value'] ?? null;

        if (empty($field) || empty($operator)) {
            return true;
        }

        // data_get soporta dot-notation para uso futuro; hoy los campos son planos.
        $actual = data_get($model, $field);

        return match ($operator) {
            'equals'       => $actual == $expected,
            'not_equals'   => $actual != $expected,
            'greater_than' => is_numeric($actual) && is_numeric($expected) && $actual > $expected,
            'less_than'    => is_numeric($actual) && is_numeric($expected) && $actual < $expected,
            'contains'     => is_string($actual) && str_contains($actual, (string) $expected),
            default        => false,
        };
    }
}
