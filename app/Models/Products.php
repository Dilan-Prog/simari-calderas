<?php

namespace App\Models;

use App\Support\UploadPath;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    use HasFactory, LogsActivity;

    protected static function logEntityType(): string
    {
        return 'product';
    }

    /**
     * FIX (SEO redirects): a product's slug can change (edit, or the
     * {marca}/{modelo}-in-name slug bug being corrected) — sin esto la URL
     * vieja simplemente 404 sin avisar. Mismo patrón que
     * Category::booted()/cascadeSlugToDescendants(): al guardar, si el
     * slug cambió en un producto YA existente (no uno recién creado, que
     * no tiene "URL vieja" que redirigir), registra un 301 de la ruta
     * anterior a la nueva.
     */
    protected static function booted(): void
    {
        static::saved(function (Products $product) {
            if ($product->wasRecentlyCreated || !$product->wasChanged('slug')) {
                return;
            }

            $oldSlug = $product->getOriginal('slug');
            if ($oldSlug) {
                \App\Models\Redirect::record('/producto/' . $oldSlug, '/producto/' . $product->slug);
            }
        });
    }

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
        'price_includes_tax',
        'cost',
        // Cargo de envío adicional fijo para productos pesados/voluminosos.
        // null o 0 = envío estándar (comportamiento actual, sin cargo extra).
        'shipping_cost',
        'reorder_point',
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
        'canonical_url',
        // FIX BUG 9: currency + stock_unit columns added via
        // 2026_07_13_201018_add_currency_and_stock_unit_to_products_table.
        'currency',
        'stock_unit',
        // Preguntas frecuentes personalizadas por producto (modal SEO).
        'faqs',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'is_featured'     => 'boolean',
        'is_new'          => 'boolean',
        'is_recommended'  => 'boolean',
        'publish_on_website' => 'boolean',
        'price_includes_tax' => 'boolean',
        'price'       => 'decimal:2',
        'cost'        => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'compare_price' => 'decimal:2',
        // FIX BUG 3: cast tags to a PHP array automatically so the edit
        // view can read $product->tags directly without manual json_decode.
        'tags'        => 'array',
        'faqs'        => 'array',
    ];

    // Catálogo de variables disponibles en Descripción corta y larga —
    // fuente única de verdad compartida entre el menú de autocompletado del
    // admin (@json()) y resolveVariables() para que nunca se desincronicen.
    const VARIABLE_CATALOG = [
        ['key' => 'nombre_producto', 'label' => 'Nombre del producto', 'description' => 'El nombre del producto tal como está registrado.'],
        ['key' => 'marca', 'label' => 'Marca', 'description' => 'El nombre de la marca del producto.'],
        ['key' => 'modelo', 'label' => 'Modelo', 'description' => 'El modelo del producto.'],
        ['key' => 'sku', 'label' => 'SKU', 'description' => 'El SKU (código) del producto.'],
        ['key' => 'categoria', 'label' => 'Categoría', 'description' => 'El nombre de la categoría del producto.'],
        ['key' => 'categoria_padre', 'label' => 'Categoría padre', 'description' => 'El nombre de la categoría padre, si la categoría del producto tiene una.'],
        ['key' => 'precio', 'label' => 'Precio', 'description' => 'El precio actual del producto, formateado con moneda (ej. "2,099.00 MXN").'],
        ['key' => 'precio_comparacion', 'label' => 'Precio de comparación', 'description' => 'El precio de comparación (antes del descuento), si existe.'],
        ['key' => 'descuento_porcentaje', 'label' => 'Descuento (%)', 'description' => 'El porcentaje de descuento calculado, si el precio de comparación es mayor al precio actual.'],
        ['key' => 'moneda', 'label' => 'Moneda', 'description' => 'La moneda del producto (MXN, USD).'],
        ['key' => 'proveedor_sku', 'label' => 'SKU del proveedor', 'description' => 'El SKU/código que usa el proveedor para este producto.'],
        ['key' => 'existencias', 'label' => 'Existencias', 'description' => 'La cantidad en existencia del producto (útil para copy de urgencia).'],
        ['key' => 'anio_actual', 'label' => 'Año actual', 'description' => 'El año en curso (ej. "Modelo {anio_actual}").'],
    ];

    // Guarda contra recursión infinita: el nombre del producto puede a su
    // vez contener variables (ej. "{marca} Bomba de Calor"), así que
    // {nombre_producto} debe ofrecer el nombre YA resuelto — pero
    // resolverlo llama de nuevo a variableValues(), por eso esta bandera
    // evita que esa llamada anidada intente resolver el nombre otra vez.
    private bool $resolvingName = false;

    protected function variableValues(): array
    {
        $hasDiscount = $this->compare_price && $this->compare_price > $this->price;
        $discountPct = $hasDiscount ? round((1 - ((float) $this->price / (float) $this->compare_price)) * 100) : null;
        // Fijo a MXN: final_price/compare_price_in_mxn ya vienen convertidos,
        // así que mostrar "USD" junto a un número ya en pesos sería engañoso.
        $currency = 'MXN';

        $resolvedName = $this->name ?? '';
        if (!$this->resolvingName) {
            $this->resolvingName = true;
            $resolvedName = $this->resolveVariables($this->name) ?? '';
            $this->resolvingName = false;
        }

        return [
            '{nombre_producto}'      => $resolvedName,
            '{marca}'                => $this->brand?->name ?? '',
            '{modelo}'               => $this->model ?? '',
            '{sku}'                  => $this->sku ?? '',
            '{categoria}'            => $this->category?->name ?? '',
            '{categoria_padre}'      => $this->category?->parent?->name ?? '',
            '{precio}'               => $this->price !== null ? number_format($this->final_price, 2) . ' ' . $currency : '',
            '{precio_comparacion}'   => $this->compare_price ? number_format($this->compare_price_in_mxn, 2) . ' ' . $currency : '',
            '{descuento_porcentaje}' => $discountPct !== null ? (string) $discountPct : '',
            '{moneda}'               => $currency,
            '{proveedor_sku}'        => $this->primarySupplierSku() ?? '',
            '{existencias}'          => $this->stock !== null ? (string) $this->stock : '',
            '{anio_actual}'          => (string) now()->year,
        ];
    }

    /**
     * Sustituye las variables {llave} de VARIABLE_CATALOG con los datos
     * reales de este producto. Dinámico: se resuelve solo al mostrarse en
     * público, el texto guardado conserva las llaves literales.
     */
    public function resolveVariables(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }

        $resolved = strtr($text, $this->variableValues());
        $resolved = preg_replace('/\s{2,}/', ' ', $resolved);

        return trim($resolved);
    }

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

    // ── Moneda (USD → MXN) ──────────────────────────────────────────────────

    // Tipo de cambio USD→MXN vigente (global, editable en Configuración >
    // Ecommerce). Separado de ivaRate() para poder leerse/mockearse aparte.
    public static function exchangeRate(): float
    {
        return (float) Setting::get('ecommerce.usd_to_mxn_rate', 17.5);
    }

    // Convierte un monto a MXN si currency es USD; si no (incluye el default
    // histórico MXN), lo devuelve tal cual — multiplicador implícito de 1
    // para todo el catálogo existente. Acepta una tasa explícita (usada por
    // Cotizaciones/Órdenes de Compra, que tienen su propio tipo de cambio
    // editable por documento) en vez de siempre caer al valor global.
    public function convertToMxn(float $amount, ?float $rate = null): float
    {
        if ($this->currency !== 'USD') {
            return $amount;
        }

        return round($amount * ($rate ?? static::exchangeRate()), 2);
    }

    public function getPriceInMxnAttribute(): float
    {
        return $this->convertToMxn((float) $this->price);
    }

    public function getComparePriceInMxnAttribute(): ?float
    {
        return $this->compare_price !== null ? $this->convertToMxn((float) $this->compare_price) : null;
    }

    // ── IVA ────────────────────────────────────────────────────────────────

    public static function ivaRate(): float
    {
        return (float) Setting::get('ecommerce.iva_rate', 16);
    }

    // Monto de IVA implícito en este producto. Si price_includes_tax, se
    // extrae del precio capturado (que ya es el final); si no, es lo que se
    // le va a sumar encima para llegar al precio final. Opera sobre
    // price_in_mxn (no price crudo) — así la conversión de moneda ocurre
    // ANTES del IVA, tal como se decidió: convertir primero, IVA después.
    public function getIvaAmountAttribute(): float
    {
        $price = $this->price_in_mxn;
        $rate = static::ivaRate();

        if ($this->price_includes_tax) {
            return round($price - ($price / (1 + $rate / 100)), 2);
        }

        return round($price * ($rate / 100), 2);
    }

    // Precio base (sin IVA), sin importar cómo se haya capturado. Ya en MXN.
    public function getBasePriceAttribute(): float
    {
        $price = $this->price_in_mxn;

        return $this->price_includes_tax
            ? round($price - $this->iva_amount, 2)
            : round($price, 2);
    }

    // Precio final con IVA, ya en MXN.
    public function getFinalPriceAttribute(): float
    {
        $price = $this->price_in_mxn;

        return $this->price_includes_tax
            ? round($price, 2)
            : round($price + $this->iva_amount, 2);
    }

    // Precio "antes" (compare_price) sin IVA, ya en MXN — mismo criterio de
    // normalización que base_price: compare_price no tiene su propia bandera
    // de impuesto, así que se asume el mismo price_includes_tax del precio
    // principal. Usado por la tarjeta/ficha de producto para mostrar el
    // precio tachado consistente con el precio de venta (ambos sin IVA).
    public function getCompareBasePriceAttribute(): ?float
    {
        if ($this->compare_price === null) {
            return null;
        }

        $price = $this->compare_price_in_mxn;

        if (!$this->price_includes_tax) {
            return round($price, 2);
        }

        $rate = static::ivaRate();
        $ivaOnCompare = round($price - ($price / (1 + $rate / 100)), 2);

        return round($price - $ivaOnCompare, 2);
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
        )->withPivot('id', 'sku', 'cost', 'lead_time_days', 'is_primary')
         ->using(SupplierProduct::class);
    }

    public function primarySupplierSku(): ?string
    {
        return $this->suppliers()->wherePivot('is_primary', true)->first()?->pivot->sku;
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
