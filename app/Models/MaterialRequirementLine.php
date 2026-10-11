<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialRequirementLine extends Model
{
    protected $fillable = [
        'requirement_id', 'product_id', 'required_quantity', 'on_hand', 'reserved', 'own_reserved',
        'shortfall', 'unit', 'source', 'supplier_id', 'suggested_price', 'suggested_currency',
        'purchase_order_id', 'status', 'sort_order',
    ];

    protected $casts = [
        'required_quantity' => 'float',
        'on_hand'           => 'float',
        'reserved'          => 'float',
        'own_reserved'      => 'float',
        'shortfall'         => 'float',
        'suggested_price'   => 'float',
    ];

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(MaterialRequirement::class, 'requirement_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public static function sourceLabel(?string $s): string
    {
        return match ($s) {
            'componente_bom' => 'Componente BOM',
            'linea_pedido'   => 'Línea de pedido',
            'servicio'       => 'Servicio',
            'quimicos'       => 'Químicos',
            'manual'         => 'Manual',
            default          => (string) $s,
        };
    }

    /** Semáforo: ok = cubierto, warn = cubierto solo con compra, bad = faltante sin OC. */
    public function tone(): string
    {
        if ($this->shortfall <= 0.00005) {
            return 'ok';
        }

        return $this->purchase_order_id ? 'warn' : 'bad';
    }
}
