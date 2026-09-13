<?php

namespace App\Models;

use App\Support\UploadPath;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServicePage extends Model
{
    use LogsActivity;

    // page_type — arquitectura de 3 niveles bajo /servicios/:
    // hub (/servicios), category (/servicios/{slug}), service hoja
    // (/servicios/{categoria}/{slug}, o /servicio/{slug} si no tiene padre —
    // ver ShopServicePageController para el detalle de resolución de URL).
    public const TYPE_HUB = 'hub';
    public const TYPE_CATEGORY = 'category';
    public const TYPE_SERVICE = 'service';

    protected static function logEntityType(): string
    {
        return 'service_page';
    }

    protected $fillable = [
        'name', 'slug', 'page_type', 'parent_id', 'short_description', 'description',
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ServicePage::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ServicePage::class, 'parent_id')->orderBy('sort_order');
    }

    public function activeChildren(): HasMany
    {
        return $this->children()->where('is_active', true);
    }

    /**
     * Cadena de ancestros de raíz a hoja (sin incluir $this), para
     * breadcrumbs y para resolver la URL pública real. Como esta jerarquía
     * es de máximo 2 niveles de profundidad (hub → categoría → servicio),
     * un loop simple basta — no hace falta CTE recursivo.
     */
    public function ancestors(): array
    {
        $chain = [];
        $node = $this->parent;

        while ($node && count($chain) < 5) {
            array_unshift($chain, $node);
            $node = $node->parent;
        }

        return $chain;
    }

    /**
     * URL pública real según el nivel:
     *  - hub                              → /servicios
     *  - category (nivel 2)               → /servicios/{slug}
     *  - service con padre category       → /servicios/{categoria}/{slug}
     *  - service sin padre (legacy plano) → /servicio/{slug}
     */
    public function publicPath(): string
    {
        if ($this->page_type === self::TYPE_HUB) {
            return '/servicios';
        }

        if ($this->page_type === self::TYPE_CATEGORY) {
            return '/servicios/' . $this->slug;
        }

        if ($this->parent && $this->parent->page_type === self::TYPE_CATEGORY) {
            return '/servicios/' . $this->parent->slug . '/' . $this->slug;
        }

        return '/servicio/' . $this->slug;
    }

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
     * Redirige 301 la URL vieja cuando cambia el slug, el padre o el
     * page_type (los 3 determinan la URL pública en la jerarquía de 3
     * niveles — ej. "promover" un servicio plano a categoría cambia slug Y
     * page_type en la misma edición). Se usa `updated` (no `saved`) para
     * poder leer con confianza los valores anteriores vía getOriginal()
     * antes de que se pierdan.
     */
    protected static function booted(): void
    {
        static::updated(function (ServicePage $service) {
            $relevant = ['slug', 'parent_id', 'page_type'];
            if (!collect($relevant)->contains(fn ($attr) => $service->wasChanged($attr))) {
                return;
            }

            // Reconstruye la ruta vieja con TODOS los atributos originales
            // que afectan publicPath(), no solo el que cambió.
            $old = $service->replicate();
            $old->slug = $service->getOriginal('slug');
            $old->parent_id = $service->getOriginal('parent_id');
            $old->page_type = $service->getOriginal('page_type');
            $old->setRelation('parent', $old->parent_id ? static::find($old->parent_id) : null);

            $oldPath = $old->publicPath();
            $newPath = $service->publicPath();

            if ($oldPath !== $newPath) {
                Redirect::record($oldPath, $newPath);
            }

            // Si esta página es (o era) una categoría y cambió su slug o su
            // page_type, sus hijos directos (servicios hoja) recalculan
            // publicPath() en base al slug/tipo NUEVO del padre en cuanto se
            // les consulte — pero como el evento `updated` de ESTE modelo no
            // dispara el de los hijos, sus URLs viejas quedarían huérfanas
            // (404 real, sin redirect) si no se reconstruyen aquí también.
            $slugOrTypeChanged = $service->wasChanged('slug') || $service->wasChanged('page_type');
            $wasOrIsCategory = $old->page_type === self::TYPE_CATEGORY || $service->page_type === self::TYPE_CATEGORY;

            if ($slugOrTypeChanged && $wasOrIsCategory) {
                static::recordChildRedirects($service, $old);
            }
        });

        static::deleting(function (ServicePage $service) {
            // parent_id tiene nullOnDelete() a nivel de BD: al borrar una
            // categoría, sus hijos sobreviven pero quedan sin padre en el
            // mismo UPDATE de la FK, sin disparar ningún evento Eloquent en
            // ellos. Sin este hook, esas URLs anidadas quedarían huérfanas
            // (404 real) en el momento exacto del borrado.
            if ($service->page_type === self::TYPE_CATEGORY) {
                static::recordChildRedirects($service, $service, forceOrphan: true);
            }
        });
    }

    /**
     * Registra un Redirect por cada hijo directo (tipo service) de $category
     * cuya URL pública cambia porque el propio $category cambió de slug o de
     * page_type — comparando la URL que tenía cada hijo bajo $old (el estado
     * anterior de $category) contra la que tiene ahora. Con $forceOrphan=true
     * (caso borrado), la URL "nueva" se calcula asumiendo que el hijo se
     * queda sin padre, ya que en ese momento $category todavía existe en BD.
     */
    private static function recordChildRedirects(ServicePage $category, ServicePage $old, bool $forceOrphan = false): void
    {
        $category->children()->where('page_type', self::TYPE_SERVICE)->get()->each(function (ServicePage $child) use ($old, $forceOrphan) {
            $oldChildClone = $child->replicate();
            $oldChildClone->setRelation('parent', $old);
            $oldChildPath = $oldChildClone->publicPath();

            if ($forceOrphan) {
                $newChildClone = $child->replicate();
                $newChildClone->parent_id = null;
                $newChildClone->setRelation('parent', null);
                $newChildPath = $newChildClone->publicPath();
            } else {
                $newChildPath = $child->publicPath();
            }

            if ($oldChildPath !== $newChildPath) {
                Redirect::record($oldChildPath, $newChildPath);
            }
        });
    }
}
