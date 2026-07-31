<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImageDuplicateGroupImage extends Model
{
    protected $table = 'image_duplicate_group_images';

    protected $fillable = [
        'group_id',
        'image_url',
        'phash',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ImageDuplicateGroup::class, 'group_id');
    }
}
