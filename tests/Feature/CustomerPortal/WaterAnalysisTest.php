<?php

namespace Tests\Feature\CustomerPortal;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Models\WaterAnalysisParameter;
use App\Models\WaterAnalysisReport;
use App\Models\WaterAnalysisResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaterAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function customer(string $company = 'ACME'): Customer
    {
        return Customer::create([
            'first_name' => 'Cli', 'last_name' => 'Ente', 'email' => uniqid() . '@example.com', 'phone' => '5511111111',
            'password_hash' => 'x', 'document_type' => 'RFC', 'document_numer' => 'X', 'birth_date' => '1990-01-01',
            'status' => 'active', 'source' => 'test', 'company' => $company, 'portal_access' => true,
        ]);
    }

    private function param(string $code): WaterAnalysisParameter
    {
        return WaterAnalysisParameter::where('code', $code)->firstOrFail();
    }

    /** @return array<int, mixed> valores por id de parámetro */
    private function values(array $byCode): array
    {
        $out = [];
        foreach ($byCode as $code => $v) {
            $out[$this->param($code)->id] = $v;
        }

        return $out;
    }

    private function adminUser(): User
    {
        $role = Role::firstOrCreate(['name_role' => 'Administrador'], ['name_role_es' => 'Administrador']);

        return $this->makeUser($role);
    }

    private function plainUser(): User
    {
        $role = Role::create(['name_role' => 'Sin permisos', 'name_role_es' => 'Sin permisos']);

        return $this->makeUser($role);
    }

    private function makeUser(Role $role): User
    {
        return User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'Administrador',
            'phone' => '5555555555', 'email' => 'u-' . uniqid() . '@example.com',
            'password' => bcrypt('password'), 'status' => 'active', 'rfc' => 'XAXX010101000', 'role_id' => $role->id,
        ]);
    }

    private function makeReport(Customer $c, string $date, array $byCode = ['ph' => 7.2], ?string $notes = null): WaterAnalysisReport
    {
        $report = WaterAnalysisReport::create(['customer_id' => $c->id, 'report_date' => $date, 'notes' => $notes]);
        foreach ($byCode as $code => $v) {
            $report->results()->create(['parameter_id' => $this->param($code)->id, 'value' => $v]);
        }

        return $report;
    }

    // ── Migración ───────────────────────────────────────────────────────────

    public function test_la_migracion_siembra_los_cinco_parametros_sin_limites(): void
    {
        $params = WaterAnalysisParameter::ordered()->get();

        $this->assertSame(['hardness', 'ph', 'alkalinity', 'conductivity', 'tds'], $params->pluck('code')->all());
        $this->assertSame('Sólidos Totales Disueltos (STD/TDS)', $this->param('tds')->name);
        $this->assertTrue($params->every(fn ($p) => $p->min_value === null && $p->max_value === null && $p->is_active));
    }

    // ── Estado (ok / bajo / alto / sin rango) ──────────────────────────────

    public function test_estado_sin_limites_es_sin_rango(): void
    {
        $p = $this->param('hardness');

        $this->assertSame('sin_rango', $p->statusFor(120));
        $this->assertNull($p->statusFor(null));
        $this->assertSame('—', $p->formatValue(null));
    }

    public function test_estado_con_limites(): void
    {
        $p = $this->param('ph');
        $p->update(['min_value' => 6.5, 'max_value' => 8.5]);
        $p->refresh();

        $this->assertSame('ok', $p->statusFor(7));
        $this->assertSame('ok', $p->statusFor(6.5));
        $this->assertSame('ok', $p->statusFor(8.5));
        $this->assertSame('bajo', $p->statusFor(6.4));
        $this->assertSame('alto', $p->statusFor('8.6'));
    }

    public function test_estado_con_un_solo_limite(): void
    {
        $p = $this->param('tds');
        $p->update(['max_value' => 500]);
        $p->refresh();

        $this->assertSame('ok', $p->statusFor(0));
        $this->assertSame('alto', $p->statusFor(501));
    }

    public function test_el_historial_se_reevalua_con_los_limites_vigentes(): void
    {
        $c = $this->customer();
        $this->makeReport($c, now()->subDay()->toDateString(), ['ph' => 9]);
        $phId = $this->param('ph')->id;

        $rows = $this->actingAs($c, 'customer')->get(route('customer.water-analysis.index'))->viewData('rows');
        $this->assertSame('sin_rango', $rows[0]['cells'][$phId]['status']);

        $this->param('ph')->update(['min_value' => 6.5, 'max_value' => 8.5]);

        $rows = $this->actingAs($c, 'customer')->get(route('customer.water-analysis.index'))->viewData('rows');
        $this->assertSame('alto', $rows[0]['cells'][$phId]['status']);
    }

    // ── Registro ────────────────────────────────────────────────────────────

    public function test_la_pagina_muestra_el_formulario_y_las_columnas(): void
    {
        $c = $this->customer();
        $this->param('hardness')->update(['min_value' => 0, 'max_value' => 17]);

        $this->actingAs($c, 'customer')
            ->get(route('customer.water-analysis.index'))
            ->assertOk()
            ->assertSee('Registro del día')
            ->assertSee('Valor mínimo')
            ->assertSee('Valor máximo')
            ->assertSee('Resultado')
            ->assertSee('Dureza')
            ->assertSee('Sólidos Totales Disueltos (STD/TDS)')
            ->assertSee('Aún no has registrado análisis químicos.');
    }

    public function test_store_crea_el_registro_con_sus_resultados(): void
    {
        $c = $this->customer();
        $today = now()->toDateString();

        $this->actingAs($c, 'customer')
            ->post(route('customer.water-analysis.store'), [
                'report_date' => $today,
                'notes' => 'Toma de la mañana',
                'values' => $this->values(['hardness' => '120.5', 'ph' => '7,25', 'tds' => '']),
            ])
            ->assertRedirect(route('customer.water-analysis.index'))
            ->assertSessionHas('success');

        $report = WaterAnalysisReport::where('customer_id', $c->id)->firstOrFail();
        $this->assertSame($today, $report->report_date->toDateString());
        $this->assertSame('Toma de la mañana', $report->notes);
        $this->assertNull($report->recorded_by_member_id);
        $this->assertCount(2, $report->results);
        $this->assertEquals(120.5, $report->results->firstWhere('parameter_id', $this->param('hardness')->id)->value);
        $this->assertEquals(7.25, $report->results->firstWhere('parameter_id', $this->param('ph')->id)->value);
    }

    public function test_guardar_una_fecha_existente_actualiza_ese_dia(): void
    {
        $c = $this->customer();
        $today = now()->toDateString();
        $this->makeReport($c, $today, ['ph' => 7, 'hardness' => 100]);

        $this->actingAs($c, 'customer')
            ->post(route('customer.water-analysis.store'), [
                'report_date' => $today,
                'values' => $this->values(['ph' => '7.8', 'tds' => '300']),
            ])
            ->assertRedirect(route('customer.water-analysis.index'));

        $this->assertSame(1, WaterAnalysisReport::count());
        $results = WaterAnalysisReport::first()->results->pluck('value', 'parameter_id');
        $this->assertEquals(7.8, $results[$this->param('ph')->id]);
        $this->assertEquals(300, $results[$this->param('tds')->id]);
        $this->assertArrayNotHasKey($this->param('hardness')->id, $results->all(), 'el valor que se dejó vacío se quita');
    }

    public function test_update_corrige_un_dia_pasado_y_conserva_la_fecha(): void
    {
        $c = $this->customer();
        $date = now()->subDays(3)->toDateString();
        $report = $this->makeReport($c, $date, ['ph' => 7]);

        $this->actingAs($c, 'customer')
            ->put(route('customer.water-analysis.update', $report->id), [
                'report_date' => now()->toDateString(), // se ignora
                'notes' => 'Corregido',
                'values' => $this->values(['ph' => '7.4']),
            ])
            ->assertRedirect(route('customer.water-analysis.index'));

        $report->refresh();
        $this->assertSame($date, $report->report_date->toDateString());
        $this->assertSame('Corregido', $report->notes);
        $this->assertEquals(7.4, $report->results()->first()->value);
    }

    public function test_el_endpoint_del_dia_devuelve_los_valores_existentes(): void
    {
        $c = $this->customer();
        $date = now()->subDay()->toDateString();
        $report = $this->makeReport($c, $date, ['ph' => 7.1], 'nota');

        $this->actingAs($c, 'customer')
            ->getJson(route('customer.water-analysis.day', $date))
            ->assertOk()
            ->assertJson(['exists' => true, 'id' => $report->id, 'notes' => 'nota'])
            ->assertJsonPath('values.' . $this->param('ph')->id, 7.1);

        $this->actingAs($c, 'customer')
            ->getJson(route('customer.water-analysis.day', now()->subDays(5)->toDateString()))
            ->assertOk()->assertJson(['exists' => false, 'id' => null]);

        $this->actingAs($c, 'customer')->getJson(route('customer.water-analysis.day', 'no-es-fecha'))->assertStatus(422);
    }

    // ── Validación ──────────────────────────────────────────────────────────

    public function test_rechaza_fecha_futura(): void
    {
        $c = $this->customer();

        $this->actingAs($c, 'customer')
            ->post(route('customer.water-analysis.store'), [
                'report_date' => now()->addDay()->toDateString(),
                'values' => $this->values(['ph' => '7']),
            ])
            ->assertSessionHasErrors(['report_date' => 'La fecha del registro no puede ser futura.']);

        $this->assertSame(0, WaterAnalysisReport::count());
    }

    public function test_rechaza_ph_fuera_de_0_a_14_y_negativos(): void
    {
        $c = $this->customer();
        $ph = $this->param('ph')->id;
        $hardness = $this->param('hardness')->id;

        $this->actingAs($c, 'customer')
            ->post(route('customer.water-analysis.store'), ['report_date' => now()->toDateString(), 'values' => [$ph => '14.5']])
            ->assertSessionHasErrors(["values.$ph" => 'pH: el valor debe estar entre 0 y 14.']);

        $this->actingAs($c, 'customer')
            ->post(route('customer.water-analysis.store'), ['report_date' => now()->toDateString(), 'values' => [$hardness => '-1']])
            ->assertSessionHasErrors(["values.$hardness"]);

        $this->actingAs($c, 'customer')
            ->post(route('customer.water-analysis.store'), ['report_date' => now()->toDateString(), 'values' => [$hardness => 'abc']])
            ->assertSessionHasErrors(["values.$hardness"]);

        $this->assertSame(0, WaterAnalysisReport::count());
    }

    public function test_requiere_al_menos_un_resultado(): void
    {
        $c = $this->customer();

        $this->actingAs($c, 'customer')
            ->post(route('customer.water-analysis.store'), [
                'report_date' => now()->toDateString(),
                'values' => $this->values(['ph' => '', 'hardness' => null]),
            ])
            ->assertSessionHasErrors(['values' => 'Captura al menos un resultado.']);

        $this->assertSame(0, WaterAnalysisReport::count());
    }

    public function test_redondea_a_los_decimales_del_parametro(): void
    {
        $c = $this->customer();

        $this->actingAs($c, 'customer')->post(route('customer.water-analysis.store'), [
            'report_date' => now()->toDateString(),
            'values' => $this->values(['ph' => '7.456']),
        ]);

        $this->assertEquals(7.46, WaterAnalysisReport::first()->results()->first()->value);
    }

    // ── Aislamiento entre clientes ──────────────────────────────────────────

    public function test_no_se_puede_modificar_ni_borrar_el_registro_de_otro_cliente(): void
    {
        $mine = $this->customer('Mía');
        $other = $this->customer('Ajena');
        $theirs = $this->makeReport($other, now()->subDay()->toDateString(), ['ph' => 7]);

        $this->actingAs($mine, 'customer')
            ->put(route('customer.water-analysis.update', $theirs->id), ['values' => $this->values(['ph' => '9'])])
            ->assertNotFound();
        $this->actingAs($mine, 'customer')
            ->delete(route('customer.water-analysis.destroy', $theirs->id))
            ->assertNotFound();

        $this->assertEquals(7, $theirs->fresh()->results()->first()->value);
        $this->assertNotNull(WaterAnalysisReport::find($theirs->id));
    }

    public function test_cada_cliente_ve_solo_su_historial_y_su_dia(): void
    {
        $mine = $this->customer('Mía');
        $other = $this->customer('Ajena');
        $date = now()->subDay()->toDateString();
        $this->makeReport($mine, $date, ['ph' => 7], 'MIA');
        $this->makeReport($other, $date, ['ph' => 8], 'AJENA');

        $this->actingAs($mine, 'customer')
            ->get(route('customer.water-analysis.index'))
            ->assertOk()->assertSee('MIA')->assertDontSee('AJENA');

        $this->actingAs($mine, 'customer')
            ->getJson(route('customer.water-analysis.day', $date))
            ->assertJson(['notes' => 'MIA']);
    }

    public function test_el_mismo_dia_puede_registrarse_por_clientes_distintos(): void
    {
        $a = $this->customer('A');
        $b = $this->customer('B');
        $today = now()->toDateString();

        foreach ([$a, $b] as $c) {
            $this->actingAs($c, 'customer')->post(route('customer.water-analysis.store'), [
                'report_date' => $today, 'values' => $this->values(['ph' => '7']),
            ]);
        }

        $this->assertSame(2, WaterAnalysisReport::count());
    }

    // ── Borrado ─────────────────────────────────────────────────────────────

    public function test_destroy_elimina_el_registro_y_sus_resultados(): void
    {
        $c = $this->customer();
        $report = $this->makeReport($c, now()->subDay()->toDateString(), ['ph' => 7, 'tds' => 200]);

        $this->actingAs($c, 'customer')
            ->delete(route('customer.water-analysis.destroy', $report->id))
            ->assertRedirect(route('customer.water-analysis.index'))
            ->assertSessionHas('success');

        $this->assertNull(WaterAnalysisReport::find($report->id));
        $this->assertSame(0, WaterAnalysisResult::count());
    }

    // ── Historial ───────────────────────────────────────────────────────────

    public function test_el_historial_filtra_por_rango_de_fechas_y_ordena_reciente_primero(): void
    {
        $c = $this->customer();
        foreach ([10, 5, 2] as $daysAgo) {
            $this->makeReport($c, now()->subDays($daysAgo)->toDateString(), ['ph' => 7], "NOTA-{$daysAgo}");
        }

        $all = $this->actingAs($c, 'customer')->get(route('customer.water-analysis.index'))->viewData('rows');
        $this->assertSame(['NOTA-2', 'NOTA-5', 'NOTA-10'], $all->map(fn ($r) => $r['report']->notes)->all());

        $filtered = $this->actingAs($c, 'customer')->get(route('customer.water-analysis.index', [
            'from' => now()->subDays(6)->toDateString(),
            'to' => now()->subDays(3)->toDateString(),
        ]));
        $filtered->assertOk()->assertSee('NOTA-5')->assertDontSee('NOTA-2')->assertDontSee('NOTA-10');

        $this->actingAs($c, 'customer')
            ->get(route('customer.water-analysis.index', ['from' => '2026-05-10', 'to' => '2026-05-01']))
            ->assertSessionHasErrors('to');
    }

    public function test_el_historial_pagina_de_a_30(): void
    {
        $c = $this->customer();
        for ($i = 0; $i < 35; $i++) {
            $this->makeReport($c, now()->subDays($i)->toDateString(), ['ph' => 7]);
        }

        $reports = $this->actingAs($c, 'customer')->get(route('customer.water-analysis.index'))->viewData('reports');
        $this->assertSame(30, $reports->count());
        $this->assertSame(35, $reports->total());
    }

    // ── Permisos del portal ─────────────────────────────────────────────────

    public function test_un_usuario_del_equipo_sin_permiso_no_puede_usar_el_modulo(): void
    {
        $c = $this->customer();
        $report = $this->makeReport($c, now()->subDay()->toDateString(), ['ph' => 7]);
        $session = ['portal_member_id' => 999];

        $denied = fn ($r) => in_array($r->getStatusCode(), [302, 403], true);

        $this->assertTrue($denied($this->actingAs($c, 'customer')->withSession($session)->get(route('customer.water-analysis.index'))));
        $this->assertTrue($denied($this->actingAs($c, 'customer')->withSession($session)->post(route('customer.water-analysis.store'), [
            'report_date' => now()->toDateString(), 'values' => $this->values(['ph' => '7']),
        ])));
        $this->assertTrue($denied($this->actingAs($c, 'customer')->withSession($session)->put(route('customer.water-analysis.update', $report->id), [
            'values' => $this->values(['ph' => '9']),
        ])));
        $this->assertTrue($denied($this->actingAs($c, 'customer')->withSession($session)->delete(route('customer.water-analysis.destroy', $report->id))));

        $this->assertSame(1, WaterAnalysisReport::count());
        $this->assertEquals(7, $report->fresh()->results()->first()->value);
    }

    // ── Admin: parámetros ───────────────────────────────────────────────────

    public function test_el_admin_edita_los_limites_de_un_parametro(): void
    {
        $p = $this->param('ph');
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get(route('admin.erp-settings.water-analysis.index'))
            ->assertOk()->assertSee('Parámetros de análisis de agua')->assertSee('Dureza');

        $this->actingAs($admin)
            ->put(route('admin.erp-settings.water-analysis.update', $p->id), [
                'name' => 'pH', 'unit' => '', 'min_value' => '6,5', 'max_value' => '8.5', 'is_active' => '1',
            ])
            ->assertRedirect(route('admin.erp-settings.water-analysis.index'))
            ->assertSessionHas('success');

        $p->refresh();
        $this->assertSame(6.5, $p->min_value);
        $this->assertSame(8.5, $p->max_value);
        $this->assertNull($p->unit);
        $this->assertTrue($p->is_active);

        // Vaciar un límite lo quita; sin checkbox queda inactivo.
        $this->actingAs($admin)
            ->put(route('admin.erp-settings.water-analysis.update', $p->id), ['name' => 'pH', 'min_value' => '', 'max_value' => '9'])
            ->assertRedirect();
        $p->refresh();
        $this->assertNull($p->min_value);
        $this->assertSame(9.0, $p->max_value);
        $this->assertFalse($p->is_active);
    }

    public function test_el_admin_valida_los_limites(): void
    {
        $p = $this->param('ph');
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->put(route('admin.erp-settings.water-analysis.update', $p->id), ['name' => 'pH', 'min_value' => '8', 'max_value' => '7'])
            ->assertSessionHasErrorsIn("parameter{$p->id}", ['max_value']);

        $this->actingAs($admin)
            ->put(route('admin.erp-settings.water-analysis.update', $p->id), ['name' => 'pH', 'max_value' => '15'])
            ->assertSessionHasErrorsIn("parameter{$p->id}", ['max_value']);

        $this->actingAs($admin)
            ->put(route('admin.erp-settings.water-analysis.update', $p->id), ['name' => '', 'min_value' => '-1'])
            ->assertSessionHasErrorsIn("parameter{$p->id}", ['name', 'min_value']);

        $this->assertNull($p->fresh()->min_value);
    }

    public function test_los_parametros_del_admin_requieren_el_permiso_erp_settings(): void
    {
        $p = $this->param('ph');
        $plain = $this->plainUser();

        $this->actingAs($plain)->get(route('admin.erp-settings.water-analysis.index'))->assertRedirect();
        $this->actingAs($plain)
            ->put(route('admin.erp-settings.water-analysis.update', $p->id), ['name' => 'pH', 'min_value' => '6', 'max_value' => '8'])
            ->assertRedirect();

        $this->assertNull($p->fresh()->min_value);
    }
}
