<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends Model
{
    protected $fillable = [
        'quote_number',
        'created_by_user_id',
        'customer_id',
        'status',
        'guest_name',
        'guest_email',
        'guest_phone',
        'guest_company',
        'guest_rfc',
        'currency',
        'exchange_rate',
        'subtotal',
        'discount_total',
        'tax_rate',
        'tax_total',
        'isr_retention_rate',
        'isr_retention_total',
        'total',
        'valid_until',
        'notes',
        'terms_conditions',
        'converted_to_order_id',
    ];

    protected $casts = [
        'subtotal'             => 'decimal:2',
        'discount_total'       => 'decimal:2',
        'tax_rate'             => 'decimal:2',
        'exchange_rate'        => 'decimal:4',
        'tax_total'            => 'decimal:2',
        'isr_retention_rate'   => 'decimal:2',
        'isr_retention_total'  => 'decimal:2',
        'total'                => 'decimal:2',
        'valid_until'          => 'date',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('sort_order');
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function isEditable(?\App\Models\User $user = null): bool
    {
        if ($user?->isAdmin()) {
            return true;
        }
        return in_array($this->status, ['draft', 'sent']);
    }

    public static function statusLabel(string $status): string
    {
        return match($status) {
            'draft'    => 'Borrador',
            'sent'     => 'Enviada',
            'accepted' => 'Aceptada',
            'rejected' => 'Rechazada',
            'expired'  => 'Vencida',
            default    => $status,
        };
    }
}
