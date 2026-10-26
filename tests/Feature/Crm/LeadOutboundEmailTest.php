<?php

namespace Tests\Feature\Crm;

use App\Models\ApiClient;
use App\Models\Lead;
use App\Models\LeadOutboundEmail;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Services\LeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeadOutboundEmailTest extends TestCase
{
    use RefreshDatabase;

    private function auth(array $abilities = ['leads:write']): void
    {
        Sanctum::actingAs(ApiClient::create(['name' => 'n8n', 'is_active' => true]), $abilities);
    }

    /** Cliente que escribio pidiendo cotizacion: crea lead + tarea (flujo de correos entrantes). */
    private function inbound(string $email = 'cliente@empresa.com', string $messageId = '<in-1@mail>'): array
    {
        return app(LeadService::class)->captureFromEmail([
            'message_id' => $messageId, 'email' => $email, 'name' => 'Carlos Herrera', 'subject' => 'Cotizar caldera', 'message' => 'Necesito una caldera',
        ]);
    }

    private function payload(array $over = []): array
    {
        return $over + [
            'message_id' => '<out-1@mail>',
            'from_email' => 'administracion@equitermindustries.com.mx',
            'to'         => 'Carlos Herrera <Cliente@Empresa.com>',
            'subject'    => 'RE: Cotizar caldera',
            'body'       => "Buenas tardes,\nAdjuntamos la cotizacion.",
            'sent_at'    => now()->toIso8601String(),
        ];
    }

    public function test_correo_a_cliente_con_tarea_se_registra_en_el_historial(): void
    {
        $in = $this->inbound();
        $this->auth();

        $this->postJson('/api/v1/leads/outbound-email', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.matched', true)
            ->assertJsonPath('data.duplicate', false)
            ->assertJsonPath('data.task_id', $in['task_id']);

        $mail = LeadOutboundEmail::first();
        $this->assertSame($in['lead_id'], $mail->lead_id);
        $this->assertSame('cliente@empresa.com', $mail->to_email);
        $this->assertStringContainsString('Adjuntamos la cotizacion', $mail->body);

        $event = Task::find($in['task_id'])->events()->where('type', 'sent_email')->first();
        $this->assertNotNull($event);
        $this->assertSame($mail->id, $event->outbound_email_id);
        $this->assertStringContainsString('Correo enviado a cliente@empresa.com: RE: Cotizar caldera', $event->title);
        $this->assertNotNull(Lead::find($in['lead_id'])->first_contact_at);
    }

    public function test_correo_a_alguien_sin_tarea_se_ignora_y_no_se_guarda(): void
    {
        $this->inbound();
        $this->auth();

        $this->postJson('/api/v1/leads/outbound-email', $this->payload(['to' => 'proveedor@otra.com']))
            ->assertOk()
            ->assertJsonPath('data.matched', false);

        $this->assertSame(0, LeadOutboundEmail::count());
    }

    public function test_lead_sin_tarea_no_cuenta(): void
    {
        $lead = app(LeadService::class)->capture(['source' => 'manual', 'name' => 'Sin tarea', 'email' => 'sintarea@empresa.com']);
        $this->assertSame(0, $lead->tasks()->count());
        $this->auth();

        $this->postJson('/api/v1/leads/outbound-email', $this->payload(['to' => 'sintarea@empresa.com']))
            ->assertOk()->assertJsonPath('data.matched', false);
    }

    public function test_mismo_message_id_no_duplica(): void
    {
        $this->inbound();
        $this->auth();
        $this->postJson('/api/v1/leads/outbound-email', $this->payload())->assertCreated();

        $this->postJson('/api/v1/leads/outbound-email', $this->payload())
            ->assertOk()->assertJsonPath('data.duplicate', true);

        $this->assertSame(1, LeadOutboundEmail::count());
        $this->assertSame(1, Task::first()->events()->where('type', 'sent_email')->count());
    }

    public function test_respuesta_en_el_hilo_se_liga_a_la_tarea_original_aunque_el_destinatario_sea_otro(): void
    {
        $in = $this->inbound('cliente@empresa.com', '<hilo-1@mail>');
        $this->auth();

        $this->postJson('/api/v1/leads/outbound-email', $this->payload(['to' => 'compras@empresa.com', 'in_reply_to' => '<hilo-1@mail>']))
            ->assertCreated()->assertJsonPath('data.task_id', $in['task_id']);
    }

    public function test_requiere_habilidad_y_valida(): void
    {
        $this->auth(['deals:read']);
        $this->postJson('/api/v1/leads/outbound-email', $this->payload())->assertForbidden();

        $this->auth();
        $this->postJson('/api/v1/leads/outbound-email', ['subject' => 'x'])->assertStatus(422);
    }

    public function test_las_entidades_html_del_cuerpo_se_muestran_como_texto_normal(): void
    {
        $this->inbound();
        $this->auth();
        $this->postJson('/api/v1/leads/outbound-email', $this->payload([
            'body' => 'Para: Ana &lt;ana@empresa.com&gt; Enviado:&nbsp;lunes 11:59 a.&nbsp;m. Tom &amp; Jerry',
        ]))->assertCreated();

        $email = LeadOutboundEmail::firstOrFail();

        $this->assertSame('Para: Ana <ana@empresa.com> Enviado: lunes 11:59 a. m. Tom & Jerry', $email->body);
        $this->assertNull((new LeadOutboundEmail())->body);
    }

    public function test_la_pagina_de_la_tarea_muestra_el_correo_enviado(): void
    {
        $this->withoutVite();
        $in = $this->inbound();
        $this->auth();
        $this->postJson('/api/v1/leads/outbound-email', $this->payload())->assertCreated();

        $role = Role::firstOrCreate(['name_role' => 'Administrador'], ['name_role_es' => 'Administrador']);
        $admin = User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'A', 'phone' => '5555555555',
            'email' => 'u-' . uniqid() . '@example.com', 'password' => bcrypt('x'), 'status' => 'active',
            'rfc' => 'RFC' . strtoupper(substr(uniqid(), -8)) . random_int(10, 99), 'role_id' => $role->id,
        ]);
        $this->app['auth']->forgetGuards();

        $this->actingAs($admin)->get(route('admin.tasks.show', $in['task_id']))
            ->assertOk()
            ->assertSee('Ver correo enviado')
            ->assertSee('Adjuntamos la cotizacion')
            ->assertSee('administracion@equitermindustries.com.mx');

        $this->actingAs($admin)->get(route('admin.leads-inbox.index', ['lead' => $in['lead_id']]))
            ->assertOk()->assertSee('Correo enviado: RE: Cotizar caldera');
    }
}
