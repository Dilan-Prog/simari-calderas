<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowVariable extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'name',
        'type',
        'value',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('workflow_id');
    }

    public static function resolveValue(string $name, ?int $workflowId = null)
    {
        if ($workflowId !== null) {
            $variable = static::where('workflow_id', $workflowId)
                ->where('name', $name)
                ->first();

            if ($variable) {
                return $variable->value;
            }
        }

        $global = static::global()
            ->where('name', $name)
            ->first();

        return $global ? $global->value : null;
    }
}
