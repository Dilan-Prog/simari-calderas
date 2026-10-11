<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use LogsActivity;

    public const STATUSES = [
        'levantamiento' => 'Levantamiento',
        'planeado'      => 'Planeado',
        'en_ejecucion'  => 'En ejecución',
        'pausado'       => 'Pausado',
        'cerrado'       => 'Cerrado',
    ];

    protected static function logEntityType(): string
    {
        return 'project';
    }

    protected $fillable = [
        'project_number', 'name', 'description', 'customer_id', 'company',
        'manager_user_id', 'estimated_duration_days', 'estimated_time',
        'start_date', 'due_date', 'status', 'notes', 'created_by_user_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date'   => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function phases(): HasMany
    {
        return $this->hasMany(ProjectPhase::class)->orderBy('sort_order')->orderBy('id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function technicalServices(): HasMany
    {
        return $this->hasMany(TechnicalService::class);
    }

    public function serviceReports(): HasMany
    {
        return $this->hasMany(ServiceReport::class);
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public static function statusLabel(?string $status): string
    {
        return self::STATUSES[$status] ?? (string) $status;
    }

    public static function statusTone(?string $status): string
    {
        return match ($status) {
            'cerrado'      => 'ok',
            'en_ejecucion' => 'warn',
            'pausado'      => 'bad',
            default        => 'neutral',
        };
    }

    /** Días hasta la fecha límite (negativo = vencido); null si no hay fecha. */
    public function daysToDue(): ?int
    {
        return $this->due_date ? (int) now()->startOfDay()->diffInDays($this->due_date->copy()->startOfDay(), false) : null;
    }

    /** Semáforo de fecha límite: ok (>7 días), warn (<=7), bad (vencido), neutral (sin fecha o cerrado). */
    public function dueTone(): string
    {
        $days = $this->daysToDue();
        if ($days === null || $this->status === 'cerrado') {
            return 'neutral';
        }

        return $days < 0 ? 'bad' : ($days <= 7 ? 'warn' : 'ok');
    }

    /** Fase actual para listados: "3/5 · Suministro" (primera fase no completada; null si no hay fases). */
    public function currentPhaseLabel(): ?string
    {
        $phases = $this->phases;
        if ($phases->isEmpty()) {
            return null;
        }
        $idx = $phases->search(fn ($ph) => $ph->status !== 'completada');
        $idx = $idx === false ? $phases->count() - 1 : $idx;

        return ($idx + 1) . '/' . $phases->count() . ' · ' . $phases[$idx]->name;
    }

    /** Monto de las cotizaciones asociadas (excluye rechazadas), convertido a MXN. Requiere quotes cargadas. */
    public function quotesTotalMxn(): float
    {
        return (float) $this->quotes
            ->reject(fn ($q) => $q->status === 'rejected')
            ->sum(fn ($q) => (float) $q->total * (($q->currency ?? 'MXN') === 'MXN' ? 1 : (float) ($q->exchange_rate ?: 1)));
    }

    public function getDisplayCustomerAttribute(): string
    {
        if ($this->customer) {
            return trim($this->customer->first_name . ' ' . $this->customer->last_name);
        }

        return (string) ($this->company ?: '—');
    }

    // Folio PRY-AAAA-NNNN secuencial por año (mismo patrón que Quote/PurchaseOrder).
    public static function generateProjectNumber(): string
    {
        $year = date('Y');
        $last = static::where('project_number', 'like', "PRY-{$year}-%")->max('project_number');
        $seq  = $last ? (intval(substr($last, -4)) + 1) : 1;

        return 'PRY-' . $year . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
