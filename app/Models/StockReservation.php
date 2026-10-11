<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Reserva de existencias para un requerimiento/proyecto (ERP Fase 2). */
class StockReservation extends Model
{
    use LogsActivity;

    protected static function logEntityType(): string
    {
        return 'stock_reservation';
    }

    protected $fillable = [
        'product_id', 'warehouse_id', 'quantity', 'project_id', 'requirement_id',
        'source_type', 'source_id', 'status', 'created_by_user_id',
    ];

    protected $casts = ['quantity' => 'float'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(MaterialRequirement::class, 'requirement_id');
    }

    public function scopeActive($q)
    {
        return $q->where('status', 'activa');
    }
}
