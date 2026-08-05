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
        'seo_title', 'seo_description', 'faqs',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'faqs' => 'array',
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
     * IDs de esta categoría más sus subcategorías directas — mismo alcance
     * que usa CatalogController para /catalogo/{slug}, para que cualquier
     * otro lugar que filtre "productos de esta categoría" (p. ej. los
     * carruseles del Home) muestre exactamente los mismos productos.
     */
    public function idsWithChildren(): array
    {
        return $this->children()->pluck('id')->push($this->id)->all();
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

    /**
     * [nombrePrincipal, nombreSub, nombreHija] recorriendo $this->parent
     * hacia arriba — strings vacíos para los niveles que no aplican (p. ej.
     * una categoría de nivel 1 devuelve [nombre, '', '']). Requiere que
     * parent.parent ya venga precargado por quien llama (mismo patrón que
     * ProductController::bulkEditIndex()/categoryBreadcrumb()) para no
     * disparar queries nuevas aquí.
     */
    public function levelNames(): array
    {
        $chain = [$this->name];
        $node = $this;
        while ($node->parent) {
            $node = $node->parent;
            array_unshift($chain, $node->name);
        }

        return [
            $chain[0] ?? '',
            $chain[1] ?? '',
            $chain[2] ?? '',
        ];
    }

    public function products()
    {
        return $this->hasMany(Products::class, 'category_id');
    }
}
