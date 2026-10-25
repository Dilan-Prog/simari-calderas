<?php

namespace Tests\Feature\Crm;

use App\Models\Customer;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Role;
use App\Models\User;
use App\Services\DealService;
use App\Services\LeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Negocios: si no hay cliente registrado y se capturan datos de contacto, se crea (o se liga) el cliente. */
class DealCustomerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Pipeline $pipeline;

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
        $this->pipeline = Pipeline::create(['name' => 'Ventas General', 'channel' => 'deals', 'is_default' => true, 'is_active' => true]);
        PipelineStage::create(['pipeline_id' => $this->pipeline->id, 'name' => 'Prospecto', 'slug' => 'prospecto', 'order' => 0]);
    }

    private function payload(array $over = []): array
    {
        return $over + [
            'pipeline_id' => $this->pipeline->id, 'name' => 'Negocio nuevo', 'amount' => 100, 'currency' => 'MXN',
            'company_snapshot' => 'Versatil Consulting', 'contact_snapshot_name' => 'Diana Romero',
            'contact_snapshot_email' => 'Diana.Romero@versatilconsulting.com.mx', 'contact_snapshot_phone' => '449 123 4567',
            'register_customer' => '1',
        ];
    }

    public function test_al_crear_un_negocio_sin_cliente_se_registra_el_cliente(): void
    {
        $this->actingAs($this->admin)->post(route('admin.deals.store'), $this->payload())->assertRedirect();

        $customer = Customer::where('email', 'diana.romero@versatilconsulting.com.mx')->firstOrFail();
        $this->assertSame('Diana', $customer->first_name);
        $this->assertSame('Romero', $customer->last_name);
        $this->assertSame('Versatil Consulting', $customer->company);
        $this->assertSame('449 123 4567', $customer->phone);
        $this->assertSame($customer->id, Deal::firstOrFail()->customer_id);
    }

    public function test_si_ya_existe_un_cliente_con_ese_correo_se_liga_sin_duplicar(): void
    {
        $existing = Customer::create(['first_name' => 'Diana', 'last_name' => 'R', 'email' => 'diana.romero@versatilconsulting.com.mx', 'company' => 'X', 'phone' => '1', 'document_type' => '', 'source' => 'admin', 'status' => 'active']);

        $this->actingAs($this->admin)->post(route('admin.deals.store'), $this->payload())->assertRedirect();

        $this->assertSame(1, Customer::count());
        $this->assertSame($existing->id, Deal::firstOrFail()->customer_id);
    }

    public function test_si_se_desmarca_registrar_cliente_no_se_crea(): void
    {
        $this->actingAs($this->admin)->post(route('admin.deals.store'), $this->payload(['register_customer' => '0']))->assertRedirect();

        $this->assertSame(0, Customer::count());
        $this->assertNull(Deal::firstOrFail()->customer_id);
    }

    public function test_si_se_elige_un_cliente_no_se_crea_otro(): void
    {
        $c = Customer::create(['first_name' => 'Ana', 'last_name' => 'Lopez', 'email' => 'ana@x.com', 'company' => 'X', 'phone' => '1', 'document_type' => '', 'source' => 'admin', 'status' => 'active']);

        $this->actingAs($this->admin)->post(route('admin.deals.store'), $this->payload(['customer_id' => $c->id]))->assertRedirect();

        $this->assertSame(1, Customer::count());
        $this->assertSame($c->id, Deal::firstOrFail()->customer_id);
    }

    public function test_sin_nombre_de_contacto_no_se_registra_nada(): void
    {
        $this->actingAs($this->admin)->post(route('admin.deals.store'), $this->payload(['contact_snapshot_name' => '']))->assertRedirect();

        $this->assertSame(0, Customer::count());
    }

    public function test_al_editar_un_negocio_sin_cliente_se_registra_y_el_lead_queda_ligado_al_cliente(): void
    {
        $r = app(LeadService::class)->captureFromEmail(['message_id' => '<' . uniqid() . '@x>', 'email' => 'diana.romero@versatilconsulting.com.mx', 'name' => 'Diana Romero', 'subject' => 'Cotizar']);
        $lead = Lead::findOrFail($r['lead_id']);
        $deal = app(DealService::class)->create(['pipeline_id' => $this->pipeline->id, 'name' => 'Lead: Diana Romero', 'amount' => 0, 'currency' => 'MXN', 'status' => 'open']);
        $lead->update(['deal_id' => $deal->id]);

        $this->actingAs($this->admin)->put(route('admin.deals.update', $deal), $this->payload(['name' => 'Lead: Diana Romero']))->assertRedirect();

        $customer = Customer::where('email', 'diana.romero@versatilconsulting.com.mx')->firstOrFail();
        $this->assertSame($customer->id, $deal->fresh()->customer_id);
        $this->assertSame($customer->id, $lead->fresh()->customer_id);
    }

    public function test_el_formulario_trae_el_selector_con_busqueda_y_la_casilla_de_registro(): void
    {
        $deal = app(DealService::class)->create(['pipeline_id' => $this->pipeline->id, 'name' => 'N', 'amount' => 0, 'currency' => 'MXN', 'status' => 'open']);

        foreach ([route('admin.deals.create'), route('admin.deals.edit', $deal)] as $url) {
            $html = $this->actingAs($this->admin)->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('data-searchable', $html);
            $this->assertStringContainsString('searchable-select.js', $html);
            $this->assertStringContainsString('name="register_customer"', $html);
        }
    }
}
