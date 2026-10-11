<?php

namespace App\Models\Concerns;

use App\Models\PaymentApplication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * Comportamiento común de Receivable y Payable: saldo, estatus, vencimiento y
 * bucket de antigüedad. Requiere columnas total, paid_amount, balance, status,
 * due_date, currency y exchange_rate, y la constante APPLICABLE_TYPE.
 */
trait HasPaymentBalance
{
    // PHP 8.1: los traits no admiten constantes; los estatus abiertos viven en openStatuses().
    public static function openStatuses(): array
    {
        return ['pendiente', 'parcial', 'vencida'];
    }

    public function applications()
    {
        return $this->hasMany(PaymentApplication::class, 'applicable_id')
            ->where('applicable_type', static::APPLICABLE_TYPE);
    }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereIn('status', self::openStatuses());
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'pendiente' => 'Pendiente',
            'parcial'   => 'Parcial',
            'pagada'    => 'Pagada',
            'vencida'   => 'Vencida',
            'cancelada' => 'Cancelada',
            default     => $status,
        };
    }

    public static function statusTone(string $status): string
    {
        return match ($status) {
            'pagada'            => 'ok',
            'vencida', 'cancelada' => 'bad',
            'parcial'           => 'warn',
            default             => 'neutral',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::openStatuses(), true);
    }

    /** Estatus que corresponde según saldo y vencimiento (sin guardar). */
    public function computeStatus(?Carbon $today = null): string
    {
        if ($this->status === 'cancelada') {
            return 'cancelada';
        }
        $today ??= now();
        $balance = round((float) $this->balance, 2);
        if ($balance <= 0.005) {
            return 'pagada';
        }
        if ($this->due_date && $this->due_date->copy()->startOfDay()->lt($today->copy()->startOfDay())) {
            return 'vencida';
        }

        return round((float) $this->paid_amount, 2) > 0 ? 'parcial' : 'pendiente';
    }

    /** Recalcula paid_amount / balance / status desde las aplicaciones de pagos no cancelados. */
    public function recalculate(): void
    {
        if ($this->status === 'cancelada') {
            return;
        }
        $paid = (float) PaymentApplication::query()
            ->where('applicable_type', static::APPLICABLE_TYPE)
            ->where('applicable_id', $this->getKey())
            ->sum('amount_applied');

        $this->paid_amount = round($paid, 2);
        $this->balance     = round(max(0, (float) $this->total - $paid), 2);
        $this->status      = $this->computeStatus();
        $this->save();
    }

    public function daysOverdue(): int
    {
        if (!$this->isOpen() || !$this->due_date) {
            return 0;
        }
        $d = (int) $this->due_date->copy()->startOfDay()->diffInDays(now()->startOfDay(), false);

        return max(0, $d);
    }

    /** Días para vencer (negativo si ya venció). */
    public function daysToDue(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->due_date->copy()->startOfDay(), false);
    }

    /** vigente | d30 | d60 | d90 | d90p */
    public function agingBucket(): string
    {
        return static::bucketFor($this->daysOverdue());
    }

    public static function bucketFor(int $daysOverdue): string
    {
        return match (true) {
            $daysOverdue <= 0  => 'current',
            $daysOverdue <= 30 => 'd30',
            $daysOverdue <= 60 => 'd60',
            $daysOverdue <= 90 => 'd90',
            default            => 'd90p',
        };
    }

    public function balanceMxn(): float
    {
        return round((float) $this->balance * (float) ($this->exchange_rate ?: 1), 2);
    }

    /** Semáforo: bad = vencido, warn = vence en ≤ 7 días, ok = vigente, neutral = cerrado. */
    public function dueTone(): string
    {
        if (!$this->isOpen()) {
            return 'neutral';
        }
        $d = $this->daysToDue();

        return $d < 0 ? 'bad' : ($d <= 7 ? 'warn' : 'ok');
    }

    /** Siguiente folio {prefijo}-AAAA-NNNN. Llamar dentro de transacción. */
    protected static function nextNumber(string $prefix): string
    {
        $year = date('Y');
        $last = static::query()->where('number', 'like', "{$prefix}-{$year}-%")->lockForUpdate()->max('number');
        $seq  = $last ? (intval(substr($last, -4)) + 1) : 1;

        return "{$prefix}-{$year}-" . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
