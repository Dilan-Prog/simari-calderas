<?php

namespace Tests\Feature\Deals;

use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Role;
use App\Models\User;
use App\Services\DealForecastService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre app/Services/DealForecastService.php (Fase 14 del plan CRM):
 * pipeline ponderado, rendimiento por ejecutivo y forecast por fecha de
 * cierre. stalledDeals() no se vuelve a probar aquí a fondo — es un
 * passthrough directo a DealReportService::stalledDeals(), ya cubierto
 * por su propio scope Deal::stalled().
 */
class DealForecastServiceTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $role = Role::create([
            'name_role' => 'Ejecutivo',
            'name_role_es' => 'Ejecutivo',
        ]);

        return User::create([
            'first_name' => 'Vendedor',
            'last_name' => 'Uno',
            'position' => 'Ejecutivo de ventas',
            'phone' => '5555555555',
            'email' => 'owner-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'rfc' => 'XEXX010101' . random_int(100, 999),
            'role_id' => $role->id,
        ]);
    }

    public function test_weighted_pipeline_sums_amount_times_stage_probability_for_open_deals(): void
    {
        $pipeline = Pipeline::create(['name' => 'Ventas', 'is_default' => true, 'is_active' => true]);

        $stage10 = PipelineStage::create([
            'pipeline_id' => $pipeline->id, 'name' => 'Contactado', 'slug' => 'contactado',
            'order' => 0, 'probability' => 10,
        ]);
        $stage50 = PipelineStage::create([
            'pipeline_id' => $pipeline->id, 'name' => 'Propuesta', 'slug' => 'propuesta',
            'order' => 1, 'probability' => 50,
        ]);

        Deal::create([
            'pipeline_id' => $pipeline->id, 'pipeline_stage_id' => $stage10->id,
            'name' => 'A', 'amount' => 1000, 'currency' => 'MXN', 'status' => 'open',
        ]);
        Deal::create([
            'pipeline_id' => $pipeline->id, 'pipeline_stage_id' => $stage50->id,
            'name' => 'B', 'amount' => 2000, 'currency' => 'MXN', 'status' => 'open',
        ]);
        // Deal ganado: no debe contarse en el pipeline ponderado (solo
        // status=open participa).
        Deal::create([
            'pipeline_id' => $pipeline->id, 'pipeline_stage_id' => $stage50->id,
            'name' => 'C', 'amount' => 5000, 'currency' => 'MXN', 'status' => 'won',
            'closed_at' => now(),
        ]);

        $service = app(DealForecastService::class);
        $result = $service->weightedPipeline($pipeline->id);

        // 1000*0.10 + 2000*0.50 = 100 + 1000 = 1100
        $this->assertEquals(1100.0, $result['total_weighted_amount']);
        $this->assertEquals(3000.0, $result['total_raw_amount']);
        $this->assertEquals(2, $result['total_deals_count']);
    }

    public function test_rep_performance_computes_win_rate_and_amounts_per_owner(): void
    {
        $pipeline = Pipeline::create(['name' => 'Ventas', 'is_default' => true, 'is_active' => true]);
        $stage = PipelineStage::create([
            'pipeline_id' => $pipeline->id, 'name' => 'Contactado', 'slug' => 'contactado',
            'order' => 0, 'probability' => 20,
        ]);

        $rep = $this->owner();

        Deal::create([
            'pipeline_id' => $pipeline->id, 'pipeline_stage_id' => $stage->id, 'owner_id' => $rep->id,
            'name' => 'Abierto', 'amount' => 1000, 'currency' => 'MXN', 'status' => 'open',
        ]);
        Deal::create([
            'pipeline_id' => $pipeline->id, 'pipeline_stage_id' => $stage->id, 'owner_id' => $rep->id,
            'name' => 'Ganado', 'amount' => 3000, 'currency' => 'MXN', 'status' => 'won', 'closed_at' => now(),
        ]);
        Deal::create([
            'pipeline_id' => $pipeline->id, 'pipeline_stage_id' => $stage->id, 'owner_id' => $rep->id,
            'name' => 'Perdido', 'amount' => 500, 'currency' => 'MXN', 'status' => 'lost', 'closed_at' => now(),
        ]);

        $service = app(DealForecastService::class);
        $performance = $service->repPerformance($pipeline->id)->firstWhere('owner_id', $rep->id);

        $this->assertNotNull($performance);
        $this->assertSame(1, $performance['open_count']);
        $this->assertSame(1, $performance['won_count']);
        $this->assertSame(1, $performance['lost_count']);
        $this->assertEquals(50.0, $performance['win_rate']); // 1 won / (1 won + 1 lost)
        $this->assertEquals(3000.0, $performance['won_amount']);
    }

    public function test_forecast_by_close_date_groups_open_deals_by_month_and_flags_deals_without_a_date(): void
    {
        $pipeline = Pipeline::create(['name' => 'Ventas', 'is_default' => true, 'is_active' => true]);
        $stage = PipelineStage::create([
            'pipeline_id' => $pipeline->id, 'name' => 'Contactado', 'slug' => 'contactado',
            'order' => 0, 'probability' => 25,
        ]);

        Deal::create([
            'pipeline_id' => $pipeline->id, 'pipeline_stage_id' => $stage->id,
            'name' => 'Con fecha', 'amount' => 400, 'currency' => 'MXN', 'status' => 'open',
            'expected_close_date' => '2026-09-15',
        ]);
        Deal::create([
            'pipeline_id' => $pipeline->id, 'pipeline_stage_id' => $stage->id,
            'name' => 'Sin fecha', 'amount' => 100, 'currency' => 'MXN', 'status' => 'open',
        ]);

        $service = app(DealForecastService::class);
        $forecast = $service->forecastByCloseDate($pipeline->id);

        $sept = $forecast->firstWhere('period', '2026-09');
        $sinFecha = $forecast->firstWhere('period', 'sin_fecha');

        $this->assertNotNull($sept);
        $this->assertEquals(100.0, $sept['weighted_amount']); // 400 * 0.25
        $this->assertNotNull($sinFecha);
        $this->assertSame(1, $sinFecha['deals_count']);
    }
}
