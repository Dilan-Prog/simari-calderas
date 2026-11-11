<?php

namespace Tests\Feature\Crm;

use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ventas > Bandeja de leads dividida en dos: Inbox de entrada (WhatsApp, llamadas, formularios, manuales
 * y lo que se integre después) e Inbox de correo. Juntas cubren todos los leads, sin repetir ninguno.
 */
class LeadInboxSplitTest extends TestCase
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

        $this->lead('whatsapp', 'Lead De Whatsapp');
        $this->lead('formulario', 'Lead De Formulario');
        $this->lead('llamada', 'Lead De Llamada');
        $this->lead('manual', 'Lead Manual');
        $this->lead('correo', 'Lead De Correo', ['contact_email' => 'cliente@correo.test']);
    }

    private function lead(string $source, string $name, array $over = []): Lead
    {
        return Lead::create(array_merge([
            'source' => $source, 'status' => 'nuevo', 'contact_name' => $name, 'last_activity_at' => now(),
        ], $over));
    }

    public function test_el_inbox_de_entrada_trae_todo_menos_el_correo(): void
    {
        $r = $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['inbox' => 'entrada']))->assertOk();

        $r->assertSee('Inbox de entrada')
            ->assertSee('Lead De Whatsapp')->assertSee('Lead De Formulario')->assertSee('Lead De Llamada')->assertSee('Lead Manual')
            ->assertDontSee('Lead De Correo');
    }

    public function test_el_inbox_de_correo_solo_trae_los_leads_de_correo(): void
    {
        $r = $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['inbox' => 'correo']))->assertOk();

        $r->assertSee('Inbox de correo')->assertSee('Lead De Correo')
            ->assertDontSee('Lead De Whatsapp')->assertDontSee('Lead De Formulario')->assertDontSee('Lead Manual');
    }

    public function test_sin_indicar_bandeja_se_abre_la_de_entrada(): void
    {
        $this->actingAs($this->admin)->get(route('admin.leads-inbox.index'))->assertOk()
            ->assertSee('Inbox de entrada')->assertDontSee('Lead De Correo');
    }

    public function test_un_enlace_con_solo_el_lead_abre_la_bandeja_que_le_toca(): void
    {
        $mail = Lead::where('source', 'correo')->firstOrFail();
        $wa = Lead::where('source', 'whatsapp')->firstOrFail();

        // Avisos y redirecciones del sistema solo traen ?lead=ID.
        $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['lead' => $mail->id]))->assertOk()
            ->assertSee('Inbox de correo')->assertSee('Lead De Correo');
        $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['lead' => $wa->id]))->assertOk()
            ->assertSee('Inbox de entrada')->assertSee('Lead De Whatsapp');
    }

    public function test_los_canales_solo_estan_en_la_bandeja_de_entrada_y_ya_no_incluyen_correo(): void
    {
        $entrada = $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['inbox' => 'entrada']))->assertOk();
        $entrada->assertSee('lw-tiles', false)->assertSee('Llamadas')->assertSee('Formulario');
        $this->assertStringNotContainsString('aria-label="Correo:', $entrada->getContent());

        // Filtrar por WhatsApp dentro de la entrada sigue funcionando.
        $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['inbox' => 'entrada', 'source' => 'whatsapp']))
            ->assertOk()->assertSee('Lead De Whatsapp')->assertDontSee('Lead De Llamada');

        // En el correo no hay canales, y un ?source= ajeno no vacía la lista.
        $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['inbox' => 'correo']))->assertOk()
            ->assertDontSee('lw-tiles', false);
        $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['inbox' => 'correo', 'source' => 'whatsapp']))
            ->assertOk()->assertSee('Lead De Correo');
    }

    public function test_los_contadores_de_cada_bandeja_no_se_mezclan(): void
    {
        $this->lead('correo', 'Otro De Correo');
        $this->lead('correo', 'Ya Descartado', ['status' => 'descartado']);

        $mail = $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['inbox' => 'correo']))->assertOk();
        $this->assertSame(2, $mail->viewData('chipCounts')['todos']);
        $this->assertSame(2, $mail->viewData('chipCounts')['sin_atender']);

        $in = $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['inbox' => 'entrada']))->assertOk();
        $this->assertSame(4, $in->viewData('chipCounts')['todos']);
        $this->assertSame(['abiertos' => 4, 'nuevos' => 4], $in->viewData('inboxCounts')['entrada']);
        $this->assertSame(['abiertos' => 2, 'nuevos' => 2], $in->viewData('inboxCounts')['correo']);
    }

    public function test_el_menu_lateral_muestra_las_dos_bandejas_y_marca_la_activa(): void
    {
        $r = $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['inbox' => 'correo']))->assertOk();
        $html = $r->getContent();

        $this->assertStringContainsString('Bandeja de leads', $html);
        $this->assertMatchesRegularExpression('~class="sidebar-nav-item sidebar-nav-sub active"[^>]*href="[^"]*inbox=correo"~', $html);
        $this->assertStringContainsString('data-section="leads-inbox-entrada"', $html);

        // Desde otra pantalla del admin el menú también trae las dos entradas.
        $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['lead' => Lead::where('source', 'whatsapp')->value('id')]))
            ->assertOk()->assertSee('Inbox de entrada')->assertSee('Inbox de correo');
    }

    public function test_la_ficha_vuelve_a_la_bandeja_del_lead(): void
    {
        $mail = Lead::where('source', 'correo')->firstOrFail();

        $this->actingAs($this->admin)->get(route('admin.leads-inbox.show', $mail))->assertOk()
            ->assertSee('inbox=correo', false);
    }
}
