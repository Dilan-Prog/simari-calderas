<?php

namespace App\Models;

use App\Models\Concerns\HasPaymentBalance;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cuenta por cobrar (CXC-AAAA-NNNN). Se genera desde un pedido (o manualmente)
 * y se salda con aplicaciones de pagos entrantes.
 */
class Receivable extends Model
{
    use HasPaymentBalance, LogsActivity;

    public const APPLICABLE_TYPE = 'receivable';

    /** Orígenes manuales permitidos: clave => clase. */
    public const SOURCE_TYPES = [
        'sales_order' => SalesOrder::class,
        'quote'       => Quote::class,
        'project'     => 'App\\Models\\Project',
        'store_order' => StoreOrder::class,
    ];

    protected static function logEntityType(): string
    {
        return 'receivable';
    }

    protected $fillable = [
        'number', 'customer_id', 'source_type', 'source_id', 'project_id',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public static function generateNumber(): string
    {
        return static::nextNumber('CXC');
    }

    public static function sourceLabel(?string $type): string
    {
        return match ($type) {
            'sales_order' => 'Pedido',
            'quote'       => 'Cotización',
            'project'     => 'Proyecto',
            'store_order' => 'Orden de tienda',
            default       => 'Manual',
        };
    }

    /** Documento origen, o null si no hay / la clase no existe. */
    public function source(): ?Model
    {
        $class = self::SOURCE_TYPES[$this->source_type] ?? null;
        if (!$class || !$this->source_id || !class_exists($class)) {
            return null;
        }

        return $class::find($this->source_id);
    }

    public function partyName(): string
    {
        return $this->customer ? $this->customer->company ?: trim($this->customer->first_name . ' ' . $this->customer->last_name) : '—';
    }
}
