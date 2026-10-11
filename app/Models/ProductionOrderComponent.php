<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderComponent extends Model
{
    protected $fillable = [
        'production_order_id', 'component_product_id', 'required_quantity',
        'reserved_quantity', 'consumed_quantity', 'unit', 'unit_cost',
    ];

    protected $casts = [
        'required_quantity' => 'float',
        'reserved_quantity' => 'float',
        'consumed_quantity' => 'float',
        'unit_cost'         => 'float',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'component_product_id');
    }

    public function pending(): float
    {
        return max(0.0, $this->required_quantity - $this->consumed_quantity);
    }

    /** Faltante respecto de lo requerido que aún no está reservado ni consumido. */
    public function shortfall(): float
    {
        return max(0.0, round($this->required_quantity - $this->consumed_quantity - $this->reserved_quantity, 4));
    }
}
