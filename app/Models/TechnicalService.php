<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TechnicalService extends Model
{
    protected $table = 'services';

    protected $fillable = [
        'service_number',
        'service_code',
        'customer_id',
        'created_by_user_id',
        'from_quote_id',
        'service_date',
        'service_time',
        'estimated_duration',
        'priority',
        'address',
        'reference',
        'location',
        'week_number',
        'current_step',
        'service_type_id',
        'requested_at',
        'description',
        'status',
        'total_cost',
        'invoice_id',
    ];

    protected $casts = [
        'service_date'       => 'date',
        'estimated_duration' => 'decimal:1',
        'requested_at'       => 'datetime',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function fromQuote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'from_quote_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class, 'service_type_id');
    }

    public function assignedTechnicians(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'service_technicians',
            'service_id',
            'user_id'
        )->withPivot('role_verified');
    }

    public function plannedMaterials(): HasMany
    {
        return $this->hasMany(ServiceMaterialPlanned::class, 'service_id')
                    ->orderBy('sort_order');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ServiceLog::class, 'service_id')
                    ->orderByDesc('created_at');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByTechnician($query, int $userId)
    {
        return $query->whereHas(
            'assignedTechnicians',
            fn($q) => $q->where('user_id', $userId)
        );
    }

    public function scopeByMonth($query, int $month, int $year)
    {
        return $query->whereMonth('service_date', $month)
                     ->whereYear('service_date', $year);
    }

    public function scopeByDateRange($query, ?string $from, ?string $to)
    {
        if ($from) {
            $query->whereDate('service_date', '>=', $from);
        }
        if ($to) {
            $query->whereDate('service_date', '<=', $to);
        }
        return $query;
    }

    // ── Accessors ──────────────────────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft'       => 'Borrador',
            'scheduled'   => 'Programado',
            'in_progress' => 'En Proceso',
            'completed'   => 'Completado',
            'cancelled'   => 'Cancelado',
            default       => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft'       => 'ts-badge--draft',
            'scheduled'   => 'ts-badge--scheduled',
            'in_progress' => 'ts-badge--in-progress',
            'completed'   => 'ts-badge--completed',
            'cancelled'   => 'ts-badge--cancelled',
            default       => 'ts-badge--draft',
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            'normal' => 'Normal',
            'high'   => 'Alta',
            'urgent' => 'Urgente',
            default  => 'Normal',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'normal' => 'ts-priority--normal',
            'high'   => 'ts-priority--high',
            'urgent' => 'ts-priority--urgent',
            default  => 'ts-priority--normal',
        };
    }

    public function getServiceTypeLabelAttribute(): string
    {
        return $this->serviceType?->name ?? '—';
    }

    public function getCustomerNameAttribute(): string
    {
        if (!$this->relationLoaded('customer') || !$this->customer) {
            return '—';
        }
        return $this->customer->company
            ?: trim("{$this->customer->first_name} {$this->customer->last_name}");
    }

    public function getCustomerCompanyAttribute(): string
    {
        return $this->customer?->company ?? '—';
    }

    public function getEstimatedDurationLabelAttribute(): string
    {
        if (!$this->estimated_duration) {
            return '—';
        }

        return match((float) $this->estimated_duration) {
            1.0  => '1 hora',
            2.0  => '2 horas',
            3.0  => '3 horas',
            4.0  => '4 horas',
            4.5  => 'Medio día',
            8.0  => 'Día completo',
            default => "{$this->estimated_duration}h",
        };
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'scheduled']);
    }

    public function isCancellable(): bool
    {
        return !in_array($this->status, ['completed', 'cancelled']);
    }

    public function isDeletable(): bool
    {
        return $this->status === 'draft';
    }
}
