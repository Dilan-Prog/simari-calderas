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
     * @param Model|array $model  Modelo (o array plano) contra el que se evalúa la condición.
     * @param array $condition    ["field" => ..., "operator" => ..., "value" => ...]
     */
    public static function evaluate(Model|array $model, array $condition): bool
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

        return self::matchesRule($model, $field, $operator, $expected);
    }

    /**
     * Evalúa un step 'switch': recorre $switchConfig['rules'] EN ORDEN y
     * retorna el branch_key de la primera regla que matchea. Si ninguna
     * matchea (o no hay reglas), retorna la cadena literal 'default'.
     *
     * Forma esperada de $switchConfig:
     * ["rules" => [
     *     ["branch_key" => "case_0", "field" => "status", "operator" => "equals", "value" => "accepted"],
     *     ...
     * ]]
     *
     * @param Model|array $model
     */
    public static function evaluateSwitch(Model|array $model, array $switchConfig): string
    {
        $rules = $switchConfig['rules'] ?? [];

        foreach ($rules as $rule) {
            $branchKey = $rule['branch_key'] ?? null;
            $field     = $rule['field'] ?? null;
            $operator  = $rule['operator'] ?? null;
            $expected  = $rule['value'] ?? null;

            // Regla incompleta (sin branch_key, field u operator) -> no matchea.
            if (empty($branchKey) || empty($field) || empty($operator)) {
                continue;
            }

            if (self::matchesRule($model, $field, $operator, $expected)) {
                return $branchKey;
            }
        }

        return 'default';
    }

    /**
     * Lógica de operadores compartida entre evaluate() y evaluateSwitch().
     *
     * @param Model|array $model
     */
    private static function matchesRule(Model|array $model, string $field, string $operator, mixed $expected): bool
    {
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
