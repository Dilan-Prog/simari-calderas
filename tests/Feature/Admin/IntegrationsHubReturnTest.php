<?php

namespace Tests\Feature\Admin;

use App\Models\ApiClient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Integraciones: tras guardar/crear/eliminar/probar, el servidor debe regresar a
 * la MISMA vista (panel de integración + pestaña interna), vía ?panel=&tab= -- el
 * JS de la página lee esos parámetros al cargar. Un back() normal perdía el estado.
 */
class IntegrationsHubReturnTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
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

    private function hub(array $query): string
    {
        return route('admin.integrations.index', $query);
    }

    // ── SMTP ─────────────────────────────────────────────────────────────

    public function test_guardar_smtp_regresa_al_panel_y_pestana_enviados_por_el_formulario(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.integrations.update'), [
                'mail_host' => 'smtp.example.com',
                'mail_port' => 465,
                '_panel'    => 'smtp',
                '_tab'      => 'credenciales',
            ])
            ->assertRedirect($this->hub(['panel' => 'smtp', 'tab' => 'credenciales']))
            ->assertSessionHas('success');
    }

    public function test_guardar_smtp_sin_campos_ocultos_cae_al_panel_por_defecto_de_la_accion(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.integrations.update'), ['mail_host' => 'smtp.example.com'])
            ->assertRedirect($this->hub(['panel' => 'smtp', 'tab' => 'credenciales']));
    }

    public function test_panel_o_pestana_invalidos_se_ignoran(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.integrations.update'), [
                'mail_host' => 'smtp.example.com',
                '_panel'    => 'whatsapp-web',      // vía QR desactivada: no es un panel válido
                '_tab'      => '"><script>',
            ])
            ->assertRedirect($this->hub(['panel' => 'smtp', 'tab' => 'credenciales']));
    }

    public function test_probar_envio_regresa_al_panel_smtp_con_el_resultado(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->admin())
            ->post(route('admin.integrations.test-mail'), [
                'test_email' => 'destino@example.com',
                '_panel'     => 'smtp',
                '_tab'       => 'credenciales',
            ]);

        $response->assertRedirect($this->hub(['panel' => 'smtp', 'tab' => 'credenciales']));
        $this->assertTrue(
            session()->has('mail_test_success') || session()->has('mail_test_error'),
            'El resultado de la prueba debe viajar en la sesión.'
        );
    }

    public function test_probar_correo_de_cotizacion_sin_cotizaciones_regresa_al_panel_smtp(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.integrations.test-quote-mail'), ['test_email' => 'destino@example.com'])
            ->assertRedirect($this->hub(['panel' => 'smtp', 'tab' => 'credenciales']))
            ->assertSessionHas('mail_test_error');
    }

    // ── Mercado Pago ─────────────────────────────────────────────────────

    public function test_guardar_mercado_pago_regresa_al_panel_mercado_pago(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.integrations.update-mercadopago'), [
                'mercadopago' => [
                    'api'          => ['mode' => 'sandbox', 'sandbox_public_key' => 'TEST-1'],
                    'checkout_pro' => ['mode' => 'live'],
                ],
                '_panel' => 'mercadopago',
            ])
            ->assertRedirect($this->hub(['panel' => 'mercadopago']))
            ->assertSessionHas('success');
    }

    // ── API / N8N ────────────────────────────────────────────────────────

    public function test_acciones_de_api_n8n_regresan_al_panel_api_n8n(): void
    {
        $admin = $this->admin();
        $back = $this->hub(['panel' => 'api-clients']);

        $this->actingAs($admin)
            ->post(route('admin.integrations.api-clients.store'), ['name' => 'N8N', '_panel' => 'api-clients'])
            ->assertRedirect($back);

        $client = ApiClient::firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.integrations.api-clients.toggle', $client), ['_panel' => 'api-clients'])
            ->assertRedirect($back);

        $abilities = collect(config('api_abilities'))->flatMap(fn ($group) => array_keys($group))->first();
        $this->actingAs($admin)
            ->post(route('admin.integrations.api-clients.tokens.store', $client), [
                'token_name' => 'Prod',
                'abilities'  => [$abilities],
                '_panel'     => 'api-clients',
            ])
            ->assertRedirect($back)
            ->assertSessionHas('plain_text_token');

        $tokenId = $client->tokens()->firstOrFail()->id;

        $this->actingAs($admin)
            ->delete(route('admin.integrations.api-clients.tokens.destroy', [$client, $tokenId]), ['_panel' => 'api-clients'])
            ->assertRedirect($back);

        $this->actingAs($admin)
            ->delete(route('admin.integrations.api-clients.destroy', $client), ['_panel' => 'api-clients'])
            ->assertRedirect($back);
    }

    public function test_acciones_de_api_n8n_sin_campos_ocultos_tambien_regresan_a_su_panel(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.integrations.api-clients.store'), ['name' => 'N8N'])
            ->assertRedirect($this->hub(['panel' => 'api-clients']));
    }

    // ── Vista ────────────────────────────────────────────────────────────

    public function test_la_pantalla_carga_el_script_de_vista_guardada_y_muestra_el_token_recien_creado(): void
    {
        $html = $this->actingAs($this->admin())
            ->withSession(['plain_text_token' => '1|abc', 'mail_test_success' => 'Enviado'])
            ->get(route('admin.integrations.index', ['panel' => 'api-clients']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString("integraciones.vista", $html);
        $this->assertStringContainsString('id="integrNewToken"', $html);
        $this->assertStringContainsString('integr-mail-toast', $html);
    }
}
