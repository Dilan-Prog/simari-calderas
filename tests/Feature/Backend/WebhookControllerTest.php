<?php

namespace Tests\Feature\Backend;

use App\Models\Credential;
use App\Models\Role;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests de integración HTTP para WebhookController (CRUD del catálogo
 * "Webhooks" bajo Integraciones + endpoint de prueba de conexión).
 *
 * Auth: mismo patrón que
 * tests/Feature/Deals/DealIndexRendersFullViewTest.php -- un usuario con
 * Role name_role 'Administrador' (Role::isAdmin() lo detecta por nombre) pasa
 * el middleware permission:settings[,edit] vía el bypass explícito en
 * CheckPermission::handle() (isAdmin() siempre pasa antes de evaluar
 * hasPermission()), sin necesitar armar una tabla de permisos aparte.
 */
class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $role = Role::create([
            'name_role'    => 'Administrador',
            'name_role_es' => 'Administrador',
        ]);

        return User::create([
            'first_name' => 'Admin',
            'last_name'  => 'Test',
            'position'   => 'Administrador',
            'phone'      => '5555555555',
            'email'      => 'admin-' . uniqid() . '@example.com',
            'password'   => bcrypt('password'),
            'status'     => 'active',
            'rfc'        => 'XAXX010101000',
            'role_id'    => $role->id,
        ]);
    }

    // --- store ---------------------------------------------------------

    public function test_store_creates_webhook_with_headers(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->postJson('/admin/integraciones/webhooks', [
            'name'    => 'CRM externo',
            'url'     => 'https://example.com/hook',
            'method'  => 'POST',
            'headers' => [['key' => 'X-Api-Key', 'value' => 'secret']],
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('webhooks', [
            'name'   => 'CRM externo',
            'url'    => 'https://example.com/hook',
            'method' => 'POST',
        ]);

        $webhook = Webhook::first();
        $this->assertEquals([['key' => 'X-Api-Key', 'value' => 'secret']], $webhook->headers);
        $this->assertEquals($admin->id, $webhook->created_by);
    }

    public function test_store_defaults_method_to_post_when_omitted(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->postJson('/admin/integraciones/webhooks', [
            'name' => 'Sin método explícito',
            'url'  => 'https://example.com/hook',
        ])->assertOk();

        $this->assertDatabaseHas('webhooks', [
            'name'   => 'Sin método explícito',
            'method' => 'POST',
        ]);
    }

    public function test_store_requires_name_and_url(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->postJson('/admin/integraciones/webhooks', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'url']);
    }

    public function test_store_rejects_credential_id_that_does_not_exist(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->postJson('/admin/integraciones/webhooks', [
            'name'          => 'Con credencial inválida',
            'url'           => 'https://example.com/hook',
            'credential_id' => 999999,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['credential_id']);
    }

    // --- edit ------------------------------------------------------------

    public function test_edit_returns_webhook_json(): void
    {
        $admin = $this->adminUser();

        $webhook = Webhook::create([
            'name'    => 'Editable',
            'url'     => 'https://example.com/hook',
            'method'  => 'PUT',
            'headers' => [['key' => 'X-Foo', 'value' => 'bar']],
        ]);

        $response = $this->actingAs($admin)->getJson("/admin/integraciones/webhooks/{$webhook->id}/editar");

        $response->assertOk();
        $response->assertJson([
            'id'            => $webhook->id,
            'name'          => 'Editable',
            'url'           => 'https://example.com/hook',
            'method'        => 'PUT',
            'headers'       => [['key' => 'X-Foo', 'value' => 'bar']],
            'credential_id' => null,
        ]);
    }

    // --- update ------------------------------------------------------------

    public function test_update_modifies_webhook_fields(): void
    {
        $admin = $this->adminUser();

        $webhook = Webhook::create([
            'name'   => 'Original',
            'url'    => 'https://example.com/original',
            'method' => 'POST',
        ]);

        $response = $this->actingAs($admin)->putJson("/admin/integraciones/webhooks/{$webhook->id}", [
            'name'   => 'Actualizado',
            'url'    => 'https://example.com/actualizado',
            'method' => 'GET',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $webhook->refresh();
        $this->assertEquals('Actualizado', $webhook->name);
        $this->assertEquals('https://example.com/actualizado', $webhook->url);
        $this->assertEquals('GET', $webhook->method);
    }

    // --- destroy -----------------------------------------------------------

    public function test_destroy_deletes_webhook(): void
    {
        $admin = $this->adminUser();

        $webhook = Webhook::create([
            'name'   => 'A borrar',
            'url'    => 'https://example.com/hook',
            'method' => 'POST',
        ]);

        $response = $this->actingAs($admin)->deleteJson("/admin/integraciones/webhooks/{$webhook->id}");

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('webhooks', ['id' => $webhook->id]);
    }

    // --- test (probar conexión) ---------------------------------------------

    public function test_probar_returns_ok_true_with_status_and_ms_on_successful_round_trip(): void
    {
        Http::fake([
            '*' => Http::response(['pong' => true], 200),
        ]);

        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->postJson('/admin/integraciones/webhooks/probar', [
            'url'    => 'https://example.com/hook',
            'method' => 'POST',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['ok', 'status', 'ms']);
        $response->assertJson(['ok' => true, 'status' => 200]);
    }

    public function test_probar_returns_ok_false_with_status_on_non_2xx_response_but_still_200(): void
    {
        Http::fake([
            '*' => Http::response('Server error', 500),
        ]);

        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->postJson('/admin/integraciones/webhooks/probar', [
            'url'    => 'https://example.com/hook',
            'method' => 'POST',
        ]);

        // El endpoint es de diagnóstico: nunca debe reflejar el 500 del
        // destino como su propio status HTTP.
        $response->assertStatus(200);
        $response->assertJson(['ok' => false, 'status' => 500]);
    }

    public function test_probar_returns_ok_false_with_message_on_thrown_exception(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
        });

        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->postJson('/admin/integraciones/webhooks/probar', [
            'url'    => 'https://example.com/hook',
            'method' => 'POST',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => false]);
        $response->assertJsonMissingPath('status');
        $this->assertStringContainsString('Connection timed out', $response->json('message'));
    }

    public function test_probar_applies_header_from_webhook_credential(): void
    {
        Http::fake([
            '*' => Http::response(['ok' => true], 200),
        ]);

        $admin = $this->adminUser();

        $credential = Credential::create([
            'name'              => 'Webhook de prueba (probar)',
            'type'              => 'webhook',
            'encrypted_payload' => Credential::storePayload([
                'header_name'  => 'Authorization',
                'header_value' => 'Bearer probar-token-789',
            ]),
        ]);

        $this->actingAs($admin)->postJson('/admin/integraciones/webhooks/probar', [
            'url'           => 'https://example.com/hook',
            'method'        => 'POST',
            'credential_id' => $credential->id,
        ])->assertOk();

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer probar-token-789');
        });
    }

    // --- guest (no autenticado) ----------------------------------------------

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/admin/integraciones/webhooks', [
            'name' => 'Sin sesión',
            'url'  => 'https://example.com/hook',
        ]);

        // 401, no 403: la ruta también pasa por el middleware 'auth' de
        // Laravel (declarado antes que 'permission:...' en el grupo admin),
        // que responde 401 "Unauthenticated." en JSON antes de que
        // CheckPermission llegue a evaluarse.
        $response->assertStatus(401);
    }
}
