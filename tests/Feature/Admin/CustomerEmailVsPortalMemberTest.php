<?php

namespace Tests\Feature\Admin;

use App\Models\ApiClient;
use App\Models\Customer;
use App\Models\CustomerPortalRole;
use App\Models\CustomerPortalUser;
use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use App\Rules\EmailNotPortalMember;
use App\Services\DealCustomerRegistrar;
use App\Services\LeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Un correo de usuario del equipo del portal no puede asignarse a un Customer (el portal ya bloquea el sentido contrario).
 * Correr con base privada: DB_DATABASE=portal_roles_test php artisan test --filter=CustomerEmailVsPortalMember
 */
class CustomerEmailVsPortalMemberTest extends TestCase
{
    use RefreshDatabase;

    private const TAKEN = 'miembro@equipo.com';

    private Customer $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->owner = $this->makeCustomer('titular@empresa.com');
        $role = CustomerPortalRole::create(['customer_id' => $this->owner->id, 'name' => 'Consulta']);
        CustomerPortalUser::create([
            'customer_id' => $this->owner->id, 'role_id' => $role->id, 'first_name' => 'Mia', 'last_name' => 'Miembro',
            'email' => self::TAKEN, 'password' => 'clave-segura-1', 'status' => 'active',
        ]);
    }

    private function makeCustomer(string $email): Customer
    {
        return Customer::create([
            'first_name' => 'Juan', 'last_name' => 'Pérez', 'email' => $email, 'phone' => '5555555555',
            'document_type' => 'ine', 'source' => 'admin', 'status' => 'active', 'company' => 'ACME', 'portal_access' => true,
        ]);
    }

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name_role' => 'Administrador'], ['name_role_es' => 'Administrador']);

        return User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'A', 'phone' => '5555555555',
            'email' => 'u-' . uniqid() . '@example.com', 'password' => bcrypt('x'), 'status' => 'active',
            'rfc' => strtoupper(substr(md5(uniqid('', true)), 0, 13)), 'role_id' => $role->id,
        ]);
    }

    private function adminPayload(string $email): array
    {
        return [
            'full_name' => 'Cliente Nuevo', 'company' => 'Empresa Uno', 'email' => $email, 'phone' => '4491234567',
            'source' => 'admin', 'status' => 'active', 'document_type' => '',
        ];
    }

    public function test_la_regla_ignora_mayusculas_y_espacios_y_los_vacios(): void
    {
        $this->assertTrue(EmailNotPortalMember::taken('  MIEMBRO@Equipo.COM '));
        $this->assertFalse(EmailNotPortalMember::taken('libre@equipo.com'));
        $this->assertFalse(EmailNotPortalMember::taken(null));
        $this->assertFalse(EmailNotPortalMember::taken(''));
    }

    public function test_el_admin_no_puede_crear_un_cliente_con_correo_de_un_usuario_del_portal(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('admin.clients.store'), $this->adminPayload('MIEMBRO@equipo.com'))
            ->assertStatus(422)->assertJsonValidationErrors('email');

        $this->assertSame(0, Customer::where('email', self::TAKEN)->count());

        // Un correo libre sigue funcionando.
        $this->postJson(route('admin.clients.store'), $this->adminPayload('libre@equipo.com'))->assertRedirect();
        $this->assertDatabaseHas('customers', ['email' => 'libre@equipo.com']);
    }

    public function test_el_admin_no_puede_cambiar_el_correo_de_un_cliente_al_de_un_usuario_del_portal(): void
    {
        $customer = $this->makeCustomer('otro@empresa.com');

        $this->actingAs($this->admin())
            ->putJson(route('admin.clients.update', $customer->id), $this->adminPayload(self::TAKEN))
            ->assertStatus(422)->assertJsonValidationErrors('email');
        $this->assertSame('otro@empresa.com', $customer->fresh()->email);

        // Conservar su propio correo sigue permitido.
        $this->putJson(route('admin.clients.update', $customer->id), $this->adminPayload('otro@empresa.com'))->assertSuccessful();
    }

    public function test_la_api_rechaza_el_correo_de_un_usuario_del_portal_al_crear_y_al_editar(): void
    {
        $client = ApiClient::create(['name' => 'N8N', 'is_active' => true]);
        $headers = ['Authorization' => 'Bearer ' . $client->createToken('t', ['customers:read', 'customers:write'])->plainTextToken];
        $payload = [
            'first_name' => 'Pedro', 'last_name' => 'Lopez', 'company' => 'Equiterm', 'phone' => '5555555555',
            'source' => 'admin', 'email' => 'Miembro@equipo.com',
        ];

        $this->postJson('/api/v1/customers', $payload, $headers)->assertStatus(422)->assertJsonValidationErrors('email');

        $customer = $this->makeCustomer('api@empresa.com');
        $this->putJson('/api/v1/customers/' . $customer->id, $payload, $headers)->assertStatus(422)->assertJsonValidationErrors('email');

        $this->postJson('/api/v1/customers', ['email' => 'libre@api.com'] + $payload, $headers)->assertStatus(201);
    }

    public function test_el_alta_de_cliente_desde_un_lead_rechaza_el_correo_de_un_usuario_del_portal(): void
    {
        $r = app(LeadService::class)->captureFromEmail([
            'message_id' => '<' . uniqid() . '@x>', 'email' => 'carlos@sigma.com', 'name' => 'Carlos Herrera', 'subject' => 'Cotizar',
        ]);
        $lead = Lead::findOrFail($r['lead_id']);

        $this->actingAs($this->admin())
            ->postJson(route('admin.leads-inbox.customer.store', $lead), ['full_name' => 'Interesado Uno', 'email' => self::TAKEN, 'document_type' => 'ine'])
            ->assertStatus(422)->assertJsonValidationErrors('email');

        $this->assertNull($lead->fresh()->customer_id);
    }

    public function test_convertir_un_lead_en_cliente_no_usa_el_correo_de_un_usuario_del_portal(): void
    {
        $r = app(LeadService::class)->captureFromEmail([
            'message_id' => '<' . uniqid() . '@x>', 'email' => self::TAKEN, 'name' => 'Mia Miembro', 'subject' => 'Cotizar',
        ]);
        $lead = Lead::findOrFail($r['lead_id']);

        $customer = app(LeadService::class)->convertToCustomer($lead);

        $this->assertNotSame(self::TAKEN, $customer->email);
        $this->assertStringEndsWith('@lead.invalid', $customer->email);
    }

    public function test_el_registro_desde_un_negocio_no_asigna_el_correo_de_un_usuario_del_portal(): void
    {
        $result = app(DealCustomerRegistrar::class)->fromContact([
            'contact_snapshot_name' => 'Contacto Nuevo', 'contact_snapshot_email' => 'Miembro@Equipo.com',
            'contact_snapshot_phone' => '4491112233', 'company_snapshot' => 'Empresa X',
        ]);

        $this->assertTrue($result['created']);
        $this->assertNull($result['customer']->fresh()->email);
        $this->assertSame(0, Customer::where('email', self::TAKEN)->count());

        // Un correo que ya es de un cliente sigue ligando al existente (no se duplica).
        $again = app(DealCustomerRegistrar::class)->fromContact([
            'contact_snapshot_name' => 'Otro Nombre', 'contact_snapshot_email' => 'titular@empresa.com',
        ]);
        $this->assertFalse($again['created']);
        $this->assertSame($this->owner->id, $again['customer']->id);
    }
}
