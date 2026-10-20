<?php

namespace Tests\Feature\Tasks;

use App\Models\Quote;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskEvent;
use App\Models\TaskNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskHistoryNotesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $role = Role::firstOrCreate(['name_role' => 'Administrador'], ['name_role_es' => 'Administrador']);
        $this->admin = $this->makeUser($role, 'Admin', 'Test');
        $this->other = $this->makeUser($role, 'Maria', 'Lopez');
    }

    private function makeUser(Role $role, string $first, string $last): User
    {
        return User::create([
            'first_name' => $first, 'last_name' => $last, 'position' => 'Administrador',
            'phone' => '5555555555', 'email' => 'u-' . uniqid() . '@example.com',
            'password' => bcrypt('password'), 'status' => 'active',
            'rfc' => 'RFC' . strtoupper(substr(uniqid(), -8)), 'role_id' => $role->id,
        ]);
    }

    public function test_alta_valida_con_todos_los_campos(): void
    {
        $this->actingAs($this->admin)->post(route('admin.tasks.store'), [
            'title' => 'Llamar a cliente', 'description' => 'Detalle', 'type' => 'llamada',
            'priority' => 'alta', 'due_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'related_reference' => 'REF-1', 'assigned_to' => $this->other->id,
        ])->assertRedirect(route('admin.tasks.index'))->assertSessionHasNoErrors();

        $task = Task::where('title', 'Llamar a cliente')->firstOrFail();
        $this->assertSame('open', $task->status);
        $this->assertSame('llamada', $task->type);
        $this->assertSame('alta', $task->priority);
        $this->assertSame('REF-1', $task->related_reference);
        $this->assertSame($this->other->id, (int) $task->assigned_to);
        $this->assertNotNull($task->due_at);
        $this->assertNull($task->taskable_type);
    }

    public function test_alta_sin_responsable_falla_validacion(): void
    {
        $this->actingAs($this->admin)->post(route('admin.tasks.store'), ['title' => 'Sin dueño'])
            ->assertSessionHasErrors('assigned_to');

        $this->assertSame(0, Task::count());
    }

    public function test_tipo_y_prioridad_invalidos_fallan(): void
    {
        $this->actingAs($this->admin)->post(route('admin.tasks.store'), [
            'title' => 'X', 'assigned_to' => $this->admin->id, 'type' => 'paloma', 'priority' => 'urgentisima',
        ])->assertSessionHasErrors(['type', 'priority']);

        $this->assertSame(0, Task::count());
    }

    public function test_referencia_con_folio_de_cotizacion_vincula_la_tarea(): void
    {
        $quote = Quote::create([
            'quote_number' => 'COT-' . uniqid(), 'status' => 'sent', 'created_by_user_id' => $this->admin->id,
            'guest_name' => 'C', 'guest_email' => 'c@example.com',
        ]);

        $this->actingAs($this->admin)->post(route('admin.tasks.store'), [
            'title' => 'Seguimiento cotizacion', 'assigned_to' => $this->admin->id,
            'related_reference' => $quote->quote_number,
        ])->assertSessionHasNoErrors();

        $task = Task::where('title', 'Seguimiento cotizacion')->firstOrFail();
        $this->assertSame(Quote::class, $task->taskable_type);
        $this->assertSame($quote->id, (int) $task->taskable_id);
    }

    public function test_alta_registra_evento_created_con_el_actor(): void
    {
        $this->actingAs($this->admin)->post(route('admin.tasks.store'), [
            'title' => 'Con evento', 'assigned_to' => $this->admin->id,
        ])->assertSessionHasNoErrors();

        $task = Task::where('title', 'Con evento')->firstOrFail();
        $event = TaskEvent::where('task_id', $task->id)->where('type', 'created')->first();
        $this->assertNotNull($event);
        $this->assertSame($this->admin->id, (int) $event->user_id);
    }

    public function test_update_registra_estatus_y_responsable_solo_cuando_cambian(): void
    {
        $task = Task::create(['title' => 'T', 'status' => 'open', 'assigned_to' => $this->admin->id]);

        $this->actingAs($this->admin)->put(route('admin.tasks.update', $task), [
            'status' => 'closed', 'assigned_to' => $this->other->id,
        ])->assertRedirect(route('admin.tasks.show', $task));

        $task->refresh();
        $this->assertNotNull($task->closed_at);

        $status = TaskEvent::where('task_id', $task->id)->where('type', 'status')->first();
        $this->assertNotNull($status);
        $this->assertSame('Estatus actualizado a «Cerrada»', $status->title);
        $this->assertSame($this->admin->id, (int) $status->user_id);

        $assigned = TaskEvent::where('task_id', $task->id)->where('type', 'assigned')->first();
        $this->assertNotNull($assigned);
        $this->assertSame('Responsable cambiado a ' . $this->other->full_name, $assigned->title);

        // Sin cambios: no se crean eventos nuevos.
        $before = TaskEvent::where('task_id', $task->id)->count();
        $this->actingAs($this->admin)->put(route('admin.tasks.update', $task), [
            'status' => 'closed', 'assigned_to' => $this->other->id,
        ])->assertRedirect(route('admin.tasks.show', $task));
        $this->assertSame($before, TaskEvent::where('task_id', $task->id)->count());
    }

    public function test_update_solo_estatus_no_registra_responsable(): void
    {
        $task = Task::create(['title' => 'T', 'status' => 'open', 'assigned_to' => $this->admin->id]);

        $this->actingAs($this->admin)->put(route('admin.tasks.update', $task), [
            'status' => 'in_progress', 'assigned_to' => $this->admin->id,
        ]);

        $this->assertSame(1, TaskEvent::where('task_id', $task->id)->where('type', 'status')->count());
        $this->assertSame(0, TaskEvent::where('task_id', $task->id)->where('type', 'assigned')->count());
    }

    public function test_destroy_registra_evento_deleted(): void
    {
        $task = Task::create(['title' => 'Borrar', 'status' => 'open']);

        $this->actingAs($this->admin)->delete(route('admin.tasks.destroy', $task))
            ->assertRedirect(route('admin.tasks.index'));

        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
        $event = TaskEvent::where('task_id', $task->id)->where('type', 'deleted')->first();
        $this->assertNotNull($event);
        $this->assertSame($this->admin->id, (int) $event->user_id);
    }

    public function test_nota_se_guarda_con_autor_y_redirige_a_notas(): void
    {
        $task = Task::create(['title' => 'Con nota', 'status' => 'open']);

        $this->actingAs($this->admin)->post(route('admin.tasks.notes.store', $task), ['body' => '  Hablé con el cliente  '])
            ->assertRedirect(route('admin.tasks.show', $task) . '#notas');

        $note = TaskNote::where('task_id', $task->id)->first();
        $this->assertNotNull($note);
        $this->assertSame('Hablé con el cliente', $note->body);
        $this->assertSame($this->admin->id, (int) $note->user_id);
    }

    public function test_nota_vacia_o_demasiado_larga_falla(): void
    {
        $task = Task::create(['title' => 'Con nota', 'status' => 'open']);

        $this->actingAs($this->admin)->post(route('admin.tasks.notes.store', $task), ['body' => ''])
            ->assertSessionHasErrors('body');
        $this->actingAs($this->admin)->post(route('admin.tasks.notes.store', $task), ['body' => str_repeat('a', 2001)])
            ->assertSessionHasErrors('body');

        $this->assertSame(0, TaskNote::where('task_id', $task->id)->count());
    }

    public function test_show_expone_timeline_con_estatus_y_notas(): void
    {
        $task = Task::create(['title' => 'Detalle', 'status' => 'open']);
        $this->actingAs($this->admin)->put(route('admin.tasks.update', $task), ['status' => 'closed']);
        $this->actingAs($this->admin)->post(route('admin.tasks.notes.store', $task), ['body' => 'Una nota']);

        $response = $this->actingAs($this->admin)->get(route('admin.tasks.show', $task))->assertOk();

        $titles = array_column($response->viewData('timeline'), 'title');
        $this->assertContains('Estatus actualizado a «Cerrada»', $titles);

        $notes = $response->viewData('notes');
        $this->assertCount(1, $notes);
        $this->assertSame('Una nota', $notes->first()->body);
    }
}
