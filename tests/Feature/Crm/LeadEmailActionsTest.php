<?php

namespace Tests\Feature\Crm;

use App\Models\Customer;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\LeadInboundEmail;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Role;
use App\Models\User;
use App\Services\LeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadEmailActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $role = Role::firstOrCreate(['name_role' => 'Administrador'], ['name_role_es' => 'Administrador']);
        $this->admin = $this->user($role);

        $deals = Pipeline::create(['name' => 'Ventas General', 'channel' => 'deals', 'is_default' => true, 'is_active' => true]);
        PipelineStage::create(['pipeline_id' => $deals->id, 'name' => 'Prospecto', 'slug' => 'prospecto', 'order' => 0]);
    }

    private function user(Role $role): User
    {
        return User::create([
            'first_name' => 'Usuario', 'last_name' => 'Test', 'position' => 'A', 'phone' => '5555555555',
            'email' => 'u-' . uniqid() . '@example.com', 'password' => bcrypt('x'), 'status' => 'active',
            'rfc' => 'RFC' . strtoupper(substr(uniqid(), -8)) . random_int(10, 99), 'role_id' => $role->id,
        ]);
    }

    /** @return array{0: Lead, 1: LeadInboundEmail} */
    private function leadWithEmail(string $email = 'carlos@sigma.com', string $subject = 'RE: Cotizar caldera 300 CC'): array
    {
        $r = app(LeadService::class)->captureFromEmail([
            'message_id' => '<' . uniqid() . '@x>', 'email' => $email, 'name' => 'Carlos Herrera', 'subject' => $subject, 'message' => 'Necesito una caldera de 300 caballos.',
        ]);

        return [Lead::findOrFail($r['lead_id']), LeadInboundEmail::where('lead_id', $r['lead_id'])->firstOrFail()];
    }

    private function withCustomer(Lead $lead): Lead
    {
        $customer = Customer::create([
            'first_name' => 'Carlos', 'last_name' => 'Herrera', 'email' => 'cliente-' . uniqid() . '@sigma.com', 'company' => 'Sigma Alimentos', 'phone' => '4421234567',
            'document_type' => 'ine', 'source' => 'admin', 'status' => 'active',
        ]);
        $lead->update(['customer_id' => $customer->id]);

        return $lead->fresh();
    }

    public function test_crea_el_negocio_desde_el_correo_con_nombre_y_notas(): void
    {
        [$lead, $mail] = $this->leadWithEmail();
        $lead = $this->withCustomer($lead);

        $res = $this->actingAs($this->admin)->post(route('admin.leads-inbox.email.deal', $lead), ['email_id' => $mail->id]);

        $deal = Deal::firstOrFail();
        $res->assertRedirect(route('admin.deals.show', $deal->id))->assertSessionHas('success');
        $this->assertSame('Cotizar caldera 300 CC', $deal->name, 'el asunto sin Re: es el nombre');
        $this->assertSame($lead->customer_id, $deal->customer_id);
        $this->assertSame($lead->id, $deal->lead_id);
        $this->assertSame($deal->id, $lead->fresh()->deal_id);
        $this->assertStringContainsString('Asunto: RE: Cotizar caldera 300 CC', $deal->notes);
        $this->assertStringContainsString('carlos@sigma.com', $deal->notes);
        $this->assertStringContainsString('Necesito una caldera de 300 caballos.', $deal->notes);
        $this->assertSame('calificado', $lead->fresh()->status);
    }

    public function test_sin_email_id_usa_el_nombre_por_defecto(): void
    {
        [$lead] = $this->leadWithEmail();
        $lead = $this->withCustomer($lead);

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.email.deal', $lead))->assertSessionHas('success');

        $this->assertSame('Negocio - Sigma Alimentos', Deal::firstOrFail()->name);
    }

    public function test_si_el_lead_ya_tiene_negocio_redirige_a_el_sin_crear_otro(): void
    {
        [$lead, $mail] = $this->leadWithEmail();
        $lead = $this->withCustomer($lead);
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.email.deal', $lead), ['email_id' => $mail->id]);
        $deal = Deal::firstOrFail();

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.email.deal', $lead->fresh()), ['email_id' => $mail->id])
            ->assertRedirect(route('admin.deals.show', $deal->id));

        $this->assertSame(1, Deal::count());
    }

    public function test_sin_cliente_pide_agregarlo_primero(): void
    {
        [$lead, $mail] = $this->leadWithEmail();

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.email.deal', $lead), ['email_id' => $mail->id])
            ->assertRedirect(route('admin.leads-inbox.index', ['lead' => $lead->id]))
            ->assertSessionHas('error');
        $this->assertSame(0, Deal::count());
        $this->assertNull($lead->fresh()->deal_id);

        $this->actingAs($this->admin)->get(route('admin.leads-inbox.email.quote', ['lead' => $lead->id, 'email_id' => $mail->id]))
            ->assertRedirect(route('admin.leads-inbox.index', ['lead' => $lead->id]))
            ->assertSessionHas('error');
    }

    public function test_la_cotizacion_redirige_con_cliente_lead_y_correo(): void
    {
        [$lead, $mail] = $this->leadWithEmail();
        $lead = $this->withCustomer($lead);

        $this->actingAs($this->admin)->get(route('admin.leads-inbox.email.quote', ['lead' => $lead->id, 'email_id' => $mail->id]))
            ->assertRedirect(route('admin.quotes.create', ['customer_id' => $lead->customer_id, 'from_lead' => $lead->id, 'from_email' => $mail->id]));
    }

    public function test_el_formulario_de_cotizacion_precarga_las_notas_internas(): void
    {
        [$lead, $mail] = $this->leadWithEmail();
        $lead = $this->withCustomer($lead);

        $this->actingAs($this->admin)
            ->get(route('admin.quotes.create', ['customer_id' => $lead->customer_id, 'from_lead' => $lead->id, 'from_email' => $mail->id]))
            ->assertOk()
            ->assertSee('Solicitud por correo: RE: Cotizar caldera 300 CC (Carlos Herrera carlos@sigma.com', false);
    }

    public function test_las_notas_no_se_precargan_con_un_correo_de_otro_lead(): void
    {
        [$lead] = $this->leadWithEmail();
        $lead = $this->withCustomer($lead);
        [, $foreignMail] = $this->leadWithEmail('otro@acme.com', 'Secreto ajeno');

        $this->actingAs($this->admin)
            ->get(route('admin.quotes.create', ['customer_id' => $lead->customer_id, 'from_lead' => $lead->id, 'from_email' => $foreignMail->id]))
            ->assertOk()->assertDontSee('Secreto ajeno');
    }

    public function test_rechaza_el_correo_de_otro_lead(): void
    {
        [$lead] = $this->leadWithEmail();
        $lead = $this->withCustomer($lead);
        [, $foreignMail] = $this->leadWithEmail('otro@acme.com', 'Otro asunto');

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.email.deal', $lead), ['email_id' => $foreignMail->id])->assertSessionHas('error');
        $this->actingAs($this->admin)->get(route('admin.leads-inbox.email.quote', ['lead' => $lead->id, 'email_id' => $foreignMail->id]))->assertSessionHas('error');
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.email.deal', $lead), ['email_id' => 'abc'])->assertSessionHasErrors('email_id');

        $this->assertSame(0, Deal::count());
    }

    public function test_exige_el_permiso_de_edicion_de_la_bandeja(): void
    {
        [$lead, $mail] = $this->leadWithEmail();
        $lead = $this->withCustomer($lead);
        $viewer = $this->user(Role::firstOrCreate(['name_role' => 'Sin permisos'], ['name_role_es' => 'Sin permisos']));

        $this->actingAs($viewer)->post(route('admin.leads-inbox.email.deal', $lead), ['email_id' => $mail->id])
            ->assertRedirect(route('admin.dashboard'));
        $this->actingAs($viewer)->get(route('admin.leads-inbox.email.quote', ['lead' => $lead->id]))
            ->assertRedirect(route('admin.dashboard'));
        $this->assertSame(0, Deal::count());
    }

    public function test_la_bandeja_muestra_los_botones_en_los_correos_recibidos(): void
    {
        [$lead] = $this->leadWithEmail();

        $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['lead' => $lead->id]))
            ->assertOk()->assertSee('Negocio desde correo')->assertSee('Cotización desde correo')
            ->assertSee(route('admin.leads-inbox.email.deal', $lead), false);

        $lead = $this->withCustomer($lead);
        $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['lead' => $lead->id]))
            ->assertOk()->assertSee('Crear negocio')->assertSee('Crear cotización');
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.email.deal', $lead));
        $deal = Deal::firstOrFail();

        $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['lead' => $lead->id]))
            ->assertOk()->assertSee('Ver negocio')->assertSee(route('admin.deals.show', $deal->id), false);
    }
}
