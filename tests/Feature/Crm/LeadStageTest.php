<?php

namespace Tests\Feature\Crm;

use App\Models\Lead;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Role;
use App\Models\User;
use App\Models\WhatsappAccount;
use App\Models\WhatsappConversation;
use App\Services\LeadService;
use App\Services\WhatsappService;
use App\Support\LeadChannels;
use App\Support\LeadStages;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadStageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Pipeline $pipeline;
    /** @var array<string,PipelineStage> */
    private array $stage = [];

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

        // Embudo de Venta (WhatsApp) como el de produccion.
        $this->pipeline = Pipeline::create(['name' => 'Ventas x Whatsapp', 'channel' => 'whatsapp', 'is_default' => true, 'is_active' => true]);
        foreach ([
            'prospectos' => ['Prospectos', 0, false, false], 'no' => ['No Calificado', 1, false, false], 'calificado' => ['Cliente Calificado', 2, false, false],
            'cotizado' => ['Cliente Cotizado', 3, false, false], 'ganada' => ['Venta Cerrada', 4, true, false], 'perdida' => ['Venta Perdida', 5, false, true],
        ] as $key => [$name, $order, $won, $lost]) {
            $this->stage[$key] = PipelineStage::create(['pipeline_id' => $this->pipeline->id, 'name' => $name, 'slug' => $key, 'order' => $order, 'is_won' => $won, 'is_lost' => $lost]);
        }
    }

    private function whatsappLead(string $phone = '5215562230266'): Lead
    {
        $account = WhatsappAccount::firstOrCreate(['phone_number' => '+1 555 000 0000'], ['name' => 'Meta', 'connection_type' => 'cloud_api', 'is_active' => true]);
        app(WhatsappService::class)->ingestInboundMessage($account, $phone, ['content' => 'Hola']);

        return Lead::where('source', 'whatsapp')->latest('id')->firstOrFail();
    }

    private function emailLead(): Lead
    {
        $r = app(LeadService::class)->captureFromEmail(['message_id' => '<' . uniqid() . '@x>', 'email' => 'carlos@sigma.com', 'name' => 'Carlos', 'subject' => 'Cotizar']);

        return Lead::findOrFail($r['lead_id']);
    }

    public function test_una_conversacion_nueva_entra_a_prospectos_y_aparece_en_el_embudo(): void
    {
        $lead = $this->whatsappLead();
        $conversation = WhatsappConversation::findOrFail($lead->whatsapp_conversation_id);

        $this->assertSame($this->stage['prospectos']->id, $conversation->pipeline_stage_id);
        $this->assertSame($this->pipeline->id, $conversation->pipeline_id);
        $this->assertSame('Prospectos', LeadStages::current($lead)->name);
    }

    public function test_la_etapa_de_un_lead_sin_etapa_guardada_sale_de_su_estado(): void
    {
        $lead = $this->emailLead();
        $this->assertSame('Prospectos', LeadStages::current($lead)->name);

        $lead->update(['status' => 'calificado']);
        $this->assertSame('Cliente Calificado', LeadStages::current($lead->fresh())->name);

        $lead->update(['status' => 'convertido']);
        $this->assertSame('Venta Cerrada', LeadStages::current($lead->fresh())->name);

        $lead->update(['status' => 'descartado']);
        $this->assertSame('Venta Perdida', LeadStages::current($lead->fresh())->name);
    }

    public function test_mover_el_lead_mueve_su_conversacion_y_ajusta_su_estado(): void
    {
        $lead = $this->whatsappLead();

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.stage.update', $lead), ['pipeline_stage_id' => $this->stage['calificado']->id])
            ->assertRedirect(route('admin.leads-inbox.index', ['lead' => $lead->id]));

        $lead->refresh();
        $this->assertSame($this->stage['calificado']->id, $lead->pipeline_stage_id);
        $this->assertSame('calificado', $lead->status);
        $this->assertSame($this->stage['calificado']->id, WhatsappConversation::find($lead->whatsapp_conversation_id)->pipeline_stage_id);

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.stage.update', $lead), ['pipeline_stage_id' => $this->stage['ganada']->id]);
        $this->assertSame('convertido', $lead->fresh()->status);

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.stage.update', $lead), ['pipeline_stage_id' => $this->stage['perdida']->id]);
        $this->assertSame('descartado', $lead->fresh()->status);

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.stage.update', $lead), ['pipeline_stage_id' => $this->stage['no']->id]);
        $this->assertSame('contactado', $lead->fresh()->status);
    }

    public function test_mover_la_tarjeta_en_el_embudo_mueve_al_lead(): void
    {
        $lead = $this->whatsappLead();
        $conversation = WhatsappConversation::findOrFail($lead->whatsapp_conversation_id);

        $this->actingAs($this->admin)->postJson(route('admin.whatsapp-funnel.move-stage', $conversation), ['to_stage_id' => $this->stage['cotizado']->id])->assertOk();

        $lead->refresh();
        $this->assertSame($this->stage['cotizado']->id, $lead->pipeline_stage_id);
        $this->assertSame('calificado', $lead->status);
        $this->assertSame('Cliente Cotizado', LeadStages::current($lead)->name);
    }

    public function test_un_lead_de_correo_tambien_tiene_etapa_aunque_no_este_en_el_embudo_de_whatsapp(): void
    {
        $lead = $this->emailLead();

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.stage.update', $lead), ['pipeline_stage_id' => $this->stage['calificado']->id])->assertRedirect();

        $this->assertSame('Cliente Calificado', LeadStages::current($lead->fresh())->name);
        $this->assertNull($lead->fresh()->whatsapp_conversation_id);
    }

    public function test_no_acepta_etapas_de_otro_embudo(): void
    {
        $deals = Pipeline::create(['name' => 'Negocios', 'channel' => 'deals', 'is_default' => true, 'is_active' => true]);
        $other = PipelineStage::create(['pipeline_id' => $deals->id, 'name' => 'Propuesta', 'slug' => 'propuesta', 'order' => 0]);
        $lead = $this->emailLead();

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.stage.update', $lead), ['pipeline_stage_id' => $other->id])->assertSessionHas('error');
        $this->assertNull($lead->fresh()->pipeline_stage_id);
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.stage.update', $lead), [])->assertSessionHasErrors('pipeline_stage_id');
    }

    public function test_la_bandeja_muestra_las_etapas_del_embudo_y_el_enlace_al_embudo_de_venta(): void
    {
        $lead = $this->whatsappLead();

        $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['lead' => $lead->id]))
            ->assertOk()
            ->assertSee('Prospectos')->assertSee('No Calificado')->assertSee('Cliente Calificado')->assertSee('Cliente Cotizado')->assertSee('Venta Cerrada')->assertSee('Venta Perdida')
            ->assertSee('Etapa (Embudo de Venta)')
            ->assertSee(route('admin.whatsapp-funnel.index'), false)
            ->assertDontSee('>Contactado<', false);
    }

    public function test_medios_de_contacto_whatsapp_telefono_y_correo(): void
    {
        $wa = $this->whatsappLead();
        $c = LeadChannels::for($wa);
        $this->assertTrue($c['whatsapp']);
        $this->assertTrue($c['phone']);
        $this->assertFalse($c['email']);
        $this->assertSame(2, $c['count']);
        $this->assertSame('Tiene WhatsApp y teléfono', LeadChannels::label($c));

        $mail = $this->emailLead();
        $c = LeadChannels::for($mail);
        $this->assertSame(['whatsapp' => false, 'phone' => false, 'email' => true, 'count' => 1], $c);
        $this->assertSame('Solo tiene correo', LeadChannels::label($c));

        $wa->update(['contact_email' => 'ana@empresa.com']);
        $all = LeadChannels::for($wa->fresh());
        $this->assertSame(3, $all['count']);
        $this->assertSame('Tiene WhatsApp, teléfono y correo', LeadChannels::label($all));
    }

    public function test_la_lista_marca_3_de_3_solo_cuando_tiene_los_tres_medios(): void
    {
        $wa = $this->whatsappLead();
        $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['lead' => $wa->id]))->assertOk()->assertDontSee('3/3');

        $wa->update(['contact_email' => 'ana@empresa.com']);
        $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['lead' => $wa->id]))
            ->assertOk()->assertSee('3/3')->assertSee('Tiene WhatsApp, teléfono y correo');
    }
}
