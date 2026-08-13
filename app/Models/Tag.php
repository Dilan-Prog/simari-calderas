<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Etiqueta reutilizable (Fase 14 del plan CRM). Acotada a Deals por ahora
 * vía la tabla pivote deal_tag, pero el modelo en sí no depende de Deal.
 */
class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
    ];

    public function deals(): BelongsToMany
    {
        return $this->belongsToMany(Deal::class, 'deal_tag')->withTimestamps();
    }
}
