<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Parámetro del análisis químico del agua (dureza, pH, alcalinidad, conductividad, STD).
 * min_value / max_value los fija el personal; el estado de cada resultado se evalúa
 * siempre contra los límites VIGENTES (no hay snapshot), así el historial se reevalúa solo.
 */
class WaterAnalysisParameter extends Model
{
    public const STATUS_OK = 'ok';
    public const STATUS_LOW = 'bajo';
    public const STATUS_HIGH = 'alto';
    public const STATUS_NONE = 'sin_rango';

    protected $fillable = [
        'code', 'name', 'unit', 'decimals', 'min_value', 'max_value', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'decimals'   => 'integer',
        'min_value'  => 'float',
        'max_value'  => 'float',
        'sort_order' => 'integer',
        'is_active'  => 'boolean',
    ];

    public function results()
    {
        return $this->hasMany(WaterAnalysisResult::class, 'parameter_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function hasRange(): bool
    {
        return $this->min_value !== null || $this->max_value !== null;
    }

    /** ok / bajo / alto contra los límites vigentes; sin_rango si no hay límites; null si no hay valor. */
    public function statusFor(float|int|string|null $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! $this->hasRange()) {
            return self::STATUS_NONE;
        }

        $value = (float) $value;
        if ($this->min_value !== null && $value < $this->min_value) {
            return self::STATUS_LOW;
        }
        if ($this->max_value !== null && $value > $this->max_value) {
            return self::STATUS_HIGH;
        }

        return self::STATUS_OK;
    }

    /** Número con los decimales del parámetro (para mostrar). */
    public function formatValue(float|int|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return number_format((float) $value, (int) $this->decimals, '.', ',');
    }
}
