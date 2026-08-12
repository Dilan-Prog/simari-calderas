<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'enrollment_trigger',
        'is_active',
        'reenrollment_allowed',
        'is_template',
        'created_by',
    ];

    protected $casts = [
        'enrollment_trigger'    => 'array',
        'is_active'             => 'boolean',
        'reenrollment_allowed'  => 'boolean',
        'is_template'           => 'boolean',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class)->whereNull('parent_step_id')->orderBy('order');
    }

    public function allSteps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(WorkflowEnrollment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
