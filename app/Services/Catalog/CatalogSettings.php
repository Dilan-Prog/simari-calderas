<?php

namespace App\Services\Catalog;

use App\Models\Setting;

/**
 * Acceso tipado a los ajustes del grupo "catalog" con defaults en código (por
 * si la fila aún no existe, ej. antes de correr la migración). Los valores
 * iniciales sembrados están en 2026_10_09_100200_seed_catalog_settings.php y
 * deben coincidir con DEFAULTS.
 *
 * Uso: CatalogSettings::get('last_units_threshold') => 3
 */
class CatalogSettings
{
    public const DEFAULTS = [
        'last_units_threshold'      => 3,
        'best_seller_top_percent'   => 10,
        'best_seller_days'          => 90,
        'best_seller_min_units'     => 3,
        'best_seller_min_products'  => 3,
        // 0 = "Nuevo" solo por la bandera is_new (criterio histórico); >0 = además
        // productos creados hace menos de N días.
        'new_days'                  => 0,
        'discount_min_percent'      => 1,
        'badge_color_best_seller'   => '#FF6213',
        'badge_color_discount'      => '#C62828',
        'badge_color_last_units'    => '#FFC107',
        'badge_color_new'           => '#2E7D32',
        'badge_enabled_best_seller' => true,
        'badge_enabled_discount'    => true,
        'badge_enabled_last_units'  => true,
        'badge_enabled_new'         => true,
        'fast_shipping_label'       => 'Envío en 24-48 h',
        'filter_max_visible'        => 6,
        'cache_ttl_minutes'         => 10,
    ];

    public static function get(string $key): mixed
    {
        $default = self::DEFAULTS[$key] ?? null;

        return Setting::get('catalog.' . $key, $default);
    }

    /** Todos los ajustes ya resueltos, como arreglo clave => valor. */
    public static function all(): array
    {
        $out = [];
        foreach (array_keys(self::DEFAULTS) as $key) {
            $out[$key] = self::get($key);
        }

        return $out;
    }
}
