<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Opcion del catalogo de terminos y condiciones de cotizacion. */
class QuoteTerm extends Model
{
    public const APPLIES = ['product' => 'Productos', 'service' => 'Servicios', 'both' => 'Productos y servicios'];

    protected $fillable = ['title', 'body', 'applies_to', 'is_default', 'is_active', 'sort_order'];

    protected $casts = ['is_default' => 'boolean', 'is_active' => 'boolean', 'sort_order' => 'integer'];

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderBy('sort_order')->orderBy('id');
    }
}
