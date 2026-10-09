<?php

namespace App\Models;

use App\Services\Catalog\TagNormalizer;
use Illuminate\Database\Eloquent\Model;

/**
 * Opción de un grupo de filtro técnico: `label` es lo que ve el cliente y
 * `tag` la etiqueta de producto que debe tener un producto para coincidir
 * (se compara por `tag_normalized`, que se mantiene sola al guardar).
 */
class CategoryFilterOption extends Model
{
    protected $fillable = ['group_id', 'label', 'tag', 'tag_normalized', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $option) {
            $option->tag_normalized = TagNormalizer::normalize((string) $option->tag);
        });
    }

    public function group()
    {
        return $this->belongsTo(CategoryFilterGroup::class, 'group_id');
    }
}
