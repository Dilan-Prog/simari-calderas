<?php

namespace Tests\Feature\Crm;

use App\Models\AdVisit;
use App\Models\ApiClient;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Models\WhatsappAccount;
use App\Services\LeadService;
use App\Services\WhatsappService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeadInboxTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $role = Role::create(['name_role' => 'Administrador', 'name_role_es' => 'Administrador']);

        return User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'Administrador',
            'phone' => '5555555555', 'email' => 'admin-' . uniqid() . '@example.com',
            'password' => bcrypt('password'), 'status' => 'active', 'rfc' => 'XAXX010101000', 'role_id' => $role->id,
        ]);
    }

    private function visit(string $uuid, array $over = []): AdVisit
    {
        return AdVisit::create(array_merge([
            'visitor_uuid' => $uuid,
            'first_gclid' => 'GCLID-FIRST', 'first_utm_source' => 'google', 'first_utm_campaign' => 'camp-primera',
            'first_seen_at' => now()->subDays(5),
            'gclid' => 'GCLID-FIRST', 'utm_source' => 'google', 'utm_campaign' => 'camp-primera',
            'last_seen_at' => now()->subDays(5), 'expires_at' => now()->addDays(90),
        ], $over));
    }

    public function test_normaliza_telefonos_a_e164_con_52(): void
    {
        $this->assertSame('+525512345678', LeadService::normalizePhone('55 1234 5678'));
        $this->assertSame('+525512345678', LeadService::normalizePhone('(55) 1234-5678'));
        $this->assertSame('+525512345678', LeadService::normalizePhone('525512345678'));
        $this->assertSame('+525512345678', LeadService::normalizePhone('+52 1 55 1234 5678'));
        $this->assertSame('+525512345678', LeadService::normalizePhone('044 55 1234 5678'));
        $this->assertSame('+14155550123', LeadService::normalizePhone('+1 415 555 0123'));
        $this->assertNull(LeadService::normalizePhone('123'));
        $this->assertNull(LeadService::normalizePhone(null));
        $this->assertSame('ana@correo.com', LeadService::normalizeEmail('  Ana@Correo.COM '));
        $this->assertNull(LeadService::normalizeEmail('no-es-correo'));
    }

    public function test_captura_crea_lead_y_deduplica_por_telefono_y_correo(): void
    {
        $svc = app(LeadService::class);

        $a = $svc->capture(['source' => 'formulario', 'name' => 'Ana', 'phone' => '55 1234 5678']);
        $this->assertSame('+525512345678', $a->phone_normalized);
        $this->assertSame('nuevo', $a->status);

        // mismo teléfono con otro formato -> actualiza, no duplica
        $b = $svc->capture(['source' => 'whatsapp', 'phone' => '+52 1 55 1234 5678', 'email' => 'ANA@x.com']);
        $this->assertSame($a->id, $b->id);
        $this->assertSame('ana@x.com', $b->contact_email);
        $this->assertSame(1, Lead::count());

        // mismo correo sin teléfono -> sigue siendo el mismo lead abierto
        $c = $svc->capture(['source' => 'correo', 'email' => 'ana@x.com']);
        $this->assertSame($a->id, $c->id);

        // lead descartado ya no es "abierto": uno nuevo
        $a->update(['status' => 'descartado']);
        $d = $svc->capture(['source' => 'manual', 'phone' => '5512345678']);
        $this->assertNotSame($a->id, $d->id);
        $this->assertSame(2, Lead::count());
    }

    public function test_detecta_cliente_existente_por_telefono_con_formato_distinto(): void
    {
        $customer = Customer::create(['first_name' => 'Luis', 'last_name' => 'Perez', 'email' => 'luis@x.com', 'phone' => '(55) 1234-5678', 'status' => 'active', 'document_type' => 'ine', 'source' => 'web', 'company' => '']);

        $lead = app(LeadService::class)->capture(['source' => 'llamada', 'phone' => '5512345678']);

        $this->assertSame($customer->id, $lead->customer_id);
        $this->assertSame('Luis Perez', $lead->contact_name);
        $this->assertSame('luis@x.com', $lead->customer->email);
    }

    public function test_conserva_primer_clic_y_actualiza_ultimo_clic(): void
    {
        $uuid = '11111111-1111-4111-8111-111111111111';
        $visit = $this->visit($uuid);
        $svc = app(LeadService::class);

        $lead = $svc->capture(['source' => 'formulario', 'phone' => '5512345678', 'visitor_uuid' => $uuid]);
        $this->assertSame('GCLID-FIRST', $lead->first_click['gclid']);
        $this->assertSame('GCLID-FIRST', $lead->gclid);

        // el visitante vuelve por otro anuncio: AdVisit actualiza last-touch, first_* no cambia
        $visit->update(['gclid' => 'GCLID-LAST', 'utm_campaign' => 'camp-ultima', 'last_seen_at' => now()]);

        $again = $svc->capture(['source' => 'whatsapp', 'phone' => '5512345678', 'visitor_uuid' => $uuid]);

        $this->assertSame($lead->id, $again->id);
        $this->assertSame('GCLID-FIRST', $again->first_click['gclid']);
        $this->assertSame('camp-primera', $again->first_click['utm_campaign']);
        $this->assertSame('GCLID-LAST', $again->last_click['gclid']);
        $this->assertSame('GCLID-LAST', $again->gclid);
        $this->assertSame('camp-ultima', $again->utm_campaign);
    }

    public function test_llamada_perdida_crea_tarea_y_llamada_larga_no(): void
    {
        $svc = app(LeadService::class);

        $lost = $svc->recordCall(['phone' => '5512345678', 'status' => 'perdida', 'duration_seconds' => 0]);
        $this->assertSame(1, Task::where('taskable_type', Lead::class)->where('taskable_id', $lost->lead_id)->count());
        $task = Task::first();
        $this->assertStringContainsString('Llamada perdida', $task->title);
        $this->assertSame('open', $task->status);

        // contestada y larga: sin tarea nueva
        $svc->recordCall(['phone' => '5512345678', 'status' => 'contestada', 'duration_seconds' => 240]);
        $this->assertSame(1, Task::count());
        $this->assertNotNull(Lead::first()->first_contact_at);

        // contestada pero muy corta (< umbral 30 s): tarea
        $svc->recordCall(['phone' => '5512345678', 'status' => 'contestada', 'duration_seconds' => 8]);
        $this->assertSame(2, Task::count());
        $this->assertSame(1, Lead::count());
    }

    public function test_api_de_llamadas_con_token_de_integracion(): void
    {
        $client = ApiClient::create(['name' => 'Telefonia', 'is_active' => true]);
        Sanctum::actingAs($client, ['leads:write']);

        $this->postJson('/api/v1/leads/call', [
            'phone' => '55 1234 5678', 'status' => 'perdida', 'duration_seconds' => 0, 'external_id' => 'CALL-1',
        ])->assertCreated()->assertJsonPath('data.follow_up', true);

        // idempotente por external_id
        $this->postJson('/api/v1/leads/call', [
            'phone' => '55 1234 5678', 'status' => 'perdida', 'external_id' => 'CALL-1',
        ])->assertCreated();

        $this->assertSame(1, Lead::count());
        $this->assertSame(1, Task::count());
        $this->postJson('/api/v1/leads/call', ['phone' => '123', 'status' => 'perdida'])->assertStatus(422);
    }

    public function test_api_de_llamadas_exige_ability(): void
    {
        $client = ApiClient::create(['name' => 'Sin permiso', 'is_active' => true]);
        Sanctum::actingAs($client, ['deals:read']);

        $this->postJson('/api/v1/leads/call', ['phone' => '5512345678', 'status' => 'perdida'])->assertForbidden();
    }

    public function test_mensaje_entrante_de_whatsapp_crea_lead_sin_duplicar(): void
    {
        $account = WhatsappAccount::create([
            'name' => 'QR', 'phone_number' => '+52 55 9876 5432', 'connection_type' => 'baileys_qr', 'is_active' => true,
        ]);
        $wa = app(WhatsappService::class);

        $wa->ingestInboundMessage($account, '5215512345678', ['content' => 'Hola, quiero una caldera']);
        $wa->ingestInboundMessage($account, '5215512345678', ['content' => 'Sigo esperando']);

        $this->assertSame(1, Lead::count());
        $lead = Lead::first();
        $this->assertSame('whatsapp', $lead->source);
        $this->assertSame('+525512345678', $lead->phone_normalized);
        $this->assertNotNull($lead->whatsapp_conversation_id);
    }

    public function test_whatsapp_con_ref_de_calculadora_hereda_atribucion(): void
    {
        $uuid = '22222222-2222-4222-8222-222222222222';
        $this->visit($uuid);
        $pool = \App\Models\PoolCalculatorLead::create(['ref' => 'EQ-AB12', 'visitor_uuid' => $uuid, 'payload' => ['datos' => []], 'status' => 'nuevo']);
        app(LeadService::class)->captureFromPoolCalculator($pool);

        $account = WhatsappAccount::create(['name' => 'QR', 'phone_number' => '+52 55 9876 5432', 'connection_type' => 'baileys_qr', 'is_active' => true]);
        app(WhatsappService::class)->ingestInboundMessage($account, '5215598765432', ['content' => 'Hola, mi Ref es EQ-AB12']);

        $lead = Lead::where('phone_normalized', '+525598765432')->first();
        $this->assertNotNull($lead);
        $this->assertSame('GCLID-FIRST', $lead->first_click['gclid']);
    }

    public function test_tareas_de_correo_n8n_se_rotulan_en_origen(): void
    {
        $this->withoutVite();
        $admin = $this->adminUser();
        app(LeadService::class)->captureFromEmail(['message_id' => '<a@b>', 'email' => 'c@d.com', 'subject' => 'Cotizar caldera']);

        $this->actingAs($admin)->get(route('admin.tasks.index'))
            ->assertOk()
            ->assertSee('Cotizar caldera')
            ->assertSee('Correo N8N');
    }

    public function test_pantallas_de_la_bandeja(): void
    {
        $this->withoutVite();
        $admin = $this->adminUser();
        $lead = app(LeadService::class)->capture(['source' => 'manual', 'name' => 'Marta', 'phone' => '5512345678']);

        $this->actingAs($admin)->get(route('admin.leads-inbox.index', ['source' => 'manual']))
            ->assertOk()->assertSee('Marta')->assertSee('Origen: Manual');
        $this->actingAs($admin)->get(route('admin.leads-inbox.show', $lead))->assertOk()->assertSee('Línea de tiempo');

        $this->actingAs($admin)->post(route('admin.leads-inbox.assign', $lead), ['assigned_to' => $admin->id])->assertRedirect();
        $this->assertSame($admin->id, $lead->fresh()->assigned_to);
        $this->assertSame('contactado', $lead->fresh()->status);

        $this->actingAs($admin)->put(route('admin.leads-inbox.update', $lead), ['status' => 'calificado'])->assertRedirect();
        $this->assertSame('calificado', $lead->fresh()->status);

        $this->actingAs($admin)->post(route('admin.leads-inbox.calls.store', $lead), ['status' => 'perdida'])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.leads-inbox.convert', $lead), ['target' => 'customer'])->assertRedirect();
        $this->assertNotNull($lead->fresh()->customer_id);

        $this->assertSame(1, Task::count());
    }
}
