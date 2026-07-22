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
     * Sustituye las variables {categoria} y {marca} por los datos del producto
     * en cuyo contexto se renderiza la sección. Sin producto (Home), las
     * variables se eliminan y se limpian los espacios sobrantes.
     */
    public function resolveText(?string $text, $product = null): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }

        $replacements = [
            '{categoria}' => $product?->category?->name ?? '',
            '{marca}'     => $product?->brand?->name ?? '',
        ];

        $resolved = strtr($text, $replacements);
        $resolved = preg_replace('/\s{2,}/', ' ', $resolved);

        return trim($resolved);
    }
}
