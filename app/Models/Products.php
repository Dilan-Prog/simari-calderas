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
        'is_new',
        'is_recommended',
        'seo_title',
        'seo_description',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'is_featured'     => 'boolean',
        'is_new'          => 'boolean',
        'is_recommended'  => 'boolean',
        'price'       => 'decimal:2',
        'cost'        => 'decimal:2',
        'compare_price' => 'decimal:2',
    ];

    // Belongs to a category
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    // Belongs to a brand
    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function getCoverImageUrlAttribute(?string $value): ?string
    {
        if (!$value) return null;
        if (str_starts_with($value, 'http')) return $value;
        return asset($value);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id')->orderBy('sort_order');
    }

    // Many-to-many with suppliers
    public function suppliers()
    {
        return $this->belongsToMany(
            Supplier::class,
            'suppliers_products',
            'product_id',
            'supplier_id'
        )->withPivot('cost', 'lead_time_days', 'is_primary');
    }
}
