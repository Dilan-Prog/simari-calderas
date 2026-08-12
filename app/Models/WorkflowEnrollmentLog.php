<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowEnrollmentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'step_id',
        'action_taken',
        'result',
        'message',
        'logged_at',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
    ];

    public function enrollment()
    {
        return $this->belongsTo(WorkflowEnrollment::class, 'enrollment_id');
    }

    public function step()
    {
        return $this->belongsTo(WorkflowStep::class, 'step_id');
    }
}
