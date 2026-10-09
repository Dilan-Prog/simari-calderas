<?php

namespace App\Services\Catalog;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Versión de la caché del catálogo. Todo lo cacheado del catálogo (filas de
 * CatalogIndex, ranking de BadgeResolver) guarda o compone su clave con
 * version(); bump() la invalida de golpe (el driver "file" no soporta tags).
 *
 * Quién llama a bump(): los listeners registrados en AppServiceProvider
 * (Products, Category, Brand, ShippingRule, CategoryFilterGroup/Option y
 * Setting con prefijo ecommerce./catalog.) y el comando catalog:refresh-sales.
 */
class CatalogCache
{
    private const KEY = 'catalog.version';

    private static ?string $memo = null;

    public static function version(): string
    {
        return self::$memo ??= (string) Cache::rememberForever(self::KEY, fn () => (string) Str::uuid());
    }

    public static function bump(): void
    {
        self::$memo = (string) Str::uuid();
        Cache::forever(self::KEY, self::$memo);
    }

    /** Solo para pruebas: olvida la versión memoizada en este proceso. */
    public static function flushMemo(): void
    {
        self::$memo = null;
    }
}
