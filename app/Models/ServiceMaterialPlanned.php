<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceMaterialPlanned extends Model
{
    protected $table = 'service_materials_planned';

    protected $fillable = [
        'service_id',
        'product_id',
        'product_name',
        'product_sku',
        'quantity',
        'unit',
        'notes',
        'sort_order',
    ];

    protected $casts = ['quantity' => 'integer'];

    // ── Relations ──────────────────────────────────────────────────────────────

    public function service(): BelongsTo
    {
        return $this->belongsTo(TechnicalService::class, 'service_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withDefault();
    }

    // ── Accessors ──────────────────────────────────────────────────────────────

    public function getUnitLabelAttribute(): string
    {
        return match($this->unit) {
            'litros'  => 'Litros',
            'kg'      => 'Kilogramos',
            'piezas'  => 'Piezas',
            'metros'  => 'Metros',
            'galones' => 'Galones',
            'otro'    => 'Otro',
            default   => $this->unit ?? '—',
        };
    }
}
