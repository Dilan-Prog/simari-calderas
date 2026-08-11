<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Sin LogsActivity: el rastro de auditoría vive en InventoryMovement/
// InventoryLog, esta tabla es solo el saldo acumulado por almacén×producto.
class WarehouseProductStock extends Model
{
    // La tabla es 'warehouse_product_stock' (singular, tal cual la
    // migración) — sin esto Eloquent adivinaría 'warehouse_product_stocks'.
    protected $table = 'warehouse_product_stock';

    protected $fillable = ['warehouse_id', 'product_id', 'quantity'];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}
