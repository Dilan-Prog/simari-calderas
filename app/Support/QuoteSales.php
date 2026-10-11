<?php

namespace App\Support;

use App\Models\QuoteItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * "Unidades vendidas" de un producto = suma de las cantidades de ese producto
 * en las líneas de las cotizaciones ACEPTADAS (quotes.status = 'accepted').
 *
 * Una cotización enlaza productos del catálogo (quote_items.product_id) que se
 * van a usar en un servicio; al aceptarse, esas cantidades cuentan como
 * vendidas. Ej.: si en 10 servicios aceptados se usaron 5 controladores
 * DC1010 en total, ese producto muestra "+5 unidades vendidas". Las líneas de
 * servicio (sin product_id) y las cotizaciones en borrador/enviadas/rechazadas/
 * vencidas no cuentan.
 *
 * Una sola consulta agregada (todos los productos a la vez) se guarda en caché
 * y se invalida al guardar/borrar una cotización o una de sus líneas
 * (AppServiceProvider); el TTL cubre cambios sin evento de modelo.
 */
class QuoteSales
{
    public const STATUS = 'accepted';

    private const CACHE_KEY = 'quote_sales.units_by_product';
    private const TTL_MINUTES = 15;

    /** @var array<int,int>|null memo por request/proceso */
    private static ?array $memo = null;

    /** @return array<int,int> product_id => unidades en cotizaciones aceptadas */
    public static function unitsByProduct(): array
    {
        if (self::$memo !== null) {
            return self::$memo;
        }

        return self::$memo = Cache::remember(self::CACHE_KEY, now()->addMinutes(self::TTL_MINUTES), function () {
            return DB::table('quote_items')
                ->join('quotes', 'quotes.id', '=', 'quote_items.quote_id')
                ->where('quotes.status', self::STATUS)
                ->whereNotNull('quote_items.product_id')
                ->groupBy('quote_items.product_id')
                ->selectRaw('quote_items.product_id as product_id, SUM(quote_items.quantity) as units')
                ->pluck('units', 'product_id')
                ->map(fn ($units) => (int) $units)
                ->all();
        });
    }

    public static function unitsFor(int $productId): int
    {
        return self::unitsByProduct()[$productId] ?? 0;
    }

    /** Texto de la insignia: "+5 unidades vendidas" / "+1 unidad vendida". */
    public static function label(int $units): string
    {
        return '+' . number_format($units) . ($units === 1 ? ' unidad vendida' : ' unidades vendidas');
    }

    /** Olvida el total en caché (y el memo de este proceso). */
    public static function forget(): void
    {
        self::$memo = null;

        try {
            Cache::forget(self::CACHE_KEY);
        } catch (\Throwable $e) {
            // Sin caché disponible (migraciones/seeders): no debe romper el guardado.
        }
    }

    /** Registra los listeners de invalidación (se llama desde AppServiceProvider::boot). */
    public static function registerInvalidation(): void
    {
        $forget = fn () => self::forget();

        foreach ([\App\Models\Quote::class, QuoteItem::class] as $model) {
            $model::saved($forget);
            $model::deleted($forget);
        }
    }
}
