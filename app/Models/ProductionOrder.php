<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

/**
 * ERP Fase 5 — Orden de producción (fabricación propia). Folio PRD-AAAA-NNNN.
 */
class ProductionOrder extends Model
{
    use LogsActivity;

    public const STATUSES = ['borrador', 'liberada', 'en_proceso', 'terminada', 'cancelada'];

    protected static function logEntityType(): string
    {
        return 'production_order';
    }

    protected $fillable = [
        'number', 'product_id', 'bom_id', 'quantity_planned', 'quantity_produced', 'warehouse_id',
        'project_id', 'sales_order_id', 'material_requirement_id', 'due_date', 'start_date',
        'status', 'progress', 'standard_cost', 'actual_cost', 'notes', 'created_by_user_id',
    ];

    protected $casts = [
        'due_date'          => 'date',
        'start_date'        => 'date',
        'quantity_planned'  => 'float',
        'quantity_produced' => 'float',
        'standard_cost'     => 'float',
        'actual_cost'       => 'float',
        'progress'          => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class, 'bom_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(MaterialRequirement::class, 'material_requirement_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function components(): HasMany
    {
        return $this->hasMany(ProductionOrderComponent::class)->orderBy('id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ProductionOrderLog::class)->orderByDesc('id');
    }

    /** Proyecto (tolerante: el módulo Proyectos puede no existir). */
    public function getProjectAttribute(): ?Model
    {
        if (!$this->project_id || !class_exists(Project::class) || !Schema::hasTable('projects')) {
            return null;
        }

        return Project::find($this->project_id);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['borrador', 'liberada', 'en_proceso'], true);
    }

    public function scopeOpen($q)
    {
        return $q->whereIn('status', ['borrador', 'liberada', 'en_proceso']);
    }

    public function scopeOverdue($q)
    {
        return $q->open()->whereNotNull('due_date')->whereDate('due_date', '<', now()->toDateString());
    }

    public function daysToDue(): ?int
    {
        return $this->due_date ? (int) now()->startOfDay()->diffInDays($this->due_date->copy()->startOfDay(), false) : null;
    }

    /** Semáforo de fecha límite: ok (>7 días), warn (<=7), bad (vencida), neutral. */
    public function dueTone(): string
    {
        $d = $this->daysToDue();
        if ($d === null || !$this->isOpen()) {
            return 'neutral';
        }

        return $d < 0 ? 'bad' : ($d <= 7 ? 'warn' : 'ok');
    }

    /** Costo estándar proporcional a lo producido. */
    public function standardCostProduced(): float
    {
        return $this->quantity_planned > 0
            ? round($this->standard_cost / $this->quantity_planned * $this->quantity_produced, 4)
            : 0.0;
    }

    public static function statusLabel(?string $s): string
    {
        return match ($s) {
            'borrador'   => 'Borrador',
            'liberada'   => 'Liberada',
            'en_proceso' => 'En proceso',
            'terminada'  => 'Terminada',
            'cancelada'  => 'Cancelada',
            default      => (string) $s,
        };
    }

    public static function statusTone(?string $s): string
    {
        return match ($s) {
            'terminada' => 'ok',
            'cancelada' => 'bad',
            'liberada', 'en_proceso' => 'warn',
            default     => 'neutral',
        };
    }

    // Folio PRD-AAAA-NNNN secuencial por año.
    public static function generateNumber(): string
    {
        $year = date('Y');
        $last = static::where('number', 'like', "PRD-{$year}-%")->max('number');
        $seq  = $last ? (intval(substr($last, -4)) + 1) : 1;

        return 'PRD-' . $year . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
