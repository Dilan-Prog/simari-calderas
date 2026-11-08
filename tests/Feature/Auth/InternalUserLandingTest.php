<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Un usuario interno con un rol creado a mano entra al panel admin, no al perfil ni al inicio de la tienda. */
class InternalUserLandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function internal(string $roleName): User
    {
        $role = Role::create(['name_role' => $roleName, 'name_role_es' => $roleName]);

        return User::create([
            'first_name' => 'Int', 'last_name' => 'Erno', 'position' => 'Ventas', 'phone' => '5555555555',
            'email' => 'i-' . uniqid() . '@example.com', 'password' => bcrypt('password'),
            'status' => 'active', 'rfc' => 'XAXX010101000', 'role_id' => $role->id,
        ]);
    }

    /** @dataProvider roles */
    public function test_el_login_lleva_al_panel_admin_con_cualquier_rol(string $roleName): void
    {
        $user = $this->internal($roleName);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    /** @dataProvider roles */
    public function test_el_pie_de_la_tienda_enlaza_al_panel_y_no_al_perfil(string $roleName): void
    {
        $this->actingAs($this->internal($roleName))->get('/')
            ->assertOk()
            ->assertSee(route('admin.dashboard'), false)
            ->assertSee('Panel Admin')
            ->assertDontSee(route('profile.edit'), false);
    }

    public function test_un_usuario_interno_autenticado_que_abre_login_va_al_panel(): void
    {
        $this->actingAs($this->internal('Ventas'))->get('/login')->assertRedirect(route('admin.dashboard'));
    }

    public function test_el_panel_abre_para_un_rol_personalizado(): void
    {
        $this->actingAs($this->internal('Ventas'))->get(route('admin.dashboard'))->assertOk();
    }

    public static function roles(): array
    {
        return [['Ventas'], ['employe'], ['Administrador'], ['admin']];
    }
}
