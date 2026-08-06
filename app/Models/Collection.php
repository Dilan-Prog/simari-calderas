<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    use LogsActivity;

    protected static function logEntityType(): string
    {
        return 'collection';
    }

    protected $fillable = [
        'name', 'slug', 'description', 'type', 'match_type',
        'image_url', 'sort_order', 'is_active',
        'seo_title', 'seo_description', 'og_image_url', 'faqs',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'faqs'      => 'array',
    ];

    public function rules()
    {
        return $this->hasMany(CollectionRule::class);
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
            'category_id' => $query->where('category_id', (int) $rule->value),
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
