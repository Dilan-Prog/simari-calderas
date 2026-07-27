<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeSection extends Model
{
    protected $fillable = ['type', 'page', 'title', 'config', 'sort_order', 'is_active'];

    protected $casts = [
        'config'    => 'array',
        'is_active' => 'boolean',
    ];

    public function slides()
    {
        return $this->hasMany(HomeSectionSlide::class)->orderBy('sort_order');
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
