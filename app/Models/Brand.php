<?php

namespace App\Models;

use App\Support\UploadPath;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Brand extends Model
{
    use HasFactory, LogsActivity;

    protected static function logEntityType(): string
    {
        return 'brand';
    }

    protected $fillable = [
        'name', 'slug', 'description', 'logo_url', 'logo_alt', 'redirect_url', 'is_active', 'show_on_site',
        'seo_title', 'seo_description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_on_site' => 'boolean',
    ];

    /** @var bool|null memo por petición de supportsSiteVisibility() */
    private static ?bool $hasShowOnSite = null;

    /**
     * ¿Ya existe la columna show_on_site? Hostinger no corre migraciones solo: entre subir el código y migrar,
     * la tienda no debe romperse. Se cachea 10 min para no consultar el esquema en cada visita.
     */
    public static function supportsSiteVisibility(): bool
    {
        return self::$hasShowOnSite ??= (bool) Cache::remember(
            'brands.has_show_on_site',
            600,
            fn () => Schema::hasColumn('brands', 'show_on_site')
        );
    }

    /** Marcas que se muestran en la tienda pública: activas y con "Mostrar en sitio web" encendido. */
    public function scopeVisibleOnSite($query)
    {
        $table = $query->getModel()->getTable();
        $query->where($table . '.is_active', true);

        return self::supportsSiteVisibility() ? $query->where($table . '.show_on_site', true) : $query;
    }

    public function getLogoUrlAttribute(?string $value): ?string
    {
        return UploadPath::url($value);
    }

    public function products()
    {
        return $this->hasMany(Products::class, 'brand_id');
    }
}
