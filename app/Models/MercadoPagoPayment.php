<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un "slot de cobro" (1 o 2 por StoreOrder cuando el carrito mezcla
 * productos con/sin MSI) contra Mercado Pago. La fila se actualiza in-place
 * en cada reintento (mismo mp_payment_id se reemplaza por uno nuevo); el
 * historial completo de cambios de estatus vive en MercadoPagoPaymentStatusLog.
 */
class MercadoPagoPayment extends Model
{
    protected $fillable = [
        'store_order_id',
        'charge_group',
        'includes_msi',
        'store_order_item_ids',
        'amount',
        'currency',
        'installments',
        'mp_payment_id',
        'mp_preference_id',
        'mp_payment_method',
        'mp_payment_type',
        'status',
        'status_detail',
        'idempotency_key',
        'attempts',
        'paid_at',
        'raw_last_response',
    ];

    protected $casts = [
        'includes_msi'         => 'boolean',
        'store_order_item_ids' => 'array',
        'raw_last_response'    => 'array',
        'amount'               => 'decimal:2',
        'paid_at'              => 'datetime',
    ];

    public function storeOrder(): BelongsTo
    {
        return $this->belongsTo(StoreOrder::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(MercadoPagoPaymentStatusLog::class)->orderByDesc('created_at');
    }

    // Resuelve store_order_item_ids contra los items ya cargados del pedido,
    // sin duplicar una query — evita N+1 si el caller ya hizo with('items').
    public function getItemsAttribute()
    {
        $ids = $this->store_order_item_ids ?? [];

        return $this->storeOrder->items->whereIn('id', $ids);
    }
}
