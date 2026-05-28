<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceReportActivity extends Model
{
    protected $fillable = [
        'service_report_id',
        'content',
        'systems_checked',
    ];

    protected $casts = [
        'systems_checked' => 'array',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(ServiceReport::class, 'service_report_id');
    }
}
