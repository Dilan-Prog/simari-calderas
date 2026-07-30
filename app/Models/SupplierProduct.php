<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class SupplierProduct extends Pivot
{
    protected $table = 'suppliers_products';

    public $incrementing = true;

    protected $fillable = [
        'supplier_id',
        'product_id',
        'sku',
        'cost',
        'lead_time_days',
        'is_primary',
    ];

    protected $casts = [
        'cost'           => 'decimal:2',
        'lead_time_days' => 'integer',
        'is_primary'     => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    // Garantiza que a lo más un proveedor quede marcado como "principal"
    // por producto, sin importar desde qué lado de la UI se guardó.
    public static function demoteOtherPrimaries(int $productId, ?int $exceptPivotId = null): void
    {
        static::where('product_id', $productId)
            ->when($exceptPivotId, fn ($q) => $q->where('id', '!=', $exceptPivotId))
            ->update(['is_primary' => false]);
    }
}
