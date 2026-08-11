<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use LogsActivity;

    protected static function logEntityType(): string
    {
        return 'inventory_movement';
    }

    // Sin updated_at en la tabla — created_at se asigna a mano en
    // InventoryService::applyMovement() en vez de dejarlo al framework.
    public $timestamps = false;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'movement_type',
        'quantity',
        'reference_type',
        'reference_id',
        'notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function logs()
    {
        return $this->hasMany(InventoryLog::class, 'inventory_movement_id');
    }
}
