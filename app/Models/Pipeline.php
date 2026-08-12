<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Pipeline de ventas (embudo) usado por el módulo de Deals/CRM.
 * Agrupa etapas ordenadas (PipelineStage) por las que transitan los Deals.
 */
class Pipeline extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function stages(): HasMany
    {
        return $this->hasMany(PipelineStage::class)->orderBy('order');
    }

    public function deals(): HasManyThrough
    {
        return $this->hasManyThrough(Deal::class, PipelineStage::class);
    }
}
