<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImageDuplicateGroup extends Model
{
    protected $table = 'image_duplicate_groups';

    protected $fillable = [
        'scan_id',
        'representative_phash',
        'image_count',
        'status',
    ];

    public function scan(): BelongsTo
    {
        return $this->belongsTo(ImageDuplicateScan::class, 'scan_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ImageDuplicateGroupImage::class, 'group_id');
    }
}
