<?php

namespace App\Models;

use App\Support\UploadPath;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

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

    /**
     * FIX (SEO slugs): slug stores the FULL ancestor-joined path (e.g.
     * "tratamiento-de-agua/filtros-turbidex"), not just this category's own
     * segment. If a category's own slug changes, every descendant's stored
     * path is now stale (it still starts with the OLD prefix) — recompute
     * and re-save each one, recording a redirect for the URL it just lost.
     */
    protected static function booted(): void
    {
        static::saved(function (Category $category) {
            if ($category->wasChanged('slug')) {
                static::cascadeSlugToDescendants($category);
            }
        });
    }

    protected static function cascadeSlugToDescendants(Category $category): void
    {
        foreach ($category->children()->get() as $child) {
            $oldChildSlug = $child->slug;
            $childOwnSegment = \Illuminate\Support\Str::afterLast($oldChildSlug, '/');
            $newChildSlug = $category->slug . '/' . $childOwnSegment;

            if ($newChildSlug !== $oldChildSlug) {
                \App\Models\Redirect::record('/catalogo/' . $oldChildSlug, '/catalogo/' . $newChildSlug);
                $child->slug = $newChildSlug;
                $child->save(); // re-fires saved() above, cascading to grandchildren too
            }
        }
    }

    public function getImageUrlAttribute(?string $value): ?string
    {
        return UploadPath::url($value);
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
}
