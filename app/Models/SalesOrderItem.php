<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderItem extends Model
{
    protected $fillable = [
        'sales_order_id', 'product_id', 'product_name', 'product_sku',
        'unit', 'quantity_ordered', 'quantity_delivered', 'sort_order',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function getPendingAttribute(): int
    {
        return max(0, $this->quantity_ordered - $this->quantity_delivered);
    }
}
