<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bitácora de cambios de estatus de un MercadoPagoPayment. Solo created_at
 * (la columna updated_at no existe en la tabla — un log es un evento
 * inmutable).
 */
class MercadoPagoPaymentStatusLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'mercado_pago_payment_id',
        'from_status',
        'to_status',
        'note',
        'source',
        'changed_by_user_id',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(MercadoPagoPayment::class, 'mercado_pago_payment_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
