<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Envío asociado 1:1 a un StoreOrder (módulo Envíos). `carrier_id` referencia
 * la tabla `carriers` (modelo App\Models\Delivery — nombre histórico del
 * modelo, la tabla real es "carriers").
 */
class Shipment extends Model
{
    use LogsActivity;

    protected static function logEntityType(): string
    {
        return 'shipment';
    }

    protected $fillable = [
        'store_order_id',
        'shippable_type',
        'shippable_id',
        'customer_id',
        'order_number_ref',
        'recipient_name',
        'recipient_company',
        'origin_address',
        'delivery_address',
        'recipient_phone',
        'recipient_email',
        'quote_id',
        'shipped_at',
        'carrier_id',
        'guia',
        'status',
        'fecha_envio',
        'eta',
        'bultos',
        'peso',
        'costo',
        'motivo',
        'incidencia_note',
        'authorized_by_user_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'fecha_envio' => 'date',
        'eta' => 'date',
        'shipped_at' => 'datetime',
        'peso' => 'decimal:2',
        'costo' => 'decimal:2',
    ];

    public function storeOrder(): BelongsTo
    {
        return $this->belongsTo(StoreOrder::class);
    }

    /** Envío B2B (no de tienda): cuelga de un pedido B2B o cotización. */
    public function isB2b(): bool
    {
        return $this->store_order_id === null;
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'shippable_id');
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function documentNumber(): ?string
    {
        return $this->storeOrder?->order_number ?? $this->order_number_ref;
    }

    public function recipientLabel(): string
    {
        if ($this->storeOrder) {
            return (string) $this->storeOrder->contact_name;
        }

        return trim(($this->recipient_name ?? '') . ($this->recipient_company ? ' · ' . $this->recipient_company : '')) ?: '—';
    }

    /** Detalle: la card de la orden para tienda, la show propia para B2B. */
    public function detailUrl(): string
    {
        return $this->store_order_id
            ? route('admin.orders.show', $this->store_order_id)
            : route('admin.shipments.show', $this->id);
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Delivery::class, 'carrier_id');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(ShipmentStatusLog::class)->orderByDesc('created_at');
    }

    public function authorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
