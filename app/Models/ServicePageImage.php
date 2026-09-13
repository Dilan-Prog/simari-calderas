<?php

namespace App\Models;

use App\Support\UploadPath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePageImage extends Model
{
    const UPDATED_AT = null;

    protected $table = 'service_page_images';

    protected $fillable = ['service_page_id', 'image_url', 'alt_text', 'sort_order'];

    public function servicePage(): BelongsTo
    {
        return $this->belongsTo(ServicePage::class);
    }

    public function getUrlAttribute(): string
    {
        if (str_starts_with($this->image_url, 'http')) {
            return $this->image_url;
        }

        return UploadPath::url($this->image_url);
    }
}
