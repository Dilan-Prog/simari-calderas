<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImageDuplicateScan extends Model
{
    protected $table = 'image_duplicate_scans';

    protected $fillable = [
        'status',
        'images_scanned',
        'groups_found',
        'error_message',
        'triggered_by',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(ImageDuplicateGroup::class, 'scan_id');
    }
}
