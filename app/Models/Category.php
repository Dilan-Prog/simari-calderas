<?php

namespace App\Models;

use App\Support\UploadPath;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory, LogsActivity;

    protected static function logEntityType(): string
    {
        return 'category';
    }

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

    /**
     * Grupos de filtro técnico definidos EN esta categoría (los de sus
     * ancestros se heredan: ver App\Services\Catalog\CatalogIndex).
     */
    public function filterGroups()
    {
        return $this->hasMany(CategoryFilterGroup::class, 'category_id')->orderBy('sort_order')->orderBy('id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * IDs de esta categoría más TODOS sus descendientes (hijos, nietos, etc.)
     * — mismo alcance que usa CatalogController para /catalogo/{slug}, para
     * que cualquier otro lugar que filtre "productos de esta categoría"
     * (p. ej. los carruseles del Home) muestre exactamente los mismos
     * productos. Recursivo: antes solo bajaba un nivel, lo que rompía el
     * filtro de checkboxes del catálogo para subcategorías anidadas a 2+
     * niveles (p. ej. "Carbón activado" bajo "Filtros" bajo "Tratamiento de
     * Agua") — el checkbox devolvía category_id fuera del alcance de esta
     * función y la intersección con el filtro quedaba vacía.
     */
    public function idsWithChildren(): array
    {
        $ids = collect([$this->id]);

        foreach ($this->children as $child) {
            $ids = $ids->merge($child->idsWithChildren());
        }

        return $ids->all();
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

    /** Bloques del editor en vivo de esta categoría (todos, activos o no). */
    public function sections()
    {
        return $this->hasMany(CategorySection::class, 'category_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Bloques que aplican a esta categoría, con herencia:
     *  - Si la categoría tiene CUALQUIER bloque propio (activo o no), usa los
     *    suyos (tener solo bloques inactivos cuenta como "personalizada").
     *  - Si no, sube por `parent` (máx. 3 niveles) hasta el ancestro más
     *    cercano que tenga bloques y usa los de ese ancestro.
     *
     * $onlyActive = true (uso público) devuelve solo los activos; false (editor)
     * devuelve todos. `inherited_from` es el ancestro del que se heredó, o null
     * si son propios o no hay ninguno.
     *
     * @return array{sections: \Illuminate\Support\Collection<int, CategorySection>, inherited_from: ?Category}
     */
    public function effectiveSections(bool $onlyActive = true): array
    {
        $filter = fn ($sections) => $onlyActive
            ? $sections->filter(fn (CategorySection $s) => $s->is_active)->values()
            : $sections->values();

        $own = $this->relationLoaded('sections') ? $this->sections : $this->sections()->get();
        if ($own->isNotEmpty()) {
            return ['sections' => $filter($own), 'inherited_from' => null];
        }

        $node = $this;
        for ($depth = 0; $depth < 3 && $node->parent_id; $depth++) {
            $node = $node->parent;
            if (!$node) {
                break;
            }
            $sections = $node->sections()->get();
            if ($sections->isNotEmpty()) {
                return ['sections' => $filter($sections), 'inherited_from' => $node];
            }
        }

        return ['sections' => collect(), 'inherited_from' => null];
    }
}
