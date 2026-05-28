<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceReportMeasurement extends Model
{
    protected $fillable = [
        'service_report_id',
        'parameter',
        'unit',
        'result',
        'limit_min',
        'limit_max',
        'in_range',
        'sort_order',
    ];

    protected $casts = [
        'limit_min' => 'float',
        'limit_max' => 'float',
        'in_range'  => 'boolean',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(ServiceReport::class, 'service_report_id');
    }
}
