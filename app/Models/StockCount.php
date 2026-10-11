<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class StockCount extends Model
{
    use LogsActivity;

    protected static function logEntityType(): string
    {
        return 'stock_count';
    }

    protected $fillable = [
        'number', 'warehouse_id', 'status', 'started_at', 'closed_at', 'adjusted_at',
        'created_by_user_id', 'closed_by_user_id', 'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
        'adjusted_at' => 'datetime',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function lines()
    {
        return $this->hasMany(StockCountLine::class, 'count_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    // CNT-YYYY-NNNN
    public static function generateNumber(): string
    {
        $year = date('Y');
        $last = static::where('number', 'like', "CNT-{$year}-%")->max('number');
        $seq = $last ? (intval(substr($last, -4)) + 1) : 1;

        return 'CNT-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'abierto' => 'Abierto',
            'en_conteo' => 'En conteo',
            'cerrado' => 'Cerrado',
            'ajustado' => 'Ajustado',
            default => $status,
        };
    }

    public static function statusTone(string $status): string
    {
        return match ($status) {
            'abierto' => 'neutral',
            'en_conteo' => 'warn',
            'cerrado' => 'warn',
            'ajustado' => 'ok',
            default => 'neutral',
        };
    }
}
