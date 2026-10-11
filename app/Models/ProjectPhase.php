<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProjectPhase extends Model
{
    public const STATUSES = [
        'pendiente'  => 'Pendiente',
        'en_curso'   => 'En curso',
        'completada' => 'Completada',
    ];

    protected $fillable = [
        'project_id', 'name', 'description', 'requirements', 'sort_order',
        'status', 'progress', 'start_date', 'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'progress'   => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_phase_assignees', 'phase_id', 'user_id')->withTimestamps();
    }
}
