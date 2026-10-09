<?php

namespace App\Services\Catalog;

use App\Models\Products;

/**
 * Etiquetas de la tarjeta de producto (WP-3 lo implementa).
 *
 * for($product) devuelve como máximo 2 elementos, en orden de prioridad
 * best_seller → discount → last_units → new, cada uno:
 *   ['key' => string, 'label' => string, 'bg' => '#RRGGBB', 'fg' => '#RRGGBB']
 * (fg se elige por contraste WCAG). Respeta catalog.badge_enabled_* y los
 * umbrales de CatalogSettings. Debe ser barato por tarjeta: el set de ids
 * "más vendidos" se calcula una vez y se cachea con la clave
 * 'catalog.badges.' . CatalogCache::version().
 */
class BadgeResolver
{
    /** @return array<int, array{key:string,label:string,bg:string,fg:string}> */
    public static function for(Products $product): array
    {
        return [];
    }

    /** Ids de productos con la etiqueta "Más vendido" (top % por categoría, con mínimos). */
    public static function bestSellerIds(): array
    {
        return [];
    }
}
