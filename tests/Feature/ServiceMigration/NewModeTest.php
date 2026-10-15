<?php

namespace Tests\Feature\ServiceMigration;

use App\Models\Quote;
use App\Services\ServiceMigration\ServiceIdService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewModeTest extends TestCase
{
    use RefreshDatabase, ServiceMigrationFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->resetSettingCache();
    }

    private function newMode(bool $on): void
    {
        app(ServiceIdService::class)->setNewMode($on);
        $this->resetSettingCache();
    }

    private function names($response): array
    {
        return collect($response->json())->pluck('name')->sort()->values()->all();
    }

    public function test_con_el_modo_apagado_el_buscador_muestra_todo_como_hoy(): void
    {
        $admin = $this->adminUser();
        $this->makeProduct('Caldera 50 BHP');
        $this->makeProduct('Mantenimiento Preventivo Mayor');
        $this->makeProduct('Visita', $this->makeCategory('Servicios'));

        $res = $this->actingAs($admin)->getJson(route('admin.quotes.search-products', ['q' => '']))->assertOk();
        $this->assertCount(3, $res->json());
    }

    public function test_con_el_modo_nuevo_el_buscador_excluye_productos_servicio(): void
    {
        $admin = $this->adminUser();
        $servicios = $this->makeCategory('Servicios');
        $this->makeProduct('Caldera 50 BHP');
        $this->makeProduct('Empaque de caldera');
        $this->makeProduct('Mantenimiento Preventivo Mayor');
        $this->makeProduct('Servicio de Calibración de válvulas');
        $this->makeProduct('Visita de revisión', $servicios);   // por categoría

        $this->newMode(true);

        $res = $this->actingAs($admin)->getJson(route('admin.quotes.search-products', ['q' => '']))->assertOk();
        $this->assertSame(['Caldera 50 BHP', 'Empaque de caldera'], $this->names($res));

        // Buscar un servicio por nombre ya no devuelve el producto.
        $res = $this->actingAs($admin)->getJson(route('admin.quotes.search-products', ['q' => 'Calibración']))->assertOk();
        $this->assertSame([], $res->json());

        // Se pueden volver a ver al apagar el modo (no se desactivó ni borró nada).
        $this->newMode(false);
        $res = $this->actingAs($admin)->getJson(route('admin.quotes.search-products', ['q' => 'Calibración']))->assertOk();
        $this->assertCount(1, $res->json());
        $this->assertSame(5, \App\Models\Products::where('is_active', 1)->count());
    }

    public function test_el_buscador_de_servicios_solo_devuelve_paginas_de_tipo_servicio(): void
    {
        $admin = $this->adminUser();
        $ok = $this->makeServicePage('Calibración de válvulas');
        $this->makeServicePage('Calibración (hub)', ['page_type' => 'hub']);

        $res = $this->actingAs($admin)->getJson(route('admin.quotes.search-services', ['q' => 'Calibra']))->assertOk();
        $this->assertSame([$ok->id], collect($res->json())->pluck('id')->all());
    }

    private function payload(array $items, array $over = []): array
    {
        $customer = $this->makeCustomer();

        return array_merge([
            'customer_id' => $customer->id, 'guest_name' => 'Cliente', 'tax_rate' => 16, 'currency' => 'MXN', 'exchange_rate' => 18,
            'items_json' => json_encode($items),
        ], $over);
    }

    private function line(string $name, array $over = []): array
    {
        return array_merge([
            'product_id' => null, 'product_name' => $name, 'quantity' => 1, 'unit_price' => 100, 'discount_percent' => 0,
            'tax_percent' => 16, 'line_total' => 100,
        ], $over);
    }

    public function test_modo_nuevo_rechaza_lineas_de_producto_servicio_al_crear(): void
    {
        $admin = $this->adminUser();
        $producto = $this->makeProduct('Mantenimiento Preventivo Mayor');
        $this->newMode(true);

        $res = $this->actingAs($admin)->post(route('admin.quotes.store'), $this->payload([
            $this->line('Mantenimiento Preventivo Mayor', ['product_id' => $producto->id]),
        ]));

        $res->assertSessionHasErrors('items_json');
        $this->assertSame(0, Quote::count());
    }

    public function test_modo_nuevo_exige_servicio_id_cuando_hay_lineas_de_servicio(): void
    {
        $admin = $this->adminUser();
        $page = $this->makeServicePage();
        $this->newMode(true);

        $items = [$this->line('Mantenimiento preventivo', ['service_page_id' => $page->id])];

        $this->actingAs($admin)->post(route('admin.quotes.store'), $this->payload($items))
            ->assertSessionHasErrors('items_json');
        $this->assertSame(0, Quote::count());

        $this->actingAs($admin)->post(route('admin.quotes.store'), $this->payload($items, ['service_page_id' => $page->id]))
            ->assertSessionHasNoErrors();
        $quote = Quote::first();
        $this->assertNotNull($quote);
        $this->assertSame($page->id, $quote->service_page_id);
        $this->assertSame($page->id, $quote->items->first()->service_page_id);
    }

    public function test_con_el_modo_apagado_se_puede_guardar_como_siempre(): void
    {
        $admin = $this->adminUser();
        $producto = $this->makeProduct('Mantenimiento Preventivo Mayor');

        $this->actingAs($admin)->post(route('admin.quotes.store'), $this->payload([
            $this->line('Mantenimiento Preventivo Mayor', ['product_id' => $producto->id]),
        ]))->assertSessionHasNoErrors();
        $this->assertSame(1, Quote::count());
        $this->assertNull(Quote::first()->service_page_id);
    }

    public function test_editar_cotizacion_vieja_conserva_sus_lineas_producto_servicio_en_modo_nuevo(): void
    {
        $admin = $this->adminUser();
        $customer = $this->makeCustomer();
        $producto = $this->makeProduct('Mantenimiento Preventivo Mayor');
        $quote = $this->makeQuote(['customer_id' => $customer->id, 'status' => 'draft']);
        $this->addLine($quote, $producto->name, $producto);
        $this->newMode(true);

        // Mantiene la línea existente: pasa.
        $this->actingAs($admin)->put(route('admin.quotes.update', $quote), $this->payload([
            $this->line($producto->name, ['product_id' => $producto->id]),
        ], ['customer_id' => $customer->id]))->assertSessionHasNoErrors();

        // Agrega otro producto-servicio nuevo: se rechaza.
        $otro = $this->makeProduct('Reparación de quemador');
        $this->actingAs($admin)->put(route('admin.quotes.update', $quote), $this->payload([
            $this->line($producto->name, ['product_id' => $producto->id]),
            $this->line($otro->name, ['product_id' => $otro->id]),
        ], ['customer_id' => $customer->id]))->assertSessionHasErrors('items_json');
    }

    public function test_actualizar_guarda_y_limpia_el_servicio_id_desde_el_asistente(): void
    {
        $admin = $this->adminUser();
        $customer = $this->makeCustomer();
        $page = $this->makeServicePage();
        $quote = $this->makeQuote(['customer_id' => $customer->id, 'status' => 'draft']);
        $producto = $this->makeProduct('Empaque');

        $items = [$this->line('Empaque', ['product_id' => $producto->id])];
        $this->actingAs($admin)->put(route('admin.quotes.update', $quote), $this->payload($items, ['customer_id' => $customer->id, 'service_page_id' => $page->id]))
            ->assertSessionHasNoErrors();
        $this->assertSame($page->id, $quote->fresh()->service_page_id);

        $this->actingAs($admin)->put(route('admin.quotes.update', $quote), $this->payload($items, ['customer_id' => $customer->id, 'service_page_id' => '']))
            ->assertSessionHasNoErrors();
        $this->assertNull($quote->fresh()->service_page_id);
    }

    public function test_la_vista_de_la_cotizacion_muestra_el_servicio_id_y_el_selector(): void
    {
        $admin = $this->adminUser();
        $page = $this->makeServicePage('Calibración de válvulas');
        $quote = $this->makeQuote(['service_page_id' => $page->id]);

        $this->actingAs($admin)->get(route('admin.quotes.show', $quote))
            ->assertOk()->assertSee("#{$page->id} · Calibración de válvulas")->assertSee('Servicio ID:')
            ->assertSee(route('admin.quotes.service-page', $quote), false);
    }
}
