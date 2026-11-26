<?php

namespace Tests\Feature\Mobile;

use App\Models\ApiClient;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Role;
use App\Models\ServiceReport;
use App\Models\ServiceReportActivity;
use App\Models\ServiceReportImage;
use App\Models\ServiceType;
use App\Models\TechnicalService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * API móvil del personal (/api/mobile/v1, routes/api/mobile.php).
 * Se usan tokens Sanctum reales (header Authorization) en lugar de
 * Sanctum::actingAs() para ejercitar el flujo completo de auth.
 */
class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = '/api/mobile/v1';

    // ── Fixtures ──────────────────────────────────────────────────────────────

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

    private function customer(string $company = 'ACME'): Customer
    {
        return Customer::create([
            'first_name' => 'Cli', 'last_name' => 'Ente', 'email' => uniqid() . '@example.com', 'phone' => '5511111111',
            'password_hash' => 'x', 'document_type' => 'RFC', 'status' => 'active', 'source' => 'test',
            'company' => $company,
        ]);
    }

    private function report(User $tech, array $over = []): ServiceReport
    {
        static $n = 0;
        $n++;

        return ServiceReport::create(array_merge([
            'report_number' => 'RS-MOV-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT) . substr(uniqid(), -4),
            'created_by_user_id' => $tech->id, 'assigned_user_id' => $tech->id,
            'customer_name' => 'Planta Norte', 'customer_company' => 'Norte SA',
            'service_type' => 'maintenance_preventive', 'status' => 'draft',
            'service_date' => now()->toDateString(), 'current_step' => 1,
        ], $over));
    }

    private function technicalService(array $over = []): TechnicalService
    {
        static $n = 0;
        $n++;
        $type = ServiceType::first();
        if (! $type) {
            $type = new ServiceType(['name' => 'Mantenimiento de caldera', 'is_active' => true]);
            $type->timestamps = false;
            $type->save();
        }

        return TechnicalService::create(array_merge([
            'service_number' => 'SERV-MOV-' . $n . '-' . substr(uniqid(), -5),
            'service_code' => 'SC-MOV-' . $n . '-' . substr(uniqid(), -5),
            'customer_id' => $this->customer()->id, 'service_type_id' => $type->id,
            'status' => 'scheduled', 'service_date' => now()->addDays(3)->toDateString(),
            'service_time' => '10:30:00', 'address' => 'Av. Siempre Viva 742',
        ], $over));
    }

    // ── Login / logout / me ───────────────────────────────────────────────────

    public function test_login_ok_returns_token_and_user(): void
    {
        $user = $this->staff('Tecnico', [['service-reports', 'view']]);

        $res = $this->postJson(self::BASE . '/login', [
            'email' => $user->email, 'password' => 'secret-123', 'device_name' => 'Pixel 8',
        ]);

        $res->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role' => ['id', 'name'], 'is_admin', 'permissions']])
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.name', 'Staff Movil')
            ->assertJsonPath('user.is_admin', false);

        $token = $user->tokens()->first();
        $this->assertSame('Pixel 8', $token->name);
        $this->assertSame(['mobile'], $token->abilities);

        // El token emitido sirve para /me.
        $this->getJson(self::BASE . '/me', ['Authorization' => 'Bearer ' . $res->json('token')])
            ->assertOk()->assertJsonPath('user.id', $user->id);
    }

    public function test_login_bad_credentials_returns_422(): void
    {
        $user = $this->staff();

        $this->postJson(self::BASE . '/login', [
            'email' => $user->email, 'password' => 'incorrecta', 'device_name' => 'Pixel',
        ])->assertStatus(422)->assertExactJson(['message' => 'Credenciales incorrectas']);

        $this->postJson(self::BASE . '/login', [
            'email' => 'nadie@example.com', 'password' => 'x', 'device_name' => 'Pixel',
        ])->assertStatus(422)->assertJsonPath('message', 'Credenciales incorrectas');

        // Validación de campos requeridos.
        $this->postJson(self::BASE . '/login', [])->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password', 'device_name']);

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_login_suspended_user_returns_403_but_inactive_can_login(): void
    {
        $suspended = $this->staff('Tecnico', [], ['status' => 'suspended']);
        $this->postJson(self::BASE . '/login', [
            'email' => $suspended->email, 'password' => 'secret-123', 'device_name' => 'Pixel',
        ])->assertStatus(403)->assertJsonStructure(['message']);
        $this->assertSame(0, $suspended->tokens()->count());

        // 'inactive' es el valor por defecto de usuarios nuevos y el panel web no
        // lo bloquea: la app tampoco.
        $inactive = $this->staff('Supervisor', [], ['status' => 'inactive']);
        $this->postJson(self::BASE . '/login', [
            'email' => $inactive->email, 'password' => 'secret-123', 'device_name' => 'Pixel',
        ])->assertOk()->assertJsonStructure(['token', 'user']);
    }

    public function test_login_is_throttled_per_email_and_ip(): void
    {
        $user = $this->staff();
        $payload = ['email' => $user->email, 'password' => 'incorrecta', 'device_name' => 'Pixel'];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(self::BASE . '/login', $payload)->assertStatus(422);
        }

        $this->postJson(self::BASE . '/login', $payload)->assertStatus(429)->assertJsonStructure(['message']);
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = $this->staff();
        $headers = $this->headersFor($user);

        $this->postJson(self::BASE . '/logout', [], $headers)->assertNoContent();
        $this->assertSame(0, $user->tokens()->count());

        // El guard de sanctum cachea el usuario entre peticiones del mismo test.
        $this->app['auth']->forgetGuards();

        $this->getJson(self::BASE . '/me', $headers)->assertStatus(401);
    }

    public function test_unauthenticated_returns_401_json_without_accept_header(): void
    {
        $this->get(self::BASE . '/me')
            ->assertStatus(401)
            ->assertHeader('content-type', 'application/json')
            ->assertJsonStructure(['message']);

        $this->get(self::BASE . '/ruta-que-no-existe')->assertStatus(404)->assertJsonStructure(['message']);
    }

    public function test_me_returns_permissions_map(): void
    {
        $user = $this->staff('Tecnico', [['service-reports', 'view'], ['service-reports', 'create'], ['technical-services', 'view']]);

        $this->getJson(self::BASE . '/me', $this->headersFor($user))
            ->assertOk()
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.role.name', 'Tecnico')
            ->assertJsonPath('user.is_admin', false)
            ->assertJsonPath('user.permissions.service-reports', ['view', 'create'])
            ->assertJsonPath('user.permissions.technical-services', ['view']);
    }

    public function test_me_admin_gets_full_catalog_and_empty_map_is_object(): void
    {
        Permission::firstOrCreate(['code' => 'finance.view'], ['name' => 'finance view', 'module' => 'finance', 'action' => 'view']);

        $res = $this->getJson(self::BASE . '/me', $this->headersFor($this->admin()))->assertOk();
        $res->assertJsonPath('user.is_admin', true);
        $this->assertContains('view', $res->json('user.permissions.finance'));

        // Usuario sin rol con permisos: permissions es {} (objeto), no [].
        $plain = $this->staff('SinPermisos');
        $this->app['auth']->forgetGuards(); // sanctum cachea el usuario entre peticiones del test
        $raw = $this->getJson(self::BASE . '/me', $this->headersFor($plain))->assertOk()->getContent();
        $this->assertStringContainsString('"permissions":{}', $raw);
    }

    // ── Separación de tokens User vs ApiClient ─────────────────────────────

    public function test_api_client_token_is_rejected_on_mobile_routes(): void
    {
        $client = ApiClient::create(['name' => 'N8N', 'is_active' => true]);
        $headers = ['Authorization' => 'Bearer ' . $client->createToken('n8n', ['*'])->plainTextToken];

        $this->getJson(self::BASE . '/me', $headers)->assertStatus(401);
        $this->getJson(self::BASE . '/reportes', $headers)->assertStatus(401);
        $this->getJson(self::BASE . '/servicios', $headers)->assertStatus(401);
    }

    public function test_user_token_is_rejected_on_v1_integration_routes(): void
    {
        $headers = $this->headersFor($this->admin());

        $this->getJson('/api/v1/customers', $headers)->assertStatus(401);
    }

    public function test_user_token_without_mobile_ability_is_rejected(): void
    {
        $user = $this->admin();
        $headers = ['Authorization' => 'Bearer ' . $user->createToken('otro', ['otra-cosa'])->plainTextToken];

        $this->getJson(self::BASE . '/me', $headers)->assertStatus(401);
    }

    public function test_user_deactivated_after_login_is_rejected(): void
    {
        $user = $this->admin();
        $headers = $this->headersFor($user);
        $user->update(['status' => 'suspended']);

        $this->getJson(self::BASE . '/me', $headers)->assertStatus(403);
    }

    // ── Reportes ─────────────────────────────────────────────────────────────

    public function test_reportes_requires_service_reports_view_permission(): void
    {
        $user = $this->staff('Ventas', [['technical-services', 'view']]);
        $headers = $this->headersFor($user);
        $report = $this->report($user);

        $this->getJson(self::BASE . '/reportes', $headers)
            ->assertStatus(403)->assertJsonPath('message', 'No tienes permiso para acceder a este módulo.');
        $this->getJson(self::BASE . '/reportes/' . $report->id, $headers)->assertStatus(403);
    }

    public function test_reportes_list_shape_and_scoping_mirrors_web(): void
    {
        // El panel no acota por técnico: con service-reports,view se ven todos.
        $tech = $this->staff('Tecnico', [['service-reports', 'view']]);
        $other = $this->staff('Tecnico');
        $customer = $this->customer('Calderas del Bajío');

        $mine = $this->report($tech, ['service_date' => now()->toDateString(), 'customer_id' => $customer->id, 'status' => 'signed']);
        $theirs = $this->report($other, ['service_date' => now()->subDay()->toDateString()]);

        $res = $this->getJson(self::BASE . '/reportes', $this->headersFor($tech))->assertOk();

        $res->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'folio', 'status', 'status_label', 'service_date', 'customer' => ['id', 'name', 'company'], 'technician' => ['id', 'name'], 'service_type', 'is_signed', 'updated_at']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('data.0.id', $mine->id)
            ->assertJsonPath('data.0.folio', $mine->report_number)
            ->assertJsonPath('data.0.status_label', 'Firmado')
            ->assertJsonPath('data.0.is_signed', true)
            ->assertJsonPath('data.0.service_date', now()->toDateString())
            ->assertJsonPath('data.0.customer.id', $customer->id)
            ->assertJsonPath('data.0.customer.name', 'Planta Norte')
            ->assertJsonPath('data.0.technician.id', $tech->id)
            ->assertJsonPath('data.0.service_type', 'Mantenimiento Preventivo')
            ->assertJsonPath('data.1.id', $theirs->id)
            ->assertJsonPath('data.1.is_signed', false)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_reportes_search_by_folio_and_customer(): void
    {
        $admin = $this->admin();
        $headers = $this->headersFor($admin);

        $a = $this->report($admin, ['report_number' => 'RS-BUSCA-0001', 'customer_name' => 'Hotel Sol', 'customer_company' => null]);
        $b = $this->report($admin, ['customer_name' => 'Fábrica Luna', 'customer_company' => 'Lunar Industrial']);

        $this->getJson(self::BASE . '/reportes?search=BUSCA', $headers)
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $a->id);

        $this->getJson(self::BASE . '/reportes?search=Lunar', $headers)
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $b->id);

        $this->getJson(self::BASE . '/reportes?search=Hotel', $headers)
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $a->id);
    }

    public function test_reportes_pagination(): void
    {
        $admin = $this->admin();
        $headers = $this->headersFor($admin);
        for ($i = 0; $i < 5; $i++) {
            $this->report($admin, ['service_date' => now()->subDays($i)->toDateString()]);
        }

        $this->getJson(self::BASE . '/reportes?per_page=2&page=3', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 3)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 5);

        // Por defecto 20 por página; per_page > 50 es inválido.
        $this->getJson(self::BASE . '/reportes', $headers)->assertJsonPath('meta.per_page', 20);
        $this->getJson(self::BASE . '/reportes?per_page=51', $headers)->assertStatus(422);
    }

    public function test_reporte_detail_shape(): void
    {
        $admin = $this->admin();
        $report = $this->report($admin, [
            'status' => 'signed', 'signed_at' => now(), 'signature_data' => 'data:image/png;base64,AAAA',
            'signature_name' => 'Firmante', 'signature_position' => 'Jefe de planta',
            'observations' => 'Fuga menor en válvula', 'recommendations' => 'Cambiar empaque',
            'location' => 'Cuarto de máquinas', 'customer_rfc' => 'XAXX010101000', 'customer_phone' => '4771234567',
        ]);
        ServiceReportActivity::create(['service_report_id' => $report->id, 'content' => 'Se revisó quemador', 'systems_checked' => ['Quemador', 'Bomba']]);
        $img = ServiceReportImage::create(['service_report_id' => $report->id, 'path' => 'uploads/service-reports/foto.jpg', 'caption' => 'Antes', 'sort_order' => 1]);

        $res = $this->getJson(self::BASE . '/reportes/' . $report->id, $this->headersFor($admin))->assertOk();

        $res->assertJsonPath('data.id', $report->id)
            ->assertJsonPath('data.folio', $report->report_number)
            ->assertJsonPath('data.form_behavior', 'activity')
            ->assertJsonPath('data.observations', 'Fuga menor en válvula')
            ->assertJsonPath('data.recommendations', 'Cambiar empaque')
            ->assertJsonPath('data.location', 'Cuarto de máquinas')
            ->assertJsonPath('data.customer.rfc', 'XAXX010101000')
            ->assertJsonPath('data.activity.content', 'Se revisó quemador')
            ->assertJsonPath('data.activity.systems_checked', ['Quemador', 'Bomba'])
            ->assertJsonPath('data.measurements', [])
            ->assertJsonPath('data.signature.name', 'Firmante')
            ->assertJsonPath('data.images.0.id', $img->id)
            ->assertJsonPath('data.images.0.caption', 'Antes')
            ->assertJsonPath('data.created_by.id', $admin->id);

        $this->assertStringEndsWith('media/uploads/service-reports/foto.jpg', $res->json('data.images.0.url'));
        $this->assertStringContainsString('/api/mobile/v1/reportes/' . $report->id . '/pdf', $res->json('data.pdf_url'));
        $this->assertStringContainsString('signature=', $res->json('data.pdf_url'));
        $this->assertStringContainsString('expires=', $res->json('data.pdf_url'));
    }

    public function test_reporte_detail_404_when_not_visible(): void
    {
        $headers = $this->headersFor($this->admin());

        $this->getJson(self::BASE . '/reportes/999999', $headers)->assertStatus(404)->assertJsonStructure(['message']);
        $this->get(self::BASE . '/reportes/abc', $headers)->assertStatus(404);
    }

    public function test_pdf_signed_url_works_and_unsigned_fails(): void
    {
        $admin = $this->admin();
        $report = $this->report($admin);

        $pdfUrl = $this->getJson(self::BASE . '/reportes/' . $report->id, $this->headersFor($admin))
            ->assertOk()->json('data.pdf_url');

        // Sin token: la firma basta.
        $this->app['auth']->forgetGuards();
        $res = $this->get($pdfUrl);
        $res->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $res->getContent());

        // Sin firma / firma alterada / vencida => 403 JSON.
        $this->get(self::BASE . '/reportes/' . $report->id . '/pdf')->assertStatus(403)->assertJsonStructure(['message']);
        $this->get($pdfUrl . 'x')->assertStatus(403);

        $expired = URL::temporarySignedRoute('mobile.v1.reportes.pdf', now()->subMinute(), ['report' => $report->id]);
        $this->get($expired)->assertStatus(403);
    }

    public function test_pdf_url_usa_app_url_y_valida_aunque_el_proxy_cambie_esquema_y_host(): void
    {
        config(['app.url' => 'https://equitermindustries.com.mx']);
        $report = $this->report($this->admin());

        $pdfUrl = \App\Http\Resources\Mobile\ServiceReportDetailResource::pdfUrl($report->id);
        $this->assertStringStartsWith('https://equitermindustries.com.mx/api/mobile/v1/reportes/' . $report->id . '/pdf?', $pdfUrl);

        // Detrás de un proxy/CDN la petición puede llegar como http y con otro host:
        // la firma es relativa, así que igual abre el PDF.
        $parts = parse_url($pdfUrl);
        $this->get('http://servidor-interno.local' . $parts['path'] . '?' . $parts['query'])
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        // Usar la firma para OTRO reporte sigue dando 403.
        $other = $this->report($this->staff('Otro'));
        $tampered = str_replace('/reportes/' . $report->id . '/', '/reportes/' . $other->id . '/', $pdfUrl);
        $this->get($tampered)->assertStatus(403);
    }

    // ── Servicios (agenda) ───────────────────────────────────────────────────

    public function test_servicios_requires_permission(): void
    {
        $user = $this->staff('SoloReportes', [['service-reports', 'view']]);

        $this->getJson(self::BASE . '/servicios', $this->headersFor($user))->assertStatus(403);
    }

    public function test_servicios_default_range_and_shape(): void
    {
        $tech = $this->staff('Tecnico', [['technical-services', 'view']]);

        $inRange = $this->technicalService(['service_date' => now()->addDays(3)->toDateString()]);
        $inRange->assignedTechnicians()->attach($tech->id);
        $today = $this->technicalService(['service_date' => now()->toDateString(), 'service_time' => '08:00:00']);
        $this->technicalService(['service_date' => now()->addDays(20)->toDateString()]);   // fuera (+14)
        $this->technicalService(['service_date' => now()->subDay()->toDateString()]);      // fuera (ayer)

        $res = $this->getJson(self::BASE . '/servicios', $this->headersFor($tech))->assertOk();

        $res->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'title', 'status', 'status_label', 'scheduled_at', 'customer' => ['id', 'name', 'company'], 'technician', 'address']]])
            ->assertJsonPath('data.0.id', $today->id)
            ->assertJsonPath('data.1.id', $inRange->id)
            ->assertJsonPath('data.1.title', 'Mantenimiento de caldera')
            ->assertJsonPath('data.1.status_label', 'Programado')
            ->assertJsonPath('data.1.technician.id', $tech->id)
            ->assertJsonPath('data.1.address', 'Av. Siempre Viva 742')
            ->assertJsonPath('data.0.technician', null)
            ->assertJsonPath('meta.from', now()->toDateString())
            ->assertJsonPath('meta.to', now()->addDays(14)->toDateString());

        $this->assertStringStartsWith(now()->addDays(3)->toDateString() . 'T10:30:00', $res->json('data.1.scheduled_at'));
    }

    public function test_servicios_custom_range_and_validation(): void
    {
        $headers = $this->headersFor($this->admin());

        $past = $this->technicalService(['service_date' => now()->subDays(10)->toDateString()]);
        $this->technicalService(['service_date' => now()->toDateString()]);

        $from = now()->subDays(12)->toDateString();
        $to = now()->subDays(8)->toDateString();
        $this->getJson(self::BASE . "/servicios?from={$from}&to={$to}", $headers)
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $past->id);

        $this->getJson(self::BASE . "/servicios?from={$to}&to={$from}", $headers)->assertStatus(422);
        $this->getJson(self::BASE . '/servicios?from=22-11-2026', $headers)->assertStatus(422);
    }
}
