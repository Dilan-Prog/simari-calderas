<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceLog extends Model
{
    const UPDATED_AT = null;

    protected $table = 'service_logs';

    protected $fillable = [
        'service_id',
        'action',
        'comment',
        'performed_by_user_id',
    ];

    // ── Relations ──────────────────────────────────────────────────────────────

    public function service(): BelongsTo
    {
        return $this->belongsTo(TechnicalService::class, 'service_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }
}
