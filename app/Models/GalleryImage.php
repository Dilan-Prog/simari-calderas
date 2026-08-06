<?php

namespace App\Models;

use App\Support\UploadPath;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class GalleryImage extends Model
{
    use LogsActivity;

    protected static function logEntityType(): string
    {
        return 'gallery_image';
    }

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
