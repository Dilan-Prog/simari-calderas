<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aplicación de un pago a una cuenta por cobrar o por pagar.
 * amount_applied está en la moneda del documento; amount_payment en la del pago.
 */
class PaymentApplication extends Model
{
    protected $fillable = ['payment_id', 'applicable_type', 'applicable_id', 'amount_applied', 'amount_payment'];

    protected $casts = [
        'amount_applied' => 'decimal:2',
        'amount_payment' => 'decimal:2',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /** Receivable o Payable según applicable_type. */
    public function document(): ?Model
    {
        return match ($this->applicable_type) {
            'receivable' => Receivable::find($this->applicable_id),
            'payable'    => Payable::find($this->applicable_id),
            default      => null,
        };
    }
}
