<?php

namespace Tests\Feature\Crm;

use App\Models\Lead;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Models\WhatsappAccount;
use App\Services\LeadMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use ReflectionProperty;
use Tests\TestCase;

/**
 * Roles y permisos (todos los paneles asignables), Embudo de Venta con permiso propio + Configuración
 * (WhatsApp y buzón de respuestas) y modo incrustado (?embed=1) que sobrevive a las redirecciones.
 */
class FunnelSettingsAndRolesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        (new ReflectionProperty(Setting::class, 'cache'))->setValue(null, []);
        $this->admin = $this->makeUser(Role::firstOrCreate(['name_role' => 'Administrador'], ['name_role_es' => 'Administrador']));
    }

    private function makeUser(Role $role): User
    {
        return User::create([
            'first_name' => 'U', 'last_name' => 'Test', 'position' => 'Tester', 'phone' => '5555555555',
            'email' => 'u-' . uniqid() . '@example.com', 'password' => bcrypt('password'), 'status' => 'active',
            'rfc' => 'RFC' . strtoupper(substr(uniqid(), -8)), 'role_id' => $role->id,
        ]);
    }

    /** Usuario no admin con los permisos "modulo:accion" indicados. */
    private function userWith(array $perms): User
    {
        Permission::syncFromConfig();
        $role = Role::create(['name_role' => 'Rol ' . uniqid(), 'name_role_es' => 'Rol']);
        $ids = [];
        foreach ($perms as $p) {
            [$m, $a] = explode(':', $p);
            $ids[] = Permission::where('module', $m)->where('action', $a)->value('id');
        }
        $role->permissions()->sync($ids);

        return $this->makeUser($role);
    }

    // ── Roles y permisos ────────────────────────────────────────────────

    public function test_roles_muestra_todos_los_modulos_y_ninguno_cae_en_otros(): void
    {
        $html = $this->actingAs($this->admin)->get(route('admin.roles.index'))->assertOk()->getContent();

        foreach (config('modules') as $key => $def) {
            $this->assertStringContainsString('data-module="' . $key . '"', $html, "Falta el módulo {$key} en Roles");
        }
        foreach (['Pipelines', 'Embudo de Venta', 'Bandeja de Leads', 'Proyectos', 'MRP', 'Cuentas por Cobrar', 'Conversiones a Google', 'Monitoreo de Clientes'] as $name) {
            $this->assertStringContainsString($name, $html);
        }
        $this->assertStringNotContainsString('Otros módulos', $html);
    }

    public function test_un_modulo_nuevo_sin_grupo_aparece_en_otros_modulos(): void
    {
        config(['modules.zz-nuevo' => ['name' => 'Panel Nuevo ZZ', 'icon' => 'x', 'route' => 'admin.roles.index', 'order' => 99]]);

        $html = $this->actingAs($this->admin)->get(route('admin.roles.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Otros módulos', $html);
        $this->assertStringContainsString('Panel Nuevo ZZ', $html);
        $this->assertTrue(Permission::where('module', 'zz-nuevo')->where('action', 'view')->exists(), 'La fila de permiso se crea sola');
    }

    public function test_guardar_un_rol_con_modulos_antes_sin_filas_si_queda_guardado(): void
    {
        // Pipelines (sin fila 'view') y Proyectos (sin ninguna fila) eran los casos que se perdían.
        Permission::query()->whereIn('module', ['pipeline', 'projects'])->delete();

        $this->actingAs($this->admin)->post(route('admin.roles.store'), [
            'name_role' => 'Supervisor ventas',
            'permissions' => ['pipeline:view', 'pipeline:edit', 'projects:view', 'sales-funnel:view', 'leads-inbox:view'],
        ])->assertRedirect(route('admin.roles.index'));

        $perms = Role::where('name_role', 'Supervisor ventas')->firstOrFail()->permissions->map(fn ($p) => $p->module . ':' . $p->action)->sort()->values()->all();
        $this->assertSame(['leads-inbox:view', 'pipeline:edit', 'pipeline:view', 'projects:view', 'sales-funnel:view'], $perms);

        $user = $this->makeUser(Role::where('name_role', 'Supervisor ventas')->first());
        $this->assertTrue($user->hasPermission('pipeline'));
        $this->assertTrue($user->hasPermission('projects'));
        $this->assertFalse($user->hasPermission('mrp'));
    }

    public function test_la_migracion_da_view_a_quien_ya_tenia_acciones_y_el_embudo_a_quien_tenia_whatsapp(): void
    {
        Permission::syncFromConfig();
        $role = Role::create(['name_role' => 'Vendedor', 'name_role_es' => 'Vendedor']);

        // Estado viejo: sin fila view de pipeline y sin permiso del embudo; el rol tenía pipeline:edit y whatsapp:view/create.
        $editId = Permission::where('module', 'pipeline')->where('action', 'edit')->value('id');
        $waIds = Permission::where('module', 'whatsapp')->whereIn('action', ['view', 'create'])->pluck('id')->all();
        Permission::where('module', 'pipeline')->where('action', 'view')->delete();
        Permission::where('module', 'sales-funnel')->delete();
        $role->permissions()->sync(array_merge([$editId], $waIds));

        (require database_path('migrations/2026_11_11_100000_sync_permissions_with_modules.php'))->up();

        $mods = $role->fresh()->permissions->map(fn ($p) => $p->module . ':' . $p->action)->sort()->values()->all();
        $this->assertContains('pipeline:view', $mods);
        $this->assertContains('pipeline:edit', $mods);
        $this->assertContains('sales-funnel:view', $mods);
        $this->assertContains('sales-funnel:create', $mods);
        $this->assertNotContains('sales-funnel:delete', $mods);

        // Idempotente: si el admin quita el embudo, otra corrida no lo vuelve a dar.
        $role->permissions()->detach(Permission::where('module', 'sales-funnel')->pluck('id'));
        (require database_path('migrations/2026_11_11_100000_sync_permissions_with_modules.php'))->up();
        $this->assertNotContains('sales-funnel:view', $role->fresh()->permissions->map(fn ($p) => $p->module . ':' . $p->action)->all());
    }

    // ── Embudo de Venta: permiso propio y Configuración ─────────────────

    public function test_el_embudo_y_su_configuracion_usan_el_permiso_sales_funnel(): void
    {
        $solo = $this->userWith(['sales-funnel:view']);
        $this->actingAs($solo)->get(route('admin.whatsapp-funnel.index'))->assertOk();
        $this->actingAs($solo)->get(route('admin.whatsapp-funnel.settings'))->assertRedirect(); // ver no basta para configurar

        $wa = $this->userWith(['whatsapp:view']); // el permiso viejo ya no abre el embudo
        $this->actingAs($wa)->get(route('admin.whatsapp-funnel.index'))->assertRedirect();

        $editor = $this->userWith(['sales-funnel:view', 'sales-funnel:edit']);
        $this->actingAs($editor)->get(route('admin.whatsapp-funnel.settings'))->assertOk()->assertSee('Configuración del Embudo de Venta');
    }

    public function test_el_menu_muestra_configuracion_debajo_del_embudo_solo_con_permiso_de_editar(): void
    {
        $href = route('admin.whatsapp-funnel.settings');

        $this->actingAs($this->admin)->get(route('admin.tasks.index'))->assertOk()->assertSee($href, false);

        $ver = $this->userWith(['sales-funnel:view']);
        $this->actingAs($ver)->get(route('admin.tasks.index'))->assertOk()
            ->assertSee(route('admin.whatsapp-funnel.index'), false)->assertDontSee($href, false);
    }

    public function test_guarda_whatsapp_y_buzon_y_las_respuestas_salen_de_ese_correo(): void
    {
        config(['mail.default' => 'smtp', 'mail.from.address' => 'no-reply@equiterm.test', 'mail.from.name' => 'Equiterm']);
        Setting::updateOrCreate(['key' => 'mail.host'], ['value' => 'smtp.test', 'type' => 'string', 'group_name' => 'integraciones', 'is_public' => false]);
        $a1 = WhatsappAccount::create(['name' => 'Ventas', 'phone_number' => '5511111111', 'connection_type' => 'meta_cloud_api', 'is_active' => true]);
        $a2 = WhatsappAccount::create(['name' => 'Soporte', 'phone_number' => '5522222222', 'connection_type' => 'meta_cloud_api', 'is_active' => true]);

        $this->assertSame('no-reply@equiterm.test', app(LeadMailService::class)->sender()['address']);

        $this->actingAs($this->admin)->post(route('admin.whatsapp-funnel.settings.update'), [
            'whatsapp_account_id' => $a2->id,
            'mail_from_address' => 'administracion@equiterm.test',
            'mail_from_name' => 'Equiterm Administración',
            'mail_password' => 'secreto-123',
        ])->assertRedirect(route('admin.whatsapp-funnel.settings'))->assertSessionHas('success');

        (new ReflectionProperty(Setting::class, 'cache'))->setValue(null, []);
        $this->assertSame($a2->id, WhatsappAccount::funnelDefault()?->id);
        $this->assertSame('administracion@equiterm.test', Setting::get('mail.inbox.username'), 'usuario = el correo si no se captura');
        $this->assertSame('secreto-123', Crypt::decryptString(Setting::get('mail.inbox.password')));
        $sender = app(LeadMailService::class)->sender();
        $this->assertSame('administracion@equiterm.test', $sender['address']);
        $this->assertFalse($sender['uses_default']);

        // La página no imprime la contraseña.
        $this->actingAs($this->admin)->get(route('admin.whatsapp-funnel.settings'))->assertOk()
            ->assertDontSee('secreto-123')->assertSee('administracion@equiterm.test');

        // Quitar el buzón regresa al remitente general.
        $this->actingAs($this->admin)->post(route('admin.whatsapp-funnel.settings.update'), ['clear_mailbox' => 1, 'whatsapp_account_id' => $a1->id])->assertSessionHas('success');
        (new ReflectionProperty(Setting::class, 'cache'))->setValue(null, []);
        $this->assertTrue(app(LeadMailService::class)->sender()['uses_default']);
        $this->assertSame($a1->id, WhatsappAccount::funnelDefault()?->id);
    }

    public function test_el_buzon_exige_contrasena_la_primera_vez_y_el_whatsapp_debe_estar_activo(): void
    {
        $this->actingAs($this->admin)->from(route('admin.whatsapp-funnel.settings'))->post(route('admin.whatsapp-funnel.settings.update'), [
            'mail_from_address' => 'administracion@equiterm.test',
        ])->assertSessionHasErrors('mail_password');

        $off = WhatsappAccount::create(['name' => 'Vieja', 'phone_number' => '5533333333', 'connection_type' => 'meta_cloud_api', 'is_active' => false]);
        $this->actingAs($this->admin)->post(route('admin.whatsapp-funnel.settings.update'), ['whatsapp_account_id' => $off->id])
            ->assertSessionHasErrors('whatsapp_account_id');
    }

    public function test_la_cuenta_elegida_se_usa_primero_y_una_inactiva_se_ignora(): void
    {
        $a1 = WhatsappAccount::create(['name' => 'A', 'phone_number' => '1', 'connection_type' => 'meta_cloud_api', 'is_active' => true]);
        $a2 = WhatsappAccount::create(['name' => 'B', 'phone_number' => '2', 'connection_type' => 'meta_cloud_api', 'is_active' => true]);

        $this->assertNull(WhatsappAccount::funnelDefault());

        Setting::updateOrCreate(['key' => 'funnel.whatsapp_account_id'], ['value' => (string) $a2->id, 'type' => 'string', 'group_name' => 'integraciones', 'is_public' => false]);
        (new ReflectionProperty(Setting::class, 'cache'))->setValue(null, []);
        $this->assertSame($a2->id, WhatsappAccount::funnelDefault()->id);

        $a2->update(['is_active' => false]);
        $this->assertNull(WhatsappAccount::funnelDefault());
    }

    public function test_la_prueba_de_correo_se_envia_y_valida_el_destino(): void
    {
        $this->actingAs($this->admin)->post(route('admin.whatsapp-funnel.settings.test-mail'), ['test_to' => 'dueno@example.com'])
            ->assertSessionHas('success');

        $this->actingAs($this->admin)->post(route('admin.whatsapp-funnel.settings.test-mail'), ['test_to' => 'no-es-correo'])
            ->assertSessionHasErrors('test_to');
    }

    // ── Modo incrustado ─────────────────────────────────────────────────

    public function test_la_redireccion_de_una_pagina_incrustada_conserva_embed(): void
    {
        $lead = Lead::create(['source' => 'correo', 'status' => 'nuevo', 'contact_email' => 'a@b.test', 'contact_name' => 'A', 'last_activity_at' => now()]);

        $res = $this->actingAs($this->admin)->post(route('admin.leads-inbox.assign', $lead), ['assigned_to' => $this->admin->id, 'embed' => 1]);
        $res->assertRedirect();
        $this->assertStringContainsString('embed=1', $res->headers->get('Location'));

        $res = $this->actingAs($this->admin)->post(route('admin.leads-inbox.assign', $lead), ['assigned_to' => $this->admin->id]);
        $this->assertStringNotContainsString('embed=1', (string) $res->headers->get('Location'));
    }

    public function test_la_pagina_incrustada_agrega_embed_a_sus_formularios(): void
    {
        $lead = Lead::create(['source' => 'correo', 'status' => 'nuevo', 'contact_email' => 'a@b.test', 'contact_name' => 'A', 'last_activity_at' => now()]);

        $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['lead' => $lead->id, 'embed' => 1]))
            ->assertOk()->assertSee('input[name="embed"]', false);
    }
}
