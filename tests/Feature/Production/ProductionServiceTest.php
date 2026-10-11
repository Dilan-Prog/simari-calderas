<?php

namespace Tests\Feature\Production;

use App\Models\Products;
use App\Models\ProductionOrder;
use App\Models\Project;
use App\Models\StockReservation;
use App\Models\Task;
use App\Models\Warehouse;
use App\Models\WarehouseProductStock;
use App\Services\BomService;
use App\Services\Erp\DocumentFlow;
use App\Services\InventoryService;
use App\Services\ProductionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesBadgeFixtures;
use Tests\TestCase;

class ProductionServiceTest extends TestCase
{
    use RefreshDatabase, CreatesBadgeFixtures;

    private ProductionService $svc;
    private Warehouse $wh;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new ProductionService();
        $this->wh = Warehouse::create(['name' => 'Principal', 'is_active' => true]);
    }

    private function bom(Products $product, array $components): void
    {
        $lines = [];
        foreach ($components as [$p, $qty, $scrap]) {
            $lines[] = ['component_product_id' => $p->id, 'quantity' => $qty, 'unit' => 'pza', 'scrap_percent' => $scrap];
        }
        (new BomService())->create(['product_id' => $product->id, 'is_active' => true], $lines);
    }

    private function stock(Products $p, int $qty): void
    {
        (new InventoryService())->applyMovement($this->wh->id, $p->id, 'entrada', $qty, null, null, 'seed');
    }

    private function onHand(Products $p): int
    {
        return (int) WarehouseProductStock::where('warehouse_id', $this->wh->id)->where('product_id', $p->id)->sum('quantity');
    }

    /** Caldera = 3 quemadores + 1 acero; quemador = 2 acero + 4 tornillos. 2 calderas => 14 acero, 24 tornillos. */
    private function fixture(int $acero = 20, int $tornillo = 100): array
    {
        $caldera = $this->makeProduct(['name' => 'Caldera', 'stock' => 0]);
        $quemador = $this->makeProduct(['name' => 'Quemador', 'stock' => 0]);
        $a = $this->makeProduct(['name' => 'Acero', 'stock' => 0, 'cost' => 10]);
        $t = $this->makeProduct(['name' => 'Tornillo', 'stock' => 0, 'cost' => 1]);
        $this->bom($quemador, [[$a, 2, 0], [$t, 4, 0]]);
        $this->bom($caldera, [[$quemador, 3, 0], [$a, 1, 0]]);
        $this->stock($a, $acero);
        $this->stock($t, $tornillo);

        return compact('caldera', 'quemador', 'a', 't');
    }

    private function make(array $f, float $qty = 2, array $extra = []): ProductionOrder
    {
        return $this->svc->create(array_merge(['product_id' => $f['caldera']->id, 'quantity' => $qty, 'warehouse_id' => $this->wh->id], $extra));
    }

    public function test_crea_con_bom_multinivel_y_costo_estandar(): void
    {
        $f = $this->fixture();
        $order = $this->make($f, 2);

        $this->assertMatchesRegularExpression('/^PRD-\d{4}-\d{4}$/', $order->number);
        $this->assertSame('borrador', $order->status);
        $comps = $order->components->keyBy('component_product_id');
        $this->assertCount(2, $comps);
        $this->assertEqualsWithDelta(14.0, $comps[$f['a']->id]->required_quantity, 0.0001);
        $this->assertEqualsWithDelta(24.0, $comps[$f['t']->id]->required_quantity, 0.0001);
        // Estándar por caldera: acero 7*10 + tornillo 12*1 = 82 => x2 = 164
        $this->assertEqualsWithDelta(164.0, $order->standard_cost, 0.01);
    }

    public function test_error_claro_sin_bom_vigente(): void
    {
        $p = $this->makeProduct(['name' => 'Sin receta']);
        try {
            $this->svc->create(['product_id' => $p->id, 'quantity' => 1]);
            $this->fail('Debió fallar');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('BOM', $e->errors()['product_id'][0]);
        }
        $this->assertSame(0, ProductionOrder::count());
    }

    public function test_liberar_reserva_componentes_y_enlaza_flujo_e_idempotente(): void
    {
        $f = $this->fixture();
        $project = Project::create(['project_number' => 'PRY-2026-0001', 'name' => 'P', 'status' => 'planeado']);
        $order = $this->make($f, 2, ['project_id' => $project->id]);

        $order = $this->svc->release($order);
        $this->assertSame('liberada', $order->status);
        $this->assertNotNull($order->material_requirement_id);
        $this->assertSame('production_order', $order->requirement->origin_type);

        $comps = $order->components->keyBy('component_product_id');
        $this->assertEqualsWithDelta(14.0, $comps[$f['a']->id]->reserved_quantity, 0.0001);
        $this->assertEqualsWithDelta(24.0, $comps[$f['t']->id]->reserved_quantity, 0.0001);
        $this->assertSame(2, StockReservation::where('status', 'activa')->count());

        $again = $this->svc->release($order);
        $this->assertSame($order->material_requirement_id, $again->material_requirement_id);
        $this->assertSame(1, \App\Models\MaterialRequirement::count());
        $this->assertSame(2, StockReservation::where('status', 'activa')->count());

        $numbers = array_column(DocumentFlow::flow($order), 'number');
        $this->assertContains($order->requirement->number, $numbers);
        $this->assertContains('PRY-2026-0001', $numbers);
    }

    public function test_liberar_con_existencia_insuficiente_reserva_parcial_y_muestra_faltante(): void
    {
        $f = $this->fixture(acero: 5);
        $order = $this->svc->release($this->make($f, 2));
        $c = $order->components->firstWhere('component_product_id', $f['a']->id);
        $this->assertEqualsWithDelta(5.0, $c->reserved_quantity, 0.0001);
        $this->assertEqualsWithDelta(9.0, $c->shortfall(), 0.0001);
    }

    public function test_consumo_total_genera_salidas_y_marca_reservas_consumidas(): void
    {
        $f = $this->fixture();
        $order = $this->svc->release($this->make($f, 2));

        $order = $this->svc->consume($order);
        $this->assertSame('en_proceso', $order->status);
        $this->assertSame(6, $this->onHand($f['a']));      // 20 - 14
        $this->assertSame(76, $this->onHand($f['t']));     // 100 - 24
        $this->assertSame(0, StockReservation::where('status', 'activa')->count());
        $this->assertSame(2, StockReservation::where('status', 'consumida')->count());
        $this->assertEqualsWithDelta(164.0, $order->actual_cost, 0.01);

        // Idempotente: sin pendiente no vuelve a descontar.
        $this->svc->consume($order);
        $this->assertSame(6, $this->onHand($f['a']));
    }

    public function test_consumo_parcial_y_validacion_de_existencias(): void
    {
        $f = $this->fixture(acero: 10);
        $order = $this->svc->release($this->make($f, 2));
        $comps = $order->components->keyBy('component_product_id');

        $order = $this->svc->consume($order, [$comps[$f['a']->id]->id => 4]);
        $this->assertSame(6, $this->onHand($f['a']));
        $this->assertSame(100, $this->onHand($f['t']));
        $c = $order->components->firstWhere('component_product_id', $f['a']->id);
        $this->assertEqualsWithDelta(4.0, $c->consumed_quantity, 0.0001);
        $this->assertEqualsWithDelta(6.0, $c->reserved_quantity, 0.0001); // 10 reservado - 4 consumido
    }

    public function test_consumo_rechaza_si_no_hay_existencias(): void
    {
        $f = $this->fixture(acero: 5);
        $order = $this->svc->release($this->make($f, 2));
        $this->expectException(ValidationException::class);
        try {
            $this->svc->consume($order);
        } finally {
            $this->assertSame(5, $this->onHand($f['a']));
            $this->assertSame(100, $this->onHand($f['t']));
        }
    }

    public function test_terminar_da_entrada_del_pt_costo_real_vs_estandar_y_produccion_parcial(): void
    {
        $f = $this->fixture();
        $order = $this->svc->release($this->make($f, 2));

        $order = $this->svc->finish($order, 1);
        $this->assertSame('en_proceso', $order->status);
        $this->assertSame(50, $order->progress);
        $this->assertSame(1, $this->onHand($f['caldera']));
        $this->assertSame(13, $this->onHand($f['a']));   // 20 - 7
        $this->assertEqualsWithDelta(82.0, $order->actual_cost, 0.01);

        $order = $this->svc->finish($order, 1);
        $this->assertSame('terminada', $order->status);
        $this->assertSame(100, $order->progress);
        $this->assertSame(2, $this->onHand($f['caldera']));
        $this->assertSame(6, $this->onHand($f['a']));
        $this->assertEqualsWithDelta(164.0, $order->actual_cost, 0.01);
        $this->assertEqualsWithDelta($order->standard_cost, $order->actual_cost, 0.01);
        $this->assertSame(0, StockReservation::where('status', 'activa')->count());
        $this->assertGreaterThanOrEqual(3, $order->logs()->count());

        // Idempotente: terminada no vuelve a mover inventario.
        $this->svc->finish($order, 1);
        $this->assertSame(2, $this->onHand($f['caldera']));
    }

    public function test_terminar_con_token_no_duplica_y_rechaza_exceso(): void
    {
        $f = $this->fixture();
        $order = $this->svc->release($this->make($f, 4));
        $this->svc->finish($order, 1, null, null, 'tok');
        $this->svc->finish($order, 1, null, null, 'tok');
        $this->assertSame(1, $this->onHand($f['caldera']));

        $this->expectException(ValidationException::class);
        $this->svc->finish($order, 10);
    }

    public function test_cancelar_libera_reservas_y_exige_accion_si_hubo_consumo(): void
    {
        $f = $this->fixture();
        $order = $this->svc->release($this->make($f, 2));
        $cancelled = $this->svc->cancel($order);
        $this->assertSame('cancelada', $cancelled->status);
        $this->assertSame(0, StockReservation::where('status', 'activa')->count());
        $this->svc->cancel($cancelled); // idempotente

        $o2 = $this->svc->release($this->make($f, 2));
        $this->svc->consume($o2);
        try {
            $this->svc->cancel($o2);
            $this->fail('Debió exigir devolución');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }
        $this->assertSame(6, $this->onHand($f['a']));

        $o2 = $this->svc->cancel($o2, null, true);
        $this->assertSame('cancelada', $o2->status);
        $this->assertSame(20, $this->onHand($f['a']));
        $this->assertSame(100, $this->onHand($f['t']));
    }

    public function test_comando_de_retraso_crea_tarea_sin_duplicar(): void
    {
        $f = $this->fixture();
        $late = $this->make($f, 1, ['due_date' => now()->subDays(3)->toDateString()]);
        $ok = $this->make($f, 1, ['due_date' => now()->addDays(3)->toDateString()]);
        $done = $this->make($f, 1, ['due_date' => now()->subDays(5)->toDateString()]);
        $done->update(['status' => 'terminada']);

        $this->artisan('production:alert-delays')->assertSuccessful();
        $this->artisan('production:alert-delays')->assertSuccessful();

        $tasks = Task::where('taskable_type', ProductionOrder::class)->get();
        $this->assertCount(1, $tasks);
        $this->assertSame($late->id, (int) $tasks[0]->taskable_id);
    }
}
