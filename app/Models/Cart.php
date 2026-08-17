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

    // unit_price_snapshot guarda el precio SIN IVA (ver CartController::add())
    // desde que el precio mostrado en tienda dejó de incluir impuesto — este
    // subtotal es, a propósito, el monto antes de IVA.
    public function subtotal(): float
    {
        return round($this->items->sum(fn (CartItem $item) => $item->quantity * $item->unit_price_snapshot), 2);
    }

    /**
     * IVA sobre el subtotal, a la tasa global vigente (Products::ivaRate()).
     * unit_price_snapshot ya normaliza cada línea a "sin IVA" al momento de
     * agregarla al carrito (independiente de cómo cada producto tenga
     * price_includes_tax) — así que basta aplicar una sola tasa plana sobre
     * el subtotal ya agregado, sin tener que revisar producto por producto.
     */
    public function taxTotal(): float
    {
        return round($this->subtotal() * (Products::ivaRate() / 100), 2);
    }

    /**
     * Convención: el envío se cobra UNA vez por línea de producto que tenga
     * shipping_cost > 0 (no se multiplica por quantity). Un producto con
     * shipping_cost nulo/0 = envío estándar gratis; con shipping_cost > 0,
     * ese cargo fijo aplica una sola vez sin importar cuántas unidades se
     * pidan de ese producto — EXCEPTO si el producto tiene
     * free_shipping_threshold y el subtotal del carrito (sin IVA, mismo
     * criterio que subtotal()) ya lo alcanza, en cuyo caso esa línea
     * tampoco cobra envío.
     */
    /**
     * Monto final del carrito (subtotal + IVA + envío) -- el total real que
     * pagaría el cliente, usado por el correo de recordatorio de carrito
     * abandonado (EmailTemplateService::render()).
     */
    public function total(): float
    {
        return round($this->subtotal() + $this->taxTotal() + $this->shippingTotal(), 2);
    }

    public function getHasEmailAttribute(): bool
    {
        return filled($this->customer?->email) || filled($this->contact_email);
    }

    public function shippingTotal(): float
    {
        $subtotal = $this->subtotal();

        return round(
            $this->items
                ->filter(function (CartItem $item) use ($subtotal) {
                    $product = $item->product;

                    if (!$product || !($product->shipping_cost > 0)) {
                        return false;
                    }

                    if ($product->free_shipping_threshold !== null && $subtotal >= $product->free_shipping_threshold) {
                        return false;
                    }

                    return true;
                })
                ->sum(fn (CartItem $item) => $item->product->shipping_cost),
            2
        );
    }
}
