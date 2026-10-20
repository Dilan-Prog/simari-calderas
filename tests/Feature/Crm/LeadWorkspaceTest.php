<?php

namespace Tests\Feature\Crm;

use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Models\WhatsappAccount;
use App\Services\LeadService;
use App\Services\WhatsappService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $role = Role::firstOrCreate(['name_role' => 'Administrador'], ['name_role_es' => 'Administrador']);
        $this->admin = User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'Administrador',
            'phone' => '5555555555', 'email' => 'u-' . uniqid() . '@example.com',
            'password' => bcrypt('password'), 'status' => 'active', 'rfc' => 'RFC' . strtoupper(substr(uniqid(), -8)), 'role_id' => $role->id,
        ]);
    }

    private function lead(array $over = []): Lead
    {
        static $n = 0;
        $n++;

        return Lead::create(array_merge([
            'source' => 'manual', 'status' => 'nuevo', 'contact_name' => 'Lead ' . $n,
            'contact_phone' => '55000000' . str_pad((string) $n, 2, '0', STR_PAD_LEFT),
            'last_activity_at' => now()->subMinutes(100 - $n),
        ], $over));
    }

    private function data(array $query = [], string $key = null)
    {
        $res = $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', $query))->assertOk();

        return $key ? $res->viewData($key) : $res;
    }

    public function test_filtros_de_origen_chip_y_busqueda(): void
    {
        $a = $this->lead(['source' => 'whatsapp', 'contact_name' => 'Ana Torres']);
        $b = $this->lead(['source' => 'llamada', 'status' => 'contactado', 'assigned_to' => $this->admin->id]);
        $c = $this->lead(['source' => 'llamada', 'status' => 'convertido']);

        $ids = fn (array $q) => $this->data($q, 'leads')->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$a->id, $b->id], $ids([]));       // convertido fuera por defecto
        $this->assertSame([$b->id], $ids(['source' => 'llamada']));
        $this->assertSame([$a->id], $ids(['chip' => 'sin_atender']));
        $this->assertSame([$b->id], $ids(['chip' => 'mios']));
        $this->assertSame([$a->id], $ids(['chip' => 'sin_asignar']));
        $this->assertSame([$a->id], $ids(['q' => 'Torres']));
        $this->assertContains($c->id, $ids(['todos' => 1]));
    }

    public function test_lead_seleccionado_por_parametro_y_por_defecto(): void
    {
        $old = $this->lead(['last_activity_at' => now()->subDays(2)]);
        $new = $this->lead(['last_activity_at' => now()]);

        $this->assertSame($new->id, $this->data([], 'selected')->id);
        $this->assertSame($old->id, $this->data(['lead' => $old->id], 'selected')->id);
        $this->assertTrue($this->data([], 'selected')->relationLoaded('noteEntries'));
    }

    public function test_sin_leads_no_hay_seleccionado(): void
    {
        $res = $this->data();
        $this->assertNull($res->viewData('selected'));
        $this->assertSame([], $res->viewData('timeline'));
        $this->assertNull($res->viewData('nextTask'));
        $this->assertNull($res->viewData('kpis')['primera_respuesta_min']);
    }

    public function test_previews_por_tipo_de_evento(): void
    {
        // WhatsApp
        $account = WhatsappAccount::create(['name' => 'QR', 'phone_number' => '+52 55 9876 5432', 'connection_type' => 'baileys_qr', 'is_active' => true]);
        app(WhatsappService::class)->ingestInboundMessage($account, '5215512345678', ['content' => 'Hola, quiero una caldera']);
        $wa = Lead::where('source', 'whatsapp')->first();

        // Correo entrante
        app(LeadService::class)->captureFromEmail(['message_id' => '<x@y>', 'email' => 'c@d.com', 'name' => 'Carla', 'subject' => 'Cotizar boiler', 'message' => 'Necesito un boiler de 100 litros']);
        $mail = Lead::where('contact_email', 'c@d.com')->first();

        // Llamada
        $call = app(LeadService::class)->recordCall(['phone' => '5599887766', 'status' => 'perdida', 'notes' => 'No contesto']);
        $callLead = Lead::find($call->lead_id);

        // Nota
        $noteLead = $this->lead();
        LeadNote::create(['lead_id' => $noteLead->id, 'user_id' => $this->admin->id, 'body' => str_repeat('Texto largo ', 30)]);

        // Sin nada
        $empty = $this->lead();

        $leads = $this->data(['todos' => 1], 'leads')->keyBy('id');

        $this->assertStringContainsString('Hola, quiero una caldera', $leads[$wa->id]->preview);
        $this->assertStringContainsString('boiler', $leads[$mail->id]->preview);
        $this->assertStringContainsString('Llamada perdida', $leads[$callLead->id]->preview);
        $this->assertStringContainsString('No contesto', $leads[$callLead->id]->preview);
        $this->assertStringStartsWith('Texto largo', $leads[$noteLead->id]->preview);
        $this->assertLessThanOrEqual(110, mb_strlen($leads[$noteLead->id]->preview));
        $this->assertSame('', $leads[$empty->id]->preview);
        $this->assertNotNull($leads[$empty->id]->preview_at);
        $this->assertNotNull($leads[$wa->id]->preview_at);
    }

    public function test_canales_chips_y_kpis(): void
    {
        $this->lead(['source' => 'whatsapp']);
        $fast = $this->lead(['source' => 'whatsapp', 'status' => 'contactado', 'assigned_to' => $this->admin->id,
            'first_contact_at' => now()->subMinutes(30)]);
        $fast->forceFill(['created_at' => now()->subHour()])->save();
        $this->lead(['source' => 'correo']);
        $this->lead(['source' => 'formulario', 'status' => 'descartado']);
        $this->lead(['source' => 'llamada', 'status' => 'convertido']);

        $res = $this->data(['source' => 'correo']); // los conteos ignoran los filtros
        $this->assertSame(['todos' => 3, 'whatsapp' => 2, 'llamada' => 0, 'correo' => 1, 'formulario' => 0], $res->viewData('channels'));
        $this->assertSame(['todos' => 3, 'sin_atender' => 2, 'mios' => 1, 'sin_asignar' => 2], $res->viewData('chipCounts'));
        $this->assertSame(2, $res->viewData('kpis')['sin_atender']);
        $this->assertSame(30, $res->viewData('kpis')['primera_respuesta_min']);
    }

    public function test_embudo_tiene_cuatro_columnas_con_totales(): void
    {
        $this->lead(['status' => 'nuevo']);
        $this->lead(['status' => 'nuevo', 'source' => 'correo']);
        $this->lead(['status' => 'contactado']);
        $this->lead(['status' => 'convertido']);
        $this->lead(['status' => 'descartado']);

        $this->assertSame([], $this->data([], 'board'));

        $board = $this->data(['view' => 'embudo'], 'board');
        $this->assertSame(['nuevo', 'contactado', 'calificado', 'convertido'], array_column($board, 'status'));
        $this->assertSame(['Nuevo', 'Contactado', 'Calificado', 'Convertido'], array_column($board, 'label'));
        $this->assertSame([2, 1, 0, 1], array_column($board, 'total'));
        $this->assertCount(2, $board[0]['leads']);
        $this->assertTrue($board[0]['leads']->first()->preview !== null);

        $filtered = $this->data(['view' => 'embudo', 'source' => 'correo'], 'board');
        $this->assertSame([1, 0, 0, 0], array_column($filtered, 'total'));
        $this->assertSame('embudo', $this->data(['view' => 'embudo'], 'view'));
    }

    public function test_embudo_limita_30_leads_por_columna_pero_total_real(): void
    {
        for ($i = 0; $i < 32; $i++) {
            $this->lead(['status' => 'nuevo']);
        }
        $board = $this->data(['view' => 'embudo'], 'board');
        $this->assertSame(32, $board[0]['total']);
        $this->assertCount(30, $board[0]['leads']);
    }

    public function test_proxima_tarea_es_la_abierta_con_vencimiento_mas_cercano(): void
    {
        $lead = $this->lead();
        $lead->tasks()->create(['title' => 'Sin fecha', 'status' => 'open']);
        $lead->tasks()->create(['title' => 'Lejana', 'status' => 'open', 'due_at' => now()->addDays(5)]);
        $lead->tasks()->create(['title' => 'Cercana', 'status' => 'open', 'due_at' => now()->addDay()]);
        $lead->tasks()->create(['title' => 'Cerrada', 'status' => 'closed', 'due_at' => now()->subDay()]);

        $this->assertSame('Cercana', $this->data([], 'nextTask')->title);
    }

    public function test_agregar_nota_guarda_valida_y_aparece_en_timeline(): void
    {
        $lead = $this->lead(['last_activity_at' => now()->subDays(3)]);

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.notes.store', $lead), ['body' => ''])
            ->assertSessionHasErrors('body');
        $this->assertSame(0, LeadNote::count());

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.notes.store', $lead), ['body' => 'Pidio llamar el lunes'])
            ->assertRedirect(route('admin.leads-inbox.index', ['lead' => $lead->id]))
            ->assertSessionHas('success', 'Nota agregada.');

        $note = LeadNote::first();
        $this->assertSame($this->admin->id, $note->user_id);
        $this->assertTrue($lead->fresh()->last_activity_at->isToday());

        $timeline = $this->data(['lead' => $lead->id], 'timeline');
        $item = collect($timeline)->firstWhere('type', 'nota');
        $this->assertSame('Nota de Admin', $item['title']);
        $this->assertSame('Pidio llamar el lunes', $item['body']);

        // la pagina de detalle tambien usa el timeline enriquecido
        $this->actingAs($this->admin)->get(route('admin.leads-inbox.show', $lead))
            ->assertOk()->assertSee('Pidio llamar el lunes');
    }

    public function test_timeline_incluye_correos_entrantes(): void
    {
        app(LeadService::class)->captureFromEmail(['message_id' => '<m1@y>', 'email' => 'z@d.com', 'name' => 'Zoe', 'subject' => 'Mantenimiento', 'message' => 'Quiero mantenimiento']);
        $lead = Lead::where('contact_email', 'z@d.com')->first();

        $item = collect($this->data(['lead' => $lead->id], 'timeline'))->firstWhere('type', 'correo');
        $this->assertSame('Correo recibido: Mantenimiento', $item['title']);
        $this->assertStringContainsString('mantenimiento', strtolower($item['body']));
    }
}
