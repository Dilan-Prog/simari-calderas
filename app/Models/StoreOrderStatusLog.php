<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bitácora de cambios de estatus de un StoreOrder. Solo created_at (la
 * columna updated_at no existe en la tabla — un log es un evento inmutable).
 */
class StoreOrderStatusLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'store_order_id',
        'from_status',
        'to_status',
        'note',
        'motivo',
        'changed_by_user_id',
    ];

    public function storeOrder(): BelongsTo
    {
        return $this->belongsTo(StoreOrder::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
