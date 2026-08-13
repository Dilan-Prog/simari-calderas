<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Etapa dentro de un Pipeline (ej. "Contactado", "Propuesta enviada",
 * "Ganado"). `is_won`/`is_lost` marcan etapas terminales; `required_fields`
 * lista los campos del Deal que deben completarse antes de mover a esta
 * etapa.
 */
class PipelineStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'pipeline_id',
        'name',
        'slug',
        'order',
        'probability',
        'wip_limit',
        'is_won',
        'is_lost',
        'required_fields',
    ];

    protected $casts = [
        'is_won' => 'boolean',
        'is_lost' => 'boolean',
        'required_fields' => 'array',
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(\App\Models\Deal::class);
    }
}
