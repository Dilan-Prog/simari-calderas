<?php

namespace App\Models;

use App\Support\UploadPath;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'logo_url', 'is_active',
        'seo_title', 'seo_description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getLogoUrlAttribute(?string $value): ?string
    {
        return UploadPath::url($value);
    }

    public function products()
    {
        return $this->hasMany(Products::class, 'brand_id');
    }
}
