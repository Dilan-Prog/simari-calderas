<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePageReview extends Model
{
    // Catálogo fijo de categorías de reseña (chips en el admin y filtros en
    // la vista pública) — mismo criterio de "lista blanca en código, no en
    // BD" ya usado para $sectionTypes en ServicePageController.
    public const CATEGORIES = [
        'resultado'      => 'Resultado',
        'puntualidad'    => 'Puntualidad',
        'documentacion'  => 'Documentación',
        'trato'          => 'Trato',
        'seguridad'      => 'Seguridad',
    ];

    protected $fillable = [
        'service_page_id', 'customer_name', 'customer_role', 'customer_company',
        'customer_city', 'customer_state', 'review_date', 'rating', 'comment',
        'categories', 'is_verified', 'photo_urls', 'business_response',
        'business_response_date', 'is_visible', 'sort_order',
    ];

    protected $casts = [
        'review_date'             => 'date',
        'business_response_date'  => 'date',
        'categories'              => 'array',
        'photo_urls'              => 'array',
        'is_verified'             => 'boolean',
        'is_visible'              => 'boolean',
        'rating'                  => 'integer',
    ];

    public function servicePage(): BelongsTo
    {
        return $this->belongsTo(ServicePage::class);
    }

    public function getCustomerInitialsAttribute(): string
    {
        $words = preg_split('/\s+/', trim($this->customer_name));

        return strtoupper(collect($words)->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode(''));
    }
}
