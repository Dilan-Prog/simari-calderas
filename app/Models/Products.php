<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',
        'sku',
        'short_description',
        'description',
        'price',
        'compare_price',
        'cost',
        'stock',
        'weight',
        'height',
        'width',
        'length',
        'cover_image_url',
        'is_active',
        'is_featured',
        'seo_title',
        'seo_description',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'is_featured' => 'boolean',
        'price'       => 'decimal:2',
        'cost'        => 'decimal:2',
    ];

    // Relationship
    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'suppliers_products', 'product_id', 'supplier_id')
            ->withPivot('cost', 'lead_time_days', 'is_primary');
    }

    // Relationship — product belongs to a category
    // public function category()
    // {
    //     return $this->belongsTo(Category::class);
    // }

    // Relationship — product belongs to a brand
    // public function brand()
    // {
    //     return $this->belongsTo(Brand::class);
    // }
}
