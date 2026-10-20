<?php

namespace Tests\Feature\Tasks;

use App\Models\Role;
use App\Models\SystemLog;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskEditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = $this->makeUser('Admin');
    }

    private function makeUser(string $first): User
    {
        $role = Role::firstOrCreate(['name_role' => 'Administrador'], ['name_role_es' => 'Administrador']);

        return User::create([
            'first_name' => $first, 'last_name' => 'Test', 'position' => 'A', 'phone' => '5555555555',
            'email' => 'u-' . uniqid() . '@example.com', 'password' => bcrypt('x'), 'status' => 'active',
            'rfc' => 'RFC' . strtoupper(substr(uniqid(), -8)) . random_int(10, 99), 'role_id' => $role->id,
        ]);
    }

    private function task(array $over = []): Task
    {
        return Task::create($over + ['title' => 'Llamar al cliente', 'status' => 'open', 'type' => 'llamada', 'priority' => 'media',
            'description' => 'Texto inicial', 'related_reference' => 'COT-1', 'due_at' => now()->setTime(15, 30)->addDays(2)]);
    }

    private function titles(Task $task): array
    {
        return $task->events()->pluck('title')->all();
    }

    public function test_editar_todos_los_campos_los_guarda_y_registra_cada_cambio(): void
    {
        $task = $this->task();
        $other = $this->makeUser('Otra');

        $this->actingAs($this->admin)->put(route('admin.tasks.update', $task), [
            'title' => 'Llamar al director', 'related_reference' => 'COT-2', 'type' => 'visita', 'priority' => 'alta',
            'due_at' => now()->addDays(5)->format('Y-m-d'), 'status' => 'pending', 'assigned_to' => $other->id, 'description' => 'Texto nuevo',
        ])->assertRedirect(route('admin.tasks.show', $task));

        $task->refresh();
        $this->assertSame('Llamar al director', $task->title);
        $this->assertSame('visita', $task->type);
        $this->assertSame('alta', $task->priority);
        $this->assertSame('COT-2', $task->related_reference);
        $this->assertSame('Texto nuevo', $task->description);
        $this->assertSame($other->id, $task->assigned_to);

        $titles = implode(' | ', $this->titles($task));
        $this->assertStringContainsString('Título cambiado: «Llamar al cliente» → «Llamar al director»', $titles);
        $this->assertStringContainsString('Relacionado con cambiado: «COT-1» → «COT-2»', $titles);
        $this->assertStringContainsString('Tipo cambiado: Llamada → Visita', $titles);
        $this->assertStringContainsString('Prioridad cambiada: Media → Alta', $titles);
        $this->assertStringContainsString('Fecha límite cambiada', $titles);
        $this->assertStringContainsString('Descripción editada', $titles);
        $this->assertStringContainsString('Estatus actualizado a «Pendiente»', $titles);
        $this->assertStringContainsString('Responsable cambiado a', $titles);
        $this->assertSame($this->admin->id, $task->events()->first()->user_id);
    }

    public function test_guardar_sin_cambios_no_genera_eventos(): void
    {
        $task = $this->task();

        $this->actingAs($this->admin)->put(route('admin.tasks.update', $task), [
            'title' => $task->title, 'related_reference' => 'COT-1', 'type' => 'llamada', 'priority' => 'media',
            'due_at' => $task->due_at->format('Y-m-d'), 'status' => 'open', 'description' => 'Texto inicial',
        ])->assertRedirect();

        $this->assertSame([], $this->titles($task));
    }

    public function test_misma_fecha_conserva_la_hora_original(): void
    {
        $task = $this->task();
        $original = $task->due_at->copy();

        $this->actingAs($this->admin)->put(route('admin.tasks.update', $task), ['due_at' => $original->format('Y-m-d')]);

        $this->assertTrue($original->equalTo($task->fresh()->due_at));
    }

    public function test_acciones_parciales_siguen_funcionando_sin_titulo(): void
    {
        $task = $this->task();

        $this->actingAs($this->admin)->put(route('admin.tasks.update', $task), ['status' => 'closed'])->assertRedirect();

        $task->refresh();
        $this->assertSame('closed', $task->status);
        $this->assertNotNull($task->closed_at);
        $this->assertSame('Llamar al cliente', $task->title);
        $this->assertSame(['Estatus actualizado a «Cerrada»'], $this->titles($task));
    }

    public function test_titulo_vacio_se_rechaza(): void
    {
        $task = $this->task();

        $this->actingAs($this->admin)->put(route('admin.tasks.update', $task), ['title' => ''])->assertSessionHasErrors('title');
        $this->assertSame('Llamar al cliente', $task->fresh()->title);
    }

    public function test_la_edicion_queda_en_la_bitacora_del_sistema(): void
    {
        $task = $this->task();

        $this->actingAs($this->admin)->put(route('admin.tasks.update', $task), ['title' => 'Nuevo título', 'priority' => 'alta']);

        $log = SystemLog::where('entity_type', 'task')->where('entity_id', $task->id)->where('action', 'updated')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($this->admin->id, $log->performed_by_user_id);
        $this->assertSame('Nuevo título', $log->new_value['title']);
        $this->assertSame('Llamar al cliente', $log->old_value['title']);
    }

    public function test_agregar_nota_queda_en_el_historial(): void
    {
        $task = $this->task();

        $this->actingAs($this->admin)->post(route('admin.tasks.notes.store', $task), ['body' => 'Se habló con el cliente']);

        $this->assertContains('Nota agregada', $this->titles($task));
    }

    public function test_el_detalle_incluye_el_modal_de_edicion_con_los_datos(): void
    {
        $task = $this->task();

        $this->actingAs($this->admin)->get(route('admin.tasks.show', $task))
            ->assertOk()
            ->assertSee('Editar tarea')
            ->assertSee('Llamar al cliente')
            ->assertSee('data-task-modal-open="edit"', false);
    }
}
