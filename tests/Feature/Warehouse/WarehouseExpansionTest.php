<?php

namespace Tests\Feature\Warehouse;

use App\Models\InventoryMovement;
use App\Models\Products;
use App\Models\Role;
use App\Models\StockCount;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Models\WarehouseProductStock;
use App\Models\WarehouseTransfer;
use App\Services\InventoryService;
use App\Services\StockCountService;
use App\Services\WarehouseTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesProductBlockFixtures;
use Tests\TestCase;

/** Fase 6 — Almacén: traspasos, conteo físico, ubicaciones y reservas visibles. */
class WarehouseExpansionTest extends TestCase
{
    use RefreshDatabase;
    use CreatesProductBlockFixtures;

    private User $admin;
    private Warehouse $a;
    private Warehouse $b;
    private Products $p1;
    private Products $p2;

    protected function setUp(): void
    {
        parent::setUp();
        $role = Role::create(['name_role' => 'Administrador', 'name_role_es' => 'Administrador']);
        $this->admin = User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'Administrador', 'phone' => '5555555555',
            'email' => 'admin-' . uniqid() . '@example.com', 'password' => bcrypt('password'), 'status' => 'active',
            'rfc' => 'XAXX010101000', 'role_id' => $role->id,
        ]);
        $this->actingAs($this->admin);

        $this->a = Warehouse::create(['name' => 'Almacén A ' . uniqid(), 'is_active' => true]);
        $this->b = Warehouse::create(['name' => 'Almacén B ' . uniqid(), 'is_active' => true]);
        $this->p1 = $this->makeProduct(['name' => 'Bomba', 'stock' => 0, 'cost' => 100]);
        $this->p2 = $this->makeProduct(['name' => 'Válvula', 'stock' => 0, 'cost' => 50]);

        $inv = app(InventoryService::class);
        $inv->applyMovement($this->a->id, $this->p1->id, 'entrada', 10);
        $inv->applyMovement($this->a->id, $this->p2->id, 'entrada', 5);
    }

    private function bal(Warehouse $w, Products $p): int
    {
        return (int) (WarehouseProductStock::where('warehouse_id', $w->id)->where('product_id', $p->id)->value('quantity') ?? 0);
    }

    private function global(Products $p): int
    {
        return (int) Products::find($p->id)->stock;
    }

    private function transfer(array $lines): WarehouseTransfer
    {
        return app(WarehouseTransferService::class)->create($this->a->id, $this->b->id, $lines, 'nota', $this->admin->id);
    }

    public function test_traspaso_feliz_y_stock_global_neto_igual(): void
    {
        $svc = app(WarehouseTransferService::class);
        $t = $this->transfer([['product_id' => $this->p1->id, 'quantity' => 4]]);
        $this->assertMatchesRegularExpression('/^TRA-\d{4}-0001$/', $t->number);
        $this->assertSame(10, $this->global($this->p1));

        $svc->ship($t, $this->admin->id);
        $this->assertSame(6, $this->bal($this->a, $this->p1));
        $this->assertSame(6, $this->global($this->p1)); // en tránsito no es vendible

        $svc->receive($t->fresh(), [], null, $this->admin->id);
        $this->assertSame(6, $this->bal($this->a, $this->p1));
        $this->assertSame(4, $this->bal($this->b, $this->p1));
        $this->assertSame(10, $this->global($this->p1)); // neto igual
        $this->assertSame('recibido', $t->fresh()->status);
        $this->assertSame(2, InventoryMovement::where('reference_type', WarehouseTransfer::class)->where('reference_id', $t->id)->count());
    }

    public function test_saldo_insuficiente_no_mueve_nada(): void
    {
        $t = $this->transfer([['product_id' => $this->p1->id, 'quantity' => 3], ['product_id' => $this->p2->id, 'quantity' => 99]]);

        try {
            app(WarehouseTransferService::class)->ship($t, $this->admin->id);
            $this->fail('Debió fallar por saldo insuficiente');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('Saldo insuficiente', $e->getMessage());
        }
        $this->assertSame(10, $this->bal($this->a, $this->p1));
        $this->assertSame('borrador', $t->fresh()->status);
        $this->assertSame(0, InventoryMovement::where('reference_type', WarehouseTransfer::class)->count());
    }

    public function test_origen_y_destino_deben_ser_distintos(): void
    {
        $this->expectException(\DomainException::class);
        app(WarehouseTransferService::class)->create($this->a->id, $this->a->id, [['product_id' => $this->p1->id, 'quantity' => 1]], null, null);
    }

    public function test_recepcion_parcial_exige_nota_y_registra_diferencia(): void
    {
        $svc = app(WarehouseTransferService::class);
        $t = $this->transfer([['product_id' => $this->p1->id, 'quantity' => 5]]);
        $svc->ship($t, $this->admin->id);
        $line = $t->lines()->first();

        try {
            $svc->receive($t->fresh(), [$line->id => 3], '', $this->admin->id);
            $this->fail('Debió exigir nota');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('nota', $e->getMessage());
        }
        $this->assertSame('en_transito', $t->fresh()->status);

        $svc->receive($t->fresh(), [$line->id => 3], 'Llegaron 2 dañadas', $this->admin->id);
        $this->assertSame(3, $this->bal($this->b, $this->p1));
        $this->assertSame(3, $line->fresh()->received_quantity);
        $this->assertSame(8, $this->global($this->p1)); // 2 faltantes documentados
        $this->assertSame('Llegaron 2 dañadas', $t->fresh()->receipt_notes);
    }

    public function test_cancelar_en_transito_revierte_y_es_idempotente(): void
    {
        $svc = app(WarehouseTransferService::class);
        $t = $this->transfer([['product_id' => $this->p1->id, 'quantity' => 4]]);
        $svc->ship($t, $this->admin->id);
        $svc->ship($t->fresh(), $this->admin->id); // idempotente: no vuelve a descontar
        $this->assertSame(6, $this->bal($this->a, $this->p1));

        $svc->cancel($t->fresh(), $this->admin->id);
        $svc->cancel($t->fresh(), $this->admin->id);
        $this->assertSame(10, $this->bal($this->a, $this->p1));
        $this->assertSame(10, $this->global($this->p1));
        $this->assertSame('cancelado', $t->fresh()->status);

        $this->expectException(\DomainException::class);
        $svc->ship($t->fresh(), $this->admin->id);
    }

    public function test_recibir_dos_veces_no_duplica_entrada(): void
    {
        $svc = app(WarehouseTransferService::class);
        $t = $this->transfer([['product_id' => $this->p2->id, 'quantity' => 2]]);
        $svc->ship($t, $this->admin->id);
        $svc->receive($t->fresh(), [], null, $this->admin->id);
        $svc->receive($t->fresh(), [], null, $this->admin->id);
        $this->assertSame(2, $this->bal($this->b, $this->p2));
    }

    public function test_conteo_genera_ajustes_solo_para_diferencias(): void
    {
        $svc = app(StockCountService::class);
        $count = $svc->create($this->a->id, 'all', [], null, null, $this->admin->id);
        $this->assertMatchesRegularExpression('/^CNT-\d{4}-0001$/', $count->number);
        $this->assertSame(2, $count->lines()->count());

        $l1 = $count->lines()->where('product_id', $this->p1->id)->first();
        $l2 = $count->lines()->where('product_id', $this->p2->id)->first();
        $svc->saveLine($count, $l1->id, 7);  // -3
        $svc->saveLine($count, $l2->id, 5);  // sin diferencia
        $this->assertSame('en_conteo', $count->fresh()->status);

        try {
            $svc->applyAdjustments($count->fresh());
            $this->fail('No debe ajustar un conteo sin cerrar');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('Cierra el conteo', $e->getMessage());
        }

        $svc->close($count->fresh(), $this->admin->id);
        $svc->applyAdjustments($count->fresh());
        $svc->applyAdjustments($count->fresh()); // idempotente

        $this->assertSame(7, $this->bal($this->a, $this->p1));
        $this->assertSame(7, $this->global($this->p1));
        $this->assertSame(5, $this->bal($this->a, $this->p2));
        $this->assertSame(1, InventoryMovement::where('reference_type', StockCount::class)->count());
        $this->assertSame('ajustado', $count->fresh()->status);

        $report = $svc->report($count->fresh());
        $this->assertSame(-300.0, $report['total_net']);
        $this->assertCount(1, $report['rows']);
    }

    public function test_conteo_sobrante_suma_y_cerrado_no_permite_captura(): void
    {
        $svc = app(StockCountService::class);
        $count = $svc->create($this->a->id, 'products', [$this->p1->id], null, null, null);
        $line = $count->lines()->first();
        $svc->saveLine($count, $line->id, 12);
        $svc->close($count->fresh(), null);
        $svc->applyAdjustments($count->fresh());
        $this->assertSame(12, $this->bal($this->a, $this->p1));

        $this->expectException(\DomainException::class);
        $svc->saveLine($count->fresh(), $line->id, 1);
    }

    public function test_reservas_tolerantes_sin_tabla(): void
    {
        $this->assertSame([], app(InventoryService::class)->reservedQuantities([$this->p1->id], $this->a->id));
    }

    public function test_ubicacion_asignable_sin_alterar_cantidades(): void
    {
        $loc = WarehouseLocation::create(['warehouse_id' => $this->a->id, 'code' => 'A-01']);
        $balance = WarehouseProductStock::where('warehouse_id', $this->a->id)->where('product_id', $this->p1->id)->first();

        $this->postJson(route('admin.warehouse-locations.assign'), ['balance_id' => $balance->id, 'location_id' => $loc->id])->assertOk();
        $this->assertSame($loc->id, $balance->fresh()->location_id);
        $this->assertSame(10, $balance->fresh()->quantity);

        $other = WarehouseLocation::create(['warehouse_id' => $this->b->id, 'code' => 'B-01']);
        $this->postJson(route('admin.warehouse-locations.assign'), ['balance_id' => $balance->id, 'location_id' => $other->id])->assertStatus(422);
    }

    public function test_pantallas_responden(): void
    {
        $this->withoutVite();
        $t = $this->transfer([['product_id' => $this->p1->id, 'quantity' => 2]]);
        $count = app(StockCountService::class)->create($this->a->id, 'all', [], null, null, null);

        foreach ([
            route('admin.warehouse-transfers.index'), route('admin.warehouse-transfers.create'),
            route('admin.warehouse-transfers.show', $t), route('admin.warehouse-transfers.edit', $t),
            route('admin.warehouse-transfers.products', ['q' => 'Bom', 'warehouse_id' => $this->a->id]),
            route('admin.stock-counts.index'), route('admin.stock-counts.create'), route('admin.stock-counts.show', $count),
            route('admin.warehouse-locations.index'), route('admin.inventory.bulk-edit'), route('admin.inventory.warehouses.index'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_flujo_http_traspaso_y_conteo(): void
    {
        $this->withoutVite();
        $res = $this->post(route('admin.warehouse-transfers.store'), [
            'from_warehouse_id' => $this->a->id, 'to_warehouse_id' => $this->b->id,
            'lines' => [['product_id' => $this->p1->id, 'quantity' => 3]],
        ]);
        $t = WarehouseTransfer::firstOrFail();
        $res->assertRedirect(route('admin.warehouse-transfers.show', $t));
        $this->post(route('admin.warehouse-transfers.ship', $t))->assertSessionHas('success');
        $this->post(route('admin.warehouse-transfers.receive', $t), ['received' => []])->assertSessionHas('success');
        $this->assertSame(3, $this->bal($this->b, $this->p1));

        $this->post(route('admin.stock-counts.store'), ['warehouse_id' => $this->a->id, 'mode' => 'all']);
        $count = StockCount::firstOrFail();
        $line = $count->lines()->where('product_id', $this->p1->id)->first();
        $this->patchJson(route('admin.stock-counts.lines.update', [$count, $line]), ['counted_quantity' => 5])
            ->assertOk()->assertJson(['success' => true, 'difference' => -2]);
        $this->post(route('admin.stock-counts.close', $count), ['apply' => 1])->assertSessionHas('success');
        $this->assertSame(5, $this->bal($this->a, $this->p1));
        $this->get(route('admin.stock-counts.export', $count))->assertOk();
        // Pantallas de documentos ya recibidos / cerrados (rutas de render distintas al borrador).
        $this->get(route('admin.warehouse-transfers.show', $t))->assertOk()->assertSee('Recibido');
        $this->get(route('admin.stock-counts.show', $count))->assertOk()->assertSee('Reporte de diferencias');
    }
    public function test_reservas_por_proyecto_lista_agrupa_y_libera(): void
    {
        $this->withoutVite();
        $project = \App\Models\Project::forceCreate(['project_number' => 'PRY-TEST-1', 'name' => 'Caldera Hotel']);
        $r1 = \App\Models\StockReservation::create([
            'product_id' => $this->p1->id, 'warehouse_id' => $this->a->id, 'quantity' => 3, 'project_id' => $project->id, 'status' => 'activa',
        ]);
        $r2 = \App\Models\StockReservation::create([
            'product_id' => $this->p2->id, 'warehouse_id' => $this->a->id, 'quantity' => 1, 'project_id' => $project->id, 'status' => 'activa',
        ]);
        \App\Models\StockReservation::create(['product_id' => $this->p2->id, 'quantity' => 9, 'project_id' => $project->id, 'status' => 'liberada']);

        $this->get(route('admin.stock-reservations.index'))->assertOk()
            ->assertSee('Reservas por proyecto')->assertSee('PRY-TEST-1')->assertSee('Bomba')->assertSee('Válvula')->assertSee('2 reservas activas', false)
            ->assertDontSee('9 uds');

        $this->get(route('admin.stock-reservations.index', ['project_id' => $project->id, 'search' => 'Bomba']))->assertOk()
            ->assertSee('Bomba')->assertDontSee('Válvula');

        $this->post(route('admin.stock-reservations.release'), ['ids' => [$r1->id]])->assertSessionHas('success');
        $this->assertSame('liberada', $r1->fresh()->status);
        $this->assertSame('activa', $r2->fresh()->status);
        $this->post(route('admin.stock-reservations.release'), [])->assertSessionHasErrors('ids');
    }
}
