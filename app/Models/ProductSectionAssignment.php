<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Une un producto con un bloque (HomeSection de page 'product_template' o
 * 'product_custom'). Una plantilla usada por N productos tiene N filas
 * apuntando al mismo home_section_id -- eso es lo que la mantiene "ligada".
 */
class ProductSectionAssignment extends Model
{
    protected $fillable = ['product_id', 'home_section_id', 'sort_order', 'is_visible'];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function section()
    {
        return $this->belongsTo(HomeSection::class, 'home_section_id');
    }
}
