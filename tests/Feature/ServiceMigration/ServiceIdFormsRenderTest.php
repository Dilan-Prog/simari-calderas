<?php

namespace Tests\Feature\ServiceMigration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Los asistentes muestran el selector "Servicio (web)" con el valor heredado/prellenado. */
class ServiceIdFormsRenderTest extends TestCase
{
    use RefreshDatabase, ServiceMigrationFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->resetSettingCache();
    }

    public function test_asistente_de_cotizacion_crear_y_editar(): void
    {
        $admin = $this->adminUser();
        $page = $this->makeServicePage('Calibración de válvulas');
        $quote = $this->makeQuote(['status' => 'draft', 'service_page_id' => $page->id]);

        $this->actingAs($admin)->get(route('admin.quotes.create'))->assertOk()->assertSee('name="service_page_id"', false);
        $this->actingAs($admin)->get(route('admin.quotes.edit', $quote))->assertOk()
            ->assertSee('name="service_page_id"', false)->assertSee("#{$page->id} · Calibración de válvulas");
    }

    public function test_asistente_de_servicio_tecnico_precarga_el_servicio_id_de_la_cotizacion(): void
    {
        $admin = $this->adminUser();
        $page = $this->makeServicePage('Instalación de caldera');
        $customer = $this->makeCustomer();
        $quote = $this->makeQuote(['customer_id' => $customer->id, 'service_page_id' => $page->id]);
        $this->makeTechnicalService();   // asegura un tipo de servicio

        $this->actingAs($admin)->get(route('admin.technical-services.create', ['from_quote' => $quote->id]))
            ->assertOk()->assertSee('name="service_page_id"', false)->assertSee("#{$page->id} · Instalación de caldera");
    }

    public function test_asistente_de_reporte_precarga_el_servicio_id_del_servicio_tecnico(): void
    {
        $admin = $this->adminUser();
        $page = $this->makeServicePage('Mantenimiento de calderas');
        $service = $this->makeTechnicalService(['service_page_id' => $page->id]);

        $this->actingAs($admin)->get(route('admin.service-reports.create', ['from_service' => $service->id]))
            ->assertOk()->assertSee('name="service_page_id"', false)->assertSee("#{$page->id} · Mantenimiento de calderas");

        $report = $this->makeReport($admin, ['service_id' => $service->id, 'service_page_id' => $page->id]);
        $this->actingAs($admin)->get(route('admin.service-reports.step', [$report, 1]))
            ->assertOk()->assertSee('name="service_page_id"', false)->assertSee("#{$page->id} · Mantenimiento de calderas");
    }
}
