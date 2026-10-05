<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class HomeSection extends Model
{
    use LogsActivity;

    protected static function logEntityType(): string
    {
        return 'home_section';
    }

    // Valores de `page` para bloques por producto (ver migración
    // add_product_blocks_to_home_sections): plantilla reutilizable ligada a N
    // productos, o sección propia de un solo producto.
    public const PAGE_PRODUCT_TEMPLATE = 'product_template';
    public const PAGE_PRODUCT_CUSTOM   = 'product_custom';

    public const ZONE_STACK   = 'stack';
    public const ZONE_SIDEBAR = 'sidebar';

    protected $fillable = ['type', 'page', 'zone', 'name', 'title', 'config', 'heading_link', 'sort_order', 'is_active'];

    protected $casts = [
        'config'       => 'array',
        'heading_link' => 'array',
        'is_active'    => 'boolean',
    ];

    public function slides()
    {
        return $this->hasMany(HomeSectionSlide::class)->orderBy('sort_order');
    }

    public function productAssignments()
    {
        return $this->hasMany(ProductSectionAssignment::class);
    }

    public function scopeProductTemplates($query)
    {
        return $query->where('page', self::PAGE_PRODUCT_TEMPLATE);
    }

    /**
     * Sustituye variables según el contexto donde se renderiza la sección:
     * con un producto, el catálogo COMPLETO de Products::VARIABLE_CATALOG
     * ({nombre_producto}, {marca}, {modelo}, {precio}, etc. — delega en
     * Products::resolveVariables() para no duplicar el catálogo); con una
     * colección, solo {coleccion}. Sin contexto (Home), las variables se
     * eliminan y se limpian espacios.
     */
    public function resolveText(?string $text, $context = null): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }

        if ($context instanceof Products) {
            return $context->resolveVariables($text);
        }

        $collection = $context instanceof Collection ? $context : null;

        $replacements = [
            '{coleccion}' => $collection?->name ?? '',
        ];

        $resolved = strtr($text, $replacements);
        $resolved = preg_replace('/\s{2,}/', ' ', $resolved);

        return trim($resolved);
    }
}
