<?php

namespace Tests\Feature\ServiceMigration;

use App\Models\ServiceReport;
use App\Models\TechnicalService;
use App\Services\ServiceMigration\ServiceIdService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceIdAssignmentTest extends TestCase
{
    use RefreshDatabase, ServiceMigrationFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->resetSettingCache();
    }

    public function test_asigna_y_limpia_el_servicio_id_de_una_cotizacion(): void
    {
        $admin = $this->adminUser();
        $page = $this->makeServicePage();
        $quote = $this->makeQuote();

        $this->actingAs($admin)
            ->patchJson(route('admin.quotes.service-page', $quote), ['service_page_id' => $page->id])
            ->assertOk()->assertJsonPath('service_page_id', $page->id)
            ->assertJsonPath('label', "#{$page->id} · {$page->name}");
        $this->assertSame($page->id, $quote->fresh()->service_page_id);

        $this->actingAs($admin)
            ->patchJson(route('admin.quotes.service-page', $quote), ['service_page_id' => null])
            ->assertOk();
        $this->assertNull($quote->fresh()->service_page_id);
    }

    public function test_rechaza_paginas_inexistentes_inactivas_o_que_no_son_de_tipo_servicio(): void
    {
        $admin = $this->adminUser();
        $quote = $this->makeQuote();
        $hub = $this->makeServicePage('Hub servicios', ['page_type' => 'hub']);
        $categoria = $this->makeServicePage('Categoria', ['page_type' => 'category']);
        $inactiva = $this->makeServicePage('Inactiva', ['is_active' => false]);

        foreach ([999999, $hub->id, $categoria->id, $inactiva->id] as $bad) {
            $this->actingAs($admin)
                ->patchJson(route('admin.quotes.service-page', $quote), ['service_page_id' => $bad])
                ->assertStatus(422)->assertJsonValidationErrors('service_page_id');
        }
        $this->assertNull($quote->fresh()->service_page_id);
    }

    public function test_requiere_permiso_quotes_edit(): void
    {
        $page = $this->makeServicePage();
        $quote = $this->makeQuote(['created_by_user_id' => $this->adminUser()->id]);

        $soloVer = $this->userWithPermissions([['quotes', 'view']]);
        $this->actingAs($soloVer)
            ->patchJson(route('admin.quotes.service-page', $quote), ['service_page_id' => $page->id])
            ->assertForbidden();
        $this->assertNull($quote->fresh()->service_page_id);

        $editor = $this->userWithPermissions([['quotes', 'view'], ['quotes', 'edit']]);
        $this->actingAs($editor)
            ->patchJson(route('admin.quotes.service-page', $quote), ['service_page_id' => $page->id])
            ->assertOk();
        $this->assertSame($page->id, $quote->fresh()->service_page_id);
    }

    public function test_buscador_solo_devuelve_paginas_de_servicio_activas(): void
    {
        $admin = $this->adminUser();
        $ok = $this->makeServicePage('Calibración de válvulas');
        $this->makeServicePage('Calibración inactiva', ['is_active' => false]);
        $this->makeServicePage('Calibración hub', ['page_type' => 'hub']);

        $res = $this->actingAs($admin)->getJson(route('admin.service-migration.search', ['q' => 'Calibra']))->assertOk();
        $this->assertSame([$ok->id], collect($res->json())->pluck('id')->all());
        $this->assertSame("#{$ok->id} · Calibración de válvulas", $res->json('0.label'));

        // Sin ningún permiso relacionado no se puede usar el buscador.
        $nadie = $this->userWithPermissions([['projects', 'view']]);
        $this->actingAs($nadie)->getJson(route('admin.service-migration.search'))->assertForbidden();
    }

    public function test_sincronizar_propaga_a_servicios_y_reportes_sin_pisar_los_que_ya_tienen(): void
    {
        $admin = $this->adminUser();
        $nueva = $this->makeServicePage('Servicio nuevo');
        $previa = $this->makeServicePage('Servicio previo');
        $quote = $this->makeQuote();

        $sinId = $this->makeTechnicalService(['from_quote_id' => $quote->id]);
        $conId = $this->makeTechnicalService(['from_quote_id' => $quote->id, 'service_page_id' => $previa->id]);
        $ajeno = $this->makeTechnicalService();   // de otra cotización
        $repSin = $this->makeReport($admin, ['service_id' => $sinId->id]);
        $repDeConId = $this->makeReport($admin, ['service_id' => $conId->id]);
        $repPropio = $this->makeReport($admin, ['service_id' => $sinId->id, 'service_page_id' => $previa->id]);

        $this->actingAs($admin)
            ->patchJson(route('admin.quotes.service-page', $quote), ['service_page_id' => $nueva->id, 'sync' => 1])
            ->assertOk()->assertJsonPath('propagated.services', 1)->assertJsonPath('propagated.reports', 2);

        $this->assertSame($nueva->id, $sinId->fresh()->service_page_id);
        $this->assertSame($previa->id, $conId->fresh()->service_page_id, 'No se pisa el servicio que ya tenía uno');
        $this->assertNull($ajeno->fresh()->service_page_id);
        $this->assertSame($nueva->id, $repSin->fresh()->service_page_id);
        $this->assertSame($previa->id, $repDeConId->fresh()->service_page_id, 'El reporte hereda el de su servicio técnico');
        $this->assertSame($previa->id, $repPropio->fresh()->service_page_id, 'No se pisa el reporte que ya tenía uno');
    }

    public function test_sin_sincronizar_no_propaga(): void
    {
        $admin = $this->adminUser();
        $page = $this->makeServicePage();
        $quote = $this->makeQuote();
        $service = $this->makeTechnicalService(['from_quote_id' => $quote->id]);

        $this->actingAs($admin)
            ->patchJson(route('admin.quotes.service-page', $quote), ['service_page_id' => $page->id])
            ->assertOk();

        $this->assertNull($service->fresh()->service_page_id);
    }

    public function test_servicio_tecnico_nuevo_desde_cotizacion_hereda_el_servicio_id(): void
    {
        $admin = $this->adminUser();
        $page = $this->makeServicePage();
        $customer = $this->makeCustomer();
        $quote = $this->makeQuote(['customer_id' => $customer->id, 'service_page_id' => $page->id]);
        $type = $this->makeTechnicalService()->service_type_id;

        $this->actingAs($admin)->postJson(route('admin.technical-services.store'), [
            'customer_id' => $customer->id, 'from_quote_id' => $quote->id,
            'service_type_id' => $type, 'service_date' => now()->addDay()->toDateString(),
        ])->assertOk();

        $this->assertSame($page->id, TechnicalService::where('from_quote_id', $quote->id)->first()->service_page_id);
    }

    public function test_servicio_tecnico_respeta_el_servicio_id_elegido_en_el_paso_1(): void
    {
        $admin = $this->adminUser();
        $deCotizacion = $this->makeServicePage('De la cotización');
        $elegida = $this->makeServicePage('Elegida a mano');
        $customer = $this->makeCustomer();
        $quote = $this->makeQuote(['customer_id' => $customer->id, 'service_page_id' => $deCotizacion->id]);
        $type = $this->makeTechnicalService()->service_type_id;

        $this->actingAs($admin)->postJson(route('admin.technical-services.store'), [
            'customer_id' => $customer->id, 'from_quote_id' => $quote->id, 'service_page_id' => $elegida->id,
            'service_type_id' => $type, 'service_date' => now()->addDay()->toDateString(),
        ])->assertOk();

        $this->assertSame($elegida->id, TechnicalService::where('from_quote_id', $quote->id)->first()->service_page_id);
    }

    public function test_reporte_nuevo_hereda_del_servicio_tecnico_y_vincular_servicio_no_pisa(): void
    {
        $admin = $this->adminUser();
        $page = $this->makeServicePage();
        $propia = $this->makeServicePage('Propia del reporte');
        $service = $this->makeTechnicalService(['service_page_id' => $page->id]);

        // attachService: reporte sin Servicio ID hereda
        $sin = $this->makeReport($admin, ['status' => 'signed']);
        $this->actingAs($admin)->post(route('admin.service-reports.attach-service', $sin), ['service_id' => $service->id])->assertRedirect();
        $this->assertSame($page->id, $sin->fresh()->service_page_id);

        // attachService: reporte con Servicio ID propio se conserva
        $con = $this->makeReport($admin, ['status' => 'signed', 'service_page_id' => $propia->id]);
        $this->actingAs($admin)->post(route('admin.service-reports.attach-service', $con), ['service_id' => $service->id])->assertRedirect();
        $this->assertSame($propia->id, $con->fresh()->service_page_id);
    }

    public function test_alta_de_reporte_hereda_o_usa_el_elegido(): void
    {
        $admin = $this->adminUser();
        $page = $this->makeServicePage();
        $elegida = $this->makeServicePage('Elegida');
        $service = $this->makeTechnicalService(['service_page_id' => $page->id]);
        $type = \App\Models\ServiceReportType::where('is_active', true)->value('key');
        if (!$type) {
            $this->markTestSkipped('No hay tipos de reporte activos sembrados en la base de pruebas.');
        }

        $base = [
            'service_id' => $service->id, 'service_date' => now()->toDateString(), 'customer_name' => 'Planta',
            'assigned_user_id' => $admin->id, 'service_type' => $type,
        ];

        $this->actingAs($admin)->post(route('admin.service-reports.store'), $base)->assertRedirect();
        $this->assertSame($page->id, ServiceReport::latest('id')->first()->service_page_id);

        $this->actingAs($admin)->post(route('admin.service-reports.store'), $base + ['service_page_id' => $elegida->id])->assertRedirect();
        $this->assertSame($elegida->id, ServiceReport::latest('id')->first()->service_page_id);
    }

    public function test_el_servicio_ya_vinculado_a_cotizacion_hereda_al_vincular(): void
    {
        $admin = $this->adminUser();
        $page = $this->makeServicePage();
        $customer = $this->makeCustomer();
        $quote = $this->makeQuote(['customer_id' => $customer->id, 'service_page_id' => $page->id]);
        $service = $this->makeTechnicalService(['status' => 'completed']);

        $this->actingAs($admin)->patch(route('admin.technical-services.attach-quote', $service), ['from_quote_id' => $quote->id])->assertRedirect();

        $this->assertSame($page->id, $service->fresh()->service_page_id);
    }

    public function test_la_etiqueta_muestra_id_y_nombre(): void
    {
        $page = $this->makeServicePage('Calibración');
        $this->assertSame("#{$page->id} · Calibración", ServiceIdService::label($page));
        $this->assertSame('', ServiceIdService::label(null));
    }
}
