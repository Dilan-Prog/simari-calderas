<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tabla de control de conversiones subidas a Google (anti-duplicados).
 * Una fila "upload" por (negocio, acción); los ajustes (restatement /
 * retraction) son filas aparte con el mismo order_id.
 */
class GoogleConversionUpload extends Model
{
    public const ACTION_QUOTED = 'cotizado';
    public const ACTION_WON    = 'venta_ganada';

    public const STATUSES = [
        'pendiente'      => 'Pendiente',
        'enviada'        => 'Enviada',
        'fallida'        => 'Fallida',
        'omitida'        => 'Omitida',
        'sin_atribucion' => 'Sin atribución',
    ];

    public const ACTIONS = [
        'cotizado'     => 'Cotizado (secundaria)',
        'venta_ganada' => 'Venta ganada (principal)',
    ];

    public const OPERATIONS = [
        'upload'      => 'Conversión',
        'restatement' => 'Ajuste de monto (RESTATEMENT)',
        'retraction'  => 'Retiro (RETRACTION)',
    ];

    public const MAX_ATTEMPTS = 3;

    protected $fillable = [
        'lead_id', 'deal_id', 'legacy_conversion_id', 'parent_upload_id',
        'action', 'operation', 'order_id', 'dedupe_key',
        'identifier_type', 'identifier', 'user_data',
        'conversion_date_time', 'timezone', 'conversion_value', 'currency',
        'click_at', 'gclid_expires_at',
        'status', 'attempts', 'last_error', 'response',
        'next_attempt_at', 'sent_at', 'alerted_at',
    ];

    protected $casts = [
        'user_data'            => 'array',
        'response'             => 'array',
        'conversion_date_time' => 'datetime',
        'click_at'             => 'datetime',
        'gclid_expires_at'     => 'datetime',
        'next_attempt_at'      => 'datetime',
        'sent_at'              => 'datetime',
        'alerted_at'           => 'datetime',
        'conversion_value'     => 'decimal:2',
        'attempts'             => 'integer',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class)->withTrashed();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_upload_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(self::class, 'parent_upload_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            'enviada'        => 'ok',
            'pendiente'      => 'warn',
            'fallida'        => 'bad',
            'sin_atribucion' => 'warn',
            default          => 'neutral',
        };
    }

    public function getActionLabelAttribute(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }

    public function getOperationLabelAttribute(): string
    {
        return self::OPERATIONS[$this->operation] ?? $this->operation;
    }

    public function canRetry(): bool
    {
        return in_array($this->status, ['fallida', 'pendiente'], true);
    }
}
