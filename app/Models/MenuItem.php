<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $fillable = [
        'menu_id', 'parent_id', 'title', 'url', 'target',
        'sort_order', 'is_active', 'linked_entity_type', 'linked_entity_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent()
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(MenuItem::class, 'parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    /**
     * URL real del ítem según su destino estructurado. `url` conserva doble
     * función según linked_entity_type: para custom_url guarda la URL cruda,
     * para static_page guarda el NOMBRE de ruta (no una URL) — para los
     * demás tipos queda null, `linked_entity_id` es la fuente de verdad.
     * Ítems legado sin linked_entity_type (sembrados antes de este cambio)
     * caen en el default y siguen usando `url` tal cual, sin romperse.
     */
    public function getResolvedUrlAttribute(): string
    {
        return match ($this->linked_entity_type) {
            'category' => ($c = Category::find($this->linked_entity_id))
                ? route('catalog.category', $c->slug) : '#',
            'collection' => ($c = Collection::find($this->linked_entity_id))
                ? route('collection.show', $c->slug) : '#',
            'brand' => $this->linked_entity_id
                ? route('catalog.index', ['marca' => [$this->linked_entity_id]]) : '#',
            'product' => ($p = Products::find($this->linked_entity_id))
                ? route('product.show', $p->slug) : '#',
            'static_page' => $this->url ? route($this->url) : '#',
            default => $this->url ?: '#',
        };
    }
}
