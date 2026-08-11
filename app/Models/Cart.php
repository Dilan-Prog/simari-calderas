<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $fillable = [
        'session_id',
        'customer_id',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function subtotal(): float
    {
        return round($this->items->sum(fn (CartItem $item) => $item->quantity * $item->unit_price_snapshot), 2);
    }

    /**
     * Convención: el envío se cobra UNA vez por línea de producto que tenga
     * shipping_cost > 0 (no se multiplica por quantity). Un producto con
     * shipping_cost nulo/0 = envío estándar gratis; con shipping_cost > 0,
     * ese cargo fijo aplica una sola vez sin importar cuántas unidades se
     * pidan de ese producto.
     */
    public function shippingTotal(): float
    {
        return round(
            $this->items
                ->filter(fn (CartItem $item) => $item->product && $item->product->shipping_cost > 0)
                ->sum(fn (CartItem $item) => $item->product->shipping_cost),
            2
        );
    }
}
