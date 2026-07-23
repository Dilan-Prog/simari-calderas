<?php

namespace App\Models;

use App\Support\UploadPath;
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
        // FIX (reported bug): 'model' has a real column and a name="model"
        // input in both forms, but was never in $fillable nor assigned in
        // the controller — it was silently discarded on every save.
        'model',
        'supplier_sku',
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
        // Independiente de is_active: controla si el producto se muestra en
        // el futuro catálogo público del sitio web (aún no conectado).
        'publish_on_website',
        'seo_title',
        'seo_description',
        // FIX BUG 3: tags column added via
        // 2026_07_13_195742_add_tags_and_seo_extra_fields_to_products_table.
        'tags',
        // FIX BUG 5: seo_keywords + Open Graph columns added in the same
        // migration.
        'seo_keywords',
        'og_title',
        'og_description',
        'og_image',
        // FIX BUG 9: currency + stock_unit columns added via
        // 2026_07_13_201018_add_currency_and_stock_unit_to_products_table.
        'currency',
        'stock_unit',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'is_featured'     => 'boolean',
        'is_new'          => 'boolean',
        'is_recommended'  => 'boolean',
        'publish_on_website' => 'boolean',
        'price'       => 'decimal:2',
        'cost'        => 'decimal:2',
        'compare_price' => 'decimal:2',
        // FIX BUG 3: cast tags to a PHP array automatically so the edit
        // view can read $product->tags directly without manual json_decode.
        'tags'        => 'array',
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
        return UploadPath::url($value);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id')->orderBy('sort_order');
    }

    // FIX (Documentación tab): added so the "Documentación" panel can save
    // and recover the 6 document uploads (ficha técnica, manual, catálogo,
    // certificación, garantía, adicional) — this relation and the
    // ProductDocument model didn't exist before; the uploads were purely
    // decorative (no name= attribute, no backend logic at all).
    public function documents()
    {
        return $this->hasMany(ProductDocument::class, 'product_id');
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

    // FIX BUG 2: Added so destroy() can check for blocking purchase order
    // items before deleting — purchase_order_items.product_id has an
    // onDelete('restrict') FK, so deleting a referenced product previously
    // crashed with an uncaught QueryException.
    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'product_id');
    }

    // FIX BUG 2: Added so destroy() can check for blocking planned service
    // materials before deleting — service_materials_planned.product_id has
    // an onDelete('restrict') FK, same crash risk as purchaseOrderItems().
    public function serviceMaterialPlans()
    {
        return $this->hasMany(ServiceMaterialPlanned::class, 'product_id');
    }
}
