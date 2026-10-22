<?php

namespace Tests\Feature\Crm;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use App\Models\WhatsappAccount;
use App\Services\LeadService;
use App\Services\WhatsappService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadCustomerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $role = Role::firstOrCreate(['name_role' => 'Administrador'], ['name_role_es' => 'Administrador']);
        $this->admin = User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'A', 'phone' => '5555555555',
            'email' => 'u-' . uniqid() . '@example.com', 'password' => bcrypt('x'), 'status' => 'active',
            'rfc' => 'RFC' . strtoupper(substr(uniqid(), -8)) . random_int(10, 99), 'role_id' => $role->id,
        ]);
    }

    private function emailLead(string $email = 'carlos@sigma.com'): Lead
    {
        $r = app(LeadService::class)->captureFromEmail(['message_id' => '<' . uniqid() . '@x>', 'email' => $email, 'name' => 'Carlos Herrera', 'subject' => 'Cotizar']);

        return Lead::findOrFail($r['lead_id']);
    }

    private function customer(array $over = []): Customer
    {
        return Customer::create($over + [
            'first_name' => 'Ana', 'last_name' => 'Lopez', 'email' => 'ana@empresa.com', 'company' => 'Empresa', 'phone' => '5512345678',
            'document_type' => 'ine', 'source' => 'admin', 'status' => 'active',
        ]);
    }

    private function payload(array $over = []): array
    {
        return $over + ['full_name' => 'Carlos Herrera Ruiz', 'company' => 'Sigma Alimentos', 'email' => 'carlos@sigma.com', 'phone' => '4421234567',
            'rfc' => 'XAXX010101000', 'tipo_persona' => 'moral', 'document_type' => 'ine', 'source' => 'admin', 'notes' => 'Compras de calderas'];
    }

    public function test_el_formulario_viene_con_los_datos_del_lead(): void
    {
        $lead = $this->emailLead();

        $this->actingAs($this->admin)->get(route('admin.leads-inbox.customer.form', $lead))
            ->assertOk()
            ->assertSee('Agregar como cliente')
            ->assertSee('Carlos Herrera')
            ->assertSee('carlos@sigma.com')
            ->assertDontSee('Vincular a este cliente');
    }

    public function test_avisa_si_ya_existe_un_cliente_con_ese_correo(): void
    {
        // El cliente se da de alta DESPUES de que llego el lead (si ya existia, el sistema los liga solo).
        $lead = $this->emailLead('carlos@sigma.com');
        $existing = $this->customer(['email' => 'carlos@sigma.com']);
        $this->assertNull($lead->fresh()->customer_id);

        $this->actingAs($this->admin)->get(route('admin.leads-inbox.customer.form', $lead))
            ->assertOk()
            ->assertSee('Vincular a este cliente')
            ->assertSee('Ana Lopez');
        $this->assertSame($existing->id, app(LeadService::class)->findCustomer($lead->phone_normalized, $lead->contact_email)?->id);
    }

    public function test_crea_el_cliente_con_los_datos_capturados_y_liga_el_lead(): void
    {
        $lead = $this->emailLead();

        $this->actingAs($this->admin)->postJson(route('admin.leads-inbox.customer.store', $lead), $this->payload())
            ->assertOk()
            ->assertJsonPath('redirect', route('admin.leads-inbox.index', ['lead' => $lead->id]));

        $customer = Customer::where('email', 'carlos@sigma.com')->firstOrFail();
        $this->assertSame('Carlos', $customer->first_name);
        $this->assertSame('Herrera Ruiz', $customer->last_name);
        $this->assertSame('Sigma Alimentos', $customer->company);
        $this->assertSame('moral', $customer->tipo_persona);
        $this->assertSame('active', $customer->status);
        $this->assertSame($customer->id, $lead->fresh()->customer_id);
    }

    public function test_sin_correo_tambien_se_puede_crear(): void
    {
        $lead = app(LeadService::class)->capture(['source' => 'llamada', 'phone' => '4429998877']);

        $this->actingAs($this->admin)->postJson(route('admin.leads-inbox.customer.store', $lead), ['full_name' => 'Cliente Por Telefono', 'document_type' => 'ine', 'phone' => '4429998877'])
            ->assertOk();

        $customer = Customer::where('first_name', 'Cliente')->firstOrFail();
        $this->assertNull($customer->email);
        $this->assertSame($customer->id, $lead->fresh()->customer_id);
        $this->assertSame('admin', $customer->source);
    }

    public function test_un_lead_de_whatsapp_liga_tambien_su_conversacion(): void
    {
        $account = WhatsappAccount::create(['name' => 'Meta', 'phone_number' => '+1 555 000 0000', 'connection_type' => 'cloud_api', 'is_active' => true]);
        app(WhatsappService::class)->ingestInboundMessage($account, '5215562230266', ['content' => 'Hola']);
        $lead = Lead::where('source', 'whatsapp')->firstOrFail();
        $this->assertNotNull($lead->whatsapp_conversation_id);

        $this->actingAs($this->admin)->postJson(route('admin.leads-inbox.customer.store', $lead), ['full_name' => 'Cliente WhatsApp', 'document_type' => 'ine'])->assertOk();

        $customer = Customer::where('first_name', 'Cliente')->firstOrFail();
        $this->assertSame('whatsapp', $customer->source);
        $this->assertSame($customer->id, $lead->fresh()->whatsappConversation->customer_id);
    }

    public function test_un_cliente_que_ya_existia_se_liga_solo_al_llegar_el_lead(): void
    {
        $existing = $this->customer(['email' => 'carlos@sigma.com']);

        $this->assertSame($existing->id, $this->emailLead('carlos@sigma.com')->customer_id);
    }

    public function test_vincular_a_un_cliente_existente_no_crea_otro(): void
    {
        $lead = $this->emailLead();
        $existing = $this->customer();

        $this->actingAs($this->admin)->postJson(route('admin.leads-inbox.customer.store', $lead), ['link_customer_id' => $existing->id])->assertOk();

        $this->assertSame(1, Customer::count());
        $this->assertSame($existing->id, $lead->fresh()->customer_id);
    }

    public function test_valida_nombre_documento_y_correo_repetido(): void
    {
        $this->customer(['email' => 'repetido@empresa.com']);
        $lead = $this->emailLead();

        $this->actingAs($this->admin)->postJson(route('admin.leads-inbox.customer.store', $lead), $this->payload(['full_name' => '']))
            ->assertStatus(422)->assertJsonValidationErrors('full_name');
        $this->actingAs($this->admin)->postJson(route('admin.leads-inbox.customer.store', $lead), $this->payload(['document_type' => 'otro']))
            ->assertStatus(422)->assertJsonValidationErrors('document_type');
        $this->actingAs($this->admin)->postJson(route('admin.leads-inbox.customer.store', $lead), $this->payload(['email' => 'repetido@empresa.com']))
            ->assertStatus(422)->assertJsonValidationErrors('email');

        $this->assertNull($lead->fresh()->customer_id);
        $this->assertSame(1, Customer::count());
    }

    public function test_un_lead_ya_ligado_no_se_vuelve_a_convertir(): void
    {
        $lead = $this->emailLead();
        $this->actingAs($this->admin)->postJson(route('admin.leads-inbox.customer.store', $lead), $this->payload())->assertOk();

        $this->actingAs($this->admin)->postJson(route('admin.leads-inbox.customer.store', $lead->fresh()), $this->payload(['email' => 'otro@x.com']))->assertStatus(422);
        $this->assertSame(1, Customer::count());

        $this->actingAs($this->admin)->get(route('admin.leads-inbox.customer.form', $lead->fresh()))->assertOk()->assertSee('Ya es cliente');
    }

    public function test_la_bandeja_muestra_agregar_como_cliente_y_luego_ver_cliente(): void
    {
        $lead = $this->emailLead();

        $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['lead' => $lead->id]))
            ->assertOk()->assertSee('data-lead-customer', false)->assertSee('Agregar como cliente');

        $this->actingAs($this->admin)->postJson(route('admin.leads-inbox.customer.store', $lead), $this->payload())->assertOk();

        $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['lead' => $lead->id]))
            ->assertOk()->assertSee('Ver cliente')->assertDontSee('data-lead-customer', false);
    }

    public function test_la_creacion_queda_en_la_bitacora_del_sistema(): void
    {
        $lead = $this->emailLead();
        $this->actingAs($this->admin)->postJson(route('admin.leads-inbox.customer.store', $lead), $this->payload())->assertOk();

        $customer = Customer::where('email', 'carlos@sigma.com')->firstOrFail();
        $this->assertDatabaseHas('system_logs', ['entity_type' => 'customer', 'entity_id' => $customer->id, 'action' => 'created']);
    }
}
