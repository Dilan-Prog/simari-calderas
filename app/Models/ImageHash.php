<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImageHash extends Model
{
    protected $table = 'image_hashes';

    protected $fillable = ['image_url', 'phash', 'file_size', 'file_mtime'];

    protected $casts = [
        'file_size'  => 'integer',
        'file_mtime' => 'integer',
    ];
}
