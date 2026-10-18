<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Variable de la encuesta de satisfacción (Limpieza, Ordenado, Resolvió mi problema…), configurable desde el panel. */
class SurveyQuestion extends Model
{
    public const KIND_POSITIVE = 'positive';
    public const KIND_NEGATIVE = 'negative';
    public const KINDS = [self::KIND_POSITIVE => 'Lo mejor', self::KIND_NEGATIVE => 'A mejorar'];

    protected $fillable = ['label', 'kind', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];

    public function scopeActive($q)
    {
        return $q->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }
}
