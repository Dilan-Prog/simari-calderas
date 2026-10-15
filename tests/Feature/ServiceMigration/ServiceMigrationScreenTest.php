<?php

namespace Tests\Feature\ServiceMigration;

use App\Services\ServiceMigration\ServiceIdService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceMigrationScreenTest extends TestCase
{
    use RefreshDatabase, ServiceMigrationFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->resetSettingCache();
    }

    public function test_requiere_permiso_service_pages_para_ver_la_pantalla(): void
    {
        $this->adminUser();
        $sinPermiso = $this->userWithPermissions([['quotes', 'view']]);
        $this->actingAs($sinPermiso)->get(route('admin.service-migration.index'))->assertRedirect();

        $conPermiso = $this->userWithPermissions([['service-pages', 'view'], ['quotes', 'view']]);
        $this->actingAs($conPermiso)->get(route('admin.service-migration.index'))->assertOk();
    }

    public function test_por_omision_lista_solo_cotizaciones_sin_servicio_id_y_muestra_lineas_detectadas(): void
    {
        $admin = $this->adminUser();
        $page = $this->makeServicePage();
        $sin = $this->makeQuote(['quote_number' => 'COT-SIN-1']);
        $this->addLine($sin, 'Mantenimiento Preventivo Mayor de caldera');
        $this->addLine($sin, 'Empaque de caldera');
        $con = $this->makeQuote(['quote_number' => 'COT-CON-1', 'service_page_id' => $page->id]);

        $res = $this->actingAs($admin)->get(route('admin.service-migration.index'))->assertOk();
        $res->assertSee('COT-SIN-1')->assertDontSee('COT-CON-1');
        $res->assertSee('Mantenimiento Preventivo Mayor de caldera');
        $res->assertDontSee('Empaque de caldera');

        $this->actingAs($admin)->get(route('admin.service-migration.index', ['servicio' => 'todas']))
            ->assertSee('COT-SIN-1')->assertSee('COT-CON-1');
        $this->actingAs($admin)->get(route('admin.service-migration.index', ['servicio' => 'con']))
            ->assertSee('COT-CON-1')->assertDontSee('COT-SIN-1');
    }

    public function test_filtros_estado_cliente_fechas_y_lineas_de_servicio(): void
    {
        $admin = $this->adminUser();
        $a = $this->makeQuote(['quote_number' => 'COT-A', 'status' => 'accepted', 'guest_name' => 'Textiles del Norte', 'created_at' => '2026-09-10 10:00:00']);
        $this->addLine($a, 'Servicio de Calibración de válvulas');
        $b = $this->makeQuote(['quote_number' => 'COT-B', 'status' => 'sent', 'guest_name' => 'Lácteos del Sur', 'created_at' => '2026-10-05 10:00:00']);
        $this->addLine($b, 'Caldera 50 BHP');
        $cat = $this->makeCategory('Servicios');
        $c = $this->makeQuote(['quote_number' => 'COT-C', 'status' => 'accepted', 'guest_name' => 'Acme', 'created_at' => '2026-10-07 10:00:00']);
        $this->addLine($c, 'Visita sin nombre de servicio', $this->makeProduct('Visita', $cat));

        $get = fn (array $q) => $this->actingAs($admin)->get(route('admin.service-migration.index', $q));

        $get(['status' => 'sent'])->assertSee('COT-B')->assertDontSee('COT-A')->assertDontSee('COT-C');
        $get(['customer' => 'Textiles'])->assertSee('COT-A')->assertDontSee('COT-B');
        $get(['date_from' => '2026-10-01'])->assertSee('COT-B')->assertSee('COT-C')->assertDontSee('COT-A');
        $get(['date_to' => '2026-09-30'])->assertSee('COT-A')->assertDontSee('COT-B');
        // Solo con líneas de producto-servicio (por nombre A, por categoría C)
        $get(['lineas' => 1])->assertSee('COT-A')->assertSee('COT-C')->assertDontSee('COT-B');
        $get(['search' => 'COT-B'])->assertSee('COT-B')->assertDontSee('COT-A');
    }

    public function test_sugerencia_solo_cuando_una_pagina_coincide_claramente(): void
    {
        $admin = $this->adminUser();
        $clara = $this->makeServicePage('Mantenimiento preventivo mayor de calderas');
        $this->makeServicePage('Calibración de válvulas de seguridad');
        $quote = $this->makeQuote(['quote_number' => 'COT-SUG']);
        $this->addLine($quote, 'Mantenimiento Preventivo Mayor de Calderas');

        $this->actingAs($admin)->get(route('admin.service-migration.index'))
            ->assertSee('Sugerido')->assertSee("#{$clara->id} · Mantenimiento preventivo mayor de calderas")
            ->assertSee('data-accept-suggestion', false);
        // Nunca asigna sola.
        $this->assertNull($quote->fresh()->service_page_id);

        // Sin coincidencia clara no hay sugerencia.
        $otra = $this->makeQuote(['quote_number' => 'COT-NOSUG']);
        $this->addLine($otra, 'Reparación de tanque');
        $svc = app(ServiceIdService::class);
        $this->assertNull($svc->suggest(['Reparación de tanque'], \App\Models\ServicePage::all()));
        $this->assertSame($clara->id, $svc->suggest(['Mantenimiento Preventivo Mayor de Calderas'], \App\Models\ServicePage::all())?->id);
    }

    public function test_sugerencia_ambigua_no_se_muestra(): void
    {
        $this->adminUser();
        $this->makeServicePage('Mantenimiento de calderas A');
        $this->makeServicePage('Mantenimiento de calderas B');

        $this->assertNull(app(ServiceIdService::class)->suggest(['Mantenimiento de calderas'], \App\Models\ServicePage::all()));
    }

    public function test_asignacion_en_lote_con_sincronizacion(): void
    {
        $admin = $this->adminUser();
        $page = $this->makeServicePage();
        $q1 = $this->makeQuote();
        $q2 = $this->makeQuote();
        $q3 = $this->makeQuote();
        $svc = $this->makeTechnicalService(['from_quote_id' => $q1->id]);

        $this->actingAs($admin)->postJson(route('admin.service-migration.bulk-assign'), [
            'quote_ids' => [$q1->id, $q2->id], 'service_page_id' => $page->id, 'sync' => 1,
        ])->assertOk()->assertJsonPath('result.quotes', 2)->assertJsonPath('result.services', 1);

        $this->assertSame($page->id, $q1->fresh()->service_page_id);
        $this->assertSame($page->id, $q2->fresh()->service_page_id);
        $this->assertNull($q3->fresh()->service_page_id);
        $this->assertSame($page->id, $svc->fresh()->service_page_id);
    }

    public function test_lote_valida_pagina_y_permisos(): void
    {
        $admin = $this->adminUser();
        $hub = $this->makeServicePage('Hub', ['page_type' => 'hub']);
        $q = $this->makeQuote();

        $this->actingAs($admin)->postJson(route('admin.service-migration.bulk-assign'), ['quote_ids' => [$q->id], 'service_page_id' => $hub->id])
            ->assertStatus(422);
        $this->actingAs($admin)->postJson(route('admin.service-migration.bulk-assign'), ['quote_ids' => [], 'service_page_id' => 1])
            ->assertStatus(422);

        $page = $this->makeServicePage();
        $soloVer = $this->userWithPermissions([['service-pages', 'view'], ['quotes', 'view']]);
        $this->actingAs($soloVer)->postJson(route('admin.service-migration.bulk-assign'), ['quote_ids' => [$q->id], 'service_page_id' => $page->id])
            ->assertForbidden();
        $this->assertNull($q->fresh()->service_page_id);

        $editor = $this->userWithPermissions([['service-pages', 'view'], ['quotes', 'view'], ['quotes', 'edit']]);
        $this->actingAs($editor)->postJson(route('admin.service-migration.bulk-assign'), ['quote_ids' => [$q->id], 'service_page_id' => $page->id])
            ->assertOk();
        $this->assertSame($page->id, $q->fresh()->service_page_id);
    }

    public function test_progreso_cuenta_migradas_sobre_las_que_tienen_lineas_o_servicio_id(): void
    {
        $admin = $this->adminUser();
        $page = $this->makeServicePage();
        $svc = app(ServiceIdService::class);

        $this->assertSame(['migrated' => 0, 'total' => 0, 'percent' => 100], $svc->progress());

        $a = $this->makeQuote(); $this->addLine($a, 'Servicio de calibración');
        $b = $this->makeQuote(); $this->addLine($b, 'Instalación de caldera');
        $c = $this->makeQuote(['service_page_id' => $page->id]); $this->addLine($c, 'Servicio de limpieza');
        $d = $this->makeQuote(); $this->addLine($d, 'Caldera 50 BHP'); // no entra al universo

        $this->assertSame(['migrated' => 1, 'total' => 3, 'percent' => 33], $svc->progress());

        $this->actingAs($admin)->get(route('admin.service-migration.index'))->assertSee('1 de 3 cotizaciones migradas');

        $svc->assign($a, $page->id);
        $this->assertSame(['migrated' => 2, 'total' => 3, 'percent' => 66], $svc->progress());
    }

    public function test_exporta_csv_con_los_filtros(): void
    {
        $admin = $this->adminUser();
        $page = $this->makeServicePage();
        $a = $this->makeQuote(['quote_number' => 'COT-CSV-A']);
        $this->addLine($a, 'Mantenimiento correctivo');
        $this->makeQuote(['quote_number' => 'COT-CSV-B', 'service_page_id' => $page->id]);

        $res = $this->actingAs($admin)->get(route('admin.service-migration.export'))->assertOk();
        $csv = $res->streamedContent();
        $this->assertStringContainsString('COT-CSV-A', $csv);
        $this->assertStringContainsString('Mantenimiento correctivo', $csv);
        $this->assertStringNotContainsString('COT-CSV-B', $csv);

        $todas = $this->actingAs($admin)->get(route('admin.service-migration.export', ['servicio' => 'con']))->streamedContent();
        $this->assertStringContainsString('COT-CSV-B', $todas);
        $this->assertStringContainsString((string) $page->id, $todas);
    }

    public function test_interruptor_del_modo_nuevo(): void
    {
        $admin = $this->adminUser();
        $this->assertFalse(ServiceIdService::newMode());

        $this->actingAs($admin)->post(route('admin.service-migration.new-mode'), ['enabled' => 1])->assertRedirect();
        $this->resetSettingCache();
        $this->assertTrue(ServiceIdService::newMode());

        $this->actingAs($admin)->post(route('admin.service-migration.new-mode'), ['enabled' => 0])->assertRedirect();
        $this->resetSettingCache();
        $this->assertFalse(ServiceIdService::newMode());

        $sinEdit = $this->userWithPermissions([['service-pages', 'view']]);
        $this->actingAs($sinEdit)->post(route('admin.service-migration.new-mode'), ['enabled' => 1])->assertRedirect(route('admin.dashboard'));
        $this->resetSettingCache();
        $this->assertFalse(ServiceIdService::newMode());
    }
}
