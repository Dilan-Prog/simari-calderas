<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialDeliveryReport extends Model
{
    use LogsActivity;

    protected static function logEntityType(): string
    {
        return 'material_delivery_report';
    }

    // signature_data guarda la imagen capturada de la firma — se excluye
    // por tamaño de fila, no por sensibilidad (mismo criterio que ServiceReport).
    protected static function logExcept(): array
    {
        return ['signature_data'];
    }

    protected $fillable = [
        'report_number',
        'created_by_user_id',
        'sales_order_id',
        'customer_id',
        'delivery_date',
        'delivery_location',
        'observations',
        'status',
        'signature_data',
        'received_by_name',
        'received_by_position',
        'received_by_phone',
        'signed_at',
        'current_step',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'signed_at'     => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────────────────────

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MaterialDeliveryReportItem::class)->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(MaterialDeliveryReportImage::class)->orderBy('sort_order');
    }

    // ── Accessors ──────────────────────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft'  => 'Borrador',
            'signed' => 'Firmado',
            default  => $this->status,
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
}
