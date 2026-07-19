<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeSection extends Model
{
    protected $fillable = ['type', 'title', 'config', 'sort_order', 'is_active'];

    protected $casts = [
        'config'    => 'array',
        'is_active' => 'boolean',
    ];

    public function slides()
    {
        return $this->hasMany(HomeSectionSlide::class)->orderBy('sort_order');
    }
}
