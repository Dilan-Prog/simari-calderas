<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bitácora de cambios de estatus de un Shipment. Solo created_at (la
 * columna updated_at no existe en la tabla — un log es un evento inmutable).
 */
class ShipmentStatusLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'shipment_id',
        'from_status',
        'to_status',
        'note',
        'motivo',
        'changed_by_user_id',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
