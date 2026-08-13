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
        'last_activity_at',
        'checkout_started_at',
        'contact_name',
        'contact_email',
        'contact_phone',
        'converted_to_store_order_id',
        'dismissed_at',
    ];

    // Sin estos casts, last_activity_at/checkout_started_at llegan como string
    // desde la BD y ->diffForHumans() en la vista de Carritos Abandonados falla.
    protected $casts = [
        'last_activity_at' => 'datetime',
        'checkout_started_at' => 'datetime',
        'dismissed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function convertedToStoreOrder(): BelongsTo
    {
        return $this->belongsTo(StoreOrder::class, 'converted_to_store_order_id');
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
