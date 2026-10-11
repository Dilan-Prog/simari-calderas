<?php

namespace Tests\Feature\Production;

use App\Models\Customer;
use App\Models\Permission;
use App\Models\ProductionOrder;
use App\Models\Project;
use App\Models\Role;
use App\Models\ServiceLog;
use App\Models\ServiceType;
use App\Models\TechnicalService;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\BomService;
use App\Services\Erp\Kpis\ProductionOrdersKpi;
use App\Services\InventoryService;
use App\Services\ProductionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesBadgeFixtures;
use Tests\TestCase;

class ProductionHttpTest extends TestCase
{
    use RefreshDatabase, CreatesBadgeFixtures;

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name_role' => 'Administrador'], ['name_role_es' => 'Administrador']);

        return $this->userWith($role);
    }

    private function userWith(Role $role): User
    {
        return User::create([
            'first_name' => 'U', 'last_name' => 'Test', 'position' => 'X', 'phone' => '5555555555',
            'email' => 'u-' . uniqid() . '@example.com', 'password' => bcrypt('password'), 'status' => 'active',
            'rfc' => strtoupper(substr(md5(uniqid('', true)), 0, 13)), 'role_id' => $role->id,
        ]);
    }

    private function viewerOnly(): User
    {
        $role = Role::create(['name_role' => 'Operador', 'name_role_es' => 'Operador']);
        $perm = Permission::create(['name' => 'Ver calendario', 'code' => 'service-calendar.view', 'module' => 'service-calendar', 'action' => 'view']);
        $role->permissions()->attach($perm->id);

        return $this->userWith($role);
    }

    private function service(array $over = []): TechnicalService
    {
        static $n = 0;
        $n++;
        $customer = Customer::first() ?? Customer::create([
            'first_name' => 'Ana', 'last_name' => 'Diaz', 'email' => 'ana@example.com', 'phone' => '1', 'password_hash' => 'x',
            'document_type' => 'RFC', 'document_numer' => 'X', 'birth_date' => '1990-01-01', 'status' => 'active', 'source' => 'web', 'company' => 'ACME',
        ]);
        $type = ServiceType::first();
        if (!$type) {
            $type = new ServiceType(['name' => 'Instalación', 'is_active' => true]);
            $type->timestamps = false;
            $type->save();
        }

        return TechnicalService::create(array_merge([
            'service_number' => 'SERV-T-' . $n, 'service_code' => 'SC-T-' . $n, 'customer_id' => $customer->id,
            'service_type_id' => $type->id, 'status' => 'scheduled', 'service_date' => '2026-10-15', 'service_time' => '09:00:00',
            'estimated_duration' => 2, 'priority' => 'normal',
        ], $over));
    }

    public function test_calendario_json_filtra_y_reprograma_con_bitacora(): void
    {
        $admin = $this->admin();
        $a = $this->service();
        $b = $this->service(['service_date' => '2026-10-20', 'status' => 'cancelled']);
        $tech = $this->admin();
        $a->assignedTechnicians()->attach($tech->id);

        $this->actingAs($admin)->withoutVite()->get(route('admin.service-calendar.index'))->assertOk()->assertSee('Calendario de servicios');

        $res = $this->actingAs($admin)->getJson(route('admin.service-calendar.events', ['start' => '2026-10-01', 'end' => '2026-10-31']))->assertOk();
        $this->assertCount(2, $res->json('events'));
        $first = collect($res->json('events'))->firstWhere('id', $a->id);
        $this->assertSame('09:00', $first['time']);
        $this->assertSame('11:00', $first['end_time']);

        $res = $this->actingAs($admin)->getJson(route('admin.service-calendar.events', ['start' => '2026-10-01', 'end' => '2026-10-31', 'technician_id' => $tech->id]))->assertOk();
        $this->assertCount(1, $res->json('events'));
        $res = $this->actingAs($admin)->getJson(route('admin.service-calendar.events', ['start' => '2026-10-01', 'end' => '2026-10-31', 'status' => 'cancelled']))->assertOk();
        $this->assertSame($b->id, $res->json('events.0.id'));

        $this->actingAs($admin)->patchJson(route('admin.service-calendar.reschedule', $a->id), ['service_date' => '2026-10-17', 'service_time' => '14:30'])
            ->assertOk()->assertJson(['success' => true, 'changed' => true]);
        $a->refresh();
        $this->assertSame('2026-10-17', $a->service_date->format('Y-m-d'));
        $this->assertSame('14:30', substr((string) $a->service_time, 0, 5));
        $this->assertSame(1, ServiceLog::where('service_id', $a->id)->where('action', 'rescheduled')->count());

        // Sin cambios no registra bitácora.
        $this->actingAs($admin)->patchJson(route('admin.service-calendar.reschedule', $a->id), ['service_date' => '2026-10-17'])->assertOk()->assertJson(['changed' => false]);
        $this->assertSame(1, ServiceLog::where('service_id', $a->id)->count());

        // Servicios cancelados / completados no se mueven.
        $this->actingAs($admin)->patchJson(route('admin.service-calendar.reschedule', $b->id), ['service_date' => '2026-10-25'])->assertStatus(422);
    }

    public function test_reprogramar_exige_permiso_de_edicion(): void
    {
        $viewer = $this->viewerOnly();
        $s = $this->service();

        $this->actingAs($viewer)->getJson(route('admin.service-calendar.events', ['start' => '2026-10-01', 'end' => '2026-10-31']))->assertOk();
        $this->actingAs($viewer)->patchJson(route('admin.service-calendar.reschedule', $s->id), ['service_date' => '2026-10-17'])->assertStatus(403);
        $this->assertSame('2026-10-15', $s->fresh()->service_date->format('Y-m-d'));

        $this->actingAs($viewer)->get(route('admin.production-orders.index'))->assertRedirect(route('admin.dashboard'));
    }

    public function test_pantallas_de_ordenes_y_acciones_http(): void
    {
        $admin = $this->admin();
        $wh = Warehouse::create(['name' => 'Principal', 'is_active' => true]);
        $pt = $this->makeProduct(['name' => 'Tablero', 'stock' => 0]);
        $mat = $this->makeProduct(['name' => 'Madera', 'stock' => 0, 'cost' => 5]);
        (new BomService())->create(['product_id' => $pt->id, 'is_active' => true], [['component_product_id' => $mat->id, 'quantity' => 2, 'unit' => 'pza', 'scrap_percent' => 0]]);
        (new InventoryService())->applyMovement($wh->id, $mat->id, 'entrada', 50, null, null, 'seed');
        $project = Project::create(['project_number' => 'PRY-2026-0009', 'name' => 'Obra', 'status' => 'planeado']);

        $this->actingAs($admin)->withoutVite();
        $this->get(route('admin.production-orders.index'))->assertOk();
        $this->get(route('admin.production-orders.create', ['project_id' => $project->id]))->assertOk();
        $this->getJson(route('admin.production-orders.search-products', ['q' => 'Tabl']))->assertOk()->assertJsonFragment(['name' => 'Tablero']);
        $this->getJson(route('admin.production-orders.search-products', ['q' => 'Madera']))->assertOk()->assertJsonCount(0);

        $this->post(route('admin.production-orders.store'), [
            'product_id' => $pt->id, 'quantity' => 5, 'warehouse_id' => $wh->id, 'project_id' => $project->id, 'due_date' => now()->subDay()->toDateString(),
        ])->assertRedirect();
        $order = ProductionOrder::firstOrFail();

        $this->get(route('admin.production-orders.show', $order))->assertOk()->assertSee($order->number);
        $this->get(route('admin.production-orders.index', ['overdue' => 1, 'status' => 'borrador']))->assertOk()->assertSee($order->number);
        $this->get(route('admin.production-orders.edit', $order))->assertOk();
        $this->put(route('admin.production-orders.update', $order), ['notes' => 'hola', 'project_id' => $project->id])->assertRedirect();

        $this->post(route('admin.production-orders.release', $order))->assertRedirect();
        $this->post(route('admin.production-orders.consume', $order), ['quantities' => [$order->components()->first()->id => 4]])->assertRedirect();
        $this->get(route('admin.production-orders.show', $order))->assertOk();
        $this->post(route('admin.production-orders.finish', $order), ['quantity' => 5, 'token' => 'abc'])->assertRedirect();

        $order->refresh();
        $this->assertSame('terminada', $order->status);
        $this->assertSame(5, (int) \App\Models\WarehouseProductStock::where('product_id', $pt->id)->sum('quantity'));

        // Pantalla de proyecto con panel de producción, y KPI.
        $this->get(route('admin.projects.show', $project))->assertOk()->assertSee('Producción y servicios')->assertSee($order->number);
        $kpi = ProductionOrdersKpi::kpi();
        $this->assertSame('0 / 0', $kpi['value']);

        $this->delete(route('admin.production-orders.destroy', $order))->assertSessionHasErrors('status');
    }

    public function test_crear_sin_bom_regresa_error_de_validacion(): void
    {
        $admin = $this->admin();
        $p = $this->makeProduct(['name' => 'Sin receta']);
        $this->actingAs($admin)->withoutVite()->post(route('admin.production-orders.store'), ['product_id' => $p->id, 'quantity' => 1])
            ->assertSessionHasErrors('product_id');
        $this->assertSame(0, ProductionOrder::count());
    }
}
