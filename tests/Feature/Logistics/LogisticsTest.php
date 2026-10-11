<?php

namespace Tests\Feature\Logistics;

use App\Models\Customer;
use App\Models\DocumentLink;
use App\Models\Pickup;
use App\Models\Quote;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\Shipment;
use App\Models\StoreOrder;
use App\Models\User;
use App\Services\Erp\DocumentFlow;
use App\Services\PickupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LogisticsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['name_role' => 'Administrador', 'name_role_es' => 'Administrador']);

        return User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'Administrador', 'phone' => '5555555555',
            'email' => 'a-' . uniqid() . '@example.com', 'password' => bcrypt('password'), 'status' => 'active',
            'rfc' => 'XAXX010101000', 'role_id' => $role->id,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function b2bOrder(User $u): SalesOrder
    {
        $c = Customer::create(['first_name' => 'Ana', 'last_name' => 'Lopez', 'email' => 'ana@example.com', 'phone' => '5511112222', 'company' => 'ACME', 'document_type' => 'RFC', 'status' => 'active', 'source' => 'admin']);

        return SalesOrder::create(['order_number' => 'PED-TEST-1', 'customer_id' => $c->id, 'status' => 'pendiente', 'created_by_user_id' => $u->id]);
    }

    private function shipmentPayload(array $over = []): array
    {
        return array_merge([
            'guia' => 'GUIA-12345', 'recipient_name' => 'Ana Lopez', 'recipient_company' => 'ACME',
            'delivery_address' => 'Calle 1, CDMX', 'origin_address' => 'Bodega', 'fecha_envio' => '2026-10-12',
            'bultos' => 2, 'recipient_phone' => '5511112222', 'recipient_email' => 'ana@example.com',
        ], $over);
    }

    public function test_envio_b2b_desde_pedido_enlaza_document_flow(): void
    {
        $admin = $this->admin();
        $order = $this->b2bOrder($admin);

        $this->actingAs($admin)->get(route('admin.sales-orders.show', $order))->assertOk()->assertSee('Crear envío');
        $this->actingAs($admin)->get(route('admin.shipments.create', ['sales_order' => $order->id]))
            ->assertOk()->assertSee('Ana Lopez')->assertSee('PED-TEST-1');

        $this->actingAs($admin)->post(route('admin.shipments.store-b2b'), $this->shipmentPayload([
            'source' => 'sales_order:' . $order->id, 'order_number_ref' => 'PED-TEST-1',
        ]))->assertRedirect();

        $s = Shipment::firstOrFail();
        $this->assertNull($s->store_order_id);
        $this->assertSame('sales_order', $s->shippable_type);
        $this->assertSame($order->id, (int) $s->shippable_id);
        $this->assertSame($order->customer_id, $s->customer_id);
        $this->assertSame('preparando', $s->status);
        $this->assertSame(1, $s->statusLogs()->count());
        $this->assertTrue(DocumentLink::where(['from_type' => 'sales_order', 'to_type' => 'shipment', 'to_id' => $s->id])->exists());

        $this->actingAs($admin)->get(route('admin.shipments.show', $s))->assertOk()->assertSee('GUIA-12345')->assertSee('Línea de tiempo');

        // Estatus hacia adelante y entrega, sin StoreOrder.
        $this->actingAs($admin)->patch(route('admin.shipments.update-status', $s), ['status' => 'entregado'])
            ->assertRedirect(route('admin.shipments.show', $s));
        $this->assertSame('entregado', $s->fresh()->status);

        $this->actingAs($admin)->get(route('admin.shipments.index', ['tipo' => 'b2b']))->assertOk()->assertSee('GUIA-12345');
    }

    public function test_envio_b2b_desde_cotizacion(): void
    {
        $admin = $this->admin();
        $q = Quote::create(['quote_number' => 'COT-T-1', 'status' => 'accepted', 'created_by_user_id' => $admin->id, 'guest_name' => 'Pedro', 'guest_email' => 'p@example.com']);

        $this->actingAs($admin)->post(route('admin.shipments.store-b2b'), $this->shipmentPayload(['shippable_type' => 'quote', 'shippable_id' => $q->id]))->assertRedirect();

        $s = Shipment::firstOrFail();
        $this->assertSame('quote', $s->shippable_type);
        $this->assertSame($q->id, $s->quote_id);
        $this->assertTrue(DocumentLink::where(['from_type' => 'quote', 'to_type' => 'shipment'])->exists());
    }

    public function test_envio_de_tienda_sin_regresion(): void
    {
        $admin = $this->admin();
        $order = StoreOrder::unguarded(fn () => StoreOrder::create([
            'order_number' => 'WEB-1', 'contact_name' => 'Cliente Web', 'contact_email' => 'w@example.com', 'contact_phone' => '555',
            'shipping_address_line1' => 'x', 'shipping_city' => 'x', 'shipping_state' => 'x', 'shipping_postal_code' => '1',
            'subtotal' => 100, 'tax_total' => 16, 'total' => 116, 'status' => 'pagado',
        ]));

        $this->actingAs($admin)->post(route('admin.shipments.store', $order), [
            'guia' => 'ABCD 1234 5678', 'fecha_envio' => '2026-10-12', 'bultos' => 1,
        ])->assertRedirect(route('admin.orders.show', $order));

        $s = Shipment::firstOrFail();
        $this->assertSame($order->id, $s->store_order_id);
        $this->assertFalse($s->isB2b());

        $this->actingAs($admin)->patch(route('admin.shipments.update-status', $s), ['status' => 'enviado'])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->actingAs($admin)->get(route('admin.shipments.index'))->assertOk()->assertSee('WEB-1')->assertSee('Cliente Web');
        $this->actingAs($admin)->get(route('admin.shipments.show', $s))->assertNotFound();
    }

    public function test_recoleccion_con_evidencia_firma_y_estatus(): void
    {
        $admin = $this->admin();
        $order = $this->b2bOrder($admin);

        $this->actingAs($admin)->post(route('admin.pickups.store'), [
            'type' => 'pedidos', 'customer_id' => $order->customer_id, 'address' => 'Calle 5', 'scheduled_date' => today()->toDateString(),
            'driver_user_id' => $admin->id, 'time_window' => '9-13',
            'items' => [['description' => 'Caldera 10HP', 'quantity' => 1, 'unit' => 'pza', 'sales_order_id' => $order->id]],
        ])->assertRedirect();

        $p = Pickup::firstOrFail();
        $this->assertMatchesRegularExpression('/^REC-\d{4}-0001$/', $p->number);
        $this->assertSame('programada', $p->status);
        $this->assertSame(1, $p->items()->count());
        $this->assertTrue(DocumentLink::where(['from_type' => 'sales_order', 'to_type' => 'pickup', 'to_id' => $p->id])->exists());

        $this->actingAs($admin)->get(route('admin.pickups.show', $p))->assertOk()->assertSee($p->number);
        $this->actingAs($admin)->get(route('admin.pickups.mobile', $p))->assertOk();
        $this->actingAs($admin)->get(route('admin.pickups.index', ['status' => 'programada']))->assertOk()->assertSee($p->number);

        // Evidencia (foto)
        $file = UploadedFile::fake()->image('evidencia.jpg', 400, 300);
        $this->actingAs($admin)->post(route('admin.pickups.evidences.store', $p), ['photo' => $file, 'caption' => 'Carga'])->assertRedirect();
        $this->assertSame(1, $p->evidences()->count());
        $path = $p->evidences()->first()->path;
        $this->assertStringStartsWith("pickups/{$p->id}/", $path);

        // Firma
        $png = 'data:image/png;base64,' . base64_encode('fakepng');
        $this->actingAs($admin)->post(route('admin.pickups.sign', $p), ['signature_data' => $png, 'signed_by_name' => 'Juan'])->assertRedirect();
        $this->assertNotNull($p->fresh()->signature_data);
        $this->actingAs($admin)->post(route('admin.pickups.sign', $p), ['signature_data' => 'javascript:alert(1)'])->assertSessionHasErrors('signature_data');

        // Estatus
        $this->actingAs($admin)->patch(route('admin.pickups.update-status', $p), ['status' => 'en_ruta'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->patch(route('admin.pickups.update-status', $p), ['status' => 'incidencia'])->assertSessionHasErrors('status');
        $this->actingAs($admin)->patch(route('admin.pickups.update-status', $p), ['status' => 'incidencia', 'note' => 'Nadie atendió']);
        $this->assertSame('incidencia', $p->fresh()->status);
        $this->actingAs($admin)->patch(route('admin.pickups.update-status', $p), ['status' => 'entregada']);
        $this->assertSame('entregada', $p->fresh()->status);
        $this->actingAs($admin)->patch(route('admin.pickups.update-status', $p), ['status' => 'en_ruta'])->assertSessionHasErrors('status');
        $this->assertSame(4, $p->statusLogs()->count()); // programada, en_ruta, incidencia, entregada

        File::deleteDirectory(\App\Support\UploadPath::full("pickups/{$p->id}"));
    }

    public function test_ruta_del_dia_agrupa_por_chofer_y_paqueteria(): void
    {
        $admin = $this->admin();
        $svc = app(PickupService::class);
        $svc->create(['type' => 'material', 'address' => 'A', 'scheduled_date' => today(), 'driver_user_id' => $admin->id], [['description' => 'x']], $admin->id);
        $svc->create(['type' => 'otro', 'address' => 'B', 'scheduled_date' => today()], [], $admin->id);
        $svc->create(['type' => 'otro', 'address' => 'C-otro-dia', 'scheduled_date' => today()->addDays(3)], [], $admin->id);
        Shipment::create(['guia' => 'ENVIO-HOY-1', 'status' => 'enviado', 'fecha_envio' => today(), 'recipient_name' => 'Zed', 'created_by_user_id' => $admin->id, 'bultos' => 1]);

        $res = $this->actingAs($admin)->get(route('admin.pickups.route'));
        $res->assertOk()->assertSee('Chofer: Admin Test')->assertSee('Sin asignar')->assertSee('ENVIO-HOY-1')->assertSee('REC-')
            ->assertDontSee('C-otro-dia');

        $this->actingAs($admin)->get(route('admin.pickups.route', ['date' => today()->addDays(3)->toDateString()]))->assertOk();
    }

    public function test_kpi_de_recolecciones(): void
    {
        $admin = $this->admin();
        $svc = app(PickupService::class);
        $p = $svc->create(['type' => 'otro', 'address' => 'B', 'scheduled_date' => today()], [], $admin->id);
        $k = \App\Services\Erp\Kpis\PickupsKpi::kpi();
        $this->assertSame(1, $k['value']);
        $svc->changeStatus($p, 'incidencia', 'Problema', $admin->id);
        $this->assertSame('bad', \App\Services\Erp\Kpis\PickupsKpi::kpi()['tone']);
    }

    public function test_entrega_de_material_se_enlaza_al_pedido(): void
    {
        $admin = $this->admin();
        $order = $this->b2bOrder($admin);
        $report = app(\App\Services\MaterialDeliveryReportService::class)->store(['sales_order_id' => $order->id], $admin->id);
        $this->assertTrue(DocumentFlow::flow($order) !== [] && collect(DocumentFlow::flow($order))->contains('type', 'material_delivery'));
        $this->assertNotNull($report->id);
    }
}
