<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickupItem extends Model
{
    protected $fillable = ['pickup_id', 'description', 'product_id', 'quantity', 'unit', 'sales_order_id'];

    protected $casts = ['quantity' => 'decimal:2'];

    public function pickup(): BelongsTo
    {
        return $this->belongsTo(Pickup::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }
}
