<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'taskable_type',
        'taskable_id',
        'assigned_to',
        'title',
        'description',
        'due_at',
        'status',
        'created_by_workflow_id',
    ];

    protected $casts = [
        'due_at' => 'datetime',
    ];

    public function taskable(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdByWorkflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'created_by_workflow_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }
}
