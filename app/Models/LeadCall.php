<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadCall extends Model
{
    protected $fillable = [
        'lead_id', 'direction', 'status', 'duration_seconds',
        'started_at', 'external_id', 'tracking_number', 'notes',
    ];

    protected $casts = [
        'started_at'       => 'datetime',
        'duration_seconds' => 'integer',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
