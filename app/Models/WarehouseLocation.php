<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class WarehouseLocation extends Model
{
    use LogsActivity;

    protected static function logEntityType(): string
    {
        return 'warehouse_location';
    }

    protected $fillable = ['warehouse_id', 'code', 'name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stockBalances()
    {
        return $this->hasMany(WarehouseProductStock::class, 'location_id');
    }
}
