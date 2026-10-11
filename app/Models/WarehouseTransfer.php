<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class WarehouseTransfer extends Model
{
    use LogsActivity;

    protected static function logEntityType(): string
    {
        return 'warehouse_transfer';
    }

    public const STATUSES = ['borrador', 'en_transito', 'recibido', 'cancelado'];

    protected $fillable = [
        'number', 'from_warehouse_id', 'to_warehouse_id', 'status', 'notes', 'receipt_notes',
        'requested_at', 'requested_by_user_id', 'shipped_at', 'shipped_by_user_id',
        'received_at', 'received_by_user_id', 'cancelled_at', 'cancelled_by_user_id',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'shipped_at' => 'datetime',
        'received_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function lines()
    {
        return $this->hasMany(WarehouseTransferLine::class, 'transfer_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function shippedBy()
    {
        return $this->belongsTo(User::class, 'shipped_by_user_id');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    // TRA-YYYY-NNNN (mismo patrón que SalesOrder::generateOrderNumber()).
    public static function generateNumber(): string
    {
        $year = date('Y');
        $last = static::where('number', 'like', "TRA-{$year}-%")->max('number');
        $seq = $last ? (intval(substr($last, -4)) + 1) : 1;

        return 'TRA-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'borrador' => 'Borrador',
            'en_transito' => 'En tránsito',
            'recibido' => 'Recibido',
            'cancelado' => 'Cancelado',
            default => $status,
        };
    }

    public static function statusTone(string $status): string
    {
        return match ($status) {
            'en_transito' => 'warn',
            'recibido' => 'ok',
            'cancelado' => 'bad',
            default => 'neutral',
        };
    }
}
