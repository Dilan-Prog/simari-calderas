<?php

namespace Tests\Feature\Whatsapp;

use App\Models\Role;
use App\Models\User;
use App\Models\WhatsappAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Pestaña "Probar conexión" (Integraciones > WhatsApp API): verificación contra la
 * Graph API (siempre con Http::fake, nunca toca Meta), mensaje de prueba y bitácora
 * del webhook.
 */
class WhatsappConnectionTestTest extends TestCase
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

    private function account(array $over = []): WhatsappAccount
    {
        $a = new WhatsappAccount(array_merge([
            'name'                         => 'Prueba',
            'phone_number'                 => '+1 555 025 3483',
            'phone_number_id'              => '1100000000000001',
            'whatsapp_business_account_id' => '2200000000000002',
            'webhook_verify_token'         => 'mi-token-secreto',
            'provider'                     => 'meta_cloud_api',
            'connection_type'              => 'meta_cloud_api',
            'encrypted_access_token'       => WhatsappAccount::encryptAccessToken('TOKEN-DE-PRUEBA'),
            'is_active'                    => true,
        ], $over));
        $a->save();

        return $a;
    }

    private function checkStatus(array $checks, string $key): ?string
    {
        return collect($checks)->firstWhere('key', $key)['status'] ?? null;
    }

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    // ── Verificar conexión ───────────────────────────────────────────────

    public function test_conexion_correcta_lee_el_numero_y_las_plantillas(): void
    {
        Http::fake([
            'graph.facebook.com/*/1100000000000001*' => Http::response([
                'display_phone_number' => '+1 555 025 3483', 'verified_name' => 'Test Number', 'quality_rating' => 'GREEN',
            ]),
            'graph.facebook.com/*/2200000000000002/message_templates*' => Http::response([
                'data' => [['name' => 'hello_world', 'status' => 'APPROVED', 'language' => 'en_US']],
            ]),
        ]);

        $a = $this->account();
        $json = $this->actingAs($this->admin())->getJson(route('admin.whatsapp-accounts.test.connection', $a))
            ->assertOk()->json();

        $this->assertTrue($json['ok']);
        $this->assertSame('ok', $this->checkStatus($json['checks'], 'meta'));
        $this->assertSame('ok', $this->checkStatus($json['checks'], 'templates'));
        $this->assertSame('Test Number', $json['phone']['verified_name']);

        Http::assertSent(fn (HttpRequest $r) => $r->hasHeader('Authorization', 'Bearer TOKEN-DE-PRUEBA'));
    }

    public function test_token_expirado_explica_como_renovarlo(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Error validating access token', 'code' => 190]], 401)]);

        $a = $this->account();
        $json = $this->actingAs($this->admin())->getJson(route('admin.whatsapp-accounts.test.connection', $a))->json();

        $this->assertFalse($json['ok']);
        $this->assertSame('fail', $this->checkStatus($json['checks'], 'meta'));
        $detail = collect($json['checks'])->firstWhere('key', 'meta')['detail'];
        $this->assertStringContainsString('24 horas', $detail);
        $this->assertStringContainsString('Error validating access token', $detail);
    }

    public function test_sin_ids_no_llama_a_meta_y_marca_lo_que_falta(): void
    {
        Http::fake();
        $a = $this->account(['phone_number_id' => null, 'encrypted_access_token' => null, 'webhook_verify_token' => null]);

        $json = $this->actingAs($this->admin())->getJson(route('admin.whatsapp-accounts.test.connection', $a))->json();

        Http::assertNothingSent();
        $this->assertFalse($json['ok']);
        $this->assertSame('fail', $this->checkStatus($json['checks'], 'phone_number_id'));
        $this->assertSame('fail', $this->checkStatus($json['checks'], 'access_token'));
        $this->assertSame('warn', $this->checkStatus($json['checks'], 'verify_token'));
    }

    // ── Mensaje de prueba ────────────────────────────────────────────────

    public function test_envia_hello_world_con_el_numero_solo_con_digitos(): void
    {
        Http::fake(['graph.facebook.com/*/messages' => Http::response(['messages' => [['id' => 'wamid.ABC']], 'contacts' => [['wa_id' => '5214491234567']]])]);
        $a = $this->account();

        $this->actingAs($this->admin())->postJson(route('admin.whatsapp-accounts.test.send', $a), ['to' => '+52 1 449 123 4567'])
            ->assertOk()->assertJsonPath('success', true)->assertJsonPath('message_id', 'wamid.ABC')->assertJsonPath('to', '5214491234567');

        Http::assertSent(function (HttpRequest $r) {
            $d = $r->data();

            return str_contains($r->url(), '/1100000000000001/messages')
                && $d['to'] === '5214491234567' && $d['type'] === 'template'
                && $d['template']['name'] === 'hello_world' && $d['template']['language']['code'] === 'en_US';
        });
    }

    public function test_envia_texto_libre_cuando_se_pide(): void
    {
        Http::fake(['graph.facebook.com/*/messages' => Http::response(['messages' => [['id' => 'wamid.T']]])]);
        $a = $this->account();

        $this->actingAs($this->admin())->postJson(route('admin.whatsapp-accounts.test.send', $a), ['to' => '5214491234567', 'mode' => 'text', 'text' => 'Hola'])
            ->assertOk()->assertJsonPath('success', true);

        Http::assertSent(fn (HttpRequest $r) => $r->data()['type'] === 'text' && $r->data()['text']['body'] === 'Hola');
    }

    public function test_numero_fuera_de_la_lista_de_prueba_muestra_el_paso_a_seguir(): void
    {
        Http::fake(['graph.facebook.com/*/messages' => Http::response(['error' => ['message' => 'Recipient phone number not in allowed list', 'code' => 131030]], 400)]);
        $a = $this->account();

        $json = $this->actingAs($this->admin())->postJson(route('admin.whatsapp-accounts.test.send', $a), ['to' => '5214491234567'])
            ->assertStatus(422)->json();

        $this->assertFalse($json['success']);
        $this->assertSame(131030, $json['error']['code']);
        $this->assertStringContainsString('lista de permitidos', $json['error']['hint']);
    }

    public function test_valida_el_numero_y_el_texto(): void
    {
        Http::fake();
        $a = $this->account();
        $admin = $this->admin();

        $this->actingAs($admin)->postJson(route('admin.whatsapp-accounts.test.send', $a), ['to' => '123'])->assertStatus(422);
        $this->actingAs($admin)->postJson(route('admin.whatsapp-accounts.test.send', $a), ['to' => '5214491234567', 'mode' => 'text'])
            ->assertStatus(422)->assertJsonValidationErrors('text');
        $this->actingAs($admin)->postJson(route('admin.whatsapp-accounts.test.send', $a), ['to' => '5214491234567', 'template' => 'mala plantilla!'])
            ->assertStatus(422)->assertJsonValidationErrors('template');

        Http::assertNothingSent();
    }

    public function test_las_rutas_de_prueba_exigen_permiso_de_editar(): void
    {
        foreach (['test.connection', 'test.send', 'test.webhook'] as $name) {
            $route = Route::getRoutes()->getByName('admin.whatsapp-accounts.' . $name);
            $this->assertNotNull($route, $name);
            $this->assertContains('permission:whatsapp,edit', $route->middleware(), $name);
        }
    }

    // ── Webhook: bitácora ────────────────────────────────────────────────

    public function test_la_verificacion_de_meta_queda_registrada(): void
    {
        $a = $this->account();
        $admin = $this->admin();

        $this->get('/whatsapp/webhook?hub_mode=subscribe&hub_verify_token=otro&hub_challenge=1')->assertStatus(403);
        $json = $this->actingAs($admin)->getJson(route('admin.whatsapp-accounts.test.webhook', $a))->json();
        $this->assertSame('warn', $this->checkStatus($json['checks'], 'webhook_verified'));
        $this->assertStringContainsString('no coincidió', collect($json['checks'])->firstWhere('key', 'webhook_verified')['detail']);

        $this->get('/whatsapp/webhook?hub_mode=subscribe&hub_verify_token=mi-token-secreto&hub_challenge=987')
            ->assertOk()->assertSee('987');
        $json = $this->actingAs($admin)->getJson(route('admin.whatsapp-accounts.test.webhook', $a))->json();
        $this->assertSame('ok', $this->checkStatus($json['checks'], 'webhook_verified'));
    }

    public function test_un_evento_valido_se_refleja_y_uno_con_firma_mala_se_reporta(): void
    {
        $a = $this->account(['encrypted_app_secret' => WhatsappAccount::encryptAppSecret('secreto-app')]);
        $admin = $this->admin();
        $payload = ['object' => 'whatsapp_business_account', 'entry' => [['changes' => [[
            'field' => 'messages',
            'value' => ['metadata' => ['phone_number_id' => '1100000000000001'], 'messages' => [], 'statuses' => [['id' => 'x', 'status' => 'delivered']]],
        ]]]]];
        $body = json_encode($payload);

        // Firma incorrecta -> 403 y queda el motivo
        $this->call('POST', '/whatsapp/webhook', [], [], [], ['HTTP_X_HUB_SIGNATURE_256' => 'sha256=malo', 'CONTENT_TYPE' => 'application/json'], $body)->assertStatus(403);
        $json = $this->actingAs($admin)->getJson(route('admin.whatsapp-accounts.test.webhook', $a))->json();
        $this->assertSame('fail', $this->checkStatus($json['checks'], 'webhook_rejected'));
        $this->assertSame('warn', $this->checkStatus($json['checks'], 'webhook_events'));

        // Firma correcta -> 200 y aparece el evento
        $sig = 'sha256=' . hash_hmac('sha256', $body, 'secreto-app');
        $this->call('POST', '/whatsapp/webhook', [], [], [], ['HTTP_X_HUB_SIGNATURE_256' => $sig, 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();
        $json = $this->actingAs($admin)->getJson(route('admin.whatsapp-accounts.test.webhook', $a))->json();
        $this->assertSame('ok', $this->checkStatus($json['checks'], 'webhook_events'));
        $this->assertStringContainsString('1 estado(s)', collect($json['checks'])->firstWhere('key', 'webhook_events')['detail']);
    }

    public function test_el_panel_muestra_la_pestana_de_prueba(): void
    {
        $this->account();

        $html = $this->actingAs($this->admin())->get(route('admin.integrations.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-tab="prueba"', $html);
        $this->assertStringContainsString('id="waTestAccount"', $html);
        $this->assertStringContainsString('data-verify-token="mi-token-secreto"', $html);
        $this->assertStringContainsString('hello_world', $html);
    }
}
