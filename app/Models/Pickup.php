<?php

namespace App\Models;

use App\Support\PickupStatus;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pickup extends Model
{
    use LogsActivity;

    protected static function logEntityType(): string
    {
        return 'pickup';
    }

    protected $fillable = [
        'number', 'type', 'customer_id', 'supplier_id', 'contact_name', 'contact_phone', 'address',
        'scheduled_date', 'time_window', 'driver_user_id', 'carrier_id', 'status', 'notes',
        'incident_note', 'signature_data', 'signed_by_name', 'signed_at', 'created_by_user_id',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'signed_at' => 'datetime',
    ];

    protected $hidden = ['signature_data'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Delivery::class, 'carrier_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PickupItem::class);
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(PickupEvidence::class)->orderBy('id');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(PickupStatusLog::class)->orderByDesc('id');
    }

    public function statusLabel(): string
    {
        return PickupStatus::label($this->status);
    }

    public function counterpartLabel(): string
    {
        if ($this->customer) {
            return trim($this->customer->first_name . ' ' . $this->customer->last_name) . ($this->customer->company ? ' · ' . $this->customer->company : '');
        }

        return $this->supplier?->company_name ?: ($this->contact_name ?: '—');
    }
}
