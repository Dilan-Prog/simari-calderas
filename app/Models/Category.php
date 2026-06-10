<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
<<<<<<< HEAD
=======

    protected $table = 'product_categories';

    protected $fillable = [
        'parent_id', 'name', 'slug', 'description',
        'image_url', 'is_active', 'sort_order',
        'seo_title', 'seo_description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Self-referencing relationships
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // Level helper
    public function getLevelAttribute(): int
    {
        if (!$this->parent_id) return 1;
        if ($this->parent && !$this->parent->parent_id) return 2;
        return 3;
    }

    public function products()
    {
        return $this->hasMany(Products::class, 'category_id');
    }
>>>>>>> 9f21f7d4ddd7b772e9904ef29e5899116acf3b89
}
