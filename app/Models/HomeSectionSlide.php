<?php

namespace App\Models;

use App\Support\UploadPath;
use Illuminate\Database\Eloquent\Model;

class HomeSectionSlide extends Model
{
    protected $fillable = [
        'home_section_id', 'image_url', 'badge_text', 'title',
        'title_highlight', 'description', 'link_url', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function homeSection()
    {
        return $this->belongsTo(HomeSection::class);
    }

    public function getImageUrlAttribute($value): string
    {
        if (str_starts_with($value, 'http')) {
            return $value;
        }

        return UploadPath::url($value);
    }
}
