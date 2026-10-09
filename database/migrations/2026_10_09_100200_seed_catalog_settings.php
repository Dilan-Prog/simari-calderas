<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Criterios configurables del catálogo y de las etiquetas de producto (grupo
 * "catalog"). Mismo patrón que seed_pool_calculator_settings. SettingController
 * solo edita claves YA existentes, por eso se siembran aquí. Los defaults están
 * duplicados en App\Services\Catalog\CatalogSettings::DEFAULTS (fuente para el
 * código cuando la fila aún no existe).
 */
return new class extends Migration
{
    private function rows(): array
    {
        return [
            ['catalog.last_units_threshold',    '3',               'integer'],
            ['catalog.best_seller_top_percent', '10',              'decimal'],
            ['catalog.best_seller_days',        '90',              'integer'],
            ['catalog.best_seller_min_units',   '3',               'integer'],
            ['catalog.best_seller_min_products', '3',              'integer'],
            ['catalog.new_days',                '0',               'integer'],
            ['catalog.discount_min_percent',    '1',               'integer'],
            ['catalog.badge_color_best_seller', '#FF6213',         'string'],
            ['catalog.badge_color_discount',    '#C62828',         'string'],
            ['catalog.badge_color_last_units',  '#FFC107',         'string'],
            ['catalog.badge_color_new',         '#2E7D32',         'string'],
            ['catalog.badge_enabled_best_seller', '1',             'boolean'],
            ['catalog.badge_enabled_discount',  '1',               'boolean'],
            ['catalog.badge_enabled_last_units', '1',              'boolean'],
            ['catalog.badge_enabled_new',       '1',               'boolean'],
            ['catalog.fast_shipping_label',     'Envío en 24-48 h', 'string'],
            ['catalog.filter_max_visible',      '6',               'integer'],
            ['catalog.cache_ttl_minutes',       '10',              'integer'],
        ];
    }

    public function up(): void
    {
        foreach ($this->rows() as [$key, $value, $type]) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value'              => $value,
                    'type'               => $type,
                    'group_name'         => 'catalog',
                    'is_public'          => false,
                    'updated_by_user_id' => null,
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('group_name', 'catalog')->delete();
    }
};
