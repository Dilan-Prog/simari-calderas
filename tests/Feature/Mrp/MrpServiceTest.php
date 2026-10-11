<?php

namespace Tests\Feature\Mrp;

use App\Models\Customer;
use App\Models\MaterialRequirement;
use App\Models\Products;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\ServiceMaterialPlanned;
use App\Models\ServiceType;
use App\Models\StockReservation;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\TechnicalService;
use App\Models\User;
use App\Services\BomService;
use App\Services\Erp\DocumentFlow;
use App\Services\MrpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesBadgeFixtures;
use Tests\TestCase;

class MrpServiceTest extends TestCase
{
    use RefreshDatabase, CreatesBadgeFixtures;

    private MrpService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new MrpService();
    }

    private function admin(): User
    {
        $role = Role::create(['name_role' => 'Administrador', 'name_role_es' => 'Administrador']);

        return User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'Administrador', 'phone' => '5555555555',
            'email' => 'a-' . uniqid() . '@example.com', 'password' => bcrypt('password'), 'status' => 'active',
            'rfc' => 'XAXX010101000', 'role_id' => $role->id,
        ]);
    }

    private function bom(Products $product, array $components): void
    {
        $lines = [];
        foreach ($components as [$p, $qty, $scrap]) {
            $lines[] = ['component_product_id' => $p->id, 'quantity' => $qty, 'unit' => 'pza', 'scrap_percent' => $scrap];
        }
        (new BomService())->create(['product_id' => $product->id, 'is_active' => true], $lines);
    }

    private function order(array $items, array $extra = []): SalesOrder
    {
        $order = SalesOrder::create(array_merge(['order_number' => SalesOrder::generateOrderNumber(), 'status' => 'pendiente'], $extra));
        foreach ($items as $i => [$product, $qty]) {
            $order->items()->create([
                'product_id' => $product->id, 'product_name' => $product->name, 'product_sku' => $product->sku,
                'unit' => 'pza', 'quantity_ordered' => $qty, 'quantity_delivered' => 0, 'sort_order' => $i,
            ]);
        }

        return $order;
    }

    private function supplier(string $name): Supplier
    {
        return Supplier::create(['company_name' => $name, 'status' => 'active']);
    }

    private function link(Supplier $s, Products $p, ?float $cost, bool $primary = false): void
    {
        SupplierProduct::create(['supplier_id' => $s->id, 'product_id' => $p->id, 'cost' => $cost, 'is_primary' => $primary, 'lead_time_days' => 5]);
    }

    /** Caldera = 3 quemadores + 1 acero + 10 tornillos(5%); quemador = 2 acero(10%) + 4 tornillos. */
    private function calderaFixture(): array
    {
        $caldera = $this->makeProduct(['name' => 'Caldera', 'stock' => 0]);
        $quemador = $this->makeProduct(['name' => 'Quemador', 'stock' => 0]);
        $acero = $this->makeProduct(['name' => 'Acero', 'stock' => 10, 'cost' => 10]);
        $tornillo = $this->makeProduct(['name' => 'Tornillo', 'stock' => 100, 'cost' => 1]);
        $bomba = $this->makeProduct(['name' => 'Bomba', 'stock' => 1, 'cost' => 500]);

        $this->bom($quemador, [[$acero, 2, 10], [$tornillo, 4, 0]]);
        $this->bom($caldera, [[$quemador, 3, 0], [$acero, 1, 0], [$tornillo, 10, 5]]);

        return compact('caldera', 'quemador', 'acero', 'tornillo', 'bomba');
    }

    public function test_demanda_por_bom_multinivel_y_linea_directa_con_faltante(): void
    {
        $f = $this->calderaFixture();
        $order = $this->order([[$f['caldera'], 2], [$f['bomba'], 3]]);

        $req = $this->svc->buildForSalesOrder($order);
        $lines = $req->lines->keyBy('product_id');

        $this->assertMatchesRegularExpression('/^REQ-\d{4}-\d{4}$/', $req->number);
        $this->assertSame('sales_order', $req->origin_type);
        $this->assertCount(3, $lines); // acero, tornillo, bomba (subensamble y caldera expandidos)

        // Acero: 2 * (3*2*1.10 + 1) = 15.2 ; existencia 10 -> faltan 5.2
        $this->assertEqualsWithDelta(15.2, $lines[$f['acero']->id]->required_quantity, 0.0001);
        $this->assertSame('componente_bom', $lines[$f['acero']->id]->source);
        $this->assertEqualsWithDelta(5.2, $lines[$f['acero']->id]->shortfall, 0.0001);
        // Tornillo: 45 requeridos de 100 -> cubierto
        $this->assertEqualsWithDelta(45.0, $lines[$f['tornillo']->id]->required_quantity, 0.0001);
        $this->assertEqualsWithDelta(0.0, $lines[$f['tornillo']->id]->shortfall, 0.0001);
        // Bomba: compra directa 3, existencia 1 -> faltan 2
        $this->assertSame('linea_pedido', $lines[$f['bomba']->id]->source);
        $this->assertEqualsWithDelta(2.0, $lines[$f['bomba']->id]->shortfall, 0.0001);

        $this->assertSame('calculado', $req->status);
        // Documento de flujo: pedido -> requerimiento
        $this->assertContains($req->number, array_column(DocumentFlow::flow($order), 'number'));
    }

    public function test_build_es_idempotente_por_origen_abierto(): void
    {
        $f = $this->calderaFixture();
        $order = $this->order([[$f['caldera'], 1]]);

        $a = $this->svc->buildForSalesOrder($order);
        $b = $this->svc->buildForSalesOrder($order);

        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, MaterialRequirement::count());
    }

    public function test_demanda_por_servicio_con_materiales_planeados(): void
    {
        $quimico = $this->makeProduct(['name' => 'Cloro', 'stock' => 4]);
        $customer = Customer::create([
            'first_name' => 'Ana', 'last_name' => 'Diaz', 'email' => 'ana@example.com', 'phone' => '1', 'password_hash' => 'x',
            'document_type' => 'RFC', 'document_numer' => 'X', 'birth_date' => '1990-01-01', 'status' => 'active', 'source' => 'web', 'company' => 'ACME',
        ]);
        $type = new ServiceType(['name' => 'Instalación', 'is_active' => true]);
        $type->timestamps = false;
        $type->save();
        $service = TechnicalService::create([
            'service_number' => 'SERV-T-1', 'service_code' => 'SC-T-1', 'customer_id' => $customer->id,
            'service_type_id' => $type->id, 'status' => 'scheduled', 'service_date' => now()->addDays(7)->toDateString(),
        ]);
        ServiceMaterialPlanned::create(['service_id' => $service->id, 'product_id' => $quimico->id, 'product_name' => 'Cloro', 'quantity' => 10, 'unit' => 'litros', 'sort_order' => 0]);
        // Material libre sin producto: no entra al cálculo.
        ServiceMaterialPlanned::create(['service_id' => $service->id, 'product_id' => null, 'product_name' => 'Cinta', 'quantity' => 2, 'sort_order' => 1]);

        $req = $this->svc->buildForTechnicalService($service);

        $this->assertSame('technical_service', $req->origin_type);
        $this->assertSame($customer->id, $req->customer_id);
        $this->assertStringStartsWith('REQ-' . date('Y') . '-', $req->number);
        $this->assertCount(1, $req->lines);
        $line = $req->lines->first();
        $this->assertSame('servicio', $line->source);
        $this->assertEqualsWithDelta(10.0, $line->required_quantity, 0.0001);
        $this->assertEqualsWithDelta(6.0, $line->shortfall, 0.0001);
        $this->assertSame('litros', $line->unit);
    }

    public function test_faltante_descuenta_reservas_de_otros_y_no_las_propias(): void
    {
        $f = $this->calderaFixture();
        // Otro proyecto ya reservó 4 de acero.
        StockReservation::create(['product_id' => $f['acero']->id, 'quantity' => 4, 'project_id' => 999, 'status' => 'activa']);
        // Reservas liberadas no cuentan.
        StockReservation::create(['product_id' => $f['acero']->id, 'quantity' => 3, 'status' => 'liberada']);

        $req = $this->svc->buildForSalesOrder($this->order([[$f['caldera'], 2]]));
        $acero = $req->lines->firstWhere('product_id', $f['acero']->id);

        $this->assertEqualsWithDelta(4.0, $acero->reserved, 0.0001);
        $this->assertEqualsWithDelta(10.0, $acero->on_hand, 0.0001);
        // 15.2 - (10 - 4) = 9.2
        $this->assertEqualsWithDelta(9.2, $acero->shortfall, 0.0001);
    }

    public function test_reservar_es_idempotente_ajusta_y_libera(): void
    {
        $f = $this->calderaFixture();
        $req = $this->svc->buildForSalesOrder($this->order([[$f['caldera'], 2]]));

        $this->svc->reserve($req);
        $this->svc->reserve($req);

        $acero = StockReservation::where('requirement_id', $req->id)->where('product_id', $f['acero']->id)->where('status', 'activa')->get();
        $this->assertCount(1, $acero);
        $this->assertEqualsWithDelta(10.0, $acero->first()->quantity, 0.0001); // min(15.2, 10)
        $tornillo = StockReservation::where('requirement_id', $req->id)->where('product_id', $f['tornillo']->id)->where('status', 'activa')->first();
        $this->assertEqualsWithDelta(45.0, $tornillo->quantity, 0.0001);
        $this->assertSame($req->id, $acero->first()->source_id);

        // Las reservas propias no cuentan como "de otros": el faltante se mantiene.
        $line = $req->fresh('lines')->lines->firstWhere('product_id', $f['acero']->id);
        $this->assertEqualsWithDelta(5.2, $line->shortfall, 0.0001);
        $this->assertEqualsWithDelta(10.0, $line->own_reserved, 0.0001);

        // Un segundo requerimiento ya no ve esas existencias.
        $other = $this->svc->buildForSalesOrder($this->order([[$f['caldera'], 1]]));
        $oAcero = $other->lines->firstWhere('product_id', $f['acero']->id);
        $this->assertEqualsWithDelta(10.0, $oAcero->reserved, 0.0001);
        $this->assertEqualsWithDelta(7.6, $oAcero->shortfall, 0.0001); // 7.6 - 0

        // Al bajar la existencia y recalcular, la reserva se reduce.
        $f['acero']->update(['stock' => 6]);
        $this->svc->recalculate($req);
        $this->assertEqualsWithDelta(6.0, StockReservation::where('requirement_id', $req->id)->where('product_id', $f['acero']->id)->where('status', 'activa')->sum('quantity'), 0.0001);

        $this->svc->release($req);
        $this->assertSame(0, StockReservation::where('requirement_id', $req->id)->where('status', 'activa')->count());
        $this->assertGreaterThan(0, StockReservation::where('requirement_id', $req->id)->where('status', 'liberada')->count());
        $this->assertEqualsWithDelta(0.0, $req->fresh('lines')->lines->first()->own_reserved, 0.0001);
    }

    public function test_sugerencias_agrupan_por_proveedor_principal_menor_precio_y_sin_proveedor(): void
    {
        $f = $this->calderaFixture();
        $prov1 = $this->supplier('Aceros SA');
        $prov2 = $this->supplier('Herrajes SA');
        $prov3 = $this->supplier('Bombas SA');
        // Acero: principal = prov1 aunque prov2 sea más barato.
        $this->link($prov1, $f['acero'], 12, true);
        $this->link($prov2, $f['acero'], 9, false);
        // Bomba: sin principal -> el de menor precio (prov3: 450 < prov2: 480).
        $this->link($prov2, $f['bomba'], 480);
        $this->link($prov3, $f['bomba'], 450);
        // Tornillo: sin proveedor y con existencia suficiente -> no se sugiere.

        $sin = $this->makeProduct(['name' => 'Manguera', 'stock' => 0]);
        $order = $this->order([[$f['caldera'], 2], [$f['bomba'], 3], [$sin, 5]]);
        $req = $this->svc->buildForSalesOrder($order);

        $groups = collect($this->svc->suggestPurchases($req))->keyBy(fn ($g) => $g['supplier_id'] ?? 'none');

        $this->assertCount(3, $groups);
        $this->assertEqualsWithDelta(5.2, $groups[$prov1->id]['lines'][0]['line']->shortfall, 0.0001);
        $this->assertSame(6, $groups[$prov1->id]['lines'][0]['quantity']); // ceil(5.2)
        $this->assertEqualsWithDelta(72.0, $groups[$prov1->id]['subtotal'], 0.01); // 6 * 12
        $this->assertEqualsWithDelta(900.0, $groups[$prov3->id]['subtotal'], 0.01); // 2 * 450
        $this->assertArrayNotHasKey($prov2->id, $groups->all());
        $this->assertFalse($groups['none']['has_supplier']);
        $this->assertSame($sin->id, $groups['none']['lines'][0]['product']->id);
        // El grupo "Sin proveedor" va al final.
        $this->assertNull(collect($this->svc->suggestPurchases($req))->last()['supplier_id']);
    }

    public function test_genera_ordenes_de_compra_y_las_liga_al_flujo(): void
    {
        $this->actingAs($this->admin());
        $f = $this->calderaFixture();
        $prov1 = $this->supplier('Aceros SA');
        $prov3 = $this->supplier('Bombas SA');
        $this->link($prov1, $f['acero'], 12, true);
        $this->link($prov3, $f['bomba'], 450);
        $sin = $this->makeProduct(['name' => 'Manguera', 'stock' => 0]);

        $order = $this->order([[$f['caldera'], 2], [$f['bomba'], 3], [$sin, 5]]);
        $req = $this->svc->buildForSalesOrder($order);

        // Solo se seleccionan acero y manguera (la manguera con proveedor elegido a mano).
        $aceroLine = $req->lines->firstWhere('product_id', $f['acero']->id);
        $sinLine = $req->lines->firstWhere('product_id', $sin->id);
        $pos = $this->svc->generatePurchaseOrders($req, [$aceroLine->id, $sinLine->id], [$sinLine->id => $prov3->id]);

        $this->assertCount(2, $pos);
        $po1 = $pos->firstWhere('supplier_id', $prov1->id);
        $this->assertSame('pending', $po1->status);
        $this->assertSame('MXN', $po1->currency);
        $this->assertEqualsWithDelta(72.0, (float) $po1->subtotal, 0.001);
        $this->assertEqualsWithDelta(11.52, (float) $po1->tax_total, 0.001);
        $this->assertEqualsWithDelta(83.52, (float) $po1->total, 0.001);
        $this->assertCount(1, $po1->items);
        $this->assertSame(6, (int) $po1->items->first()->quantity);
        $this->assertMatchesRegularExpression('/^PO-\d{4}-\d{4}$/', $po1->po_number);

        $req = $req->fresh('lines');
        $this->assertSame('en_compra', $req->status);
        $this->assertSame($po1->id, $req->lines->firstWhere('product_id', $f['acero']->id)->purchase_order_id);
        $this->assertNull($req->lines->firstWhere('product_id', $f['bomba']->id)->purchase_order_id);

        // Flujo: pedido -> requerimiento -> OCs
        $numbers = array_column(DocumentFlow::flow($req), 'number');
        $this->assertContains($order->order_number, $numbers);
        $this->assertContains($po1->po_number, $numbers);
        $this->assertSame('compras', $this->svc->processStep($req));

        // Idempotente: lo ya comprado no vuelve a generarse; queda solo la bomba.
        $again = $this->svc->generatePurchaseOrders($req);
        $this->assertCount(1, $again);
        $this->assertSame($prov3->id, $again->first()->supplier_id);
        $this->assertSame(3, PurchaseOrder::count());

        $this->expectException(ValidationException::class);
        $this->svc->generatePurchaseOrders($req->fresh());
    }

    public function test_requerimiento_manual_y_estado_cubierto(): void
    {
        $p = $this->makeProduct(['stock' => 20]);
        $req = $this->svc->buildManual(['needed_by_date' => now()->addDays(3)->toDateString()], [
            ['product_id' => $p->id, 'quantity' => 5, 'unit' => 'pza'],
            ['product_id' => $p->id, 'quantity' => 3],
        ]);

        $this->assertSame('manual', $req->origin_type);
        $this->assertCount(1, $req->lines);
        $this->assertEqualsWithDelta(8.0, $req->lines->first()->required_quantity, 0.0001);
        $this->assertSame('cubierto', $req->status);
        $this->assertSame('logistica', $this->svc->processStep($req));

        $p->update(['stock' => 2]);
        $this->assertSame('calculado', $this->svc->recalculate($req)->status);
        $this->assertSame('planeacion', $this->svc->processStep($req->fresh()));

        $this->svc->cancel($req);
        $this->assertNull($this->svc->processStep($req->fresh()));
    }

    public function test_demanda_por_proyecto_suma_pedidos_y_enlaza_el_flujo(): void
    {
        $f = $this->calderaFixture();
        $project = \App\Models\Project::create(['project_number' => \App\Models\Project::generateProjectNumber(), 'name' => 'Cuarto de máquinas', 'status' => 'planeado', 'due_date' => now()->addDays(20)->toDateString()]);
        $o1 = $this->order([[$f['caldera'], 1]], ['project_id' => $project->id]);
        $this->order([[$f['bomba'], 2]], ['project_id' => $project->id]);
        $this->order([[$f['bomba'], 50]]); // otro proyecto: no cuenta

        $req = $this->svc->buildForProject($project);

        $this->assertSame($project->id, $req->project_id);
        $this->assertSame($project->due_date->toDateString(), $req->needed_by_date->toDateString());
        $lines = $req->lines->keyBy('product_id');
        $this->assertEqualsWithDelta(2.0, $lines[$f['bomba']->id]->required_quantity, 0.0001);
        $this->assertEqualsWithDelta(7.6, $lines[$f['acero']->id]->required_quantity, 0.0001); // 3*2*1.1 + 1 por caldera
        $this->assertContains($project->project_number, array_column(DocumentFlow::flow($req), 'number'));
    }

    public function test_lote_del_listado_genera_oc_de_faltantes_con_proveedor(): void
    {
        $this->withoutVite();
        $admin = $this->admin();
        $f = $this->calderaFixture();
        $prov = $this->supplier('Aceros SA');
        $this->link($prov, $f['acero'], 12, true);
        $order = $this->order([[$f['caldera'], 2]]);
        $this->actingAs($admin)->post(route('admin.mrp.from-sales-order', $order))->assertRedirect();
        $req = MaterialRequirement::firstOrFail();

        $this->post(route('admin.mrp.generate-purchase-orders-batch'), [])->assertSessionHasErrors('ids');
        $this->post(route('admin.mrp.generate-purchase-orders-batch'), ['ids' => [$req->id]])
            ->assertRedirect(route('admin.mrp.index'))->assertSessionHas('success');
        $this->assertSame(1, PurchaseOrder::count());
        $this->get(route('admin.mrp.index'))->assertOk()->assertSee('Generar órdenes de compra');
    }

    public function test_pantallas_y_atajo_desde_pedido(): void
    {
        $this->withoutVite();
        $admin = $this->admin();
        $f = $this->calderaFixture();
        $prov = $this->supplier('Aceros SA');
        $this->link($prov, $f['acero'], 12, true);
        $order = $this->order([[$f['caldera'], 2]]);

        $this->actingAs($admin)->post(route('admin.mrp.from-sales-order', $order))->assertRedirect();
        $req = MaterialRequirement::firstOrFail();

        $this->get(route('admin.mrp.index'))->assertOk()->assertSee($req->number);
        $this->get(route('admin.mrp.index', ['status' => 'calculado', 'shortfall' => 1]))->assertOk()->assertSee($req->number);
        $this->get(route('admin.mrp.create'))->assertOk();
        $this->get(route('admin.mrp.show', $req))->assertOk()
            ->assertSee('Planeación')->assertSee('Aceros SA')->assertSee('Generar órdenes de compra');

        $this->post(route('admin.mrp.reserve', $req))->assertRedirect();
        $this->post(route('admin.mrp.recalculate', $req))->assertRedirect();
        $this->post(route('admin.mrp.generate-purchase-orders', $req), ['selection' => [$req->lines->firstWhere('product_id', $f['acero']->id)->id]])
            ->assertRedirect(route('admin.mrp.show', $req));
        $this->assertSame(1, PurchaseOrder::count());
        $this->get(route('admin.mrp.show', $req))->assertOk()->assertSee('Órdenes de compra generadas');
        $this->post(route('admin.mrp.release', $req))->assertRedirect();

        // El requerimiento anterior ya está en compra: crear otro desde el mismo pedido genera uno nuevo.
        $this->post(route('admin.mrp.store'), ['origin' => 'sales_order', 'origin_id' => $order->id])->assertRedirect();
        $this->post(route('admin.mrp.store'), ['origin' => 'manual', 'lines' => [['product_id' => $f['bomba']->id, 'quantity' => 2]]])->assertRedirect();
        $this->assertSame(3, MaterialRequirement::count());

        $this->assertSame('en_compra', $req->fresh()->status);
        $this->assertSame('3 / 1', \App\Services\Erp\Kpis\MrpKpi::kpi()['value']);
    }
}
