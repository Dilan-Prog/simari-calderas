<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoringEquipment extends Model
{
    protected $table = 'monitoring_equipment';

    public const TYPES = [
        'caldera'            => 'Caldera',
        'torre_enfriamiento' => 'Torre de enfriamiento',
        'chiller'            => 'Chiller',
        'suavizador'         => 'Suavizador',
        'osmosis'            => 'Ósmosis inversa',
        'otro'               => 'Otro',
    ];

    protected $fillable = ['customer_id', 'name', 'type', 'location', 'notes', 'is_active', 'created_via'];

    protected $casts = ['is_active' => 'boolean'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(MonitoringEntry::class, 'equipment_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }
}
