<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'enrollable_type',
        'enrollable_id',
        'trigger_context',
        'current_step_id',
        'status',
        'resume_at',
        'enrolled_at',
        'completed_at',
    ];

    protected $casts = [
        'trigger_context' => 'array',
        'resume_at'        => 'datetime',
        'enrolled_at'      => 'datetime',
        'completed_at'     => 'datetime',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    public function currentStep()
    {
        return $this->belongsTo(WorkflowStep::class, 'current_step_id');
    }

    public function logs()
    {
        return $this->hasMany(WorkflowEnrollmentLog::class, 'enrollment_id');
    }

    public function enrollable()
    {
        return $this->morphTo();
    }
}
