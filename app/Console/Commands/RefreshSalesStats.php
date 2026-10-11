<?php

namespace App\Console\Commands;

use App\Models\ProductSalesStat;
use App\Services\Catalog\CatalogCache;
use App\Services\Catalog\CatalogSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Recalcula product_sales_stats: unidades vendidas por producto en pedidos
 * PAGADOS de la tienda y en cotizaciones ACEPTADAS dentro de la ventana catalog.best_seller_days. Alimenta
 * la etiqueta "Más vendido" (BadgeResolver) y el orden "vendidos" del catálogo.
 * Se programa cada hora en App\Console\Kernel.
 */
class RefreshSalesStats extends Command
{
    /**
     * Estatus de StoreOrder que cuentan como venta. Duplicado intencional de
     * StoreOrderController::PAGADAS_STATUSES (privada ahí; StoreOrderStatus no
     * expone una lista equivalente): pagado, en_preparacion, enviado, entregado.
     */
    public const PAID_STATUSES = ['pagado', 'en_preparacion', 'enviado', 'entregado'];

    protected $signature = 'catalog:refresh-sales {--dry-run : Calcula y muestra el resumen sin escribir en la base de datos}';

    protected $description = 'Recalcula las unidades vendidas por producto (pedidos pagados de la tienda y cotizaciones aceptadas en la ventana configurada) para las etiquetas y el orden del catálogo';

    public function handle(): int
    {
        $days = max(1, (int) CatalogSettings::get('best_seller_days'));
        $since = now()->subDays($days);

        $units = DB::table('store_order_items')
            ->join('store_orders', 'store_orders.id', '=', 'store_order_items.store_order_id')
            ->whereIn('store_orders.status', self::PAID_STATUSES)
            ->where('store_orders.created_at', '>=', $since)
            ->groupBy('store_order_items.product_id')
            ->selectRaw('store_order_items.product_id as product_id, SUM(store_order_items.quantity) as units')
            ->pluck('units', 'product_id')
            ->map(fn ($value) => max(0, (int) $value));

        // Cotizaciones ACEPTADAS dentro de la misma ventana: venta B2B por cotizacion
        // (las mismas que alimentan "+N unidades vendidas" en la ficha, ver QuoteSales).
        $quoteUnits = DB::table('quote_items')
            ->join('quotes', 'quotes.id', '=', 'quote_items.quote_id')
            ->where('quotes.status', 'accepted')
            ->whereNotNull('quote_items.product_id')
            ->whereRaw('COALESCE(quotes.accepted_at, quotes.updated_at) >= ?', [$since])
            ->groupBy('quote_items.product_id')
            ->selectRaw('quote_items.product_id as product_id, SUM(quote_items.quantity) as units')
            ->pluck('units', 'product_id');

        foreach ($quoteUnits as $productId => $qty) {
            $units[$productId] = ($units[$productId] ?? 0) + max(0, (int) $qty);
        }

        $units = $units->filter(fn ($value) => $value > 0);

        $existing = ProductSalesStat::query()->pluck('product_id')->all();
        $obsolete = array_values(array_diff($existing, $units->keys()->all()));

        if ($this->option('dry-run')) {
            $this->info("[dry-run] Ventana: {$days} días. Productos con ventas: {$units->count()}. Filas obsoletas a borrar: " . count($obsolete) . '. No se escribió nada.');

            return self::SUCCESS;
        }

        $now = now();

        DB::transaction(function () use ($units, $obsolete, $now) {
            $rows = $units->map(fn (int $total, $productId) => [
                'product_id'  => (int) $productId,
                'units'       => $total,
                'computed_at' => $now,
            ])->values();

            foreach ($rows->chunk(500) as $chunk) {
                ProductSalesStat::upsert($chunk->all(), ['product_id'], ['units', 'computed_at']);
            }

            foreach (array_chunk($obsolete, 500) as $chunk) {
                ProductSalesStat::whereIn('product_id', $chunk)->delete();
            }
        });

        CatalogCache::bump();

        $this->info("Ventas actualizadas (ventana de {$days} días): {$units->count()} productos con ventas, " . count($obsolete) . ' filas obsoletas eliminadas.');

        return self::SUCCESS;
    }
}
