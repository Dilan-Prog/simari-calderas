<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoringProgram extends Model
{
    public const STATUSES = [
        'active'    => 'Activo',
        'paused'    => 'Pausado',
        'completed' => 'Terminado',
    ];

    protected $fillable = ['customer_id', 'name', 'start_date', 'weeks', 'status', 'notes', 'created_by_user_id'];

    protected $casts = [
        'start_date' => 'date',
        'weeks'      => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MonitoringProgramItem::class, 'program_id')->orderBy('sort_order')->orderBy('id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(MonitoringEntry::class, 'program_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    /** Fecha del último día del programa (start_date + weeks*7 - 1). */
    public function endDate(): Carbon
    {
        return $this->start_date->copy()->addDays($this->weeks * 7 - 1);
    }

    /** Semana (1-based) a la que pertenece una fecha; puede ser <1 o > weeks si cae fuera del programa. */
    public function weekOf($date): int
    {
        $d = Carbon::parse($date)->startOfDay();
        $diff = $this->start_date->copy()->startOfDay()->diffInDays($d, false);

        return (int) floor($diff / 7) + 1;
    }

    /** Semana actual del programa, o null si hoy está fuera de su rango. */
    public function currentWeek(): ?int
    {
        $w = $this->weekOf(now());

        return ($w >= 1 && $w <= $this->weeks) ? $w : null;
    }

    public function weekStart(int $week): Carbon
    {
        return $this->start_date->copy()->addDays(($week - 1) * 7);
    }

    public function weekEnd(int $week): Carbon
    {
        return $this->weekStart($week)->addDays(6);
    }
}
