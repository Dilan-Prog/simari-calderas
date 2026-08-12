<?php

namespace App\Models;

use App\Services\WorkflowConditionEvaluator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class EmailList extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'filter_definition',
    ];

    protected $casts = [
        'filter_definition' => 'array',
    ];

    public function members()
    {
        return $this->belongsToMany(Customer::class, 'email_list_members', 'list_id', 'customer_id')
            ->withPivot('added_at')
            ->withTimestamps();
    }

    /**
     * Resuelve los destinatarios de la lista.
     *
     * - type = 'static': los miembros explícitos (tabla pivote).
     * - type = 'active': (v1 simple) Customer::query() filtrado con las
     *   condiciones de filter_definition, forma:
     *   [{"field":"status","operator":"equals","value":"active"}, ...]
     *   Usa WorkflowConditionEvaluator si existe; si no, aplica where()
     *   directo con un mapeo simple de operadores.
     */
    public function resolveRecipients(): Collection
    {
        if ($this->type === 'static') {
            return $this->members;
        }

        if ($this->type === 'active') {
            $conditions = $this->filter_definition;

            if (empty($conditions)) {
                return collect();
            }

            if (class_exists(WorkflowConditionEvaluator::class)) {
                return Customer::all()->filter(function (Customer $customer) use ($conditions) {
                    foreach ($conditions as $condition) {
                        if (!WorkflowConditionEvaluator::evaluate($customer, $condition)) {
                            return false;
                        }
                    }
                    return true;
                })->values();
            }

            $query = Customer::query();

            foreach ($conditions as $condition) {
                $field    = $condition['field'] ?? null;
                $operator = $condition['operator'] ?? null;
                $value    = $condition['value'] ?? null;

                if (empty($field) || empty($operator)) {
                    continue;
                }

                match ($operator) {
                    'equals'       => $query->where($field, '=', $value),
                    'not_equals'   => $query->where($field, '!=', $value),
                    'greater_than' => $query->where($field, '>', $value),
                    'less_than'    => $query->where($field, '<', $value),
                    'contains'     => $query->where($field, 'like', '%' . $value . '%'),
                    default        => null,
                };
            }

            return $query->get();
        }

        return collect();
    }
}
