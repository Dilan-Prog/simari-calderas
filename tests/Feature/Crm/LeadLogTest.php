<?php

namespace Tests\Feature\Crm;

use App\Models\AdEvent;
use App\Models\AdVisit;
use App\Models\Lead;
use App\Models\LeadCall;
use App\Models\LeadNote;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Role;
use App\Models\User;
use App\Models\WhatsappAccount;
use App\Services\LeadService;
use App\Services\WhatsappService;
use App\Support\LeadLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LeadLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    /** @var array<string,PipelineStage> */
    private array $stage = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $role = Role::firstOrCreate(['name_role' => 'Administrador'], ['name_role_es' => 'Administrador']);
        $this->admin = User::create([
            'first_name' => 'Ana', 'last_name' => 'Lopez', 'position' => 'A', 'phone' => '5555555555',
            'email' => 'u-' . uniqid() . '@example.com', 'password' => bcrypt('x'), 'status' => 'active',
            'rfc' => 'RFC' . strtoupper(substr(uniqid(), -8)) . random_int(10, 99), 'role_id' => $role->id,
        ]);
        $pipeline = Pipeline::create(['name' => 'Ventas x Whatsapp', 'channel' => 'whatsapp', 'is_default' => true, 'is_active' => true]);
        foreach (['prospectos' => ['Prospectos', 0], 'calificado' => ['Cliente Calificado', 1]] as $key => [$name, $order]) {
            $this->stage[$key] = PipelineStage::create(['pipeline_id' => $pipeline->id, 'name' => $name, 'slug' => $key, 'order' => $order, 'is_won' => false, 'is_lost' => false]);
        }
    }

    private function whatsappLead(): Lead
    {
        $account = WhatsappAccount::firstOrCreate(['phone_number' => '+1 555 000 0000'], ['name' => 'Meta', 'connection_type' => 'cloud_api', 'is_active' => true]);
        app(WhatsappService::class)->ingestInboundMessage($account, '5215562230266', ['content' => 'Hola, quiero una caldera']);

        return Lead::where('source', 'whatsapp')->latest('id')->firstOrFail();
    }

    private function emailLead(): Lead
    {
        $r = app(LeadService::class)->captureFromEmail(['message_id' => '<' . uniqid() . '@x>', 'email' => 'carlos@sigma.com', 'name' => 'Carlos', 'subject' => 'Cotizar caldera']);

        return Lead::findOrFail($r['lead_id']);
    }

    private function titles(Lead $lead): array
    {
        return array_column(LeadLog::for($lead), 'title');
    }

    public function test_origen_por_correo_es_lead_agregado_por_n8n(): void
    {
        $lead = $this->emailLead();
        $log = LeadLog::for($lead);

        $this->assertSame('origen', $log[0]['kind']);
        $this->assertSame('Lead agregado por N8N', $log[0]['title']);
        $this->assertStringContainsString('carlos@sigma.com', $log[0]['detail']);
        $this->assertStringContainsString('Cotizar caldera', $log[0]['detail']);
    }

    public function test_origen_whatsapp_y_mensajes(): void
    {
        $lead = $this->whatsappLead();
        $log = LeadLog::for($lead);

        $this->assertSame('Escribio por WhatsApp', $log[0]['title']);
        $this->assertContains('Mensaje recibido', $this->titles($lead));
    }

    public function test_el_orden_es_ascendente(): void
    {
        $lead = $this->emailLead();
        LeadNote::create(['lead_id' => $lead->id, 'user_id' => $this->admin->id, 'body' => 'Primera']);
        LeadCall::create(['lead_id' => $lead->id, 'direction' => 'saliente', 'status' => 'contestada', 'started_at' => now()->addHour()]);

        $times = array_map(fn ($x) => $x['at']->getTimestamp(), LeadLog::for($lead));
        $sorted = $times;
        sort($sorted);
        $this->assertSame($sorted, $times);
        $this->assertSame('origen', LeadLog::for($lead)[0]['kind']);
    }

    public function test_nota_y_llamada(): void
    {
        $lead = $this->emailLead();
        LeadNote::create(['lead_id' => $lead->id, 'user_id' => $this->admin->id, 'body' => 'Pidio precio de caldera']);
        LeadCall::create(['lead_id' => $lead->id, 'direction' => 'entrante', 'status' => 'perdida', 'started_at' => now()]);

        $log = collect(LeadLog::for($lead));
        $note = $log->firstWhere('kind', 'nota');
        $this->assertSame('Nota agregada', $note['title']);
        $this->assertSame('Pidio precio de caldera', $note['detail']);
        $this->assertSame('Ana Lopez', $note['by']);
        $this->assertSame('Llamada entrante · perdida', $log->firstWhere('kind', 'llamada')['title']);
    }

    public function test_cambios_de_asignacion_estado_y_etapa_registran_al_usuario(): void
    {
        $lead = $this->emailLead();
        $this->actingAs($this->admin);

        $this->post(route('admin.leads-inbox.assign', $lead), ['assigned_to' => $this->admin->id])->assertRedirect();
        $this->post(route('admin.leads-inbox.stage.update', $lead), ['pipeline_stage_id' => $this->stage['calificado']->id]);
        $lead->refresh()->update(['status' => 'contactado']);

        $log = collect(LeadLog::for($lead->fresh()));
        $asg = $log->firstWhere('kind', 'asignacion');
        $this->assertSame('Asignado a Ana Lopez', $asg['title']);
        $this->assertSame('Ana Lopez', $asg['by']);
        $this->assertTrue($log->contains(fn ($x) => $x['kind'] === 'etapa' && str_contains($x['title'], 'Cliente Calificado')));
        $this->assertTrue($log->contains(fn ($x) => $x['kind'] === 'estado' && str_contains($x['title'], '-> Contactado')));
    }

    public function test_clic_de_whatsapp_del_sitio(): void
    {
        $lead = $this->emailLead();
        $uuid = (string) \Illuminate\Support\Str::uuid();
        $visit = AdVisit::create([
            'visitor_uuid' => $uuid, 'first_utm_campaign' => 'calderas', 'first_landing_url' => 'https://x.test/',
            'first_seen_at' => now()->subHour(), 'last_seen_at' => now(), 'expires_at' => now()->addDays(30),
        ]);
        $lead->update(['visitor_uuid' => $uuid]);
        $event = AdEvent::create(['ad_visit_id' => $visit->id, 'event_type' => 'whatsapp_click', 'url' => 'https://x.test/p/1', 'occurred_at' => now()->subMinutes(30)]);

        $expected = 'Clic en WhatsApp';
        if (Schema::hasColumn('ad_events', 'element')) {
            DB::table('ad_events')->where('id', $event->id)->update(['element' => 'flotante']);
            $expected = 'Clic en el boton flotante de WhatsApp';
        }

        $log = collect(LeadLog::for($lead->fresh()));
        $this->assertTrue($log->contains(fn ($x) => $x['kind'] === 'sitio' && $x['title'] === 'Primera visita al sitio'));
        $click = $log->first(fn ($x) => $x['kind'] === 'sitio' && str_starts_with($x['title'], 'Clic'));
        $this->assertSame($expected, $click['title']);
        $this->assertStringContainsString('https://x.test/p/1', $click['detail']);
    }

    public function test_el_parcial_renderiza(): void
    {
        $lead = $this->whatsappLead();
        $html = view('admin.leads-inbox._log', ['lead' => $lead])->render();

        $this->assertStringContainsString('Bitacora (', $html);
        $this->assertStringContainsString('lw-log', $html);
        $this->assertStringContainsString('Escribio por WhatsApp', $html);
    }
}
