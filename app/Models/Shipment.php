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
        'peso' => 'decimal:2',
        'costo' => 'decimal:2',
    ];

    public function storeOrder(): BelongsTo
    {
        return $this->belongsTo(StoreOrder::class);
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
