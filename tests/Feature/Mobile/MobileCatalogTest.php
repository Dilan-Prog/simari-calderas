<?php

namespace Tests\Feature\Mobile;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Permission;
use App\Models\Role;
use App\Models\ServiceType;
use App\Models\TechnicalService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Catálogos y altas de la app móvil: GET /clientes, POST /servicios,
 * GET /usuarios y GET /roles (routes/api/mobile.php).
 */
class MobileCatalogTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = '/api/mobile/v1';

    // ── Fixtures (mismo estilo que MobileApiTest) ─────────────────────────────

    private function staff(string $roleName = 'Tecnico', array $permissions = [], array $over = []): User
    {
        $role = Role::firstOrCreate(['name_role' => $roleName], ['name_role_es' => $roleName]);
        foreach ($permissions as [$module, $action]) {
            $perm = Permission::firstOrCreate(
                ['code' => "$module.$action"],
                ['name' => "$module $action", 'module' => $module, 'action' => $action]
            );
            $role->permissions()->syncWithoutDetaching([$perm->id]);
        }

        return User::create(array_merge([
            'first_name' => 'Staff', 'last_name' => 'Movil', 'position' => $roleName, 'phone' => '5555555555',
            'email' => 'staff-' . uniqid() . '@example.com', 'password' => 'secret-123',
            'status' => 'active', 'rfc' => 'XAXX' . random_int(100000, 999999) . 'AB1', 'role_id' => $role->id,
        ], $over));
    }

    private function admin(): User
    {
        return $this->staff('Administrador');
    }

    private function headersFor(User $user): array
    {
        return ['Authorization' => 'Bearer ' . $user->createToken('test-device', ['mobile'])->plainTextToken];
    }

    private function customer(array $over = []): Customer
    {
        return Customer::create(array_merge([
            'first_name' => 'Cli', 'last_name' => 'Ente', 'email' => uniqid() . '@example.com', 'phone' => '5511111111',
            'password_hash' => 'x', 'document_type' => 'RFC', 'status' => 'active', 'source' => 'test',
            'company' => 'ACME',
        ], $over));
    }

    private function serviceType(string $name): ServiceType
    {
        $type = new ServiceType(['name' => $name, 'is_active' => true]);
        $type->timestamps = false;
        $type->save();

        return $type;
    }

    private function scheduler(): User
    {
        return $this->staff('Coordinador', [['technical-services', 'create'], ['technical-services', 'view']]);
    }

    // ── GET /clientes ─────────────────────────────────────────────────────────

    public function test_clientes_requires_one_of_the_allowed_permissions(): void
    {
        $this->customer();

        $this->getJson(self::BASE . '/clientes', $this->headersFor($this->staff('Sin permisos')))
            ->assertForbidden()
            ->assertJsonStructure(['message']);

        // Basta con cualquiera de los tres.
        foreach ([['service-reports', 'create'], ['technical-services', 'create'], ['clients', 'view']] as $i => $perm) {
            $h = $this->headersFor($this->staff("Rol $i", [$perm]));
            // Sanctum cachea el usuario entre peticiones del test; se olvida
            // DESPUÉS de crear el usuario (LogsActivity resuelve auth() al guardar).
            $this->app['auth']->forgetGuards();
            $this->getJson(self::BASE . '/clientes', $h)->assertOk();
        }

        $h = $this->headersFor($this->admin());
        $this->app['auth']->forgetGuards();
        $this->getJson(self::BASE . '/clientes', $h)->assertOk();
    }

    public function test_clientes_search_shape_and_pagination(): void
    {
        $user = $this->staff('Tecnico', [['service-reports', 'create']]);
        $lacteos = $this->customer([
            'first_name' => 'Rosa', 'last_name' => 'Pérez', 'company' => 'Lácteos del Bajío',
            'rfc' => 'LBA010101AB1', 'email' => 'rosa@lacteos.test', 'phone' => '4771234567',
        ]);
        CustomerAddress::create([
            'customer_id' => $lacteos->id, 'label' => 'Planta', 'recipient_name' => 'Rosa', 'phone' => '4771234567',
            'country' => 'MX', 'state' => 'Guanajuato', 'city' => 'León', 'postal_code' => '37000',
            'address_line1' => 'Blvd. López Mateos 100', 'is_default' => true,
        ]);
        $this->customer(['first_name' => 'Juan', 'last_name' => 'Gómez', 'company' => '', 'rfc' => 'GOMJ800101XX1']);
        $this->customer(['company' => 'Inactivo SA', 'status' => 'inactive']);
        $h = $this->headersFor($user);

        $res = $this->getJson(self::BASE . '/clientes?search=lacteos', $h)->assertOk();
        // La búsqueda no distingue acentos/mayúsculas por la collation de MySQL; se acepta
        // también la búsqueda exacta por si la collation fuera binaria.
        if ($res->json('meta.total') === 0) {
            $res = $this->getJson(self::BASE . '/clientes?search=Lácteos', $h)->assertOk();
        }
        $res->assertJsonPath('meta.total', 1)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'full_name', 'contact_name', 'company', 'rfc', 'phone', 'email', 'address', 'city', 'state', 'location']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('data.0.id', $lacteos->id)
            ->assertJsonPath('data.0.name', 'Lácteos del Bajío')
            ->assertJsonPath('data.0.contact_name', 'Rosa Pérez')
            ->assertJsonPath('data.0.location', 'León, Guanajuato')
            ->assertJsonPath('data.0.address', 'Blvd. López Mateos 100, León, Guanajuato, C.P. 37000');
        $this->assertArrayNotHasKey('password_hash', $res->json('data.0'));

        // Por RFC, email y teléfono; sin empresa el nombre es la persona.
        $this->getJson(self::BASE . '/clientes?search=GOMJ800101', $h)
            ->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.name', 'Juan Gómez')
            ->assertJsonPath('data.0.company', null)->assertJsonPath('data.0.address', null);
        $this->getJson(self::BASE . '/clientes?search=rosa@lacteos', $h)->assertJsonPath('meta.total', 1);
        $this->getJson(self::BASE . '/clientes?search=4771234567', $h)->assertJsonPath('meta.total', 1);
        $this->getJson(self::BASE . '/clientes?search=Inactivo', $h)->assertJsonPath('meta.total', 0);

        // Paginación.
        $this->getJson(self::BASE . '/clientes?per_page=1&page=2', $h)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.last_page', 2);

        $this->getJson(self::BASE . '/clientes?per_page=500', $h)->assertStatus(422);
    }

    // ── POST /servicios ───────────────────────────────────────────────────────

    public function test_servicios_store_requires_permission(): void
    {
        $customer = $this->customer();
        $this->serviceType('Mantenimiento Preventivo');
        $viewer = $this->staff('Solo agenda', [['technical-services', 'view']]);

        $this->postJson(self::BASE . '/servicios', [
            'customer_id' => $customer->id, 'service_type' => 'Preventivo', 'scheduled_at' => now()->addDay()->format('Y-m-d') . ' 09:00:00',
        ], $this->headersFor($viewer))->assertForbidden();

        $this->assertSame(0, TechnicalService::count());
    }

    public function test_servicios_store_with_app_body_creates_scheduled_service(): void
    {
        $user = $this->scheduler();
        $customer = $this->customer(['company' => 'Planta Norte']);
        $this->serviceType('Mantenimiento Correctivo');
        $preventivo = $this->serviceType('Mantenimiento Preventivo');
        $tech = $this->staff('Tecnico', [], ['first_name' => 'Ana', 'last_name' => 'Ruiz']);
        $date = now()->addDays(2)->format('Y-m-d');

        // Cuerpo tal como lo arma la app (buildServiceBody) y lo manda la bandeja (multipart).
        $res = $this->post(self::BASE . '/servicios', [
            'title' => 'Servicio preventivo', 'service_type' => 'Preventivo',
            'customer_id' => (string) $customer->id, 'customer_name' => 'Planta Norte',
            'scheduled_at' => "$date 10:30:00", 'technician_id' => (string) $tech->id, 'technician_name' => 'Ana Ruiz',
            'address' => 'Av. Industrial 5', 'description' => 'Revisar quemador',
            'idempotency_key' => 'k-body-1',
        ], $this->headersFor($user) + ['Idempotency-Key' => 'k-1', 'Accept' => 'application/json']);

        $res->assertCreated()
            ->assertHeaderMissing('Idempotent-Replayed')
            ->assertJsonStructure(['data' => [
                'id', 'title', 'service_number', 'status', 'status_label', 'priority', 'priority_label', 'is_customer_request',
                'scheduled_at', 'estimated_duration', 'customer' => ['id', 'name', 'company'], 'technician', 'technicians',
                'address', 'reference', 'description',
            ]])
            ->assertJsonPath('data.status', 'scheduled')
            ->assertJsonPath('data.title', 'Mantenimiento Preventivo')
            ->assertJsonPath('data.customer.id', $customer->id)
            ->assertJsonPath('data.technician.id', $tech->id)
            ->assertJsonPath('data.technician.name', 'Ana Ruiz')
            ->assertJsonPath('data.address', 'Av. Industrial 5')
            ->assertJsonPath('data.description', 'Revisar quemador');
        $this->assertMatchesRegularExpression('/^SVC-\d{4}-\d{4}$/', $res->json('data.service_number'));
        $this->assertStringStartsWith("{$date}T10:30:00", $res->json('data.scheduled_at'));

        $service = TechnicalService::findOrFail($res->json('data.id'));
        $this->assertSame($preventivo->id, $service->service_type_id);
        $this->assertSame($user->id, $service->created_by_user_id);
        $this->assertSame('k-1', $service->idempotency_key); // el header gana al campo
        $this->assertNull($service->draft_token);
        $this->assertSame(4, (int) $service->current_step);
        $this->assertDatabaseHas('service_logs', ['service_id' => $service->id, 'action' => 'created']);
        $this->assertDatabaseHas('service_logs', ['service_id' => $service->id, 'action' => 'status_changed']);

        // Aparece en la agenda (GET /servicios) con el mismo formato.
        $this->getJson(self::BASE . "/servicios?from=$date&to=$date", $this->headersFor($user))
            ->assertOk()->assertJsonPath('data.0.id', $service->id);
    }

    public function test_servicios_store_is_idempotent(): void
    {
        $user = $this->scheduler();
        $customer = $this->customer();
        $type = $this->serviceType('Diagnóstico');
        $h = $this->headersFor($user) + ['Idempotency-Key' => 'abc-123'];
        $body = ['customer_id' => $customer->id, 'service_type_id' => $type->id, 'service_date' => now()->addDay()->format('Y-m-d'), 'service_time' => '08:00'];

        $first = $this->postJson(self::BASE . '/servicios', $body, $h)->assertCreated();
        $again = $this->postJson(self::BASE . '/servicios', $body, $h)
            ->assertOk()
            ->assertHeader('Idempotent-Replayed', 'true');

        $this->assertSame($first->json('data'), $again->json('data'));
        $this->assertSame(1, TechnicalService::count());

        // Solo con el campo idempotency_key (sin header) también es idempotente.
        $h2 = $this->headersFor($user);
        $this->postJson(self::BASE . '/servicios', $body + ['idempotency_key' => 'campo-1'], $h2)->assertCreated();
        $this->postJson(self::BASE . '/servicios', $body + ['idempotency_key' => 'campo-1'], $h2)
            ->assertOk()->assertHeader('Idempotent-Replayed', 'true');
        $this->assertSame(2, TechnicalService::count());

        // Sin clave: cada envío crea uno nuevo.
        $this->postJson(self::BASE . '/servicios', $body, $h2)->assertCreated();
        $this->assertSame(3, TechnicalService::count());

        // La clave de otro usuario no devuelve su servicio.
        $other = $this->headersFor($this->scheduler());
        $this->app['auth']->forgetGuards();
        $this->postJson(self::BASE . '/servicios', $body, $other + ['Idempotency-Key' => 'abc-123'])
            ->assertStatus(409);
        $this->assertSame(3, TechnicalService::count());
    }

    public function test_servicios_store_validation_returns_422(): void
    {
        $user = $this->scheduler();
        $h = $this->headersFor($user);
        $customer = $this->customer();
        $this->serviceType('Mantenimiento Preventivo');
        $inactive = $this->staff('Tecnico', [], ['status' => 'inactive']);

        // Cliente escrito a mano (sin id), sin fecha ni tipo.
        $this->postJson(self::BASE . '/servicios', ['customer_name' => 'Cliente nuevo'], $h + ['Idempotency-Key' => 'v-1'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id', 'service_type_id', 'service_date']);

        // Tipo de servicio que no existe.
        $this->postJson(self::BASE . '/servicios', [
            'customer_id' => $customer->id, 'service_type' => 'Arranque', 'scheduled_at' => now()->addDay()->format('Y-m-d') . ' 09:00:00',
        ], $h)->assertStatus(422)->assertJsonValidationErrors(['service_type']);

        // Fecha inválida, técnico inactivo, cliente inexistente.
        $this->postJson(self::BASE . '/servicios', [
            'customer_id' => 999999, 'service_type' => 'Preventivo', 'scheduled_at' => 'mañana temprano',
        ], $h)->assertStatus(422)->assertJsonValidationErrors(['scheduled_at']);
        $this->postJson(self::BASE . '/servicios', [
            'customer_id' => 999999, 'service_type' => 'Preventivo', 'scheduled_at' => now()->addDay()->format('Y-m-d') . ' 09:00',
            'technician_id' => $inactive->id, 'priority' => 'altisima',
        ], $h)->assertStatus(422)->assertJsonValidationErrors(['customer_id', 'technician_id', 'priority']);

        $this->assertSame(0, TechnicalService::count());

        // Un 422 no "quema" la clave: el reintento corregido se crea.
        $this->postJson(self::BASE . '/servicios', [
            'customer_id' => $customer->id, 'service_type' => 'Preventivo', 'scheduled_at' => now()->addDay()->format('Y-m-d') . ' 09:00:00',
        ], $h + ['Idempotency-Key' => 'v-1'])->assertCreated();
    }

    // ── GET /usuarios y /roles ────────────────────────────────────────────────

    public function test_usuarios_and_roles_require_permission(): void
    {
        $tech = $this->staff('Tecnico', [['service-reports', 'view']]);
        $h = $this->headersFor($tech);

        $this->getJson(self::BASE . '/usuarios', $h)->assertForbidden()->assertJsonStructure(['message']);
        $this->getJson(self::BASE . '/roles', $h)->assertForbidden()->assertJsonStructure(['message']);

        $reader = $this->headersFor($this->staff('RH', [['users', 'view'], ['roles', 'view']]));
        $this->app['auth']->forgetGuards(); // sanctum cachea el usuario entre peticiones del test
        $this->getJson(self::BASE . '/usuarios', $reader)->assertOk();
        $this->getJson(self::BASE . '/roles', $reader)->assertOk();
    }

    public function test_usuarios_list_for_admin_without_sensitive_fields(): void
    {
        $admin = $this->admin();
        $tech = $this->staff('Tecnico', [['service-reports', 'view']], [
            'first_name' => 'Ana', 'last_name' => 'Ruiz', 'position' => 'Técnico de campo', 'curp' => 'RUAA900101MDFXXX01',
        ]);
        $this->staff('Tecnico', [], ['first_name' => 'Luis', 'last_name' => 'Baja', 'status' => 'inactive']);
        $tech->createToken('otro', ['mobile']);
        $h = $this->headersFor($admin);

        $res = $this->getJson(self::BASE . '/usuarios', $h)->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'name', 'first_name', 'last_name', 'email', 'phone', 'position', 'status', 'is_active', 'role' => ['id', 'name'], 'is_admin', 'last_seen_at', 'created_at']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('meta.total', 3);

        $forbidden = ['password', 'remember_token', 'tokens', 'rfc', 'curp', 'social_segurity_number', 'birthdate'];
        foreach ($res->json('data') as $row) {
            foreach ($forbidden as $field) {
                $this->assertArrayNotHasKey($field, $row);
            }
        }
        $this->assertStringNotContainsString('secret-123', $res->getContent());
        $this->assertStringNotContainsString('$2y$', $res->getContent());

        $ana = collect($res->json('data'))->firstWhere('id', $tech->id);
        $this->assertSame('Ana Ruiz', $ana['name']);
        $this->assertSame('Técnico de campo', $ana['position']);
        $this->assertTrue($ana['is_active']);
        $this->assertSame('Tecnico', $ana['role']['name']);
        $this->assertFalse($ana['is_admin']);

        $this->assertTrue(collect($res->json('data'))->firstWhere('id', $admin->id)['is_admin']);

        // Búsqueda y filtro por estatus.
        $this->getJson(self::BASE . '/usuarios?search=Ruiz', $h)->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', $tech->id);
        $this->getJson(self::BASE . '/usuarios?search=Ana Ruiz', $h)->assertJsonPath('meta.total', 1);
        $this->getJson(self::BASE . '/usuarios?status=inactive', $h)
            ->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.is_active', false);
        $this->getJson(self::BASE . '/usuarios?per_page=1', $h)->assertJsonCount(1, 'data')->assertJsonPath('meta.last_page', 3);
    }

    public function test_roles_list_with_users_count_and_permissions(): void
    {
        $admin = $this->admin();
        $this->staff('Tecnico', [['service-reports', 'view'], ['service-reports', 'create']]);
        $this->staff('Tecnico');
        Role::create(['name_role' => 'Vacio', 'name_role_es' => 'Vacío']);

        $res = $this->getJson(self::BASE . '/roles', $this->headersFor($admin))->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'key', 'description', 'users_count', 'is_admin', 'permissions']], 'meta' => ['total']]);

        $roles = collect($res->json('data'))->keyBy('key');

        $this->assertSame(2, $roles['Tecnico']['users_count']);
        $this->assertFalse($roles['Tecnico']['is_admin']);
        $this->assertEqualsCanonicalizing(['view', 'create'], $roles['Tecnico']['permissions']['service-reports']);

        // Admin: catálogo completo, como /me.
        $this->assertTrue($roles['Administrador']['is_admin']);
        $this->assertSame(1, $roles['Administrador']['users_count']);
        $this->assertArrayHasKey('service-reports', $roles['Administrador']['permissions']);

        // Rol sin permisos: {} (objeto), no [].
        $this->assertSame('Vacío', $roles['Vacio']['name']);
        $this->assertSame(0, $roles['Vacio']['users_count']);
        $this->assertStringContainsString('"permissions":{}', $res->getContent());
    }
}
