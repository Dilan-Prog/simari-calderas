<?php

namespace App\Console\Commands;

use App\Models\ProductSalesStat;
use App\Services\Catalog\CatalogCache;
use App\Services\Catalog\CatalogSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Recalcula product_sales_stats: unidades vendidas por producto en pedidos
 * PAGADOS de la tienda dentro de la ventana catalog.best_seller_days. Alimenta
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

    protected $description = 'Recalcula las unidades vendidas por producto (pedidos pagados en la ventana configurada) para las etiquetas y el orden del catálogo';

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
            ->map(fn ($value) => max(0, (int) $value))
            ->filter(fn (int $value) => $value > 0);

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
