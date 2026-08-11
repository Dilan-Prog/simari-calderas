<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use LogsActivity;

    protected static function logEntityType(): string
    {
        return 'warehouse';
    }

    // Sin updated_at en la tabla (ver migración original de warehouses).
    public $timestamps = false;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = ['name', 'location', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class, 'warehouse_id');
    }

    public function stockBalances()
    {
        return $this->hasMany(WarehouseProductStock::class, 'warehouse_id');
    }
}
