<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ServiceReport extends Model
{
    protected $fillable = [
        'report_number',
        'created_by_user_id',
        'assigned_user_id',
        'customer_id',
        'customer_name',
        'customer_company',
        'customer_rfc',
        'customer_phone',
        'service_type',
        'custom_service_type',
        'status',
        'service_date',
        'week_number',
        'location',
        'observations',
        'recommendations',
        'sampling_date_day',
        'sampling_date_month',
        'sampling_date_year',
        'analyst_name',
        'analyst_position',
        'include_sampling',
        'signature_data',
        'signature_name',
        'signature_position',
        'signature_phone',
        'signed_at',
        'current_step',
    ];

    protected $casts = [
        'service_date'    => 'date',
        'signed_at'       => 'datetime',
        'include_sampling'=> 'boolean',
    ];

    // ── Relations ──────────────────────────────────────────────────────────────

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(ServiceReportMeasurement::class)->orderBy('sort_order');
    }

    public function activity(): HasOne
    {
        return $this->hasOne(ServiceReportActivity::class);
    }

    public function customFields(): HasMany
    {
        return $this->hasMany(ServiceReportCustomField::class)->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ServiceReportImage::class)->orderBy('sort_order');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('service_type', $type);
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

    public function getServiceTypeLabelAttribute(): string
    {
        return match($this->service_type) {
            'chemical_analysis'      => 'Análisis Químico',
            'maintenance_preventive' => 'Mantenimiento Preventivo',
            'maintenance_corrective' => 'Mantenimiento Correctivo',
            'inspection'             => 'Inspección',
            'cleaning'               => 'Limpieza',
            'calibration'            => 'Calibración',
            'activity_report'        => 'Reporte de Actividades',
            'custom'                 => $this->custom_service_type ?? 'Personalizado',
            default                  => $this->service_type,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft'       => 'Borrador',
            'in_progress' => 'En Proceso',
            'completed'   => 'Completado',
            'signed'      => 'Firmado',
            default       => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft'       => 'sr-badge--draft',
            'in_progress' => 'sr-badge--in-progress',
            'completed'   => 'sr-badge--completed',
            'signed'      => 'sr-badge--signed',
            default       => 'sr-badge--draft',
        };
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function isEditable(): bool
    {
        return $this->status !== 'signed';
    }

    public function isDeletable(): bool
    {
        return $this->status === 'draft';
    }

    public function usesActivityForm(): bool
    {
        return in_array($this->service_type, [
            'maintenance_preventive',
            'maintenance_corrective',
            'activity_report',
            'inspection',
            'cleaning',
            'calibration',
        ]);
    }

    public function usesMeasurementsForm(): bool
    {
        return $this->service_type === 'chemical_analysis';
    }

    public function usesCustomForm(): bool
    {
        return $this->service_type === 'custom';
    }

    public function getAssignedUserNameAttribute(): string
    {
        return $this->assignedUser
            ? trim("{$this->assignedUser->first_name} {$this->assignedUser->last_name}")
            : '—';
    }
}
