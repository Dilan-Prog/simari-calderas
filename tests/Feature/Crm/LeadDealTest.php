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

    private function lead(string $email = 'carlos@sigma.com'): Lead
    {
        $r = app(LeadService::class)->captureFromEmail(['message_id' => '<' . uniqid() . '@x>', 'email' => $email, 'name' => 'Carlos Herrera', 'subject' => 'Cotizar']);

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

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.quotes.link', [$lead, $deal]), ['quote_id' => $q1->id])->assertSessionHas('success');
        $this->assertSame('5049.00', $deal->fresh()->amount);

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.quotes.link', [$lead, $deal]), ['quote_number' => 'cot-2026-0002'])->assertSessionHas('success');
        $deal->refresh();
        $this->assertSame('8200.50', $deal->amount);
        $this->assertSame('USD', $deal->currency);
        $this->assertSame(2, $deal->quotes()->count());

        $this->page($lead->fresh())->assertSee('COT-2026-0001')->assertSee('COT-2026-0002')->assertSee('Cotizaciones (2)');

        $this->actingAs($this->admin)->delete(route('admin.leads-inbox.deal.quotes.unlink', [$lead, $deal, $q2]))->assertSessionHas('success');
        $deal->refresh();
        $this->assertSame(1, $deal->quotes()->count());
        $this->assertSame('5049.00', $deal->amount, 'el monto vuelve a la cotizacion que queda ligada');
    }

    public function test_no_acepta_cotizaciones_de_otro_cliente_ni_inexistentes(): void
    {
        $lead = $this->leadWithCustomer();
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.store', $lead), []);
        $deal = Deal::firstOrFail();
        $other = $this->customer(['email' => 'otro@x.com', 'first_name' => 'Otro']);
        $foreign = $this->quote($other, 'COT-2026-0099', 100);

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.quotes.link', [$lead, $deal]), ['quote_id' => $foreign->id])->assertSessionHas('error');
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.quotes.link', [$lead, $deal]), ['quote_number' => 'COT-NO-EXISTE'])->assertSessionHas('error');

        $this->assertSame(0, Deal::firstOrFail()->quotes()->count());
    }

    public function test_un_negocio_de_otro_lead_no_se_puede_tocar(): void
    {
        $lead = $this->leadWithCustomer();
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.store', $lead), []);
        $mine = Deal::firstOrFail();
        $q = $this->quote($lead->customer, 'COT-2026-0001', 10);
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.quotes.link', [$lead, $mine]), ['quote_id' => $q->id]);

        $other = $this->lead('otro-lead@sigma.com');
        $other->update(['customer_id' => $lead->customer_id]);

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.quotes.link', [$other, $mine]), ['quote_id' => $q->id])->assertSessionHas('error');
        $this->actingAs($this->admin)->delete(route('admin.leads-inbox.deal.quotes.unlink', [$other, $mine, $q]))->assertSessionHas('error');
        $this->assertSame(1, $mine->quotes()->count(), 'el negocio ajeno no se modifica');
    }

    public function test_se_puede_crear_un_segundo_negocio_sin_cerrar_el_primero(): void
    {
        $lead = $this->leadWithCustomer();
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.store', $lead), [])->assertSessionHas('success');
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.store', $lead), [])->assertSessionHas('success');
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.store', $lead), ['name' => 'Otro']);

        $this->assertSame(3, Deal::count());
        $this->assertSame(3, $lead->deals()->count());
        $names = $lead->deals()->pluck('name')->all();
        $this->assertContains('Negocio - Sigma Alimentos', $names);
        $this->assertContains('Negocio - Sigma Alimentos (2)', $names);
        $this->assertContains('Otro', $names);
        $this->assertSame((int) Deal::max('id'), $lead->fresh()->deal_id, 'leads.deal_id apunta al mas reciente');
        $this->assertSame('open', Deal::orderBy('id')->first()->status);
    }

    public function test_las_cotizaciones_son_independientes_por_negocio(): void
    {
        $lead = $this->leadWithCustomer();
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.store', $lead), []);
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.store', $lead), []);
        [$d1, $d2] = [Deal::orderBy('id')->first(), Deal::orderByDesc('id')->first()];
        $q1 = $this->quote($lead->customer, 'COT-2026-0001', 100);
        $q2 = $this->quote($lead->customer, 'COT-2026-0002', 200, 'USD');

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.quotes.link', [$lead, $d1]), ['quote_id' => $q1->id]);
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.quotes.link', [$lead, $d2]), ['quote_id' => $q2->id]);

        $this->assertSame([$q1->id], $d1->quotes()->pluck('quotes.id')->all());
        $this->assertSame([$q2->id], $d2->quotes()->pluck('quotes.id')->all());
        $this->assertSame('100.00', $d1->fresh()->amount);
        $this->assertSame('200.00', $d2->fresh()->amount);
        $this->assertSame('USD', $d2->fresh()->currency);

        $this->actingAs($this->admin)->delete(route('admin.leads-inbox.deal.quotes.unlink', [$lead, $d1, $q1]));
        $this->assertSame(0, $d1->quotes()->count());
        $this->assertSame(1, $d2->quotes()->count());
    }

    public function test_la_migracion_llena_deals_lead_id_desde_leads_deal_id(): void
    {
        $lead = $this->leadWithCustomer();
        $pipelineId = Pipeline::query()->deals()->value('id');
        $stageId = PipelineStage::where('pipeline_id', $pipelineId)->value('id');
        $deal = Deal::create(['pipeline_id' => $pipelineId, 'pipeline_stage_id' => $stageId, 'name' => 'Viejo', 'status' => 'open']);
        $orphan = Deal::create(['pipeline_id' => $pipelineId, 'pipeline_stage_id' => $stageId, 'name' => 'Sin lead', 'status' => 'open']);
        $lead->update(['deal_id' => $deal->id]);
        \DB::table('deals')->update(['lead_id' => null]);

        $migration = require base_path('database/migrations/2026_10_29_100000_add_lead_id_to_deals.php');
        $migration->backfill(); // sin DDL: en MySQL un ALTER confirma la transaccion del test

        $this->assertSame($lead->id, (int) \DB::table('deals')->where('id', $deal->id)->value('lead_id'));
        $this->assertNull(\DB::table('deals')->where('id', $orphan->id)->value('lead_id'));

        // Un negocio legacy (solo leads.deal_id, sin lead_id) sigue siendo accesible desde el lead.
        \DB::table('deals')->where('id', $deal->id)->update(['lead_id' => null]);
        $q = $this->quote($lead->customer, 'COT-2026-0003', 5);
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.quotes.link', [$lead, $deal->fresh()]), ['quote_id' => $q->id])->assertSessionHas('success');
    }

    public function test_la_bitacora_con_dos_negocios_registra_ambos_y_sus_cotizaciones(): void
    {
        $lead = $this->leadWithCustomer();
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.store', $lead), []);
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.store', $lead), []);
        [$d1, $d2] = [Deal::orderBy('id')->first(), Deal::orderByDesc('id')->first()];
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.quotes.link', [$lead, $d1]), ['quote_id' => $this->quote($lead->customer, 'COT-2026-0010', 1)->id]);
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.quotes.link', [$lead, $d2]), ['quote_id' => $this->quote($lead->customer, 'COT-2026-0011', 2)->id]);

        $titles = collect(\App\Support\LeadLog::for($lead->fresh()))->pluck('title');
        $this->assertTrue($titles->contains('Negocio creado: ' . $d1->folio));
        $this->assertTrue($titles->contains('Negocio creado: ' . $d2->folio));
        $this->assertTrue($titles->contains('Cotizacion COT-2026-0010 ligada al negocio ' . $d1->folio));
        $this->assertTrue($titles->contains('Cotizacion COT-2026-0011 ligada al negocio ' . $d2->folio));
    }

    public function test_la_bitacora_registra_el_negocio_y_las_cotizaciones_ligadas(): void
    {
        $lead = $this->leadWithCustomer();
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.store', $lead), []);
        $deal = Deal::firstOrFail();
        $q = $this->quote($lead->customer, 'COT-2026-0007', 5049.00);
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.deal.quotes.link', [$lead, $deal]), ['quote_id' => $q->id]);

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
