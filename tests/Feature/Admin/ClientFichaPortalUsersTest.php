<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\CustomerPortalRole;
use App\Models\CustomerPortalUser;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Portal\PortalMemberAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Pestaña "Usuarios del portal" de la ficha del cliente (admin).
 * Correr con base privada: DB_DATABASE=portal_roles_test php artisan test --filter=ClientFichaPortalUsers
 */
class ClientFichaPortalUsersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function customer(string $company = 'ACME'): Customer
    {
        return Customer::create([
            'first_name' => 'Juan', 'last_name' => 'Pérez', 'email' => 'c-' . uniqid() . '@example.com',
            'phone' => '5555555555', 'password_hash' => bcrypt('secret'), 'document_type' => 'ine',
            'source' => 'admin', 'status' => 'active', 'company' => $company, 'portal_access' => true,
        ]);
    }

    private function member(Customer $customer, array $over = []): CustomerPortalUser
    {
        $role = $customer->portalRoles()->first()
            ?? CustomerPortalRole::create(['customer_id' => $customer->id, 'name' => 'Consulta ' . $customer->company]);

        return CustomerPortalUser::create($over + [
            'customer_id' => $customer->id, 'role_id' => $role->id, 'first_name' => 'Ana', 'last_name' => 'Equipo',
            'email' => 'm-' . uniqid() . '@example.com', 'password' => 'clave-vieja-1', 'status' => 'active',
        ]);
    }

    private function userWith(Role $role): User
    {
        return User::create([
            'first_name' => 'Staff', 'last_name' => 'Test', 'position' => 'X', 'phone' => '5555555555',
            'email' => 'staff-' . uniqid() . '@example.com', 'password' => bcrypt('password'), 'status' => 'active',
            'rfc' => strtoupper(substr(md5(uniqid('', true)), 0, 13)), 'role_id' => $role->id,
        ]);
    }

    private function admin(): User
    {
        return $this->userWith(Role::firstOrCreate(['name_role' => 'Administrador'], ['name_role_es' => 'Administrador']));
    }

    /** Personal con permisos de clientes limitados a las acciones dadas. */
    private function staffWith(array $actions): User
    {
        $role = Role::create(['name_role' => 'Operador ' . uniqid(), 'name_role_es' => 'Operador']);
        foreach ($actions as $action) {
            $perm = Permission::firstOrCreate(
                ['code' => 'clients.' . $action],
                ['name' => 'Clientes ' . $action, 'module' => 'clients', 'action' => $action]
            );
            $role->permissions()->attach($perm->id);
        }

        return $this->userWith($role);
    }

    private function url(Customer $c, CustomerPortalUser $m, string $kind): string
    {
        return route('admin.clients.portal-users.' . $kind, [$c->id, $m->id]);
    }

    private function pass(string $value): array
    {
        return ['password' => $value, 'password_confirmation' => $value];
    }

    public function test_la_pestana_lista_solo_los_usuarios_de_ese_cliente_y_sus_roles(): void
    {
        $customer = $this->customer();
        $other = $this->customer('Otra');
        $this->member($customer, ['first_name' => 'Marta', 'email' => 'marta@propia.com', 'last_login_at' => now()]);
        $this->member($customer, ['first_name' => 'Nico', 'email' => 'nico@propia.com', 'status' => 'inactive']);
        $this->member($other, ['first_name' => 'Zeta', 'email' => 'zeta@ajena.com']);

        $this->actingAs($this->admin())
            ->get(route('admin.clients.information', $customer->id))
            ->assertOk()
            ->assertSee('id="portal-users"', false)
            ->assertSee('Titular de la cuenta')->assertSee($customer->email)
            ->assertSee('Marta')->assertSee('marta@propia.com')->assertSee('nico@propia.com')
            ->assertSee('Inactivo')->assertSee('Nunca')
            ->assertSee('Consulta ACME')
            ->assertDontSee('zeta@ajena.com')->assertDontSee('Consulta Otra');
    }

    public function test_estado_vacio_cuando_el_cliente_no_tiene_usuarios(): void
    {
        $customer = $this->customer();

        $this->actingAs($this->admin())
            ->get(route('admin.clients.information', $customer->id))
            ->assertOk()
            ->assertSee('Este cliente aún no ha agregado usuarios al portal');
    }

    public function test_el_personal_puede_desactivar_y_reactivar_a_un_usuario(): void
    {
        $customer = $this->customer();
        $member = $this->member($customer);

        $this->actingAs($this->staffWith(['view', 'edit']))->patch($this->url($customer, $member, 'toggle'))
            ->assertRedirect(route('admin.clients.information', $customer->id) . '#portal-users')
            ->assertSessionHas('success');
        $this->assertSame('inactive', $member->fresh()->status);

        $this->patch($this->url($customer, $member, 'toggle'))->assertSessionHas('success');
        $this->assertSame('active', $member->fresh()->status);
    }

    public function test_el_personal_cambia_la_contrasena_y_el_usuario_puede_entrar_con_la_nueva(): void
    {
        $customer = $this->customer();
        $member = $this->member($customer, ['email' => 'entra@equipo.com']);

        $this->actingAs($this->staffWith(['view', 'edit']))
            ->put($this->url($customer, $member, 'password'), $this->pass('nueva-clave-99'))
            ->assertRedirect(route('admin.clients.information', $customer->id) . '#portal-users')
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('nueva-clave-99', $member->fresh()->password));

        $auth = app(PortalMemberAuth::class);
        $this->assertNull($auth->attempt('entra@equipo.com', 'clave-vieja-1'));
        $this->assertSame($member->id, $auth->attempt('entra@equipo.com', 'nueva-clave-99')?->id);
    }

    public function test_la_contrasena_nueva_se_valida(): void
    {
        $customer = $this->customer();
        $member = $this->member($customer);

        $this->actingAs($this->staffWith(['view', 'edit']))
            ->put($this->url($customer, $member, 'password'), $this->pass('corta'))
            ->assertRedirect(route('admin.clients.information', $customer->id) . '#portal-users')
            ->assertSessionHasErrors('password');

        $this->put($this->url($customer, $member, 'password'), ['password' => 'larga-clave-1', 'password_confirmation' => 'distinta-1'])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('clave-vieja-1', $member->fresh()->password));
    }

    public function test_el_personal_elimina_a_un_usuario(): void
    {
        $customer = $this->customer();
        $member = $this->member($customer);
        $keep = $this->member($customer);

        $this->actingAs($this->staffWith(['view', 'edit']))
            ->delete($this->url($customer, $member, 'destroy'))
            ->assertRedirect(route('admin.clients.information', $customer->id) . '#portal-users')
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('customer_portal_users', ['id' => $member->id]);
        $this->assertDatabaseHas('customer_portal_users', ['id' => $keep->id]);
    }

    public function test_sin_permiso_de_editar_clientes_se_niega_y_no_cambia_nada(): void
    {
        $customer = $this->customer();
        $member = $this->member($customer);
        $this->actingAs($this->staffWith(['view']));

        $this->patchJson($this->url($customer, $member, 'toggle'))->assertForbidden();
        $this->putJson($this->url($customer, $member, 'password'), $this->pass('nueva-clave-99'))->assertForbidden();
        $this->deleteJson($this->url($customer, $member, 'destroy'))->assertForbidden();

        $fresh = $member->fresh();
        $this->assertSame('active', $fresh->status);
        $this->assertTrue(Hash::check('clave-vieja-1', $fresh->password));

        // Sin permiso de edición la pestaña no muestra los botones de acción.
        $this->get(route('admin.clients.information', $customer->id))
            ->assertOk()->assertDontSee('Cambiar contraseña')->assertDontSee('data-pu-delete', false);
    }

    public function test_un_usuario_de_otro_cliente_responde_404(): void
    {
        $customer = $this->customer();
        $other = $this->customer('Otra');
        $foreign = $this->member($other, ['first_name' => 'Ajeno']);
        $this->actingAs($this->staffWith(['view', 'edit']));

        $this->patch(route('admin.clients.portal-users.toggle', [$customer->id, $foreign->id]))->assertNotFound();
        $this->put(route('admin.clients.portal-users.password', [$customer->id, $foreign->id]), $this->pass('nueva-clave-99'))->assertNotFound();
        $this->delete(route('admin.clients.portal-users.destroy', [$customer->id, $foreign->id]))->assertNotFound();

        $this->assertSame('active', $foreign->fresh()->status);
        $this->assertTrue(Hash::check('clave-vieja-1', $foreign->fresh()->password));
        $this->assertDatabaseHas('customer_portal_users', ['id' => $foreign->id]);
    }
}
