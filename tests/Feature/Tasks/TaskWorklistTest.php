<?php

namespace Tests\Feature\Tasks;

use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskWorklistTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = $this->adminUser();
    }

    private function adminUser(string $first = 'Admin'): User
    {
        $role = Role::firstOrCreate(['name_role' => 'Administrador'], ['name_role_es' => 'Administrador']);

        return User::create([
            'first_name' => $first, 'last_name' => 'Test', 'position' => 'Administrador',
            'phone' => '5555555555', 'email' => 'u-' . uniqid() . '@example.com',
            'password' => bcrypt('password'), 'status' => 'active', 'rfc' => 'RFC' . strtoupper(substr(uniqid(), -8)) . random_int(10, 99), 'role_id' => $role->id,
        ]);
    }

    private function task(string $title, array $over = []): Task
    {
        return Task::create(array_merge([
            'title' => $title, 'status' => 'open',
        ], $over));
    }

    private function titles(array $params = []): array
    {
        $response = $this->actingAs($this->admin)->get(route('admin.tasks.index', $params));
        $response->assertOk();

        return collect($response->viewData('tasks')->items())->pluck('title')->sort()->values()->all();
    }

    public function test_vista_por_defecto_solo_muestra_abiertas(): void
    {
        $this->task('T-abierta');
        $this->task('T-pendiente', ['status' => 'pending']);
        $this->task('T-cerrada', ['status' => 'closed', 'closed_at' => now()]);

        $this->assertSame(['T-abierta'], $this->titles());

        $response = $this->actingAs($this->admin)->get(route('admin.tasks.index'));
        $this->assertSame('open', $response->viewData('view'));
    }

    public function test_vista_mine_solo_asignadas_al_usuario_y_no_cerradas(): void
    {
        $other = $this->adminUser('Otro');
        $this->task('M-mia', ['assigned_to' => $this->admin->id]);
        $this->task('M-mia-pendiente', ['assigned_to' => $this->admin->id, 'status' => 'pending']);
        $this->task('M-mia-cerrada', ['assigned_to' => $this->admin->id, 'status' => 'closed']);
        $this->task('M-ajena', ['assigned_to' => $other->id]);
        $this->task('M-sin-asignar');

        $this->assertSame(['M-mia', 'M-mia-pendiente'], $this->titles(['view' => 'mine']));
    }

    public function test_vista_overdue_excluye_cerradas_y_sin_fecha(): void
    {
        $this->task('V-vencida', ['due_at' => now()->subDay()]);
        $this->task('V-vencida-pendiente', ['due_at' => now()->subHours(2), 'status' => 'pending']);
        $this->task('V-futura', ['due_at' => now()->addDay()]);
        $this->task('V-sin-fecha');
        $this->task('V-vencida-cerrada', ['due_at' => now()->subDay(), 'status' => 'closed']);

        $this->assertSame(['V-vencida', 'V-vencida-pendiente'], $this->titles(['view' => 'overdue']));
    }

    public function test_vista_closed_solo_cerradas(): void
    {
        $this->task('C-abierta');
        $this->task('C-cerrada', ['status' => 'closed', 'closed_at' => now()]);

        $this->assertSame(['C-cerrada'], $this->titles(['view' => 'closed']));
    }

    public function test_vista_all_incluye_borradas_logicamente(): void
    {
        $this->task('A-abierta');
        $this->task('A-cerrada', ['status' => 'closed']);
        $this->task('A-borrada')->delete();

        $this->assertSame(['A-abierta', 'A-borrada', 'A-cerrada'], $this->titles(['view' => 'all']));
        // Las demas vistas no la muestran.
        $this->assertSame(['A-abierta'], $this->titles());
    }

    public function test_busqueda_por_titulo(): void
    {
        $this->task('Llamar a Proveedor Zeta');
        $this->task('Otra cosa');

        $this->assertSame(['Llamar a Proveedor Zeta'], $this->titles(['q' => 'Zeta']));
    }

    public function test_busqueda_por_descripcion(): void
    {
        $this->task('Tarea uno', ['description' => 'revisar el boiler marca Quasar']);
        $this->task('Tarea dos', ['description' => 'nada relevante']);

        $this->assertSame(['Tarea uno'], $this->titles(['q' => 'Quasar']));
    }

    public function test_filtro_assigned_to_numerico(): void
    {
        $other = $this->adminUser('Otro');
        $this->task('U-admin', ['assigned_to' => $this->admin->id]);
        $this->task('U-otro', ['assigned_to' => $other->id]);
        $this->task('U-nadie');

        $this->assertSame(['U-otro'], $this->titles(['assigned_to' => $other->id]));
    }

    public function test_filtro_assigned_to_sin_asignar(): void
    {
        $this->task('S-admin', ['assigned_to' => $this->admin->id]);
        $this->task('S-nadie');

        $this->assertSame(['S-nadie'], $this->titles(['assigned_to' => 'sin']));
    }

    public function test_counts_son_correctos(): void
    {
        $other = $this->adminUser('Otro');
        $this->task('N1', ['assigned_to' => $this->admin->id, 'due_at' => now()->subDay()]); // open, mine, overdue
        $this->task('N2', ['assigned_to' => $other->id]);                                     // open
        $this->task('N3', ['assigned_to' => $this->admin->id, 'status' => 'pending']);       // mine
        $this->task('N4', ['status' => 'closed', 'due_at' => now()->subDay()]);              // closed
        $this->task('N5')->delete();                                                          // solo en all

        $response = $this->actingAs($this->admin)->get(route('admin.tasks.index'));
        $counts = $response->viewData('counts');

        $this->assertSame(2, $counts['open']);    // N1, N2
        $this->assertSame(2, $counts['mine']);    // N1, N3
        $this->assertSame(1, $counts['overdue']); // N1
        $this->assertSame(1, $counts['closed']);  // N4
        $this->assertSame(5, $counts['all']);     // N1-N5 (incluye borrada)
    }

    public function test_compatibilidad_status_all_y_status_open(): void
    {
        $this->task('K-abierta');
        $this->task('K-cerrada', ['status' => 'closed']);
        $this->task('K-borrada')->delete();

        $this->assertSame(['K-abierta', 'K-borrada', 'K-cerrada'], $this->titles(['status' => 'all']));
        $this->assertSame(['K-abierta'], $this->titles(['status' => 'open']));

        $response = $this->actingAs($this->admin)->get(route('admin.tasks.index', ['status' => 'all']));
        $this->assertSame('all', $response->viewData('view'));
    }
}
