<?php

namespace Tests\Feature\Whatsapp;

use App\Models\Lead;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Role;
use App\Models\User;
use App\Models\WhatsappAccount;
use App\Models\WhatsappConversation;
use App\Services\LeadService;
use App\Services\WhatsappService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Embudo de Venta: además de las conversaciones de WhatsApp muestra los demás contactos
 * (correo, formulario, llamada) y el responsable de cada tarjeta.
 */
class WhatsappFunnelLeadsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $agent;
    private Pipeline $pipeline;
    /** @var array<string,PipelineStage> */
    private array $stage = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $role = Role::firstOrCreate(['name_role' => 'Administrador'], ['name_role_es' => 'Administrador']);
        $mk = fn (string $first) => User::create([
            'first_name' => $first, 'last_name' => 'Test', 'position' => 'A', 'phone' => '5555555555',
            'email' => strtolower($first) . '-' . uniqid() . '@example.com', 'password' => bcrypt('x'), 'status' => 'active',
            'rfc' => 'RFC' . strtoupper(substr(uniqid(), -8)) . random_int(10, 99), 'role_id' => $role->id,
        ]);
        $this->admin = $mk('Admin');
        $this->agent = $mk('Vendedor');

        $this->pipeline = Pipeline::create(['name' => 'Ventas x Whatsapp', 'channel' => 'whatsapp', 'is_default' => true, 'is_active' => true]);
        foreach ([
            'prospectos' => ['Prospectos', 0, false, false], 'calificado' => ['Cliente Calificado', 1, false, false],
            'ganada' => ['Venta Cerrada', 2, true, false], 'perdida' => ['Venta Perdida', 3, false, true],
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

    private function emailLead(string $email = 'carlos@sigma.com', string $name = 'Carlos Sigma'): Lead
    {
        $r = app(LeadService::class)->captureFromEmail([
            'message_id' => '<' . uniqid() . '@x>', 'email' => $email, 'name' => $name, 'subject' => 'Necesito cotizar una caldera', 'message' => 'Buen día, me interesa',
        ]);

        return Lead::findOrFail($r['lead_id']);
    }

    private function board(array $query = []): string
    {
        return $this->actingAs($this->admin)->get(route('admin.whatsapp-funnel.index', $query))->assertOk()->getContent();
    }

    public function test_los_leads_de_correo_aparecen_como_tarjetas_con_canal_y_vista_previa(): void
    {
        $lead = $this->emailLead();

        $html = $this->board();

        $this->assertStringContainsString('data-lead-id="' . $lead->id . '"', $html);
        $this->assertStringContainsString('Carlos Sigma', $html);
        $this->assertStringContainsString('data-ch="correo"', $html);
        $this->assertStringContainsString('Necesito cotizar una caldera', $html);
        $this->assertStringContainsString(route('admin.leads-inbox.index', ['lead' => $lead->id]), $html);
    }

    public function test_la_tarjeta_cae_en_la_etapa_del_lead_y_las_conversaciones_siguen_apareciendo(): void
    {
        $wa = $this->whatsappLead();
        $mail = $this->emailLead();
        app(\App\Support\LeadStages::class);
        \App\Support\LeadStages::move($mail, $this->stage['calificado']);

        $html = $this->board();

        $this->assertStringContainsString('data-conv-id="' . $wa->whatsapp_conversation_id . '"', $html);
        // la tarjeta del correo está dentro de la columna "Cliente Calificado"
        $col = $this->stage['calificado']->id;
        $this->assertMatchesRegularExpression('/data-stage-id="' . $col . '"[^>]*>.*?data-lead-id="' . $mail->id . '"/s', $html);
        // el lead de WhatsApp no se duplica como tarjeta de lead
        $this->assertStringNotContainsString('data-lead-id="' . $wa->id . '"', $html);
    }

    public function test_muestra_al_responsable_en_tarjetas_de_lead_y_de_conversacion(): void
    {
        $wa = $this->whatsappLead();
        $mail = $this->emailLead();
        $svc = app(LeadService::class);
        $svc->assign($wa, $this->agent->id);
        $svc->assign($mail, $this->agent->id);

        $html = $this->board();

        $this->assertSame(2, preg_match_all('/data-wf-agent>\s*' . preg_quote($this->agent->full_name, '/') . '\s*</', $html));
        $this->assertStringNotContainsString('Sin asignar</span>', $html);
        $this->assertSame($this->agent->id, WhatsappConversation::find($wa->whatsapp_conversation_id)->assigned_user_id);
    }

    public function test_responsable_asignado_en_la_bandeja_antes_de_esta_mejora_tambien_se_ve(): void
    {
        $wa = $this->whatsappLead();
        // dato histórico: el lead tiene responsable pero la conversación no
        $wa->forceFill(['assigned_to' => $this->agent->id])->save();
        $this->assertNull(WhatsappConversation::find($wa->whatsapp_conversation_id)->assigned_user_id);

        $this->assertStringContainsString($this->agent->full_name, $this->board());
    }

    public function test_reasignar_desde_el_embudo_actualiza_el_lead(): void
    {
        $wa = $this->whatsappLead();

        $this->actingAs($this->admin)->postJson(route('admin.whatsapp-funnel.reassign-agent', $wa->whatsapp_conversation_id), ['assigned_user_id' => $this->agent->id])->assertOk();

        $this->assertSame($this->agent->id, $wa->fresh()->assigned_to);
    }

    public function test_arrastrar_una_tarjeta_de_lead_cambia_su_etapa_y_su_estado(): void
    {
        $mail = $this->emailLead();

        $this->actingAs($this->admin)->postJson(route('admin.whatsapp-funnel.move-lead', $mail), ['to_stage_id' => $this->stage['calificado']->id])
            ->assertOk()->assertJsonPath('success', true);

        $this->assertSame($this->stage['calificado']->id, $mail->fresh()->pipeline_stage_id);
        $this->assertSame('contactado', $mail->fresh()->status); // 2.ª etapa abierta = contactado
    }

    public function test_no_se_puede_mover_un_lead_a_una_etapa_de_un_embudo_de_negocios(): void
    {
        $mail = $this->emailLead();
        $deals = Pipeline::create(['name' => 'Negocios', 'channel' => 'deals', 'is_active' => true]);
        $other = PipelineStage::create(['pipeline_id' => $deals->id, 'name' => 'X', 'slug' => 'x', 'order' => 0]);

        $this->actingAs($this->admin)->postJson(route('admin.whatsapp-funnel.move-lead', $mail), ['to_stage_id' => $other->id])->assertStatus(422);

        $this->assertNull($mail->fresh()->pipeline_stage_id);
    }

    public function test_filtros_de_busqueda_y_responsable_aplican_a_los_leads(): void
    {
        $a = $this->emailLead('ana@uno.com', 'Ana Uno');
        $b = $this->emailLead('beto@dos.com', 'Beto Dos');
        app(LeadService::class)->assign($b, $this->agent->id);

        $html = $this->board(['search' => 'Ana']);
        $this->assertStringContainsString('data-lead-id="' . $a->id . '"', $html);
        $this->assertStringNotContainsString('data-lead-id="' . $b->id . '"', $html);

        $html = $this->board(['agent_id' => $this->agent->id]);
        $this->assertStringContainsString('data-lead-id="' . $b->id . '"', $html);
        $this->assertStringNotContainsString('data-lead-id="' . $a->id . '"', $html);

        // "Sin leer" es de chats: no muestra leads sin conversación
        $this->assertStringNotContainsString('data-lead-id=', $this->board(['unread' => 1]));
    }
}
