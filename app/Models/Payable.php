<?php

namespace App\Models;

use App\Models\Concerns\HasPaymentBalance;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cuenta por pagar (CXP-AAAA-NNNN). Se genera desde una orden de compra
 * aceptada (o manualmente) y se salda con pagos salientes.
 */
class Payable extends Model
{
    use HasPaymentBalance, LogsActivity;

    public const APPLICABLE_TYPE = 'payable';

    protected static function logEntityType(): string
    {
        return 'payable';
    }

    protected $fillable = [
        'number', 'supplier_id', 'purchase_order_id', 'supplier_invoice',
        'issue_date', 'due_date', 'credit_days', 'currency', 'exchange_rate',
        'subtotal', 'tax_total', 'total', 'paid_amount', 'balance', 'status',
        'notes', 'created_by_user_id',
    ];

    protected $casts = [
        'issue_date'    => 'date',
        'due_date'      => 'date',
        'credit_days'   => 'integer',
        'exchange_rate' => 'decimal:4',
        'subtotal'      => 'decimal:2',
        'tax_total'     => 'decimal:2',
        'total'         => 'decimal:2',
        'paid_amount'   => 'decimal:2',
        'balance'       => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public static function generateNumber(): string
    {
        return static::nextNumber('CXP');
    }

    public function partyName(): string
    {
        return $this->supplier ? $this->supplier->company_name ?: ($this->supplier->contact_name ?: '—') : '—';
    }
}
