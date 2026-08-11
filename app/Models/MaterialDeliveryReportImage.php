<?php

namespace App\Models;

use App\Support\UploadPath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialDeliveryReportImage extends Model
{
    protected $fillable = ['material_delivery_report_id', 'path', 'caption', 'sort_order'];

    public function report(): BelongsTo
    {
        return $this->belongsTo(MaterialDeliveryReport::class, 'material_delivery_report_id');
    }

    public function getUrlAttribute(): string
    {
        return UploadPath::url($this->path);
    }
}
