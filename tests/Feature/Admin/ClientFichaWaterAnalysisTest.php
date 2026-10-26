<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\WaterAnalysisParameter;
use App\Models\WaterAnalysisReport;
use App\Models\WaterAnalysisResult;
use App\Services\WaterAnalysisInsights;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Pestaña "Análisis químico" (solo lectura) de la ficha admin del cliente. */
class ClientFichaWaterAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function staff(string $roleName = 'Administrador', array $permissions = []): User
    {
        $role = Role::firstOrCreate(['name_role' => $roleName], ['name_role_es' => $roleName]);
        foreach ($permissions as [$module, $action]) {
            $perm = Permission::firstOrCreate(
                ['code' => "$module.$action"],
                ['name' => "$module $action", 'module' => $module, 'action' => $action]
            );
            $role->permissions()->syncWithoutDetaching([$perm->id]);
        }

        return User::create([
            'first_name' => 'Staff', 'last_name' => 'Test', 'position' => $roleName, 'phone' => '5555555555',
            'email' => 'staff-' . uniqid() . '@example.com', 'password' => bcrypt('password'),
            'status' => 'active', 'rfc' => 'XAXX' . random_int(100000, 999999) . 'AB1', 'role_id' => $role->id,
        ]);
    }

    private function customer(string $company = 'ACME'): Customer
    {
        return Customer::create([
            'first_name' => 'Cli', 'last_name' => 'Ente', 'email' => uniqid() . '@example.com', 'phone' => '5511111111',
            'password_hash' => 'x', 'document_type' => 'RFC', 'status' => 'active', 'source' => 'test',
            'company' => $company, 'portal_access' => true,
        ]);
    }

    private function report(Customer $c, int $daysAgo, array $byCode, ?string $notes = null, ?int $memberId = null): WaterAnalysisReport
    {
        $r = WaterAnalysisReport::create([
            'customer_id' => $c->id, 'report_date' => now()->subDays($daysAgo)->toDateString(),
            'notes' => $notes, 'recorded_by_member_id' => $memberId,
        ]);
        foreach ($byCode as $code => $value) {
            WaterAnalysisResult::create([
                'report_id' => $r->id,
                'parameter_id' => WaterAnalysisParameter::where('code', $code)->value('id'),
                'value' => $value,
            ]);
        }

        return $r;
    }

    private function ficha(Customer $c, array $query = [], ?User $user = null)
    {
        return $this->actingAs($user ?? $this->staff())
            ->get(route('admin.clients.information', ['id' => $c->id] + $query));
    }

    public function test_tab_shows_charts_and_table_of_that_customer_only(): void
    {
        $mine = $this->customer('Mi Cliente');
        $other = $this->customer('Otro Cliente');
        WaterAnalysisParameter::where('code', 'ph')->update(['min_value' => 7, 'max_value' => 8.2]);
        $this->report($mine, 0, ['ph' => 7.5, 'tds' => 612], 'Nota del titular MIA');
        $this->report($mine, 1, ['ph' => 9.4], null, 42);
        $this->report($other, 0, ['ph' => 5.37, 'tds' => 4321], 'Nota AJENA');

        $this->ficha($mine)
            ->assertOk()
            ->assertSee('data-tab="water-analysis"', false)
            ->assertSee('id="water-analysis"', false)
            ->assertSee('id="waLine"', false)
            ->assertSee('id="waPie"', false)
            ->assertSee('id="waArea"', false)
            ->assertSee('Últimos registros')
            ->assertSee('Nota del titular MIA')
            ->assertSee('Titular')
            ->assertSee('Usuario del equipo')
            ->assertSee('612')
            ->assertDontSee('Nota AJENA')
            ->assertDontSee('5.37')
            ->assertDontSee('4,321');
    }

    public function test_chartjs_is_loaded_once_per_page(): void
    {
        $c = $this->customer();
        $this->report($c, 0, ['ph' => 7.5]);

        $html = $this->ficha($c)->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'vendor/chartjs/chart.umd.min.js'));
    }

    public function test_shows_status_colors_per_reading(): void
    {
        $c = $this->customer();
        WaterAnalysisParameter::where('code', 'ph')->update(['min_value' => 7, 'max_value' => 8.2]);
        $this->report($c, 0, ['ph' => 9.1, 'hardness' => 150]);

        $this->ficha($c)
            ->assertOk()
            ->assertSee('wa-val--high', false)   // pH alto
            ->assertSee('wa-val--none', false);  // dureza sin rango
    }

    public function test_empty_state_without_records(): void
    {
        $c = $this->customer();
        $this->report($this->customer('Otro'), 0, ['ph' => 7.2]);

        $this->ficha($c)
            ->assertOk()
            ->assertSee('Aún no hay registros de análisis químico en los últimos 30 días')
            ->assertSee('Este cliente todavía no tiene registros de análisis químico')
            ->assertDontSee('id="waPie"', false);
    }

    public function test_period_param_changes_window_and_links_keep_the_tab_hash(): void
    {
        $c = $this->customer();
        $this->report($c, 0, ['ph' => 7.5]);
        $this->report($c, 60, ['ph' => 7.4]);

        $res = $this->ficha($c, ['dias' => 90])->assertOk();
        $res->assertSee('de 90', false);
        $res->assertSee(route('admin.clients.information', ['id' => $c->id, 'dias' => 7]) . '#water-analysis', false);
        $res->assertSee(route('admin.clients.information', ['id' => $c->id, 'dias' => 30]) . '#water-analysis', false);

        // Con 30 días (default) el registro de hace 60 días no cuenta.
        $this->ficha($c)->assertOk()->assertSee('de 30', false);
        $this->assertSame(2, app(WaterAnalysisInsights::class)->forCustomer($c->id, 90)['kpis']['days_logged']);
        $this->assertSame(1, app(WaterAnalysisInsights::class)->forCustomer($c->id, 30)['kpis']['days_logged']);
    }

    public function test_table_lists_old_reports_even_outside_the_period(): void
    {
        $c = $this->customer();
        $this->report($c, 0, ['ph' => 7.5]);
        $this->report($c, 60, ['ph' => 7.4], 'Registro antiguo visible');

        $this->ficha($c, ['dias' => 7])->assertOk()->assertSee('Registro antiguo visible');
    }

    public function test_recent_reports_limit_and_shape(): void
    {
        $c = $this->customer();
        for ($i = 0; $i < 35; $i++) {
            $this->report($c, $i, ['ph' => 7.5]);
        }

        $data = app(WaterAnalysisInsights::class)->recentReports($c->id, 30);

        $this->assertCount(30, $data['rows']);
        $this->assertSame(now()->format('d/m/Y'), $data['rows'][0]['date']);
        $this->assertSame('Titular', $data['rows'][0]['recorded_by']);
        $this->assertCount(count($data['parameters']), $data['rows'][0]['cells']);
    }

    public function test_tab_is_read_only(): void
    {
        $c = $this->customer();
        $this->report($c, 0, ['ph' => 7.5]);

        $html = $this->ficha($c)->assertOk()->getContent();
        $start = strpos($html, 'id="water-analysis"');
        $end = strpos($html, '</script>', strrpos($html, 'waArea') ?: $start);
        $tab = substr($html, $start, $end - $start);

        $this->assertStringNotContainsString('<form', $tab);
        $this->assertStringNotContainsString('<input', $tab);
        $this->assertStringNotContainsString('<textarea', $tab);
        $this->assertStringNotContainsString('method="POST"', $tab);
    }

    public function test_link_to_limits_only_with_erp_settings_permission(): void
    {
        $c = $this->customer();
        $limits = route('admin.erp-settings.water-analysis.index');

        $this->ficha($c)->assertOk()->assertSee('Configurar rangos')->assertSee($limits, false);

        $viewer = $this->staff('Ventas', [['clients', 'view']]);
        $this->ficha($c, [], $viewer)->assertOk()->assertDontSee('Configurar rangos');

        $configurer = $this->staff('Config', [['clients', 'view'], ['erp-settings', 'view']]);
        $this->ficha($c, [], $configurer)->assertOk()->assertSee('Configurar rangos')->assertSee($limits, false);
    }
}
