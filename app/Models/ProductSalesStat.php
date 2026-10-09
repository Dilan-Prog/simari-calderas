<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Unidades vendidas (pedidos pagados de la tienda) de un producto en la
 * ventana configurada (catalog.best_seller_days). Se rellena con el comando
 * `catalog:refresh-sales`; sin fila = 0 unidades.
 */
class ProductSalesStat extends Model
{
    public $timestamps = false;

    protected $fillable = ['product_id', 'units', 'computed_at'];

    protected $casts = [
        'units'       => 'integer',
        'computed_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}
