<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Sin CRUD propio ni LogsActivity: esta tabla ES la bitácora (creada a mano
// por InventoryService::applyMovement() junto con cada InventoryMovement).
class InventoryLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'inventory_movement_id',
        'action',
        'reason',
        'performed_by_user_id',
        // created_at es NOT NULL sin default en el esquema y $timestamps
        // está desactivado (no hay updated_at) — InventoryService lo asigna
        // a mano en cada create(), por eso necesita estar en fillable.
        'created_at',
    ];

    public function movement()
    {
        return $this->belongsTo(InventoryMovement::class, 'inventory_movement_id');
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }
}
