<?php

namespace Tests\Feature\Whatsapp;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Guardia del aislamiento de la vía QR/Baileys (bloques "QR-AISLADO"): el proyecto
 * usa SOLO la API oficial de Meta (Cloud API). Estas pruebas fallan si alguien
 * reactiva el QR sin querer, o si el aislamiento rompe la API oficial.
 */
class WhatsappQrIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $role = Role::create(['name_role' => 'Administrador', 'name_role_es' => 'Administrador']);

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

    public function test_las_rutas_del_qr_estan_apagadas(): void
    {
        $this->assertFalse(Route::has('whatsapp.qr.webhook'));
        $this->assertFalse(Route::has('admin.whatsapp-accounts.qr-status'));
        // Sin ruta: nunca llega al controlador del QR (404, o 405 si lo atrapa otra ruta de otro método).
        $status = $this->postJson('/whatsapp-qr/webhook', ['event' => 'status'])->getStatusCode();
        $this->assertContains($status, [404, 405]);
    }

    public function test_el_webhook_oficial_de_meta_sigue_activo(): void
    {
        $this->assertTrue(Route::has('whatsapp.webhook.verify'));
        $this->assertTrue(Route::has('whatsapp.webhook.receive'));

        // Verificación de Meta sin token válido: responde (no 404) rechazando.
        $this->get('/whatsapp/webhook?hub_mode=subscribe&hub_verify_token=x&hub_challenge=123')
            ->assertStatus(403);
    }

    public function test_el_panel_de_integraciones_solo_muestra_la_api_oficial(): void
    {
        $html = $this->actingAs($this->adminUser())
            ->get(route('admin.integrations.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-panel="whatsapp-api"', $html);
        $this->assertStringNotContainsString('data-panel="whatsapp-web"', $html);
        // Los modales ya no existen como elementos (el JS comentado puede mencionar sus ids como texto).
        $this->assertStringNotContainsString('id="whatsappQrFormModal"', $html);
        $this->assertStringNotContainsString('id="whatsappQrScanModal"', $html);
        $this->assertStringNotContainsString('id="integrPanel-whatsapp-web"', $html);
    }

    public function test_no_se_pueden_crear_cuentas_por_qr(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->postJson(route('admin.whatsapp-accounts.store'), [
            'name'            => 'QR',
            'phone_number'    => '5214491234567',
            'connection_type' => 'baileys_qr',
        ])->assertStatus(422)->assertJsonValidationErrors('connection_type');

        $this->actingAs($admin)->postJson(route('admin.whatsapp-accounts.store'), [
            'name'            => 'API oficial',
            'phone_number'    => '5214491234567',
            'connection_type' => 'meta_cloud_api',
        ])->assertSuccessful();
    }
}
