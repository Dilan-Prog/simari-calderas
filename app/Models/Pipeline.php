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

    /** Pipeline "clásico" de Negocios (Deals) — comportamiento por default. */
    public const CHANNEL_DEALS = 'deals';

    /** Embudo de Venta de conversaciones de WhatsApp (Fase 12). */
    public const CHANNEL_WHATSAPP = 'whatsapp';

    protected $fillable = [
        'name',
        'channel',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Pipelines de tipo Negocios (Deals).
     *
     * Nota: por la colisión de nombre con la relación de instancia deals()
     * (HasManyThrough) de más abajo, este scope solo se puede invocar
     * encadenado sobre el query builder (Pipeline::query()->deals() o
     * Pipeline::where(...)->deals()) — Pipeline::deals() a secas resuelve
     * a la relación, no al scope, porque PHP encuentra el método de
     * instancia existente antes de intentar __callStatic.
     */
    public function scopeDeals($query)
    {
        return $query->where('channel', self::CHANNEL_DEALS);
    }

    /** Pipelines de tipo Embudo de Venta (WhatsApp). */
    public function scopeWhatsapp($query)
    {
        return $query->where('channel', self::CHANNEL_WHATSAPP);
    }

    public function stages(): HasMany
    {
        return $this->hasMany(PipelineStage::class)->orderBy('order');
    }

    public function deals(): HasManyThrough
    {
        return $this->hasManyThrough(Deal::class, PipelineStage::class);
    }
}
