<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringEntry extends Model
{
    public const TYPE_READING = 'reading'; // lectura del agua
    public const TYPE_DOSE    = 'dose';    // dosificación aplicada

    protected $fillable = [
        'program_id', 'item_id', 'equipment_id', 'entry_type', 'entry_date', 'value', 'in_range', 'note',
        'recorded_by_customer_id', 'recorded_by_user_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'value'      => 'float',
        'in_range'   => 'boolean',
    ];

    protected static function booted(): void
    {
        // in_range se recalcula siempre contra los límites vigentes del elemento (solo lecturas).
        static::saving(function (MonitoringEntry $entry) {
            if ($entry->entry_type === self::TYPE_READING) {
                $item = $entry->relationLoaded('item') ? $entry->item : MonitoringProgramItem::find($entry->item_id);
                $entry->in_range = $item ? $item->evaluateRange((float) $entry->value) : null;
            } else {
                $entry->in_range = null;
            }
        });
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(MonitoringProgram::class, 'program_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(MonitoringProgramItem::class, 'item_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(MonitoringEquipment::class, 'equipment_id');
    }
}
