<?php

namespace Tests\Feature\Crm;

use App\Models\Customer;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Quote;
use App\Models\Role;
use App\Models\User;
use App\Services\LeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadDealTest extends TestCase
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

        $deals = Pipeline::create(['name' => 'Ventas General', 'channel' => 'deals', 'is_default' => true, 'is_active' => true]);
        PipelineStage::create(['pipeline_id' => $deals->id, 'name' => 'Prospecto', 'slug' => 'prospecto', 'order' => 0]);
    }

    private function lead(): Lead
    {
        $r = app(LeadService::class)->captureFromEmail(['message_id' => '<' . uniqid() . '@x>', 'email' => 'carlos@sigma.com', 'name' => 'Carlos Herrera', 'subject' => 'Cotizar']);

        return Lead::findOrFail($r['lead_id']);
    }

    private function customer(array $over = []): Customer
    {
        return Customer::create($over + [
            'first_name' => 'Carlos', 'last_name' => 'Herrera', 'email' => 'carlos@sigma.com', 'company' => 'Sigma Alimentos', 'phone' => '4421234567',
            'document_type' => 'ine', 'source' => 'admin', 'status' => 'active', 'website' => 'https://sigma-alimentos.com',
        ]);
    }

    private function leadWithCustomer(): Lead
    {
        $lead = $this->lead();
        $lead->update(['customer_id' => $this->customer()->id]);

        return $lead->fresh();
    }

    private function quote(Customer $customer, string $number, float $total, string $currency = 'MXN'): Quote
    {
        return Quote::create([
            'quote_number' => $number, 'status' => 'sent', 'created_by_user_id' => $this->admin->id, 'customer_id' => $customer->id,
            'guest_name' => 'Carlos', 'guest_email' => 'carlos@sigma.com', 'total' => $total, 'currency' => $currency,
        ]);
    }

    private function page(Lead $lead)
    {
        return $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['lead' => $lead->id]))->assertOk();
    }

    public function test_sin_cliente_pide_agregarlo_primero_y_no_crea_el_negocio(): void
    {
        $lead = $this->lead();

        $this->page($lead)->assertSee('primero agrega al lead como cliente')->assertDontSee('Crear negocio')->assertDontSee('Convertir a negocio');

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.store', $lead), ['amount' => 1000])
            ->assertRedirect()->assertSessionHas('error');
        $this->assertNull($lead->fresh()->deal_id);
        $this->assertSame(0, Deal::count());
    }

    public function test_con_cliente_muestra_sus_datos_y_crea_el_negocio_con_ellos(): void
    {
        $lead = $this->leadWithCustomer();

        $this->page($lead)->assertSee('Crear negocio')->assertSee('Sigma Alimentos')->assertSee('sigma-alimentos.com');

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.store', $lead), [
            'name' => 'Caldera 300 CC', 'amount' => 125000, 'currency' => 'USD', 'expected_close_date' => '2026-12-15', 'notes' => 'Urge para enero', 'owner_id' => $this->admin->id,
        ])->assertRedirect(route('admin.leads-inbox.index', ['lead' => $lead->id]))->assertSessionHas('success');

        $deal = Deal::firstOrFail();
        $this->assertSame($deal->id, $lead->fresh()->deal_id);
        $this->assertSame('Caldera 300 CC', $deal->name);
        $this->assertSame($lead->customer_id, $deal->customer_id);
        $this->assertSame('Sigma Alimentos', $deal->company_snapshot);
        $this->assertSame('Carlos Herrera', $deal->contact_snapshot_name);
        $this->assertSame('carlos@sigma.com', $deal->contact_snapshot_email);
        $this->assertSame('4421234567', $deal->contact_snapshot_phone);
        $this->assertSame('USD', $deal->currency);
        $this->assertSame($this->admin->id, $deal->owner_id);
        $this->assertSame('calificado', $lead->fresh()->status);
    }

    public function test_el_nombre_del_negocio_por_defecto_usa_la_empresa(): void
    {
        $lead = $this->leadWithCustomer();

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.store', $lead), [])->assertSessionHas('success');

        $this->assertSame('Negocio - Sigma Alimentos', Deal::firstOrFail()->name);
    }

    public function test_con_negocio_muestra_folio_y_el_boton_para_crear_cotizacion_en_modal(): void
    {
        $lead = $this->leadWithCustomer();
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.store', $lead), []);
        $deal = Deal::firstOrFail();

        $this->page($lead->fresh())
            ->assertSee($deal->folio)->assertSee('Cotizaciones (0)')->assertSee('+ Crear cotización')
            ->assertSee('customer_id=' . $lead->customer_id . '&amp;deal_id=' . $deal->id . '&amp;embed=1', false)
            ->assertDontSee('Convertir a negocio');
    }

    public function test_ligar_una_cotizacion_toma_su_monto_y_se_puede_quitar(): void
    {
        $lead = $this->leadWithCustomer();
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.store', $lead), []);
        $deal = Deal::firstOrFail();
        $q1 = $this->quote($lead->customer, 'COT-2026-0001', 5049.00);
        $q2 = $this->quote($lead->customer, 'COT-2026-0002', 8200.50, 'USD');

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.quotes.link', $lead), ['quote_id' => $q1->id])->assertSessionHas('success');
        $this->assertSame('5049.00', $deal->fresh()->amount);

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.quotes.link', $lead), ['quote_number' => 'cot-2026-0002'])->assertSessionHas('success');
        $deal->refresh();
        $this->assertSame('8200.50', $deal->amount);
        $this->assertSame('USD', $deal->currency);
        $this->assertSame(2, $deal->quotes()->count());

        $this->page($lead->fresh())->assertSee('COT-2026-0001')->assertSee('COT-2026-0002')->assertSee('Cotizaciones (2)');

        $this->actingAs($this->admin)->delete(route('admin.leads-inbox.deal.quotes.unlink', [$lead, $q2]))->assertSessionHas('success');
        $deal->refresh();
        $this->assertSame(1, $deal->quotes()->count());
        $this->assertSame('5049.00', $deal->amount, 'el monto vuelve a la cotizacion que queda ligada');
    }

    public function test_no_acepta_cotizaciones_de_otro_cliente_ni_inexistentes(): void
    {
        $lead = $this->leadWithCustomer();
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.store', $lead), []);
        $other = $this->customer(['email' => 'otro@x.com', 'first_name' => 'Otro']);
        $foreign = $this->quote($other, 'COT-2026-0099', 100);

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.quotes.link', $lead), ['quote_id' => $foreign->id])->assertSessionHas('error');
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.quotes.link', $lead), ['quote_number' => 'COT-NO-EXISTE'])->assertSessionHas('error');

        $this->assertSame(0, Deal::firstOrFail()->quotes()->count());
    }

    public function test_ligar_sin_negocio_da_error(): void
    {
        $lead = $this->leadWithCustomer();
        $q = $this->quote($lead->customer, 'COT-2026-0001', 10);

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.quotes.link', $lead), ['quote_id' => $q->id])->assertSessionHas('error');
    }

    public function test_la_bitacora_registra_el_negocio_y_las_cotizaciones_ligadas(): void
    {
        $lead = $this->leadWithCustomer();
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.store', $lead), []);
        $q = $this->quote($lead->customer, 'COT-2026-0007', 5049.00);
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.quotes.link', $lead), ['quote_id' => $q->id]);

        $titles = collect(\App\Support\LeadLog::for($lead->fresh()))->pluck('title')->implode(' | ');
        $this->assertStringContainsString('Negocio creado:', $titles);
        $this->assertStringContainsString('Cotizacion COT-2026-0007 ligada al negocio', $titles);
    }

    public function test_el_sitio_web_se_normaliza_se_valida_y_se_muestra_en_el_contacto(): void
    {
        $lead = $this->lead();

        $this->actingAs($this->admin)->put(route('admin.leads-inbox.contact.update', $lead), ['website' => 'www.sigma-alimentos.com'])->assertRedirect();
        $this->assertSame('https://www.sigma-alimentos.com', $lead->fresh()->contact_website);
        $this->page($lead)->assertSee('Sitio web')->assertSee('href="https://www.sigma-alimentos.com"', false);

        $this->actingAs($this->admin)->put(route('admin.leads-inbox.contact.update', $lead), ['website' => 'no es un sitio'])->assertSessionHasErrors('website');
        $this->assertSame('https://www.sigma-alimentos.com', $lead->fresh()->contact_website);

        $this->actingAs($this->admin)->put(route('admin.leads-inbox.contact.update', $lead), ['website' => ''])->assertRedirect();
        $this->assertNull($lead->fresh()->contact_website);
    }

    public function test_agregar_como_cliente_guarda_el_sitio_web_y_lo_trae_del_lead(): void
    {
        $lead = $this->lead();
        $lead->update(['contact_website' => 'https://sigma-alimentos.com']);

        $this->actingAs($this->admin)->get(route('admin.leads-inbox.customer.form', $lead))->assertOk()->assertSee('Sitio web')->assertSee('https://sigma-alimentos.com');

        $this->actingAs($this->admin)->postJson(route('admin.leads-inbox.customer.store', $lead), [
            'full_name' => 'Carlos Herrera Ruiz', 'document_type' => 'ine', 'email' => 'nuevo@sigma.com', 'website' => 'sigma-alimentos.com',
        ])->assertOk();

        $this->assertSame('https://sigma-alimentos.com', Customer::where('email', 'nuevo@sigma.com')->firstOrFail()->website);
    }

    public function test_crea_el_negocio_aunque_el_embudo_de_negocios_este_marcado_como_inactivo(): void
    {
        Pipeline::query()->deals()->update(['is_active' => false]);
        $lead = $this->leadWithCustomer();

        $deal = app(\App\Services\LeadDealService::class)->createForLead($lead, [], $this->admin);

        $this->assertNotNull($deal->id);
        $this->assertSame($deal->id, $lead->fresh()->deal_id);
    }

    public function test_sigue_marcando_error_si_no_hay_ningun_embudo_de_negocios_con_etapas(): void
    {
        Pipeline::query()->deals()->delete();
        $lead = $this->leadWithCustomer();

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(\App\Services\LeadDealService::class)->createForLead($lead, [], $this->admin);
    }
}
