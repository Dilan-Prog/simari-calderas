<?php

namespace Tests\Feature\Crm;

use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use App\Models\WhatsappAccount;
use App\Services\LeadService;
use App\Services\WhatsappService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadLiveTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name_role' => 'Administrador'], ['name_role_es' => 'Administrador']);

        return User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'A', 'phone' => '5555555555',
            'email' => 'u-' . uniqid() . '@example.com', 'password' => bcrypt('x'), 'status' => 'active',
            'rfc' => 'RFC' . strtoupper(substr(uniqid(), -8)) . random_int(10, 99), 'role_id' => $role->id,
        ]);
    }

    private function fp(User $admin): string
    {
        $res = $this->actingAs($admin)->getJson(route('admin.leads-inbox.live'))->assertOk()->assertJsonStructure(['fp', 'at']);

        return $res->json('fp');
    }

    public function test_la_huella_es_estable_sin_actividad_y_cambia_con_un_mensaje_nuevo(): void
    {
        $admin = $this->admin();
        $account = WhatsappAccount::create(['name' => 'Meta', 'phone_number' => '+1 555 000 0000', 'connection_type' => 'cloud_api', 'is_active' => true]);
        $wa = app(WhatsappService::class);
        $wa->ingestInboundMessage($account, '5215512345678', ['content' => 'Hola']);

        $first = $this->fp($admin);
        $this->assertSame($first, $this->fp($admin), 'sin actividad la huella no debe cambiar');

        $wa->ingestInboundMessage($account, '5215512345678', ['content' => 'Segundo mensaje']);

        $this->assertNotSame($first, $this->fp($admin));
    }

    public function test_cambia_con_notas_correos_y_nuevos_leads(): void
    {
        $admin = $this->admin();
        $svc = app(LeadService::class);
        $lead = $svc->capture(['source' => 'manual', 'name' => 'Marta', 'phone' => '5512345678']);

        $a = $this->fp($admin);
        $this->actingAs($admin)->post(route('admin.leads-inbox.notes.store', $lead), ['body' => 'Una nota']);
        $b = $this->fp($admin);
        $this->assertNotSame($a, $b);

        $svc->captureFromEmail(['message_id' => '<x@y>', 'email' => 'nuevo@cliente.com', 'subject' => 'Cotizar']);
        $this->assertNotSame($b, $this->fp($admin));
    }

    public function test_requiere_sesion(): void
    {
        $this->getJson(route('admin.leads-inbox.live'))->assertUnauthorized();
    }

    public function test_no_choca_con_las_rutas_de_un_lead(): void
    {
        $this->assertStringEndsWith('/leads/live', route('admin.leads-inbox.live'));
        $lead = Lead::create(['source' => 'manual', 'status' => 'nuevo', 'contact_name' => 'X']);
        $this->assertStringEndsWith('/leads/' . $lead->id . '/notas', route('admin.leads-inbox.notes.store', $lead));
    }
}
