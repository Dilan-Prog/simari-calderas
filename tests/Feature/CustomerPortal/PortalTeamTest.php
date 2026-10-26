<?php

namespace Tests\Feature\CustomerPortal;

use App\Models\Customer;
use App\Models\CustomerPortalRole;
use App\Models\CustomerPortalUser;
use App\Services\Portal\PortalRoleDefaults;
use App\Support\PortalContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Usuarios y roles del portal de clientes (/customer).
 * Correr con base privada: DB_DATABASE=portal_roles_test php artisan test --filter=PortalTeam
 */
class PortalTeamTest extends TestCase
{
    use RefreshDatabase;

    private Customer $owner;
    private Customer $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->owner = $this->makeCustomer('titular@empresa.com', 'Ana', 'Titular');
        $this->other = $this->makeCustomer('otro@otra.com', 'Beto', 'Otro');
    }

    // ───────────────────────── helpers ─────────────────────────

    private function makeCustomer(string $email, string $first, string $last, array $over = []): Customer
    {
        return Customer::create($over + [
            'first_name' => $first, 'last_name' => $last, 'email' => $email, 'company' => 'Empresa ' . $first,
            'phone' => '5512345678', 'document_type' => 'ine', 'source' => 'admin', 'status' => 'active',
            'portal_access' => true, 'password_hash' => Hash::make('secreto123'),
        ]);
    }

    /** Rol propio de la cuenta con los permisos "modulo:accion" dados. */
    private function makeRole(Customer $customer, string $name, array $perms): CustomerPortalRole
    {
        $role = $customer->portalRoles()->create(['name' => $name]);
        $role->syncPermissions($perms);

        return $role;
    }

    private function makeMember(Customer $customer, ?CustomerPortalRole $role, array $over = []): CustomerPortalUser
    {
        return CustomerPortalUser::create($over + [
            'customer_id' => $customer->id,
            'role_id'     => $role?->id,
            'first_name'  => 'Luis',
            'last_name'   => 'Miembro',
            'email'       => 'luis@empresa.com',
            'password'    => 'clave-segura-1',
            'status'      => 'active',
        ]);
    }

    /** Petición como un usuario del equipo ya logueado (titular en el guard + marca de sesión). */
    private function asMember(CustomerPortalUser $member): static
    {
        return $this->actingAs($member->customer, 'customer')->withSession(['portal_member_id' => $member->id]);
    }

    private function asOwner(?Customer $customer = null): static
    {
        return $this->actingAs($customer ?? $this->owner, 'customer');
    }

    // ───────────────────────── titular: roles y usuarios ─────────────────────────

    public function test_owner_opens_team_page_and_default_roles_are_created(): void
    {
        $this->asOwner()->get(route('customer.team.index'))
            ->assertOk()
            ->assertSee('Titular de la cuenta')
            ->assertSee('Administrador del portal')
            ->assertSee('Solo consulta');

        $this->assertSame(4, $this->owner->portalRoles()->where('is_system', true)->count());
        // Idempotente: abrirla otra vez no duplica.
        PortalRoleDefaults::ensureFor($this->owner);
        $this->assertSame(4, $this->owner->portalRoles()->count());

        // El rol de administrador no incluye "team" (owner_only).
        $admin = $this->owner->portalRoles()->where('name', 'Administrador del portal')->first();
        $this->assertFalse(in_array('team:view', $admin->permissionKeys(), true));
        $this->assertTrue(in_array('equipment:delete', $admin->permissionKeys(), true));
    }

    public function test_owner_creates_role_and_member(): void
    {
        $this->asOwner()->post(route('customer.team.roles.store'), [
            'name' => 'Operadores',
            'description' => 'Equipo de planta',
            // "equipment:edit" sin "equipment:view" se descarta; "team:view" (owner_only) y módulos inventados también.
            'permissions' => ['monitoring:view', 'monitoring:create', 'equipment:edit', 'team:view', 'nada:view', 'quotes:create'],
        ])->assertRedirect(route('customer.team.index', ['tab' => 'roles']));

        $role = $this->owner->portalRoles()->where('name', 'Operadores')->firstOrFail();
        $this->assertEqualsCanonicalizing(['monitoring:view', 'monitoring:create'], $role->fresh()->load('permissions')->permissionKeys());
        $this->assertFalse($role->is_system);

        $this->asOwner()->post(route('customer.team.users.store'), [
            'first_name' => 'Luis', 'last_name' => 'Pérez', 'email' => '  LUIS@Empresa.COM ', 'phone' => '5511112222',
            'role_id' => $role->id, 'password' => 'clave-segura-1',
        ])->assertRedirect(route('customer.team.index'));

        $member = CustomerPortalUser::firstOrFail();
        $this->assertSame('luis@empresa.com', $member->email);
        $this->assertSame($this->owner->id, $member->customer_id);
        $this->assertSame('active', $member->status);
        $this->assertTrue(Hash::check('clave-segura-1', $member->password));
        $this->assertNotSame('clave-segura-1', $member->getRawOriginal('password'));
    }

    public function test_owner_can_edit_toggle_reset_password_and_delete_member(): void
    {
        $role = $this->makeRole($this->owner, 'R1', ['quotes:view']);
        $role2 = $this->makeRole($this->owner, 'R2', ['orders:view']);
        $member = $this->makeMember($this->owner, $role);

        $this->asOwner()->put(route('customer.team.users.update', $member->id), [
            'first_name' => 'Luis', 'last_name' => 'Nuevo', 'email' => 'luis@empresa.com', 'role_id' => $role2->id,
        ])->assertRedirect(route('customer.team.index'));
        $this->assertSame($role2->id, $member->fresh()->role_id);

        $this->asOwner()->patch(route('customer.team.users.toggle', $member->id))->assertRedirect();
        $this->assertSame('inactive', $member->fresh()->status);
        $this->asOwner()->patch(route('customer.team.users.toggle', $member->id));
        $this->assertSame('active', $member->fresh()->status);

        $this->asOwner()->put(route('customer.team.users.password', $member->id), ['password' => 'corta'])
            ->assertSessionHasErrors('password');
        $this->asOwner()->put(route('customer.team.users.password', $member->id), ['password' => 'otra-clave-99'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('otra-clave-99', $member->fresh()->password));

        $this->asOwner()->delete(route('customer.team.users.destroy', $member->id))->assertRedirect();
        $this->assertNull(CustomerPortalUser::find($member->id));
    }

    // ───────────────────────── autenticación ─────────────────────────

    public function test_member_logs_in_through_shop_login(): void
    {
        $role = $this->makeRole($this->owner, 'R', ['quotes:view']);
        $member = $this->makeMember($this->owner, $role);

        $this->post(route('shop.login.store'), ['email' => 'Luis@Empresa.com', 'password' => 'clave-segura-1'])
            ->assertRedirect(route('customer.dashboard'));

        $this->assertAuthenticatedAs($this->owner, 'customer');
        $this->assertSame($member->id, session('portal_member_id'));
        $this->assertNotNull($member->fresh()->last_login_at);

        $this->get(route('customer.dashboard'))->assertOk()->assertSee('Luis Miembro');
    }

    public function test_member_logs_in_through_admin_login_page(): void
    {
        $role = $this->makeRole($this->owner, 'R', ['quotes:view']);
        $member = $this->makeMember($this->owner, $role);

        $this->post('/login', ['email' => 'luis@empresa.com', 'password' => 'clave-segura-1'])
            ->assertRedirect(route('customer.dashboard'));

        $this->assertAuthenticatedAs($this->owner, 'customer');
        $this->assertSame($member->id, session('portal_member_id'));
    }

    public function test_member_login_failures(): void
    {
        $role = $this->makeRole($this->owner, 'R', ['quotes:view']);
        $member = $this->makeMember($this->owner, $role);
        $creds = ['email' => 'luis@empresa.com', 'password' => 'clave-segura-1'];

        // Contraseña incorrecta.
        $this->post(route('shop.login.store'), ['email' => 'luis@empresa.com', 'password' => 'mala'])->assertSessionHasErrors('email');
        $this->assertGuest('customer');

        // Usuario desactivado.
        $member->update(['status' => 'inactive']);
        $this->post(route('shop.login.store'), $creds)->assertSessionHasErrors('email');
        $this->assertGuest('customer');
        $member->update(['status' => 'active']);

        // Sin rol.
        $member->update(['role_id' => null]);
        $this->post('/login', $creds)->assertSessionHasErrors('email');
        $this->assertGuest('customer');
        $member->update(['role_id' => $role->id]);

        // Titular sin acceso al portal / cuenta desactivada.
        $this->owner->update(['portal_access' => false]);
        $this->post(route('shop.login.store'), $creds)->assertSessionHasErrors('email');
        $this->assertGuest('customer');
        $this->owner->update(['portal_access' => true, 'status' => 'inactive']);
        $this->post(route('shop.login.store'), $creds)->assertSessionHasErrors('email');
        $this->assertGuest('customer');
    }

    public function test_owner_login_clears_member_mark_and_logout_clears_session(): void
    {
        $this->withSession(['portal_member_id' => 999])
            ->post(route('shop.login.store'), ['email' => 'titular@empresa.com', 'password' => 'secreto123'])
            ->assertRedirect();
        $this->assertNull(session('portal_member_id'));

        $role = $this->makeRole($this->owner, 'R', ['quotes:view']);
        $member = $this->makeMember($this->owner, $role);
        $this->asMember($member)->post(route('logout'))->assertRedirect('/');
        $this->assertGuest('customer');
        $this->assertNull(session('portal_member_id'));
    }

    // ───────────────────────── permisos ─────────────────────────

    public function test_member_sees_only_permitted_modules_and_routes_are_protected(): void
    {
        $role = $this->makeRole($this->owner, 'Solo equipos', ['equipment:view']);
        $member = $this->makeMember($this->owner, $role);

        $this->asMember($member);

        $dash = $this->get(route('customer.dashboard'))->assertOk();
        $dash->assertSee('Mis Equipos')
            ->assertDontSee('Mis Pedidos')
            ->assertDontSee('Direcciones')
            ->assertDontSee('Usuarios y Roles')
            ->assertDontSee('Servicios próximos');

        $this->get(route('customer.equipment.index'))->assertOk();
        $this->post(route('customer.equipment.store'), ['name' => 'Caldera'])
            ->assertRedirect(route('customer.dashboard'));
        $this->get(route('customer.addresses.index'))->assertRedirect(route('customer.dashboard'));
        $this->get(route('customer.orders.index'))->assertRedirect(route('customer.dashboard'));
        $this->get(route('customer.quotes.index'))->assertRedirect(route('customer.dashboard'));
        $this->get(route('customer.service-reports.index'))->assertRedirect(route('customer.dashboard'));
        $this->get(route('customer.technical-services.index'))->assertRedirect(route('customer.dashboard'));
        $this->get(route('customer.monitoring.index'))->assertRedirect(route('customer.dashboard'));
        $this->get(route('customer.calendar'))->assertRedirect(route('customer.dashboard'));
        $this->getJson(route('customer.calendar.events'))->assertForbidden();

        // Siempre disponible: perfil.
        $this->get(route('customer.profile'))->assertOk();
    }

    public function test_actions_require_matching_permission(): void
    {
        $role = $this->makeRole($this->owner, 'Direcciones lectura', ['addresses:view']);
        $member = $this->makeMember($this->owner, $role);

        $this->asMember($member)->get(route('customer.addresses.index'))->assertOk();
        $this->post(route('customer.addresses.store'), [])->assertRedirect(route('customer.dashboard'));
        $address = \App\Models\CustomerAddress::create([
            'customer_id' => $this->owner->id, 'recipient_name' => 'Ana', 'phone' => '5512345678', 'country' => 'MX',
            'state' => 'CDMX', 'city' => 'CDMX', 'postal_code' => '01000', 'address_line1' => 'Calle 1', 'is_default' => true,
        ]);
        $this->delete(route('customer.addresses.destroy', $address->id))->assertRedirect(route('customer.dashboard'));
        $this->assertNotNull($address->fresh());

        $role->syncPermissions(['addresses:view', 'addresses:create']);
        PortalContext::flush();
        // Con "create" ya llega a validar (302 de vuelta con errores, no al dashboard por falta de permiso).
        $resp = $this->post(route('customer.addresses.store'), []);
        $resp->assertSessionHasErrors();
    }

    public function test_member_cannot_reach_team_pages(): void
    {
        $role = $this->makeRole($this->owner, 'Todo', array_map(fn ($m) => $m . ':view', array_keys(PortalContext::assignableModules())));
        $member = $this->makeMember($this->owner, $role);

        $this->asMember($member);
        $this->get(route('customer.team.index'))->assertRedirect(route('customer.dashboard'));
        $this->post(route('customer.team.users.store'), ['first_name' => 'X'])->assertRedirect(route('customer.dashboard'));
        $this->post(route('customer.team.roles.store'), ['name' => 'X'])->assertRedirect(route('customer.dashboard'));
        $this->assertSame(0, CustomerPortalRole::where('name', 'X')->count());
        $this->assertSame(1, CustomerPortalUser::count());
    }

    public function test_deactivated_or_deleted_member_is_blocked_mid_session(): void
    {
        $role = $this->makeRole($this->owner, 'R', ['equipment:view']);
        $member = $this->makeMember($this->owner, $role);

        $this->asMember($member)->get(route('customer.equipment.index'))->assertOk();

        $member->update(['status' => 'inactive']);
        $this->get(route('customer.equipment.index'))->assertRedirect(route('shop.login'));
        $this->assertGuest('customer');

        // Sin rol.
        $member->update(['status' => 'active', 'role_id' => null]);
        $this->asMember($member)->get(route('customer.dashboard'))->assertRedirect(route('shop.login'));

        // Eliminado.
        $member->update(['role_id' => $role->id]);
        $this->asMember($member)->get(route('customer.dashboard'))->assertOk();
        $member->delete();
        $this->get(route('customer.dashboard'))->assertRedirect(route('shop.login'));
    }

    public function test_role_permission_change_applies_on_next_request(): void
    {
        $role = $this->makeRole($this->owner, 'R', ['equipment:view']);
        $member = $this->makeMember($this->owner, $role);

        $this->asMember($member)->get(route('customer.orders.index'))->assertRedirect(route('customer.dashboard'));
        $role->syncPermissions(['equipment:view', 'orders:view']);
        $this->get(route('customer.orders.index'))->assertOk();
    }

    public function test_member_cannot_reach_shop_account_areas(): void
    {
        $role = $this->makeRole($this->owner, 'R', ['equipment:view']);
        $member = $this->makeMember($this->owner, $role);

        $this->asMember($member);
        $this->get(route('shop.account'))->assertRedirect(route('customer.dashboard'));
        $this->post(route('shop.addresses.store'), [])->assertRedirect(route('customer.dashboard'));
        $this->post(route('shop.portal-request.finish'))->assertRedirect(route('customer.dashboard'));
        $this->get(route('checkout.index'))->assertRedirect(route('customer.dashboard'));
        $this->post(route('checkout.confirm'))->assertRedirect(route('customer.dashboard'));
        $this->post(route('cart.add'), [])->assertRedirect(route('customer.dashboard'));
        $this->getJson(route('cart.mini'))->assertForbidden();
        // Las páginas de login para quien ya está dentro lo mandan al portal.
        $this->get(route('shop.login'))->assertRedirect(route('customer.dashboard'));

        // Cerrar sesión desde la tienda sí está permitido.
        $this->post(route('shop.logout'))->assertRedirect(route('home'));
        $this->assertGuest('customer');
    }

    public function test_owner_is_not_blocked_from_shop_account(): void
    {
        $this->asOwner()->get(route('shop.account'))->assertOk();
    }

    // ───────────────────────── unicidad de correos ─────────────────────────

    public function test_member_email_must_be_unique_across_customers_and_members(): void
    {
        $role = $this->makeRole($this->owner, 'R', ['quotes:view']);
        $base = ['first_name' => 'Nuevo', 'role_id' => $role->id, 'password' => 'clave-segura-1'];

        // Correo de un titular (cualquier cuenta, sin distinguir mayúsculas).
        $this->asOwner()->post(route('customer.team.users.store'), $base + ['email' => 'OTRO@otra.com'])
            ->assertSessionHasErrors('email');

        // Correo de otro usuario del equipo (incluso de otra cuenta).
        $theirRole = $this->makeRole($this->other, 'R', ['quotes:view']);
        $this->makeMember($this->other, $theirRole, ['email' => 'ajeno@otra.com']);
        $this->asOwner()->post(route('customer.team.users.store'), $base + ['email' => 'AJENO@otra.com'])
            ->assertSessionHasErrors('email');

        $this->assertSame(1, CustomerPortalUser::count());

        // Edición: no puede tomar un correo existente, sí conservar el suyo.
        $mine = $this->makeMember($this->owner, $role, ['email' => 'mio@empresa.com']);
        $this->asOwner()->put(route('customer.team.users.update', $mine->id), $base + ['email' => 'ajeno@otra.com'])
            ->assertSessionHasErrors('email');
        $this->asOwner()->put(route('customer.team.users.update', $mine->id), $base + ['email' => 'mio@empresa.com'])
            ->assertSessionHasNoErrors();

        // El titular tampoco puede cambiar su correo por el de un usuario del equipo, ni registrarse con él.
        $this->asOwner()->put(route('customer.profile.update'), [
            'first_name' => 'Ana', 'email' => 'mio@empresa.com', 'phone' => '5512345678',
        ])->assertSessionHasErrors('email');
    }

    public function test_member_with_email_equal_to_a_customer_follows_normal_customer_login(): void
    {
        $role = $this->makeRole($this->other, 'R', ['quotes:view']);
        // Inconsistencia forzada: usuario del equipo con el correo de un titular.
        CustomerPortalUser::create([
            'customer_id' => $this->other->id, 'role_id' => $role->id, 'first_name' => 'X', 'last_name' => '',
            'email' => 'titular@empresa.com', 'password' => 'otra-clave-123', 'status' => 'active',
        ]);

        $this->post(route('shop.login.store'), ['email' => 'titular@empresa.com', 'password' => 'otra-clave-123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest('customer');
    }

    // ───────────────────────── aislamiento entre cuentas ─────────────────────────

    public function test_accounts_are_isolated(): void
    {
        $roleA = $this->makeRole($this->owner, 'Rol A', ['quotes:view']);
        $memberA = $this->makeMember($this->owner, $roleA);
        $roleB = $this->makeRole($this->other, 'Rol B', ['quotes:view']);

        // B no puede tocar roles ni usuarios de A.
        $this->asOwner($this->other);
        $this->put(route('customer.team.roles.update', $roleA->id), ['name' => 'Hackeado'])->assertNotFound();
        $this->delete(route('customer.team.roles.destroy', $roleA->id))->assertNotFound();
        $this->put(route('customer.team.users.update', $memberA->id), [
            'first_name' => 'H', 'email' => 'h@h.com', 'role_id' => $roleB->id,
        ])->assertNotFound();
        $this->patch(route('customer.team.users.toggle', $memberA->id))->assertNotFound();
        $this->put(route('customer.team.users.password', $memberA->id), ['password' => 'hackeada-123'])->assertNotFound();
        $this->delete(route('customer.team.users.destroy', $memberA->id))->assertNotFound();

        $this->assertSame('Rol A', $roleA->fresh()->name);
        $this->assertNotNull(CustomerPortalUser::find($memberA->id));

        // B no puede asignar un rol de A a un usuario suyo.
        $this->post(route('customer.team.users.store'), [
            'first_name' => 'N', 'email' => 'n@b.com', 'role_id' => $roleA->id, 'password' => 'clave-segura-1',
        ])->assertSessionHasErrors('role_id');

        // Las listas solo muestran lo propio.
        $this->get(route('customer.team.index'))->assertOk()->assertDontSee('Rol A')->assertDontSee('luis@empresa.com');

        // Un usuario de A con sesión de A no se confunde con la cuenta B (marca de sesión ajena => sin permisos).
        $this->asOwner($this->other)->withSession(['portal_member_id' => $memberA->id]);
        $this->assertFalse(PortalContext::can('quotes', 'view'));
    }

    public function test_role_deletion_rules(): void
    {
        PortalRoleDefaults::ensureFor($this->owner);
        $system = $this->owner->portalRoles()->where('is_system', true)->first();
        $custom = $this->makeRole($this->owner, 'Con usuarios', ['quotes:view']);
        $free = $this->makeRole($this->owner, 'Libre', ['quotes:view']);
        $this->makeMember($this->owner, $custom);

        $this->asOwner()->delete(route('customer.team.roles.destroy', $system->id))->assertSessionHas('error');
        $this->assertNotNull($system->fresh());

        $this->delete(route('customer.team.roles.destroy', $custom->id))->assertSessionHas('error');
        $this->assertNotNull($custom->fresh());

        $this->delete(route('customer.team.roles.destroy', $free->id))->assertSessionHas('success');
        $this->assertNull($free->fresh());

        // Los roles de sistema sí se pueden editar.
        $this->put(route('customer.team.roles.update', $system->id), ['name' => 'Renombrado', 'permissions' => ['quotes:view']])
            ->assertSessionHasNoErrors();
        $this->assertSame('Renombrado', $system->fresh()->name);

        // Nombre repetido en la misma cuenta.
        $this->post(route('customer.team.roles.store'), ['name' => 'Libre 2'])->assertSessionHasNoErrors();
        $this->post(route('customer.team.roles.store'), ['name' => 'Libre 2'])->assertSessionHasErrors('name');
    }

    // ───────────────────────── PortalContext ─────────────────────────

    public function test_portal_context_can_for_owner_and_member(): void
    {
        $role = $this->makeRole($this->owner, 'R', ['equipment:view', 'equipment:edit', 'quotes:view']);
        $member = $this->makeMember($this->owner, $role);

        // Titular: todo, sin marca de sesión.
        $this->asOwner();
        $this->assertNull(PortalContext::memberId());
        $this->assertTrue(PortalContext::can('team', 'delete'));
        $this->assertTrue(PortalContext::can('water-analysis', 'create'));

        // Usuario del equipo: solo lo del rol.
        $this->asMember($member);
        session(['portal_member_id' => $member->id]);
        PortalContext::flush();
        $this->assertSame($member->id, PortalContext::memberId());
        $this->assertTrue(PortalContext::can('equipment', 'view'));
        $this->assertTrue(PortalContext::can('equipment', 'edit'));
        $this->assertFalse(PortalContext::can('equipment', 'delete'));
        $this->assertFalse(PortalContext::can('orders', 'view'));
        $this->assertFalse(PortalContext::can('team', 'view'));
        $this->assertFalse(PortalContext::can('modulo-inexistente', 'view'));

        // Desactivado o sin rol: sin permisos.
        $member->update(['status' => 'inactive']);
        PortalContext::flush();
        $this->assertFalse(PortalContext::can('equipment', 'view'));
    }

    // ───────────────────────── Mi perfil ─────────────────────────

    public function test_member_profile_edits_only_own_data(): void
    {
        $role = $this->makeRole($this->owner, 'R', ['quotes:view']);
        $member = $this->makeMember($this->owner, $role);

        $this->asMember($member)->get(route('customer.profile'))
            ->assertOk()
            ->assertSee('Mis datos')
            ->assertDontSee('RFC');

        // Intenta colarse en datos de la empresa: se ignoran.
        $this->put(route('customer.profile.update'), [
            'first_name' => 'Luisito', 'last_name' => 'M', 'phone' => '999',
            'email' => 'hack@x.com', 'company' => 'Hackeada', 'rfc' => 'XAXX010101000',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Luisito', $member->fresh()->first_name);
        $this->assertSame('luis@empresa.com', $member->fresh()->email);
        $this->owner->refresh();
        $this->assertSame('titular@empresa.com', $this->owner->email);
        $this->assertSame('Empresa Ana', $this->owner->company);
    }

    public function test_member_password_change_requires_current_password_and_does_not_touch_owner(): void
    {
        $role = $this->makeRole($this->owner, 'R', ['quotes:view']);
        $member = $this->makeMember($this->owner, $role);
        $ownerHash = $this->owner->password_hash;

        $this->asMember($member)->put(route('customer.profile.password'), [
            'current_password' => 'incorrecta', 'password' => 'nueva-clave-99', 'password_confirmation' => 'nueva-clave-99',
        ])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('clave-segura-1', $member->fresh()->password));

        // La contraseña del TITULAR no sirve como "actual" del usuario del equipo.
        $this->put(route('customer.profile.password'), [
            'current_password' => 'secreto123', 'password' => 'nueva-clave-99', 'password_confirmation' => 'nueva-clave-99',
        ])->assertSessionHasErrors('current_password');

        $this->put(route('customer.profile.password'), [
            'current_password' => 'clave-segura-1', 'password' => 'nueva-clave-99', 'password_confirmation' => 'nueva-clave-99',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('nueva-clave-99', $member->fresh()->password));
        $this->assertSame($ownerHash, $this->owner->fresh()->password_hash);
    }

    public function test_owner_profile_behaves_as_before(): void
    {
        $this->asOwner()->get(route('customer.profile'))
            ->assertOk()
            ->assertSee('Datos de contacto y fiscales');

        $this->put(route('customer.profile.update'), [
            'first_name' => 'Ana', 'email' => 'titular@empresa.com', 'phone' => '5512345678', 'company' => 'Nueva SA',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Nueva SA', $this->owner->fresh()->company);
    }

    public function test_owner_dashboard_shows_everything(): void
    {
        $this->asOwner()->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('Servicios próximos')
            ->assertSee('Usuarios y Roles');
    }
}
