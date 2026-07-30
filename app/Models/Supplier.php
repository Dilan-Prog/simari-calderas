<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_name',
        'company_name',
        'phone',
        'email',
        'address',
        'rfc',
        'tipo_persona',
        'website',
        'status',
        'payment_terms',
        'notes',
        'rating_quality',
        'rating_compliance',
    ];

    // Relationships

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }
    public function products()
    {
        return $this->belongsToMany(Products::class, 'suppliers_products', 'supplier_id', 'product_id')
            ->withPivot('id', 'sku', 'cost', 'lead_time_days', 'is_primary')
            ->using(SupplierProduct::class);
    }

    // Solo cuentan órdenes de compra aceptadas — refleja compras reales
    // confirmadas, no borradores ni rechazadas.
    private function acceptedItemsQuery()
    {
        return PurchaseOrderItem::whereHas(
            'purchaseOrder',
            fn ($q) => $q->where('supplier_id', $this->id)->where('status', 'accepted')
        );
    }

    public function totalPurchased(): float
    {
        return (float) $this->acceptedItemsQuery()->sum('line_total');
    }

    // [product_id => ['quantity' => int, 'total' => float]] — las líneas
    // libres (sin product_id) cuentan en totalPurchased() pero no aquí,
    // porque no hay producto al que atribuirlas.
    public function purchasedByProduct(): \Illuminate\Support\Collection
    {
        return $this->acceptedItemsQuery()
            ->whereNotNull('product_id')
            ->get(['product_id', 'quantity', 'line_total'])
            ->groupBy('product_id')
            ->map(fn ($rows) => [
                'quantity' => (int) $rows->sum('quantity'),
                'total'    => (float) $rows->sum('line_total'),
            ]);
    }
}
