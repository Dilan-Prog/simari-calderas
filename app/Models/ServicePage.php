<?php

namespace App\Models;

use App\Support\UploadPath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServicePage extends Model
{
    protected $fillable = [
        'name', 'slug', 'short_description', 'description',
        'price', 'currency', 'cover_image_url', 'is_active', 'sort_order',
        'seo_title', 'seo_description', 'og_image_url', 'faqs',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'faqs'      => 'array',
        'price'     => 'decimal:2',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(ServiceSection::class)->orderBy('sort_order');
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
