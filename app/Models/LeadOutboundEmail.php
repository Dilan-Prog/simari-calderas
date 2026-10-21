<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadOutboundEmail extends Model
{
    protected $fillable = [
        'message_id', 'lead_id', 'task_id', 'from_email', 'to_email', 'recipients',
        'subject', 'body', 'attachments', 'sent_at',
    ];

    protected $casts = ['recipients' => 'array', 'attachments' => 'array', 'sent_at' => 'datetime'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
