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
     * {categoria}/{marca} con un producto, {coleccion} con una colección.
     * Sin contexto (Home), las variables se eliminan y se limpian espacios.
     */
    public function resolveText(?string $text, $context = null): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }

        $product    = $context instanceof Products ? $context : null;
        $collection = $context instanceof Collection ? $context : null;

        $replacements = [
            '{categoria}' => $product?->category?->name ?? '',
            '{marca}'     => $product?->brand?->name ?? '',
            '{coleccion}' => $collection?->name ?? '',
        ];

        $resolved = strtr($text, $replacements);
        $resolved = preg_replace('/\s{2,}/', ' ', $resolved);

        return trim($resolved);
    }
}
