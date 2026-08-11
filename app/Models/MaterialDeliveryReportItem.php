<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialDeliveryReportItem extends Model
{
    protected $fillable = [
        'material_delivery_report_id', 'sales_order_item_id', 'product_name',
        'product_sku', 'unit', 'quantity_delivered_in_event', 'sort_order',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(MaterialDeliveryReport::class, 'material_delivery_report_id');
    }

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }
}
