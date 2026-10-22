<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoringProgramItem extends Model
{
    public const KIND_PARAMETER = 'parameter'; // lectura del agua (pH, dureza, TDS...)
    public const KIND_CHEMICAL  = 'chemical';  // dosificación de un químico

    protected $fillable = [
        'program_id', 'kind', 'name', 'unit', 'limit_min', 'limit_max', 'product_id',
        'planned_daily_dose', 'start_week', 'end_week', 'added_by', 'sort_order',
    ];

    protected $casts = [
        'limit_min'          => 'float',
        'limit_max'          => 'float',
        'planned_daily_dose' => 'float',
        'start_week'         => 'integer',
        'end_week'           => 'integer',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(MonitoringProgram::class, 'program_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(MonitoringEntry::class, 'item_id');
    }

    public function isChemical(): bool
    {
        return $this->kind === self::KIND_CHEMICAL;
    }

    public function isParameter(): bool
    {
        return $this->kind === self::KIND_PARAMETER;
    }

    /** null = sin rango definido; true/false según el valor esté entre limit_min y limit_max. */
    public function evaluateRange(float $value): ?bool
    {
        if ($this->limit_min === null && $this->limit_max === null) {
            return null;
        }

        return ($this->limit_min === null || $value >= $this->limit_min)
            && ($this->limit_max === null || $value <= $this->limit_max);
    }
}
