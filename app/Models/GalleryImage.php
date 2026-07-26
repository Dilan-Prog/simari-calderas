<?php

namespace App\Models;

use App\Support\UploadPath;
use Illuminate\Database\Eloquent\Model;

class GalleryImage extends Model
{
    protected $fillable = ['path', 'original_name', 'uploaded_by'];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        if (str_starts_with($this->path, 'http')) {
            return $this->path;
        }

        return UploadPath::url($this->path);
    }
}
