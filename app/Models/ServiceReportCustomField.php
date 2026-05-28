<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceReportCustomField extends Model
{
    protected $fillable = [
        'service_report_id',
        'field_name',
        'field_type',
        'field_value',
        'sort_order',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(ServiceReport::class, 'service_report_id');
    }
}
