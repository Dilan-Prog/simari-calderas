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
        'website',
        'status',
        'payment_terms',
        'notes',
        'rating_quality',
        'rating_compliance',
    ];

    // Relationships
    public function products()
    {
        return $this->belongsToMany(Products::class, 'suppliers_products', 'supplier_id', 'product_id')
            ->withPivot('cost', 'lead_time_days', 'is_primary');
    }
}
