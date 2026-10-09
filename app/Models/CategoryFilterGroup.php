<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Grupo de filtro técnico de una categoría (ej. "Tamaño DIN"). Aplica a la
 * categoría y a sus descendientes; sus opciones apuntan a etiquetas de
 * producto (products.tags). Ver App\Services\Catalog.
 */
class CategoryFilterGroup extends Model
{
    protected $fillable = ['category_id', 'name', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function options()
    {
        return $this->hasMany(CategoryFilterOption::class, 'group_id')->orderBy('sort_order')->orderBy('id');
    }
}
