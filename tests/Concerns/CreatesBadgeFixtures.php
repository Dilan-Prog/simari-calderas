<?php

namespace Tests\Concerns;

use App\Models\Category;
use App\Models\ProductSalesStat;
use App\Models\Products;
use App\Models\Setting;
use App\Services\Catalog\BadgeResolver;
use App\Services\Catalog\CatalogCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Datos y limpieza de estado para las pruebas de etiquetas (BadgeResolver),
 * del comando catalog:refresh-sales y de la tarjeta de producto. Se asume
 * RefreshDatabase en la clase que lo usa. Los ajustes catalog.* ya los siembra
 * la migración 2026_10_09_100200_seed_catalog_settings.
 */
trait CreatesBadgeFixtures
{
    /** Limpia las cachés estáticas/memoizadas que sobreviven entre pruebas. */
    protected function resetCatalogState(): void
    {
        Cache::flush();
        CatalogCache::flushMemo();
        BadgeResolver::flushMemo();

        $property = new \ReflectionProperty(Setting::class, 'cache');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }

    /** Cambia un ajuste catalog.* (la fila ya existe por la migración) y limpia memos. */
    protected function setCatalog(string $key, mixed $value): void
    {
        Setting::set('catalog.' . $key, $value);
        CatalogCache::bump();
        BadgeResolver::flushMemo();
    }

    protected function makeCategory(array $overrides = []): Category
    {
        return Category::create(array_merge([
            'name'      => 'Categoria',
            'slug'      => 'cat-' . uniqid(),
            'is_active' => true,
        ], $overrides));
    }

    /**
     * Producto publicado. Acepta además los campos no asignables en masa
     * (availability, created_at) y los aplica con forceFill.
     */
    protected function makeProduct(array $overrides = []): Products
    {
        $forced = [];
        foreach (['availability', 'created_at'] as $key) {
            if (array_key_exists($key, $overrides)) {
                $forced[$key] = $overrides[$key];
                unset($overrides[$key]);
            }
        }

        $product = Products::create(array_merge([
            'category_id'        => $this->makeCategory()->id,
            'name'               => 'Producto',
            'slug'               => 'prod-' . uniqid(),
            'sku'                => 'SKU-' . uniqid(),
            'price'              => 100,
            'price_includes_tax' => false,
            'currency'           => 'MXN',
            'stock'              => 50,
            'is_active'          => true,
            'publish_on_website' => true,
        ], $overrides));

        if ($forced) {
            $product->forceFill($forced)->save();
        }

        return $product->refresh();
    }

    /** Fija las unidades vendidas de un producto en product_sales_stats. */
    protected function setSales(Products $product, int $units): void
    {
        ProductSalesStat::updateOrCreate(
            ['product_id' => $product->id],
            ['units' => $units, 'computed_at' => now()]
        );
    }

    /**
     * Crea una categoría con $total productos publicados donde $winner es el
     * más vendido (10 unidades) y otros dos tienen ventas (5 y 4): cumple los
     * mínimos por defecto (3 unidades, 3 productos con ventas).
     *
     * @return array{category: Category, winner: Products, others: array<int, Products>}
     */
    protected function seedBestSellerCategory(int $total = 10): array
    {
        $category = $this->makeCategory();
        $winner = $this->makeProduct(['category_id' => $category->id]);
        $others = [];
        for ($i = 1; $i < $total; $i++) {
            $others[] = $this->makeProduct(['category_id' => $category->id]);
        }

        $this->setSales($winner, 10);
        $this->setSales($others[0], 5);
        $this->setSales($others[1], 4);
        CatalogCache::bump();
        BadgeResolver::flushMemo();

        return compact('category', 'winner', 'others');
    }

    /** Inserta un pedido de la tienda con una partida; devuelve el id del pedido. */
    protected function makeOrder(string $status, Products $product, int $quantity, $createdAt = null): int
    {
        $createdAt ??= now();

        $orderId = DB::table('store_orders')->insertGetId([
            'order_number'           => 'T-' . uniqid(),
            'contact_name'           => 'Cliente',
            'contact_email'          => 'cliente@example.com',
            'contact_phone'          => '5555555555',
            'shipping_address_line1' => 'Calle 1',
            'shipping_city'          => 'Ciudad',
            'shipping_state'         => 'Estado',
            'shipping_postal_code'   => '00000',
            'subtotal'               => 100,
            'tax_total'              => 16,
            'total'                  => 116,
            'status'                 => $status,
            'created_at'             => $createdAt,
            'updated_at'             => $createdAt,
        ]);

        $this->addOrderItem($orderId, $product, $quantity, $createdAt);

        return $orderId;
    }

    protected function addOrderItem(int $orderId, Products $product, int $quantity, $createdAt = null): void
    {
        $createdAt ??= now();

        DB::table('store_order_items')->insert([
            'store_order_id' => $orderId,
            'product_id'     => $product->id,
            'product_name'   => $product->name,
            'product_sku'    => $product->sku,
            'quantity'       => $quantity,
            'unit_price'     => 100,
            'line_total'     => 100 * $quantity,
            'created_at'     => $createdAt,
            'updated_at'     => $createdAt,
        ]);
    }
}
