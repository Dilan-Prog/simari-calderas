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
        'business_response_date', 'is_visible', 'sort_order', 'service_rating_id', 'source',
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

    /**
     * Reseñas que muestra el bloque "rating_reviews" según su config:
     * mode 'selected' = solo review_ids, en ese orden (ocultas o borradas se
     * ignoran); cualquier otro valor = todas las visibles por sort_order.
     */
    public static function forBlock(ServicePage $servicePage, array $config = [])
    {
        $visible = $servicePage->visibleReviews()->get();

        if (($config['mode'] ?? 'auto') !== 'selected') {
            return $visible;
        }

        $byId = $visible->keyBy('id');

        return collect((array) ($config['review_ids'] ?? []))
            ->map(fn ($id) => $byId->get((int) $id))
            ->filter()
            ->unique('id')
            ->values();
    }

    /**
     * Promedio por aspecto de la encuesta (Limpieza, Puntualidad…) de las
     * opiniones de cliente entre las reseñas dadas. Solo cuenta las respuestas
     * `positive`; las "a mejorar" son internas (admin).
     *
     * @return \Illuminate\Support\Collection  [{label, average, count}]
     */
    public static function breakdownFor($reviews)
    {
        $ratingIds = collect($reviews)->pluck('service_rating_id')->filter()->unique()->values();
        if ($ratingIds->isEmpty()) {
            return collect();
        }

        return ServiceRatingAnswer::whereIn('service_rating_id', $ratingIds)
            ->where('kind', 'positive') // las opciones "a mejorar" nunca se publican como promedio
            ->orderBy('id')
            ->get()
            ->groupBy('question_label')
            ->map(fn ($g, $label) => [
                'label'   => $label,
                'average' => round($g->avg('score'), 1),
                'count'   => $g->count(),
            ])
            ->values();
    }

    public function getCustomerInitialsAttribute(): string
    {
        $words = preg_split('/\s+/', trim($this->customer_name));

        return strtoupper(collect($words)->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode(''));
    }
}
