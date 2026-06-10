<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    const UPDATED_AT = null;

    protected $table = 'product_images';

    protected $fillable = ['product_id', 'image_url', 'alt_text', 'sort_order'];

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function getUrlAttribute(): string
    {
        if (str_starts_with($this->image_url, 'http')) {
            return $this->image_url;
        }

        return asset($this->image_url);
    }
}
