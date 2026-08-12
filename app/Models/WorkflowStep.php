<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'parent_step_id',
        'branch_condition',
        'order',
        'step_type',
        'action_type',
        'action_config',
        'branch_key',
        'position_x',
        'position_y',
    ];

    protected $casts = [
        'branch_condition' => 'array',
        'action_config'    => 'array',
    ];

    public const BRANCH_YES = 'yes';
    public const BRANCH_NO = 'no';

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'parent_step_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(WorkflowStep::class, 'parent_step_id')->orderBy('order');
    }
}
