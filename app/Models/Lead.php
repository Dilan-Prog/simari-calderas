<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Lead unificado (bandeja): una fila por contacto con oportunidad abierta,
 * venga de WhatsApp, formulario, llamada, correo o captura manual.
 * Conserva la atribución: primer clic (nunca se sobreescribe) y último clic.
 */
class Lead extends Model
{
    use LogsActivity;

    public const SOURCES = [
        'whatsapp'   => 'WhatsApp',
        'formulario' => 'Formulario',
        'llamada'    => 'Llamada',
        'correo'     => 'Correo',
        'manual'     => 'Manual',
    ];

    public const STATUSES = [
        'nuevo'      => 'Nuevo',
        'contactado' => 'Contactado',
        'calificado' => 'Calificado',
        'descartado' => 'Descartado',
        'convertido' => 'Convertido',
    ];

    /** Estados que cuentan como "lead abierto" para detectar duplicados. */
    public const OPEN_STATUSES = ['nuevo', 'contactado', 'calificado'];

    protected static function logEntityType(): string
    {
        return 'lead';
    }

    protected $fillable = [
        'source', 'status',
        'contact_name', 'contact_company', 'contact_phone', 'phone_normalized', 'contact_whatsapp', 'whatsapp_normalized', 'contact_email', 'contact_website',
        'customer_id', 'deal_id', 'whatsapp_conversation_id', 'pool_calculator_lead_id', 'assigned_to',
        'visitor_uuid', 'gclid', 'gbraid', 'wbraid',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
        'click_at', 'first_click', 'last_click',
        'first_contact_at', 'last_activity_at', 'notes',
    ];

    protected $casts = [
        'click_at'         => 'datetime',
        'first_contact_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'reply_pending_at' => 'datetime',
        'first_click'      => 'array',
        'last_click'       => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class)->withTrashed();
    }

    public function whatsappConversation(): BelongsTo
    {
        return $this->belongsTo(WhatsappConversation::class);
    }

    public function poolCalculatorLead(): BelongsTo
    {
        return $this->belongsTo(PoolCalculatorLead::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function calls(): HasMany
    {
        return $this->hasMany(LeadCall::class)->orderByDesc('started_at');
    }

    /** Notas internas del hilo (tabla lead_notes). Ojo: NO se llama `notes` porque choca con la columna de texto `leads.notes`. */
    public function noteEntries(): HasMany
    {
        return $this->hasMany(LeadNote::class)->latest('id');
    }

    public function adVisit(): BelongsTo
    {
        return $this->belongsTo(AdVisit::class, 'visitor_uuid', 'visitor_uuid');
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    public function conversionUploads(): HasMany
    {
        return $this->hasMany(GoogleConversionUpload::class);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', self::OPEN_STATUSES);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->contact_name ?: ($this->contact_phone ?: ($this->contact_email ?: 'Lead #' . $this->id));
    }

    /** Número (E.164) al que se escribe por WhatsApp: el propio del lead o, si no tiene, el teléfono. */
    public function getWhatsappTargetAttribute(): ?string
    {
        return $this->whatsapp_normalized ?: $this->phone_normalized;
    }

    /** Número tal como se muestra en pantalla (el propio de WhatsApp o el teléfono). */
    public function getWhatsappDisplayAttribute(): ?string
    {
        return $this->contact_whatsapp ?: $this->contact_phone;
    }

    public function getSourceLabelAttribute(): string
    {
        return self::SOURCES[$this->source] ?? $this->source;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            'nuevo'      => 'warn',
            'contactado' => 'neutral',
            'calificado' => 'ok',
            'convertido' => 'ok',
            'descartado' => 'bad',
            default      => 'neutral',
        };
    }

    /** Identificador de clic vigente (GCLID gana sobre GBRAID/WBRAID). */
    public function getClickIdentifierAttribute(): ?array
    {
        foreach (['gclid', 'gbraid', 'wbraid'] as $type) {
            if (! empty($this->{$type})) {
                return ['type' => $type, 'value' => $this->{$type}];
            }
        }

        return null;
    }
}
