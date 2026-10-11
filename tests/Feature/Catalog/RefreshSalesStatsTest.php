<?php

namespace Tests\Feature\Catalog;

use App\Models\ProductSalesStat;
use App\Services\Catalog\CatalogCache;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesBadgeFixtures;
use Tests\TestCase;

class RefreshSalesStatsTest extends TestCase
{
    use RefreshDatabase;
    use CreatesBadgeFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetCatalogState();
    }

    private function units(): array
    {
        return ProductSalesStat::orderBy('product_id')->pluck('units', 'product_id')->map(fn ($u) => (int) $u)->all();
    }

    public function test_cuenta_solo_pedidos_pagados(): void
    {
        $paid = [];
        foreach (['pagado', 'en_preparacion', 'enviado', 'entregado'] as $status) {
            $product = $this->makeProduct();
            $this->makeOrder($status, $product, 2);
            $paid[$product->id] = 2;
        }

        $ignored = [];
        foreach (['pendiente_pago', 'cancelado', 'reembolsado', 'pago_parcial'] as $status) {
            $product = $this->makeProduct();
            $this->makeOrder($status, $product, 7);
            $ignored[] = $product->id;
        }

        $this->artisan('catalog:refresh-sales')->assertSuccessful();

        $this->assertSame($paid, $this->units());
        foreach ($ignored as $id) {
            $this->assertDatabaseMissing('product_sales_stats', ['product_id' => $id]);
        }
    }

    public function test_suma_cotizaciones_aceptadas_dentro_de_la_ventana(): void
    {
        $user = \App\Models\User::create([
            'first_name' => 'A', 'last_name' => 'B', 'position' => 'X', 'phone' => '5555555555',
            'email' => 'q-' . uniqid() . '@example.com', 'password' => bcrypt('x'), 'status' => 'active',
            'rfc' => 'XAXX010101000', 'role_id' => \App\Models\Role::create(['name_role' => 'r', 'name_role_es' => 'r'])->id,
        ]);
        $product = $this->makeProduct();
        $this->makeOrder('pagado', $product, 2);

        $mk = function (string $status, $acceptedAt, int $qty) use ($user, $product) {
            $q = \App\Models\Quote::create(['quote_number' => 'COT-' . uniqid(), 'status' => $status, 'created_by_user_id' => $user->id,
                'guest_name' => 'C', 'guest_email' => 'c@example.com', 'accepted_at' => $acceptedAt]);
            \Illuminate\Support\Facades\DB::table('quote_items')->insert([
                'quote_id' => $q->id, 'product_id' => $product->id, 'quantity' => $qty, 'created_at' => now(), 'updated_at' => now(),
            ] + $this->quoteItemDefaults());
        };
        $mk('accepted', now()->subDays(5), 10);   // cuenta
        $mk('accepted', now()->subDays(200), 99); // fuera de la ventana
        $mk('sent', null, 50);                    // no aceptada

        $this->artisan('catalog:refresh-sales')->assertSuccessful();

        $this->assertSame([$product->id => 12], $this->units());
    }

    private function quoteItemDefaults(): array
    {
        $cols = \Illuminate\Support\Facades\Schema::getColumnListing('quote_items');
        $defaults = ['product_name' => 'Item', 'product_sku' => 'SKU', 'name' => 'Item', 'description' => 'Item', 'unit_price' => 1, 'unit' => 'pza', 'line_total' => 1, 'subtotal' => 1, 'total' => 1, 'type' => 'product', 'sort_order' => 0, 'discount_percent' => 0];

        return array_intersect_key($defaults, array_flip($cols));
    }

    public function test_suma_cantidades_de_varias_partidas_y_pedidos_por_producto(): void
    {
        $a = $this->makeProduct();
        $b = $this->makeProduct();

        $order = $this->makeOrder('pagado', $a, 2);
        $this->addOrderItem($order, $b, 5);
        $this->makeOrder('entregado', $a, 3);

        $this->artisan('catalog:refresh-sales')->assertSuccessful();

        $this->assertSame([$a->id => 5, $b->id => 5], $this->units());
    }

    public function test_respeta_la_ventana_de_dias_configurada(): void
    {
        $inside = $this->makeProduct();
        $outside = $this->makeProduct();
        $this->makeOrder('pagado', $inside, 1, now()->subDays(80));
        $this->makeOrder('pagado', $outside, 1, now()->subDays(100));

        $this->artisan('catalog:refresh-sales')->assertSuccessful();
        $this->assertSame([$inside->id => 1], $this->units(), 'ventana por defecto: 90 días');

        $this->setCatalog('best_seller_days', 120);
        $this->artisan('catalog:refresh-sales')->assertSuccessful();
        $this->assertSame([$inside->id => 1, $outside->id => 1], $this->units());

        $this->setCatalog('best_seller_days', 30);
        $this->artisan('catalog:refresh-sales')->assertSuccessful();
        $this->assertSame([], $this->units(), 'ventana de 30 días: ambos quedan fuera y se limpian');
    }

    public function test_upsert_actualiza_filas_existentes_y_borra_las_obsoletas(): void
    {
        $kept = $this->makeProduct();
        $stale = $this->makeProduct();
        $neverSold = $this->makeProduct();

        $this->setSales($kept, 99);   // valor viejo: debe actualizarse
        $this->setSales($stale, 4);   // ya no tiene ventas pagadas: debe borrarse
        $this->makeOrder('pagado', $kept, 3);
        $this->makeOrder('cancelado', $stale, 4);

        $this->artisan('catalog:refresh-sales')->assertSuccessful();

        $this->assertSame([$kept->id => 3], $this->units());
        $this->assertDatabaseMissing('product_sales_stats', ['product_id' => $stale->id]);
        $this->assertDatabaseMissing('product_sales_stats', ['product_id' => $neverSold->id]);
        $this->assertNotNull(ProductSalesStat::where('product_id', $kept->id)->value('computed_at'));

        // Idempotente: correrlo de nuevo no duplica filas.
        $this->artisan('catalog:refresh-sales')->assertSuccessful();
        $this->assertSame(1, ProductSalesStat::count());
        $this->assertSame([$kept->id => 3], $this->units());
    }

    public function test_incrementa_la_version_de_cache_del_catalogo(): void
    {
        $before = CatalogCache::version();

        $this->artisan('catalog:refresh-sales')->assertSuccessful();

        $this->assertNotSame($before, CatalogCache::version());
    }

    public function test_dry_run_no_escribe_ni_invalida_la_cache(): void
    {
        $product = $this->makeProduct();
        $stale = $this->makeProduct();
        $this->setSales($stale, 8);
        $this->makeOrder('pagado', $product, 2);
        $before = CatalogCache::version();

        $this->artisan('catalog:refresh-sales', ['--dry-run' => true])
            ->expectsOutputToContain('dry-run')
            ->assertSuccessful();

        $this->assertSame([$stale->id => 8], $this->units(), 'dry-run no altera la tabla');
        $this->assertSame($before, CatalogCache::version());
    }

    public function test_alimenta_la_etiqueta_mas_vendido_de_punta_a_punta(): void
    {
        $category = $this->makeCategory();
        $top = $this->makeProduct(['category_id' => $category->id]);
        $b = $this->makeProduct(['category_id' => $category->id]);
        $c = $this->makeProduct(['category_id' => $category->id]);
        $this->makeOrder('pagado', $top, 9);
        $this->makeOrder('enviado', $b, 4);
        $this->makeOrder('entregado', $c, 3);

        $this->artisan('catalog:refresh-sales')->assertSuccessful();

        $this->assertSame([$top->id], \App\Services\Catalog\BadgeResolver::bestSellerIds());
    }

    public function test_esta_programado_cada_hora_sin_solaparse(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($e) => str_contains((string) $e->command, 'catalog:refresh-sales'));

        $this->assertNotNull($event, 'catalog:refresh-sales debe estar en el scheduler');
        $this->assertSame('0 * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
    }
}
