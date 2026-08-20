<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

/**
 * Regla de envío a nivel Marca o Categoría (nunca ambas, nunca ninguna —
 * validado en el controller). Complementa (no reemplaza) el shipping_cost /
 * free_shipping_threshold propio de cada Products: si el producto tiene su
 * propio valor, ese gana; si no, se busca la regla que lo cubra (marca
 * primero, luego categoría). Ver Cart::shippingTotal().
 */
class ShippingRule extends Model
{
    use LogsActivity;

    protected static function logEntityType(): string
    {
        return 'shipping_rule';
    }

    protected $fillable = [
        'brand_id', 'category_id', 'shipping_cost', 'free_shipping_threshold', 'is_active',
    ];

    protected $casts = [
        'shipping_cost' => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
