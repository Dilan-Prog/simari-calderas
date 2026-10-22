<?php

namespace Tests\Feature\Crm;

use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use App\Models\WhatsappAccount;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Services\LeadService;
use App\Services\WhatsappService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Bandeja de leads: enviar WhatsApp (texto libre y plantillas) por la API oficial y
 * editar los datos de contacto (teléfono, WhatsApp y correo por separado).
 * Meta siempre va con Http::fake: nunca se toca la Graph API.
 */
class LeadWhatsappSendTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Cache::flush();
        $role = Role::firstOrCreate(['name_role' => 'Administrador'], ['name_role_es' => 'Administrador']);
        $this->admin = User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'Administrador',
            'phone' => '5555555555', 'email' => 'u-' . uniqid() . '@example.com',
            'password' => bcrypt('password'), 'status' => 'active', 'rfc' => 'RFC' . strtoupper(substr(uniqid(), -8)), 'role_id' => $role->id,
        ]);
    }

    private function account(array $over = []): WhatsappAccount
    {
        $a = new WhatsappAccount(array_merge([
            'name'                         => 'Oficial',
            'phone_number'                 => '+52 449 000 0000',
            'phone_number_id'              => '1100000000000001',
            'whatsapp_business_account_id' => '2200000000000002',
            'webhook_verify_token'         => 'tok',
            'provider'                     => 'meta_cloud_api',
            'connection_type'              => 'meta_cloud_api',
            'encrypted_access_token'       => WhatsappAccount::encryptAccessToken('TOKEN'),
            'is_active'                    => true,
        ], $over));
        $a->save();

        return $a;
    }

    private function lead(array $over = []): Lead
    {
        return Lead::create(array_merge([
            'source' => 'whatsapp', 'status' => 'nuevo', 'contact_name' => 'Ana Torres',
            'contact_phone' => '449 123 4567', 'phone_normalized' => '+524491234567',
            'last_activity_at' => now(),
        ], $over));
    }

    /** Conversación con un mensaje entrante hace $hoursAgo horas (define si la ventana de 24 h sigue abierta). */
    private function conversationWithInbound(WhatsappAccount $account, Lead $lead, float $hoursAgo, string $phone = '524491234567'): WhatsappConversation
    {
        $c = WhatsappConversation::create([
            'account_id' => $account->id, 'contact_phone' => $phone, 'status' => 'open',
            'started_at' => now()->subHours($hoursAgo), 'unread_count' => 0,
        ]);
        $c->messages()->create([
            'sender_type' => WhatsappMessage::SENDER_CONTACT, 'message_type' => 'text', 'content' => 'Hola, necesito una cotización',
            'is_template' => false, 'sent_at' => now()->subHours($hoursAgo),
        ]);
        $lead->update(['whatsapp_conversation_id' => $c->id]);

        return $c;
    }

    private function ok(string $id = 'wamid.OK'): array
    {
        return ['graph.facebook.com/*/messages' => Http::response(['messages' => [['id' => $id]]])];
    }

    // ── Enviar mensaje libre ─────────────────────────────────────────────

    public function test_envia_texto_libre_dentro_de_la_ventana_y_deja_el_lead_contactado(): void
    {
        Http::fake($this->ok('wamid.T1'));
        $account = $this->account();
        $lead = $this->lead();
        $conv = $this->conversationWithInbound($account, $lead, 2);

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.whatsapp.send', $lead), ['type' => 'text', 'text' => '  Claro, te mando la cotización  '])
            ->assertRedirect(route('admin.leads-inbox.index', ['lead' => $lead->id]))
            ->assertSessionHas('success');

        Http::assertSent(function (HttpRequest $r) {
            $d = $r->data();

            return str_contains($r->url(), '/1100000000000001/messages') && $r->hasHeader('Authorization', 'Bearer TOKEN')
                && $d['type'] === 'text' && $d['text']['body'] === 'Claro, te mando la cotización' && $d['to'] === '524491234567';
        });
        $msg = $conv->messages()->where('sender_type', 'agent')->first();
        $this->assertSame('wamid.T1', $msg->external_message_id);
        $this->assertSame('contactado', $lead->fresh()->status);
        $this->assertNotNull($lead->fresh()->first_contact_at);
    }

    public function test_texto_libre_con_la_ventana_cerrada_se_rechaza_sin_llamar_a_meta(): void
    {
        Http::fake();
        $account = $this->account();
        $lead = $this->lead();
        $conv = $this->conversationWithInbound($account, $lead, 30);

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.whatsapp.send', $lead), ['type' => 'text', 'text' => 'Hola'])
            ->assertSessionHas('error');

        Http::assertNothingSent();
        $this->assertSame(0, $conv->messages()->where('sender_type', 'agent')->count());
        $this->assertSame('nuevo', $lead->fresh()->status);
    }

    // ── Plantillas ───────────────────────────────────────────────────────

    public function test_plantilla_abre_la_conversacion_y_la_crea_si_no_existia(): void
    {
        Http::fake($this->ok('wamid.TPL'));
        $account = $this->account();
        $lead = $this->lead(['whatsapp_conversation_id' => null]);

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.whatsapp.send', $lead), [
            'type' => 'template', 'template_name' => 'seguimiento_cotizacion', 'language' => 'es_MX', 'params' => ['Ana', 'COT-0042'],
        ])->assertSessionHas('success');

        Http::assertSent(function (HttpRequest $r) {
            $t = $r->data()['template'] ?? [];

            return $r->data()['type'] === 'template' && $t['name'] === 'seguimiento_cotizacion' && $t['language']['code'] === 'es_MX'
                && $t['components'][0]['parameters'][1]['text'] === 'COT-0042' && $r->data()['to'] === '524491234567';
        });

        $conv = WhatsappConversation::where('account_id', $account->id)->firstOrFail();
        $this->assertSame($conv->id, $lead->fresh()->whatsapp_conversation_id);
        $msg = $conv->messages()->firstOrFail();
        $this->assertTrue((bool) $msg->is_template);
        $this->assertSame('seguimiento_cotizacion', $msg->template_name);
        $this->assertSame('contactado', $lead->fresh()->status);
    }

    public function test_plantilla_exige_todos_los_parametros_y_un_nombre_valido(): void
    {
        Http::fake();
        $this->account();
        $lead = $this->lead();

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.whatsapp.send', $lead), [
            'type' => 'template', 'template_name' => 'seguimiento_cotizacion', 'params' => ['Ana', ' '],
        ])->assertSessionHas('error');

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.whatsapp.send', $lead), [
            'type' => 'template', 'template_name' => 'Mala plantilla!',
        ])->assertSessionHasErrors('template_name');

        Http::assertNothingSent();
    }

    public function test_si_meta_rechaza_el_envio_se_muestra_el_motivo_y_no_queda_en_el_historial(): void
    {
        Http::fake(['graph.facebook.com/*/messages' => Http::response(['error' => ['message' => 'Recipient phone number not in allowed list', 'code' => 131030]], 400)]);
        $account = $this->account();
        $lead = $this->lead();
        $conv = $this->conversationWithInbound($account, $lead, 1);

        $res = $this->actingAs($this->admin)->post(route('admin.leads-inbox.whatsapp.send', $lead), ['type' => 'text', 'text' => 'Hola']);

        $res->assertSessionHas('error');
        $this->assertStringContainsString('not in allowed list', session('error'));
        $this->assertSame(0, $conv->messages()->where('sender_type', 'agent')->count());
        $this->assertSame('nuevo', $lead->fresh()->status);
    }

    public function test_sin_cuenta_activa_o_sin_numero_explica_que_falta(): void
    {
        Http::fake();
        $lead = $this->lead();

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.whatsapp.send', $lead), ['type' => 'template', 'template_name' => 'hello_world'])
            ->assertSessionHas('error');
        $this->assertStringContainsString('Integraciones', session('error'));

        $this->account();
        $sinNumero = $this->lead(['contact_phone' => null, 'phone_normalized' => null, 'contact_email' => 'a@b.com']);
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.whatsapp.send', $sinNumero), ['type' => 'template', 'template_name' => 'hello_world'])
            ->assertSessionHas('error');
        $this->assertStringContainsString('Editar contacto', session('error'));

        Http::assertNothingSent();
    }

    public function test_el_whatsapp_propio_tiene_prioridad_sobre_el_telefono(): void
    {
        Http::fake($this->ok());
        $this->account();
        $lead = $this->lead(['contact_whatsapp' => '33 1234 5678', 'whatsapp_normalized' => '+523312345678']);

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.whatsapp.send', $lead), ['type' => 'template', 'template_name' => 'hello_world', 'language' => 'en_US'])
            ->assertSessionHas('success');

        Http::assertSent(fn (HttpRequest $r) => $r->data()['to'] === '523312345678' && $r->data()['template']['language']['code'] === 'en_US');
    }

    // ── Plantillas disponibles ───────────────────────────────────────────

    public function test_lista_las_plantillas_aprobadas_con_su_idioma(): void
    {
        Http::fake(['graph.facebook.com/*/message_templates*' => Http::response(['data' => [
            ['name' => 'hello_world', 'status' => 'APPROVED', 'language' => 'en_US', 'components' => [['type' => 'BODY', 'text' => 'Hello']]],
            ['name' => 'seguimiento', 'status' => 'APPROVED', 'language' => 'es_MX', 'components' => [['type' => 'BODY', 'text' => 'Hola {{1}}, folio {{2}}']]],
            ['name' => 'borrador', 'status' => 'PENDING', 'language' => 'es_MX', 'components' => []],
        ]])]);
        $this->account();
        $lead = $this->lead();

        $json = $this->actingAs($this->admin)->getJson(route('admin.leads-inbox.whatsapp.templates', $lead))->assertOk()->json('templates');

        $this->assertCount(2, $json);
        $this->assertSame('en_US', $json[0]['language']);
        $this->assertCount(2, $json[1]['params']);
        $this->assertSame('es_MX', $json[1]['language']);
    }

    // ── Editar contacto ──────────────────────────────────────────────────

    public function test_edita_telefono_whatsapp_y_correo_por_separado_normalizados(): void
    {
        $lead = $this->lead(['source' => 'manual', 'contact_phone' => null, 'phone_normalized' => null]);

        $this->actingAs($this->admin)->put(route('admin.leads-inbox.contact.update', $lead), [
            'name' => 'Ana Torres López', 'phone' => '(449) 123-4567', 'whatsapp' => '33 1234 5678', 'email' => ' ANA@Empresa.COM ',
        ])->assertRedirect(route('admin.leads-inbox.index', ['lead' => $lead->id]))->assertSessionHas('success');

        $l = $lead->fresh();
        $this->assertSame('Ana Torres López', $l->contact_name);
        $this->assertSame('+524491234567', $l->phone_normalized);
        $this->assertSame('+523312345678', $l->whatsapp_normalized);
        $this->assertSame('33 1234 5678', $l->whatsapp_display);
        $this->assertSame('ana@empresa.com', $l->contact_email);
    }

    public function test_whatsapp_vacio_vuelve_a_usar_el_telefono_y_numeros_malos_se_rechazan(): void
    {
        $lead = $this->lead(['contact_whatsapp' => '33 1234 5678', 'whatsapp_normalized' => '+523312345678']);

        $this->actingAs($this->admin)->put(route('admin.leads-inbox.contact.update', $lead), ['whatsapp' => ''])->assertSessionHas('success');
        $this->assertNull($lead->fresh()->whatsapp_normalized);
        $this->assertSame('+524491234567', $lead->fresh()->whatsapp_target);

        $this->actingAs($this->admin)->put(route('admin.leads-inbox.contact.update', $lead), ['phone' => '123', 'email' => 'no-es-correo'])
            ->assertSessionHasErrors(['phone', 'email']);
        $this->assertSame('+524491234567', $lead->fresh()->phone_normalized);
    }

    public function test_un_whatsapp_entrante_se_enlaza_al_lead_que_ya_tiene_ese_numero_de_whatsapp(): void
    {
        $lead = $this->lead(['source' => 'manual', 'contact_phone' => '449 000 1111', 'phone_normalized' => '+524490001111',
            'contact_whatsapp' => '33 1234 5678', 'whatsapp_normalized' => '+523312345678']);

        $found = app(LeadService::class)->findOpenLead('+523312345678', null);

        $this->assertSame($lead->id, $found?->id);
    }

    // ── Webhook: mismo número en otro formato ────────────────────────────

    public function test_el_mensaje_entrante_reusa_la_conversacion_aunque_meta_mande_el_521(): void
    {
        $account = $this->account();
        $existing = WhatsappConversation::create([
            'account_id' => $account->id, 'contact_phone' => '524491234567', 'status' => 'open', 'started_at' => now(), 'unread_count' => 0,
        ]);

        $msg = app(WhatsappService::class)->ingestInboundMessage($account, '5214491234567', ['content' => 'Hola']);

        $this->assertSame($existing->id, $msg->conversation_id);
        $this->assertSame(1, WhatsappConversation::count());
    }

    // ── Pantalla y permisos ──────────────────────────────────────────────

    public function test_la_bandeja_muestra_el_envio_de_whatsapp_y_el_formulario_de_contacto(): void
    {
        $account = $this->account();
        $lead = $this->lead();
        $this->conversationWithInbound($account, $lead, 1);

        $html = $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['lead' => $lead->id]))->assertOk()->getContent();

        $this->assertStringContainsString(route('admin.leads-inbox.whatsapp.send', $lead), $html);
        $this->assertStringContainsString('Ventana de 24 h abierta', $html);
        $this->assertStringContainsString(route('admin.leads-inbox.contact.update', $lead), $html);
        $this->assertStringContainsString('name="whatsapp"', $html);
    }

    public function test_con_la_ventana_cerrada_la_pantalla_pide_plantilla(): void
    {
        $account = $this->account();
        $lead = $this->lead();
        $this->conversationWithInbound($account, $lead, 40);

        $html = $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['lead' => $lead->id]))->assertOk()->getContent();

        $this->assertStringContainsString('Ventana de 24 h cerrada', $html);
    }

    public function test_las_rutas_nuevas_exigen_permiso_de_editar(): void
    {
        foreach (['contact.update', 'whatsapp.templates', 'whatsapp.send'] as $name) {
            $route = Route::getRoutes()->getByName('admin.leads-inbox.' . $name);
            $this->assertNotNull($route, $name);
            $this->assertContains('permission:leads-inbox,edit', $route->middleware(), $name);
        }
    }
}
