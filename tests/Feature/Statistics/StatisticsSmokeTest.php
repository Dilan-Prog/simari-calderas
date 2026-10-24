<?php

namespace Tests\Feature\Statistics;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsSmokeTest extends TestCase
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

    public function test_todas_las_secciones_cargan_con_y_sin_comparacion(): void
    {
        foreach (['', '/ventas', '/embudo', '/compras', '/servicios', '/reportes', '/inventario', '/tienda', '/marketing'] as $s) {
            foreach (['', '?period=hoy', '?period=semana&compare=1', '?period=trimestre&compare=1', '?period=anio&compare=1', '?date_from=2026-01-01&date_to=2026-01-31&compare=1'] as $q) {
                $this->actingAs($this->admin)->get('/admin/estadisticas' . $s . $q)->assertOk();
            }
        }
    }
}
