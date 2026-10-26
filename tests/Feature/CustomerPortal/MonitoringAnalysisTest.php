<?php

namespace Tests\Feature\CustomerPortal;

use App\Models\Customer;
use App\Models\WaterAnalysisParameter;
use App\Models\WaterAnalysisReport;
use App\Models\WaterAnalysisResult;
use App\Services\WaterAnalysisInsights;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Gráficas del Reporte de Análisis Químico dentro de Portal > Monitoreo. */
class MonitoringAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function customer(): Customer
    {
        return Customer::create([
            'first_name' => 'Cli', 'last_name' => 'Ente', 'email' => uniqid() . '@example.com', 'phone' => '5511111111',
            'password_hash' => 'x', 'document_type' => 'RFC', 'status' => 'active', 'source' => 'test',
            'company' => 'ACME', 'portal_access' => true,
        ]);
    }

    private function report(Customer $c, int $daysAgo, array $byCode): void
    {
        $r = WaterAnalysisReport::create(['customer_id' => $c->id, 'report_date' => now()->subDays($daysAgo)->toDateString()]);
        foreach ($byCode as $code => $value) {
            WaterAnalysisResult::create([
                'report_id' => $r->id,
                'parameter_id' => WaterAnalysisParameter::where('code', $code)->value('id'),
                'value' => $value,
            ]);
        }
    }

    public function test_insights_count_status_against_current_limits(): void
    {
        $c = $this->customer();
        WaterAnalysisParameter::where('code', 'ph')->update(['min_value' => 7, 'max_value' => 8.2]);
        $this->report($c, 0, ['ph' => 7.5, 'hardness' => 150]); // ph ok, dureza sin rango
        $this->report($c, 1, ['ph' => 9.1]);                      // ph alto
        $this->report($c, 2, ['ph' => 6.2]);                      // ph bajo

        $data = app(WaterAnalysisInsights::class)->forCustomer($c->id, 7);

        $this->assertTrue($data['has_data']);
        $this->assertCount(7, $data['labels']);
        $this->assertSame(1, $data['distribution']['ok']);
        $this->assertSame(1, $data['distribution']['alto']);
        $this->assertSame(1, $data['distribution']['bajo']);
        $this->assertSame(1, $data['distribution']['sin_rango']);
        $this->assertSame(3, $data['kpis']['days_logged']);
        $this->assertSame(2, $data['kpis']['out_of_range']);
        $this->assertSame(33, $data['kpis']['compliance']);
        $this->assertNull($data['area']['ok'][0]); // día sin registro = hueco, no cero
    }

    public function test_insights_ignore_other_customers_and_old_reports(): void
    {
        $mine = $this->customer();
        $other = $this->customer();
        $this->report($other, 0, ['ph' => 7]);
        $this->report($mine, 60, ['ph' => 7]);

        $this->assertFalse(app(WaterAnalysisInsights::class)->forCustomer($mine->id, 30)['has_data']);
        $this->assertTrue(app(WaterAnalysisInsights::class)->forCustomer($mine->id, 90)['has_data']);
    }

    public function test_invalid_period_falls_back_to_30_days(): void
    {
        $this->assertSame(30, app(WaterAnalysisInsights::class)->forCustomer($this->customer()->id, 999)['days']);
    }

    public function test_monitoring_page_shows_the_three_charts_and_no_equipment_link(): void
    {
        $c = $this->customer();
        $this->report($c, 0, ['ph' => 7.5, 'tds' => 600]);

        $this->actingAs($c, 'customer')
            ->get(route('customer.monitoring.index'))
            ->assertOk()
            ->assertSee('id="maLine"', false)
            ->assertSee('id="maPie"', false)
            ->assertSee('id="maArea"', false)
            ->assertDontSee('Mis equipos')
            ->assertDontSee('Mis Equipos');
    }

    public function test_monitoring_page_shows_empty_state_without_records(): void
    {
        $this->actingAs($this->customer(), 'customer')
            ->get(route('customer.monitoring.index'))
            ->assertOk()
            ->assertSee('Aún no hay registros de análisis químico')
            ->assertDontSee('id="maPie"', false);
    }

    public function test_equipment_routes_no_longer_exist_in_the_portal(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('customer.equipment.index'));
    }
}
