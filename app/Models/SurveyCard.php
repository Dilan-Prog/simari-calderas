<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Tarjeta de calificación de "Califica este servicio" (1 a 5 estrellas), configurable desde el panel. */
class SurveyCard extends Model
{
    protected $fillable = ['label', 'stars', 'color', 'is_active', 'sort_order'];

    protected $casts = ['stars' => 'integer', 'is_active' => 'boolean', 'sort_order' => 'integer'];

    public function scopeActive($q)
    {
        return $q->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }
}
