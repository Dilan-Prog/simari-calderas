<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadInboundEmail extends Model
{
    protected $fillable = [
        'message_id', 'lead_id', 'from_email', 'from_name', 'subject', 'excerpt',
        'received_at', 'task_id', 'is_new_lead',
    ];

    protected $casts = ['received_at' => 'datetime', 'is_new_lead' => 'boolean'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
