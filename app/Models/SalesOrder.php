<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends Model
{
    protected $fillable = [
        'order_number', 'quote_id', 'customer_id', 'created_by_user_id',
        'status', 'notes',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class)->orderBy('sort_order');
    }

    // Auto-generate order number: PED-YYYY-XXXX (mismo patrón que PurchaseOrder::generatePoNumber()).
    public static function generateOrderNumber(): string
    {
        $year = date('Y');
        $last = static::whereYear('created_at', $year)->max('order_number');
        $seq  = $last ? (intval(substr($last, -4)) + 1) : 1;

        return 'PED-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'pendiente'               => 'Pendiente',
            'en_preparacion'          => 'En preparación',
            'parcialmente_entregado'  => 'Parcialmente entregado',
            'entregado'               => 'Entregado',
            'cancelado'               => 'Cancelado',
            default                   => $status,
        };
    }

    /**
     * Recalcula el status a partir del progreso de entrega de sus líneas.
     * No toca 'cancelado' — ese estado solo se cambia manualmente.
     */
    public function recalculateStatus(): void
    {
        if ($this->status === 'cancelado') {
            return;
        }

        // Consulta fresca (no la relación cacheada): recalculateStatus() puede
        // llamarse más de una vez sobre la misma instancia tras actualizar
        // líneas por separado, y una colección cacheada quedaría obsoleta.
        $items = $this->items()->get();
        $allComplete = $items->isNotEmpty() && $items->every(fn ($i) => $i->quantity_delivered >= $i->quantity_ordered);
        $anyDelivered = $items->contains(fn ($i) => $i->quantity_delivered > 0);

        $this->status = $allComplete ? 'entregado' : ($anyDelivered ? 'parcialmente_entregado' : $this->status);
        $this->save();
    }
}
