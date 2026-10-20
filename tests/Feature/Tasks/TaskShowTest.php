<?php

namespace Tests\Feature\Tasks;

use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Services\LeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskShowTest extends TestCase
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

    public function test_detalle_de_tarea_de_correo_muestra_origen_correo_e_historial(): void
    {
        $result = app(LeadService::class)->captureFromEmail([
            'message_id' => '<a@b>', 'email' => 'cliente@empresa.com', 'name' => 'Carlos Herrera',
            'subject' => 'Cotizar suavizador', 'message' => 'Necesito un suavizador TWIN 360',
        ]);

        $this->actingAs($this->admin)->get(route('admin.tasks.show', $result['task_id']))
            ->assertOk()
            ->assertSee('Correo N8N')
            ->assertSee('Historial')
            ->assertSee('Necesito un suavizador TWIN 360')
            ->assertSee('Carlos Herrera');
    }

    public function test_completar_cierra_la_tarea_y_vuelve_al_detalle(): void
    {
        $task = Task::create(['title' => 'Llamar al cliente', 'status' => 'open']);

        $this->actingAs($this->admin)->put(route('admin.tasks.update', $task), ['status' => 'closed'])
            ->assertRedirect(route('admin.tasks.show', $task));

        $task->refresh();
        $this->assertSame('closed', $task->status);
        $this->assertNotNull($task->closed_at);
    }

    public function test_detalle_de_tarea_eliminada_se_puede_ver(): void
    {
        $task = Task::create(['title' => 'Tarea borrada', 'status' => 'open']);
        $task->update(['deleted_by' => $this->admin->id]);
        $task->delete();

        $this->actingAs($this->admin)->get(route('admin.tasks.show', $task->id))
            ->assertOk()
            ->assertSee('Tarea borrada')
            ->assertSee('Eliminada');
    }

    public function test_el_listado_enlaza_al_detalle(): void
    {
        $task = Task::create(['title' => 'Tarea enlazada', 'status' => 'open']);

        $this->actingAs($this->admin)->get(route('admin.tasks.index'))
            ->assertOk()
            ->assertSee(route('admin.tasks.show', $task), false);
    }
}
