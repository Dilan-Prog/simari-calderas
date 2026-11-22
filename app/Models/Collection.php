<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Collection extends Model
{
    use LogsActivity;

    protected static function logEntityType(): string
    {
        return 'collection';
    }

    protected $fillable = [
        'name', 'slug', 'description', 'type', 'match_type',
        'image_url', 'image_alt', 'sort_order', 'is_active', 'show_products',
        'seo_title', 'seo_description', 'og_image_url', 'og_image_alt', 'faqs',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'show_products' => 'boolean',
        'faqs'          => 'array',
    ];

    /** @var bool|null memo por petición de supportsShowProducts() */
    private static ?bool $hasShowProducts = null;

    /**
     * ¿Ya existe la columna show_products? Hostinger no corre migraciones solo: entre subir el código y
     * migrar, ni la tienda ni el admin deben romperse. Se cachea 10 min (igual que Brand::supportsSiteVisibility).
     */
    public static function supportsShowProducts(): bool
    {
        return self::$hasShowProducts ??= (bool) Cache::remember(
            'collections.has_show_products',
            600,
            fn () => Schema::hasColumn('collections', 'show_products')
        );
    }

    /** ¿La página pública muestra contador y cuadrícula de productos? (true si la columna aún no existe) */
    public function showsProducts(): bool
    {
        return $this->show_products ?? true;
    }

    public function rules()
    {
        return $this->hasMany(CollectionRule::class);
    }

    /** Bloques propios de la página pública (editor en vivo). */
    public function sections()
    {
        return $this->hasMany(CollectionSection::class)->orderBy('sort_order');
    }

    public function manualProducts()
    {
        return $this->belongsToMany(Products::class, 'collection_products', 'collection_id', 'product_id')
            ->withPivot('sort_order')
            ->orderBy('collection_products.sort_order');
    }

    /**
     * Builder (sin ejecutar) de los productos de la colección — selección
     * manual (orden del pivote) o reglas de una colección automática.
     * Siempre respeta is_active + publish_on_website (visibilidad pública).
     * Devolver el builder permite paginar en la página pública.
     */
    public function productsQuery()
    {
        if ($this->type === 'manual') {
            return $this->manualProducts()
                ->where('is_active', true)
                ->where('publish_on_website', true)
                ->with(['images' => fn ($q) => $q->orderBy('sort_order')]);
        }

        $query = Products::query()
            ->where('is_active', true)
            ->where('publish_on_website', true)
            ->with(['images' => fn ($q) => $q->orderBy('sort_order')]);

        $rules = $this->rules;

        if ($rules->isNotEmpty()) {
            if ($this->match_type === 'any') {
                $query->where(function ($q) use ($rules) {
                    foreach ($rules as $rule) {
                        $q->orWhere(fn ($q2) => static::applyRule($q2, $rule));
                    }
                });
            } else {
                foreach ($rules as $rule) {
                    $query->where(fn ($q) => static::applyRule($q, $rule));
                }
            }
        }

        return $query;
    }

    /**
     * Colección ejecutada (con límite opcional) — la usan los carruseles
     * de secciones. La página pública usa productsQuery()->paginate().
     */
    public function resolveProducts(?int $limit = null)
    {
        $query = $this->productsQuery();

        return $limit ? $query->limit($limit)->get() : $query->get();
    }

    protected static function applyRule($query, CollectionRule $rule): void
    {
        match ($rule->field) {
            'tag' => $query->whereJsonContains('tags', $rule->value),
            // Principal o adicional (product_category_map), sin descendientes.
            'category_id' => $query->inCategories([(int) $rule->value]),
            'brand_id' => $query->where('brand_id', (int) $rule->value),
            'price' => match ($rule->operator) {
                'greater_than' => $query->where('price', '>', (float) $rule->value),
                'less_than' => $query->where('price', '<', (float) $rule->value),
                default => $query->where('price', '=', (float) $rule->value),
            },
            default => null,
        };
    }
}
