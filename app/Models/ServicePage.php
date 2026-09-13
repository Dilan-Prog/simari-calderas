<?php

namespace App\Models;

use App\Support\UploadPath;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServicePage extends Model
{
    use LogsActivity;

    protected static function logEntityType(): string
    {
        return 'service_page';
    }

    protected $fillable = [
        'name', 'slug', 'short_description', 'description',
        'price', 'currency', 'cover_image_url', 'is_active', 'sort_order',
        'seo_title', 'seo_description', 'og_image_url', 'faqs',
        // Estadísticas de marketing editables a mano — nunca alimentan el
        // JSON-LD, ver comentario en la migración add_rating_stats_to_...
        'rating_average_displayed', 'rating_total_rated', 'rating_distribution',
        'rating_recommend_percent', 'rating_punctuality_average',
        'rating_recurring_clients', 'rating_since_year',
    ];

    protected $casts = [
        'is_active'                  => 'boolean',
        'faqs'                       => 'array',
        'price'                      => 'decimal:2',
        'rating_average_displayed'   => 'decimal:2',
        'rating_distribution'        => 'array',
        'rating_recommend_percent'   => 'decimal:2',
        'rating_punctuality_average' => 'decimal:2',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(ServiceSection::class)->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ServicePageImage::class)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ServicePageReview::class)->orderBy('sort_order');
    }

    public function visibleReviews(): HasMany
    {
        return $this->reviews()->where('is_visible', true);
    }

    public function getCoverImageUrlAttribute(?string $value): ?string
    {
        return UploadPath::url($value);
    }

    /**
     * A diferencia de Category (que propaga a descendientes), ServicePage no
     * tiene jerarquía — solo necesita redirigir su propia URL vieja cuando
     * cambia el slug. Se usa `updated` (no `saved`) para poder leer con
     * confianza el valor anterior vía getOriginal() antes de que se pierda.
     */
    protected static function booted(): void
    {
        static::updated(function (ServicePage $service) {
            if ($service->wasChanged('slug')) {
                $old = $service->getOriginal('slug');

                if ($old) {
                    Redirect::record('/servicio/' . $old, '/servicio/' . $service->slug);
                }
            }
        });
    }
}
