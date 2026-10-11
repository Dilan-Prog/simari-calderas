<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectPhaseAssignee extends Model
{
    protected $fillable = ['phase_id', 'user_id'];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ProjectPhase::class, 'phase_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
