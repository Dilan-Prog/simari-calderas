<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pago (PAG-AAAA-NNNN). direction 'in' = cobro de cliente (aplica a CXC),
 * 'out' = pago a proveedor (aplica a CXP). exchange_rate = MXN por unidad de
 * la moneda del pago (1 si es MXN).
 */
class Payment extends Model
{
    use LogsActivity;

    protected static function logEntityType(): string
    {
        return 'payment';
    }

    protected $fillable = [
        'number', 'direction', 'customer_id', 'supplier_id', 'payment_date',
        'amount', 'applied_amount', 'currency', 'exchange_rate',
        'payment_method_id', 'reference', 'proof_path', 'status',
        'reconciled_at', 'notes', 'created_by_user_id',
    ];

    protected $casts = [
        'payment_date'   => 'date',
        'amount'         => 'decimal:2',
        'applied_amount' => 'decimal:2',
        'exchange_rate'  => 'decimal:4',
        'reconciled_at'  => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(PaymentApplication::class);
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $last = static::query()->where('number', 'like', "PAG-{$year}-%")->lockForUpdate()->max('number');
        $seq  = $last ? (intval(substr($last, -4)) + 1) : 1;

        return "PAG-{$year}-" . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'pendiente'  => 'Pendiente',
            'aplicado'   => 'Aplicado',
            'conciliado' => 'Conciliado',
            'cancelado'  => 'Cancelado',
            default      => $status,
        };
    }

    public static function statusTone(string $status): string
    {
        return match ($status) {
            'aplicado', 'conciliado' => 'ok',
            'cancelado'              => 'bad',
            default                  => 'warn',
        };
    }

    public function isIncoming(): bool
    {
        return $this->direction === 'in';
    }

    public function directionLabel(): string
    {
        return $this->isIncoming() ? 'Cobro' : 'Pago a proveedor';
    }

    /** Monto aún sin aplicar, en la moneda del pago. */
    public function unappliedAmount(): float
    {
        return round(max(0, (float) $this->amount - (float) $this->applied_amount), 2);
    }

    public function partyName(): string
    {
        if ($this->isIncoming()) {
            $c = $this->customer;

            return $c ? ($c->company ?: trim($c->first_name . ' ' . $c->last_name)) : '—';
        }
        $s = $this->supplier;

        return $s ? ($s->company_name ?: ($s->contact_name ?: '—')) : '—';
    }
}
