<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

/**
 * ERP Fase 2 — Requerimiento de materiales (MRP). Folio REQ-AAAA-NNNN.
 */
class MaterialRequirement extends Model
{
    use LogsActivity;

    public const STATUSES = ['borrador', 'calculado', 'en_compra', 'cubierto', 'cancelado'];

    public const ORIGINS = ['sales_order', 'project', 'technical_service', 'production_order', 'manual'];

    protected static function logEntityType(): string
    {
        return 'material_requirement';
    }

    protected $fillable = [
        'number', 'origin_type', 'origin_id', 'project_id', 'customer_id', 'warehouse_id',
        'needed_by_date', 'status', 'include_chemicals', 'notes', 'created_by_user_id',
    ];

    protected $casts = [
        'needed_by_date'    => 'date',
        'include_chemicals' => 'boolean',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(MaterialRequirementLine::class, 'requirement_id')->orderBy('sort_order')->orderBy('id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(StockReservation::class, 'requirement_id');
    }

    /** Proyecto (tolerante: el módulo Proyectos puede no existir aún). */
    public function getProjectAttribute(): ?Model
    {
        if (!$this->project_id || !class_exists(Project::class) || !Schema::hasTable('projects')) {
            return null;
        }

        return Project::find($this->project_id);
    }

    /** Documento de origen (pedido, proyecto, servicio) o null. */
    public function getOriginAttribute(): ?Model
    {
        $class = match ($this->origin_type) {
            'sales_order'       => SalesOrder::class,
            'project'           => class_exists(Project::class) ? Project::class : null,
            'technical_service' => TechnicalService::class,
            'production_order'  => class_exists(ProductionOrder::class) ? ProductionOrder::class : null,
            default             => null,
        };

        return ($class && $this->origin_id) ? $class::find($this->origin_id) : null;
    }

    public function originLabel(): string
    {
        return match ($this->origin_type) {
            'sales_order'       => 'Pedido',
            'project'           => 'Proyecto',
            'technical_service' => 'Servicio técnico',
            'production_order'  => 'Orden de producción',
            default             => 'Manual',
        };
    }

    public function originNumber(): ?string
    {
        $o = $this->origin;
        if (!$o) {
            return null;
        }

        return (string) ($o->order_number ?? $o->project_number ?? $o->service_number ?? $o->number ?? ('#' . $o->getKey()));
    }

    public static function statusLabel(?string $s): string
    {
        return match ($s) {
            'borrador'  => 'Borrador',
            'calculado' => 'Calculado',
            'en_compra' => 'En compra',
            'cubierto'  => 'Cubierto',
            'cancelado' => 'Cancelado',
            default     => (string) $s,
        };
    }

    public static function statusTone(?string $s): string
    {
        return match ($s) {
            'cubierto'  => 'ok',
            'cancelado' => 'bad',
            'en_compra', 'calculado' => 'warn',
            default     => 'neutral',
        };
    }

    // Folio REQ-AAAA-NNNN secuencial por año.
    public static function generateNumber(): string
    {
        $year = date('Y');
        $last = static::where('number', 'like', "REQ-{$year}-%")->max('number');
        $seq  = $last ? (intval(substr($last, -4)) + 1) : 1;

        return 'REQ-' . $year . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
