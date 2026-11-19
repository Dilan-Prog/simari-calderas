<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bloque del editor en vivo de una categoría (/catalogo/{slug}). Mismo
 * contrato que CollectionSection; `config.position` decide la zona pública:
 * 'top' (por defecto, bajo las migas) o 'bottom' (tras la paginación).
 */
class CategorySection extends Model
{
    public const POSITION_TOP = 'top';
    public const POSITION_BOTTOM = 'bottom';

    protected $fillable = ['category_id', 'type', 'config', 'title', 'sort_order', 'is_active'];

    protected $casts = [
        'config'    => 'array',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Zona pública del bloque: 'top' | 'bottom' (cualquier otro valor => 'top'). */
    public function position(): string
    {
        return (($this->config['position'] ?? null) === self::POSITION_BOTTOM)
            ? self::POSITION_BOTTOM
            : self::POSITION_TOP;
    }

    /**
     * Mismo mecanismo que CollectionSection::resolveText(): en el contexto de
     * una Category reemplaza {categoria} por su nombre (sin perder los demás
     * contextos, por si el bloque se pinta en otra página).
     */
    public function resolveText(?string $text, $context = null): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }

        if ($context instanceof Products) {
            return $context->resolveVariables($text);
        }

        $replacements = match (true) {
            $context instanceof Category    => ['{categoria}' => $context->name ?? ''],
            $context instanceof Collection  => ['{coleccion}' => $context->name ?? ''],
            $context instanceof ServicePage => ['{servicio}' => $context->name ?? ''],
            default => [],
        };

        $resolved = strtr($text, $replacements);
        $resolved = preg_replace('/\s{2,}/', ' ', $resolved);

        return trim($resolved);
    }
}
