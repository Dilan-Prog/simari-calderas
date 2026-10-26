<?php

namespace Tests\Feature\Crm;

use App\Models\ApiClient;
use App\Models\Lead;
use App\Models\EmailTemplate;
use App\Models\LeadInboundEmail;
use App\Models\LeadOutboundEmail;
use App\Models\Setting;
use App\Models\Workflow;
use App\Models\WorkflowEnrollment;
use App\Models\WorkflowEnrollmentLog;
use App\Models\WorkflowStep;
use App\Services\AutomatableModuleRegistry;
use App\Services\LeadService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use ReflectionProperty;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeadEmailApiTest extends TestCase
{
    use RefreshDatabase;

    private function auth(array $abilities = ['leads:write']): void
    {
        Sanctum::actingAs(ApiClient::create(['name' => 'n8n', 'is_active' => true]), $abilities);
    }

    private function payload(array $over = []): array
    {
        return $over + [
            'message_id' => '<abc@mail.test>',
            'email'      => 'Cliente@Empresa.com',
            'name'       => 'Juan Pérez',
            'subject'    => 'Cotización caldera',
            'message'    => 'Necesito una caldera de 100 CC',
        ];
    }

    public function test_crea_lead_y_tarea(): void
    {
        $this->auth();

        $this->postJson('/api/v1/leads', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.duplicate', false)
            ->assertJsonPath('data.is_new_lead', true);

        $lead = Lead::first();
        $this->assertSame('correo', $lead->source);
        $this->assertSame('cliente@empresa.com', $lead->contact_email);
        $this->assertSame(1, Task::where('taskable_type', Lead::class)->where('taskable_id', $lead->id)->count());
        $this->assertSame(1, LeadInboundEmail::count());
    }

    public function test_mismo_message_id_no_duplica(): void
    {
        $this->auth();
        $this->postJson('/api/v1/leads', $this->payload())->assertCreated();

        $this->postJson('/api/v1/leads', $this->payload())
            ->assertOk()
            ->assertJsonPath('data.duplicate', true);

        $this->assertSame(1, Lead::count());
        $this->assertSame(1, Task::count());
    }

    public function test_otro_correo_del_mismo_remitente_reutiliza_el_lead_abierto(): void
    {
        $this->auth();
        $this->postJson('/api/v1/leads', $this->payload())->assertCreated();

        $this->postJson('/api/v1/leads', $this->payload(['message_id' => '<otro@mail.test>']))
            ->assertCreated()
            ->assertJsonPath('data.is_new_lead', false);

        $this->assertSame(1, Lead::count());
        $this->assertSame(2, Task::count());
    }

    public function test_asigna_la_tarea_por_correo_del_responsable(): void
    {
        $this->auth();
        $user = \App\Models\User::create([
            'first_name' => 'F', 'last_name' => 'O', 'position' => 'X', 'phone' => '5555555555',
            'email' => 'admin@equiterm.test', 'password' => bcrypt('x'), 'status' => 'active',
            'rfc' => 'XAXX010101000', 'role_id' => \App\Models\Role::create(['name_role' => 'r', 'name_role_es' => 'r'])->id,
        ]);

        $this->postJson('/api/v1/leads', $this->payload(['assigned_to_email' => 'admin@equiterm.test']))
            ->assertCreated()
            ->assertJsonPath('data.assigned_to', $user->id);
        $this->assertSame($user->id, Task::first()->assigned_to);

        $this->postJson('/api/v1/leads', $this->payload(['message_id' => '<x@y>', 'assigned_to_email' => 'nadie@x.com']))
            ->assertStatus(422);
    }

    public function test_requiere_habilidad_y_valida(): void
    {
        $this->auth(['deals:read']);
        $this->postJson('/api/v1/leads', $this->payload())->assertForbidden();

        $this->auth();
        $this->postJson('/api/v1/leads', ['email' => 'no-es-correo'])->assertStatus(422);
    }

    // ── POST /api/v1/leads/{lead}/send-email ────────────────────────────

    private function mailReady(): void
    {
        (new ReflectionProperty(Setting::class, 'cache'))->setValue(null, []);
        config([
            'mail.default'      => 'array',
            'mail.from.address' => 'administracion@equiterm.test',
            'mail.from.name'    => 'Equiterm Industries',
        ]);
    }

    /** Lead que escribió por correo (con tarea abierta y correo entrante). */
    private function leadFromEmail(string $email = 'cliente@empresa.com'): Lead
    {
        $r = app(LeadService::class)->captureFromEmail([
            'message_id' => '<in-' . uniqid() . '@mail.test>', 'email' => $email, 'name' => 'Carlos Herrera',
            'subject' => 'Cotizar caldera', 'message' => 'Necesito una caldera',
        ]);

        return Lead::findOrFail($r['lead_id']);
    }

    private function sentCount(): int
    {
        return count(app('mail.manager')->mailer('array')->getSymfonyTransport()->messages()->all());
    }

    public function test_send_email_exige_token_y_habilidad(): void
    {
        $this->mailReady();
        $lead = $this->leadFromEmail();

        $this->postJson("/api/v1/leads/{$lead->id}/send-email", ['subject' => 'Hola', 'body' => 'Texto'])->assertUnauthorized();

        $this->auth(['deals:read']);
        $this->postJson("/api/v1/leads/{$lead->id}/send-email", ['subject' => 'Hola', 'body' => 'Texto'])->assertForbidden();
        $this->assertSame(0, LeadOutboundEmail::count());
    }

    public function test_send_email_envia_y_registra_como_plataforma_sin_usuario(): void
    {
        $this->mailReady();
        $lead = $this->leadFromEmail();
        $task = Task::where('taskable_type', Lead::class)->where('taskable_id', $lead->id)->firstOrFail();
        $before = $this->sentCount();
        $this->auth();

        $res = $this->postJson("/api/v1/leads/{$lead->id}/send-email", [
            'subject' => 'Acuse de recibo', 'body' => "Recibimos tu solicitud.\nGracias.",
        ])->assertCreated()
            ->assertJsonPath('data.lead_id', $lead->id)
            ->assertJsonPath('data.task_id', $task->id);

        $this->assertSame($before + 1, $this->sentCount());

        $email = LeadOutboundEmail::firstOrFail();
        $this->assertSame($email->id, $res->json('data.outbound_email_id'));
        $this->assertSame($email->message_id, $res->json('data.message_id'));
        $this->assertSame('platform', $email->source);
        $this->assertNull($email->sent_by_user_id);
        $this->assertSame('cliente@empresa.com', $email->to_email);
        $this->assertSame('Acuse de recibo', $email->subject);
        $this->assertSame($lead->id, $email->lead_id);

        // La tarea sigue abierta salvo que se pida close_task.
        $this->assertNotSame('closed', $task->fresh()->status);
        $this->assertSame('contactado', $lead->fresh()->status);
    }

    public function test_send_email_usa_el_buzon_de_la_bandeja_si_esta_configurado(): void
    {
        $this->mailReady();
        config([
            'mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.equiterm.test', 'mail.from.address' => 'no-reply@equiterm.test',
            'mail.lead_inbox_transport' => 'array',
        ]);
        Setting::set('mail.inbox.from_address', 'administracion@equiterm.test');
        Setting::set('mail.inbox.username', 'administracion@equiterm.test');
        Setting::set('mail.inbox.password', Crypt::encryptString('secreta-123'));
        $lead = $this->leadFromEmail();
        $this->auth();

        $this->postJson("/api/v1/leads/{$lead->id}/send-email", ['subject' => 'Acuse', 'body' => 'Texto'])->assertCreated();

        $messages = app('mail.manager')->mailer('lead_inbox')->getSymfonyTransport()->messages()->all();
        $this->assertCount(1, $messages);
        $raw = $messages[0]->getOriginalMessage();
        $this->assertSame('administracion@equiterm.test', $raw->getFrom()[0]->getAddress());
        $this->assertSame('administracion@equiterm.test', $raw->getReplyTo()[0]->getAddress());
        $this->assertSame('administracion@equiterm.test', LeadOutboundEmail::firstOrFail()->from_email);
    }

    public function test_send_email_close_task_cierra_la_tarea(): void
    {
        $this->mailReady();
        $lead = $this->leadFromEmail();
        $this->auth();

        $this->postJson("/api/v1/leads/{$lead->id}/send-email", [
            'subject' => 'Listo', 'body' => 'Texto', 'close_task' => true,
        ])->assertCreated();

        $this->assertSame('closed', Task::where('taskable_type', Lead::class)->where('taskable_id', $lead->id)->firstOrFail()->status);
    }

    public function test_send_email_acepta_to_y_cc_explicitos(): void
    {
        $this->mailReady();
        $lead = $this->leadFromEmail();
        $this->auth();

        $this->postJson("/api/v1/leads/{$lead->id}/send-email", [
            'to' => 'otro@empresa.com', 'cc' => ['jefe@empresa.com'], 'subject' => 'Hola', 'body' => 'Texto',
        ])->assertCreated();

        $email = LeadOutboundEmail::firstOrFail();
        $this->assertSame('otro@empresa.com', $email->to_email);
        $this->assertSame(['otro@empresa.com', 'jefe@empresa.com'], $email->recipients);
    }

    public function test_send_email_con_plantilla_toma_asunto_y_cuerpo(): void
    {
        $this->mailReady();
        $lead = $this->leadFromEmail();
        $tpl = EmailTemplate::create([
            'name' => 'Acuse', 'type' => 'custom',
            'subject' => 'Hola {{contact.name}}, recibimos tu solicitud',
            'html_body' => '<p>Gracias {{contact.name}}.</p><p>Te contactamos pronto.</p>',
        ]);
        $this->auth();

        $this->postJson("/api/v1/leads/{$lead->id}/send-email", ['template_id' => $tpl->id])->assertCreated();

        $email = LeadOutboundEmail::firstOrFail();
        $this->assertSame('Hola Carlos Herrera, recibimos tu solicitud', $email->subject);
        $this->assertStringContainsString('Gracias Carlos Herrera.', $email->body);
        $this->assertStringNotContainsString('<p>', $email->body);

        // Un asunto explícito tiene prioridad sobre el de la plantilla.
        $this->postJson("/api/v1/leads/{$lead->id}/send-email", ['template_id' => $tpl->id, 'subject' => 'Asunto propio'])->assertCreated();
        $this->assertSame('Asunto propio', LeadOutboundEmail::latest('id')->firstOrFail()->subject);
    }

    public function test_send_email_valida_asunto_y_cuerpo_sin_plantilla(): void
    {
        $this->mailReady();
        $lead = $this->leadFromEmail();
        $this->auth();

        $this->postJson("/api/v1/leads/{$lead->id}/send-email", ['subject' => 'Solo asunto'])->assertStatus(422)->assertJsonValidationErrors('body');
        $this->postJson("/api/v1/leads/{$lead->id}/send-email", ['subject' => 'x', 'body' => 'y', 'template_id' => 999999])->assertStatus(422);
        $this->assertSame(0, LeadOutboundEmail::count());
    }

    public function test_send_email_422_si_el_smtp_no_esta_configurado(): void
    {
        $this->mailReady();
        $lead = $this->leadFromEmail();
        $this->auth();

        config(['mail.from.address' => '']);
        $this->postJson("/api/v1/leads/{$lead->id}/send-email", ['subject' => 'Hola', 'body' => 'Texto'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'No hay un remitente configurado. Captúralo en Integraciones > Correo.');

        config(['mail.from.address' => 'administracion@equiterm.test', 'mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1']);
        $this->postJson("/api/v1/leads/{$lead->id}/send-email", ['subject' => 'Hola', 'body' => 'Texto'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'El correo saliente (SMTP) no está configurado. Configúralo en Integraciones > Correo.');

        $this->assertSame(0, LeadOutboundEmail::count());
    }

    public function test_send_email_422_si_el_lead_no_tiene_correo(): void
    {
        $this->mailReady();
        $lead = Lead::create(['source' => 'llamada', 'status' => 'nuevo', 'contact_name' => 'Sin correo', 'contact_phone' => '5551234567']);
        $this->auth();

        $this->postJson("/api/v1/leads/{$lead->id}/send-email", ['subject' => 'Hola', 'body' => 'Texto'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'El destinatario no es un correo válido.');
        $this->assertSame(0, LeadOutboundEmail::count());

        // Indicando el destinatario sí se puede enviar.
        $this->postJson("/api/v1/leads/{$lead->id}/send-email", ['to' => 'alguien@empresa.com', 'subject' => 'Hola', 'body' => 'Texto'])->assertCreated();
    }

    public function test_send_email_lead_inexistente_404(): void
    {
        $this->mailReady();
        $this->auth();

        $this->postJson('/api/v1/leads/987654/send-email', ['subject' => 'Hola', 'body' => 'Texto'])->assertNotFound();
    }

    // ── Automatizaciones: lead / correo recibido / correo enviado ───────

    private function workflowWithWebhook(string $type, array $trigger, ?array $condition = null): Workflow
    {
        $workflow = Workflow::create([
            'name' => 'Prueba ' . $type, 'type' => $type, 'enrollment_trigger' => $trigger,
            'is_active' => true, 'reenrollment_allowed' => false,
        ]);

        $parent = null;
        $branch = null;
        if ($condition) {
            $parent = WorkflowStep::create([
                'workflow_id' => $workflow->id, 'order' => 0, 'step_type' => 'condition', 'branch_condition' => $condition,
            ]);
            $branch = 'yes';
        }

        WorkflowStep::create([
            'workflow_id' => $workflow->id, 'parent_step_id' => $parent?->id, 'branch_key' => $branch, 'order' => 0,
            'step_type' => 'action', 'action_type' => 'call_webhook',
            'action_config' => [
                'url' => 'https://n8n.example.test/webhook/correo', 'method' => 'POST', 'headers' => [],
                'body' => [
                    ['key' => 'lead_id', 'value' => '{{ lead_id }}'],
                    ['key' => 'email', 'value' => '{{ lead.contact_email }}'],
                    ['key' => 'subject', 'value' => '{{ subject }}'],
                ],
            ],
        ]);

        return $workflow;
    }

    public function test_el_catalogo_de_automatizaciones_lista_lead_y_correos(): void
    {
        $registry = app(AutomatableModuleRegistry::class);

        foreach (['lead', 'lead_inbound_email', 'lead_outbound_email'] as $type) {
            $this->assertSame('CRM', $registry->group($type), $type);
            $this->assertNotEmpty($registry->fieldsFor($type), $type);
        }

        $this->assertSame('Correo recibido de lead', $registry->label('lead_inbound_email'));
        $this->assertSame('Correo enviado a lead', $registry->label('lead_outbound_email'));
        $this->assertSame(['lead'], $registry->entry('lead_inbound_email')['relations']);
        $this->assertSame(['lead'], $registry->entry('lead_outbound_email')['relations']);
        $this->assertContains('lead_id', $registry->fieldsFor('lead_inbound_email'));
        $this->assertContains('source', $registry->fieldsFor('lead_outbound_email'));
        $this->assertTrue($registry->entry('lead')['supports_stale']);
        $this->assertSame('last_activity_at', $registry->entry('lead')['stale_field']);
        $this->assertSame('customer', $registry->entry('lead')['customer_relation']);
        $this->assertSame('lead_inbound_email', $registry->typeForModel(new LeadInboundEmail()));
        $this->assertSame('lead_outbound_email', $registry->typeForModel(new LeadOutboundEmail()));
        $this->assertSame('lead', $registry->typeForModel(new Lead()));
    }

    public function test_un_correo_recibido_dispara_el_workflow_y_resuelve_tokens_del_lead(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);
        $workflow = $this->workflowWithWebhook('lead_inbound_email', ['event' => 'created']);
        $this->auth();

        $this->postJson('/api/v1/leads', $this->payload())->assertCreated();

        $lead = Lead::firstOrFail();
        $this->assertSame(1, WorkflowEnrollment::where('workflow_id', $workflow->id)->count());
        Http::assertSent(fn ($r) => $r->url() === 'https://n8n.example.test/webhook/correo'
            && $r['lead_id'] == $lead->id
            && $r['email'] === 'cliente@empresa.com'
            && $r['subject'] === 'Cotización caldera');

        // Reenviar el mismo Message-ID no crea otro correo y por tanto no vuelve a disparar.
        $this->postJson('/api/v1/leads', $this->payload())->assertOk();
        $this->assertSame(1, WorkflowEnrollment::where('workflow_id', $workflow->id)->count());
    }

    public function test_un_correo_enviado_por_la_api_dispara_workflows_de_correo_enviado(): void
    {
        $this->mailReady();
        Http::fake(['*' => Http::response(['ok' => true], 200)]);
        $lead = $this->leadFromEmail();
        $workflow = $this->workflowWithWebhook('lead_outbound_email', ['event' => 'created']);
        $this->auth();

        $this->postJson("/api/v1/leads/{$lead->id}/send-email", ['subject' => 'Hola', 'body' => 'Texto'])->assertCreated();

        $this->assertSame(1, WorkflowEnrollment::where('workflow_id', $workflow->id)->count());
        Http::assertSentCount(1);
    }

    public function test_condicion_por_source_evita_el_bucle_con_correos_de_la_plataforma(): void
    {
        // Sin guarda de recursión en el motor, un workflow de "correo enviado" que envíe correo por la API
        // se reinscribiría con cada envío (cada correo es una fila nueva). La condición source = external lo corta.
        $this->mailReady();
        Http::fake(['*' => Http::response(['ok' => true], 200)]);
        $lead = $this->leadFromEmail();
        $workflow = $this->workflowWithWebhook('lead_outbound_email', ['event' => 'created'], ['field' => 'source', 'operator' => 'equals', 'value' => 'external']);
        $this->auth();

        $this->postJson("/api/v1/leads/{$lead->id}/send-email", ['subject' => 'Hola', 'body' => 'Texto'])->assertCreated();

        Http::assertNothingSent();
        $this->assertSame('completed', WorkflowEnrollment::where('workflow_id', $workflow->id)->firstOrFail()->status);

        // Un correo registrado desde Enviados (source external) sí pasa la condición.
        $this->postJson('/api/v1/leads/outbound-email', [
            'message_id' => '<sent-1@mail.test>', 'to' => 'cliente@empresa.com', 'subject' => 'Seguimiento', 'body' => 'Texto',
        ])->assertCreated();
        Http::assertSentCount(1);
    }

    public function test_el_tick_de_inactividad_inscribe_solo_leads_sin_actividad_y_sin_responder(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);
        $workflow = $this->workflowWithWebhook('lead', ['event' => 'stale', 'hours' => 24, 'field' => 'last_activity_at'], ['field' => 'status', 'operator' => 'equals', 'value' => 'nuevo']);

        $old = Lead::create(['source' => 'correo', 'status' => 'nuevo', 'contact_email' => 'a@x.com', 'last_activity_at' => now()->subDays(2)]);
        $closed = Lead::create(['source' => 'correo', 'status' => 'descartado', 'contact_email' => 'b@x.com', 'last_activity_at' => now()->subDays(2)]);
        $recent = Lead::create(['source' => 'correo', 'status' => 'nuevo', 'contact_email' => 'c@x.com', 'last_activity_at' => now()->subHours(2)]);

        $this->artisan('automatable-modules:tick')->assertSuccessful();

        $enrolled = WorkflowEnrollment::where('workflow_id', $workflow->id)->pluck('enrollable_id')->all();
        $this->assertEqualsCanonicalizing([$old->id, $closed->id], $enrolled);
        // La condición por estado deja pasar solo al lead "nuevo".
        Http::assertSentCount(1);

        // Un segundo tick no repite (reenrollment_allowed = false).
        $this->artisan('automatable-modules:tick')->assertSuccessful();
        Http::assertSentCount(1);
        $this->assertNotContains($recent->id, $enrolled);
    }

    public function test_un_lead_nuevo_dispara_workflows_de_lead(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);
        $workflow = $this->workflowWithWebhook('lead', ['event' => 'created']);

        $lead = Lead::create(['source' => 'manual', 'status' => 'nuevo', 'contact_name' => 'Ana', 'contact_email' => 'ana@empresa.com']);

        $this->assertSame(1, WorkflowEnrollment::where('workflow_id', $workflow->id)->where('enrollable_id', $lead->id)->count());
        Http::assertSentCount(1);
    }
}
