<?php

namespace Tests\Feature\Crm;

use App\Models\Lead;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Services\LeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadPanelTest extends TestCase
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

    private function lead(): Lead
    {
        $r = app(LeadService::class)->captureFromEmail(['message_id' => '<' . uniqid() . '@x>', 'email' => 'carlos@sigma.com', 'name' => 'Carlos Herrera', 'subject' => 'Cotizar']);

        return Lead::findOrFail($r['lead_id']);
    }

    private function payload(array $over = []): array
    {
        return $over + ['title' => 'Llamar para confirmar la cotizacion', 'type' => 'llamada', 'priority' => 'alta', 'due_at' => now()->addDay()->format('Y-m-d 10:30'),
            'assigned_to' => $this->admin->id, 'description' => 'Preguntar por el equipo'];
    }

    public function test_crea_una_tarea_ligada_al_lead(): void
    {
        $lead = $this->lead();
        Task::query()->delete();

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.task.store', $lead), $this->payload())
            ->assertRedirect(route('admin.leads-inbox.index', ['lead' => $lead->id]));

        $task = Task::where('title', 'Llamar para confirmar la cotizacion')->firstOrFail();
        $this->assertSame(Lead::class, $task->taskable_type);
        $this->assertSame($lead->id, $task->taskable_id);
        $this->assertSame('open', $task->status);
        $this->assertSame($this->admin->id, $task->assigned_to);
        $this->assertSame('llamada', $task->type);
        $this->assertSame('alta', $task->priority);
        $this->assertSame($this->admin->id, $task->events()->where('type', 'created')->first()->user_id);
    }

    public function test_valida_titulo_y_responsable(): void
    {
        $lead = $this->lead();

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.task.store', $lead), $this->payload(['title' => '']))->assertSessionHasErrors('title');
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.task.store', $lead), $this->payload(['assigned_to' => '']))->assertSessionHasErrors('assigned_to');
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.task.store', $lead), $this->payload(['type' => 'otra']))->assertSessionHasErrors('type');
    }

    public function test_la_tarea_creada_aparece_como_proxima_tarea_y_abre_su_detalle(): void
    {
        $lead = $this->lead();
        Task::query()->delete(); // la tarea automatica del correo vence antes y seria la "proxima"
        $this->actingAs($this->admin)->post(route('admin.leads-inbox.task.store', $lead), $this->payload(['title' => 'Enviar ficha tecnica']));
        $task = Task::where('title', 'Enviar ficha tecnica')->firstOrFail();

        $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['lead' => $lead->id]))
            ->assertOk()->assertSee('Enviar ficha tecnica')->assertSee(route('admin.tasks.show', $task), false);

        $this->actingAs($this->admin)->get(route('admin.tasks.show', $task))
            ->assertOk()->assertSee('Lead ' . $lead->display_name)->assertSee(route('admin.leads-inbox.show', $lead), false);
    }

    public function test_las_notas_estan_en_el_panel_derecho_y_tambien_como_pestana_del_compositor(): void
    {
        $lead = $this->lead();

        $response = $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['lead' => $lead->id]))->assertOk();
        $html = $response->getContent();

        $response->assertSee('Escribe una nota interna')->assertSee('Guardar nota')->assertSee('+ Crear tarea')
            ->assertSee('data-tab="nota"', false)->assertSee('data-pane="nota"', false)
            ->assertSee('data-sec="notas"', false)->assertSee('data-sec="bitacora"', false);

        // Orden del panel derecho: Notas, luego Proxima tarea.
        $this->assertLessThan(strpos($html, 'data-sec="tarea"'), strpos($html, 'Escribe una nota interna'));
    }

    public function test_el_modal_de_tarea_del_lead_viene_con_sus_datos(): void
    {
        $lead = $this->lead();

        $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['lead' => $lead->id]))
            ->assertOk()
            ->assertSee('Nueva tarea del lead')
            ->assertSee(route('admin.leads-inbox.task.store', $lead), false)
            ->assertSee('Lead ' . $lead->display_name);
    }

    public function test_guardar_una_nota_sigue_funcionando(): void
    {
        $lead = $this->lead();

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.notes.store', $lead), ['body' => 'Cliente pide visita'])
            ->assertRedirect(route('admin.leads-inbox.index', ['lead' => $lead->id]));

        $this->assertDatabaseHas('lead_notes', ['lead_id' => $lead->id, 'body' => 'Cliente pide visita']);
    }
}
