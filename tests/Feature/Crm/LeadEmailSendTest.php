<?php

namespace Tests\Feature\Crm;

use App\Http\Controllers\Backend\Crm\LeadInboxController;
use App\Models\Customer;
use App\Models\EmailTemplate;
use App\Models\Lead;
use App\Models\LeadInboundEmail;
use App\Models\LeadOutboundEmail;
use App\Models\Quote;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use App\Services\LeadMailService;
use App\Services\LeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;
use Exception;
use Illuminate\Support\Facades\Mail;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use Tests\TestCase;

/**
 * Responder por correo a un lead desde la bandeja: SMTP (array en pruebas), hilo, adjuntos,
 * registro del correo saliente, tarea y estado del lead. Nunca se toca un SMTP real.
 */
class LeadEmailSendTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        // Setting::get memoiza en una propiedad estática que sobrevive entre pruebas.
        (new ReflectionProperty(Setting::class, 'cache'))->setValue(null, []);

        config([
            'mail.default'      => 'array',
            'mail.from.address' => 'administracion@equiterm.test',
            'mail.from.name'    => 'Equiterm Industries',
        ]);

        $role = Role::firstOrCreate(['name_role' => 'Administrador'], ['name_role_es' => 'Administrador']);
        $this->admin = User::create([
            'first_name' => 'Laura', 'last_name' => 'Mendoza', 'position' => 'Administrador',
            'phone' => '5555555555', 'email' => 'u-' . uniqid() . '@example.com',
            'password' => bcrypt('password'), 'status' => 'active', 'rfc' => 'RFC' . strtoupper(substr(uniqid(), -8)), 'role_id' => $role->id,
        ]);
    }

    /** Cliente que escribió por correo: lead + tarea abierta + correo entrante con message_id. */
    private function inbound(string $email = 'cliente@empresa.com', string $messageId = '<in-1@mail.test>', string $subject = 'Cotizar caldera'): array
    {
        return app(LeadService::class)->captureFromEmail([
            'message_id' => $messageId, 'email' => $email, 'name' => 'Carlos Herrera', 'subject' => $subject, 'message' => 'Necesito una caldera',
        ]);
    }

    private function sentMessages(): array
    {
        return app('mail.manager')->mailer('array')->getSymfonyTransport()->messages()->all();
    }

    private function payload(array $over = []): array
    {
        return $over + ['to' => 'cliente@empresa.com', 'subject' => 'Re: Cotizar caldera', 'body' => "Buenas tardes,\nAdjunto la información <b>solicitada</b>."];
    }

    // ── Servicio ─────────────────────────────────────────────────────────

    public function test_envia_y_registra_el_correo_con_hilo_y_firma(): void
    {
        Setting::set('mail.signature', "Equiterm Industries\nTel. 449 000 0000");
        $in = $this->inbound();
        $lead = Lead::find($in['lead_id']);

        $email = app(LeadMailService::class)->send($lead, $this->payload(['cc' => ['Copia@Empresa.com']]), $this->admin);

        // Mensaje real que salió por el transporte.
        $messages = $this->sentMessages();
        $this->assertCount(1, $messages);
        $raw = $messages[0]->getOriginalMessage();
        $headers = $raw->getHeaders();
        $this->assertSame('<in-1@mail.test>', $headers->get('In-Reply-To')->getBodyAsString());
        $this->assertSame('<in-1@mail.test>', $headers->get('References')->getBodyAsString());
        $this->assertSame($email->message_id, $headers->get('Message-ID')->getBodyAsString());
        $this->assertMatchesRegularExpression('/^<[0-9a-f-]{36}@equiterm\.test>$/', $email->message_id);
        $this->assertSame('administracion@equiterm.test', $raw->getFrom()[0]->getAddress());
        $this->assertSame('administracion@equiterm.test', $raw->getReplyTo()[0]->getAddress());
        $this->assertSame('cliente@empresa.com', $raw->getTo()[0]->getAddress());
        $this->assertSame('copia@empresa.com', $raw->getCc()[0]->getAddress());
        $this->assertSame('Re: Cotizar caldera', $raw->getSubject());
        $this->assertStringContainsString('Tel. 449 000 0000', $raw->getTextBody());
        $this->assertStringContainsString('&lt;b&gt;solicitada&lt;/b&gt;', $raw->getHtmlBody());
        $this->assertStringContainsString('<br>', $raw->getHtmlBody());
        $this->assertStringContainsString('Tel. 449 000 0000', $raw->getHtmlBody());

        // Registro: el cuerpo guardado es lo que tecleó el usuario, sin firma.
        $this->assertSame(LeadOutboundEmail::SOURCE_PLATFORM, $email->source);
        $this->assertSame($this->admin->id, $email->sent_by_user_id);
        $this->assertSame('<in-1@mail.test>', $email->in_reply_to);
        $this->assertSame('administracion@equiterm.test', $email->from_email);
        $this->assertSame('cliente@empresa.com', $email->to_email);
        $this->assertSame(['cliente@empresa.com', 'copia@empresa.com'], $email->recipients);
        $this->assertSame($in['task_id'], $email->task_id);
        $this->assertStringNotContainsString('Tel. 449', (string) $email->body);
        $this->assertStringContainsString('Adjunto la información', (string) $email->body);
        $this->assertNotNull($email->sent_at);
    }

    public function test_registra_el_evento_de_la_tarea_y_la_deja_abierta_si_no_se_pide_cerrarla(): void
    {
        $in = $this->inbound();
        $lead = Lead::find($in['lead_id']);

        $email = app(LeadMailService::class)->send($lead, $this->payload(), $this->admin);

        $task = Task::find($in['task_id']);
        $event = $task->events()->where('type', 'sent_email')->first();
        $this->assertNotNull($event);
        $this->assertSame($email->id, $event->outbound_email_id);
        $this->assertSame($this->admin->id, $event->user_id);
        $this->assertSame('Correo enviado a cliente@empresa.com: Re: Cotizar caldera', $event->title);
        $this->assertSame('open', $task->status);
        $this->assertNull($task->closed_at);
    }

    public function test_cierra_la_tarea_solo_cuando_se_pide(): void
    {
        $in = $this->inbound();
        $lead = Lead::find($in['lead_id']);

        app(LeadMailService::class)->send($lead, $this->payload(['close_task' => true]), $this->admin);

        $task = Task::find($in['task_id']);
        $this->assertSame('closed', $task->status);
        $this->assertNotNull($task->closed_at);
        $status = $task->events()->where('type', 'status')->first();
        $this->assertNotNull($status);
        $this->assertSame($this->admin->id, $status->user_id);
        $this->assertStringContainsString('Cerrada', $status->title);
    }

    public function test_sin_correo_entrante_usa_la_ultima_tarea_abierta_o_ninguna(): void
    {
        $lead = Lead::create(['source' => 'formulario', 'status' => 'nuevo', 'contact_name' => 'Ana', 'contact_email' => 'ana@empresa.com', 'last_activity_at' => now()]);

        // Sin tarea: se envía y se registra sin task_id ni evento.
        $a = app(LeadMailService::class)->send($lead, $this->payload(['to' => 'ana@empresa.com', 'close_task' => true]), $this->admin);
        $this->assertNull($a->task_id);
        $this->assertNull($a->in_reply_to);
        $headers = $this->sentMessages()[0]->getOriginalMessage()->getHeaders();
        $this->assertFalse($headers->has('In-Reply-To'));

        $old = $lead->tasks()->create(['title' => 'Vieja', 'status' => 'open']);
        $new = $lead->tasks()->create(['title' => 'Nueva', 'status' => 'open']);
        $b = app(LeadMailService::class)->send($lead->fresh(), $this->payload(['to' => 'ana@empresa.com']), $this->admin);
        $this->assertSame($new->id, $b->task_id);
        $this->assertSame('open', $old->fresh()->status);
    }

    public function test_marca_el_lead_como_contactado_y_actualiza_su_actividad(): void
    {
        $in = $this->inbound();
        $lead = Lead::find($in['lead_id']);
        $lead->forceFill(['status' => 'nuevo', 'first_contact_at' => null, 'reply_pending_at' => now()])->save();

        app(LeadMailService::class)->send($lead, $this->payload(), $this->admin);

        $lead->refresh();
        $this->assertSame('contactado', $lead->status);
        $this->assertNotNull($lead->first_contact_at);
        $this->assertNotNull($lead->last_activity_at);
        $this->assertNull($lead->reply_pending_at);
    }

    public function test_sin_smtp_configurado_se_rechaza_y_no_se_registra_nada(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'localhost']);
        $in = $this->inbound();
        $lead = Lead::find($in['lead_id']);

        try {
            app(LeadMailService::class)->send($lead, $this->payload(), $this->admin);
            $this->fail('Debió rechazar el envío.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('SMTP', $e->getMessage());
        }

        $this->assertSame(0, LeadOutboundEmail::count());
        $this->assertSame(0, Task::find($in['task_id'])->events()->where('type', 'sent_email')->count());
        $this->assertCount(0, $this->sentMessages());
    }

    public function test_destinatario_invalido_se_rechaza(): void
    {
        $lead = Lead::find($this->inbound()['lead_id']);

        $this->expectException(RuntimeException::class);
        app(LeadMailService::class)->send($lead, $this->payload(['to' => 'no-es-correo']), $this->admin);
    }

    public function test_si_el_transporte_falla_no_se_registra_nada(): void
    {
        $in = $this->inbound();
        $lead = Lead::find($in['lead_id']);
        $lead->forceFill(['status' => 'nuevo', 'first_contact_at' => null])->save();

        // El servidor SMTP rechaza/pierde la conexión.
        Mail::shouldReceive('to')->once()->andThrow(new Exception('Connection refused'));

        try {
            app(LeadMailService::class)->send($lead, $this->payload(), $this->admin);
            $this->fail('Debió fallar el envío.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('No se pudo enviar', $e->getMessage());
        }

        $this->assertSame(0, LeadOutboundEmail::count());
        $this->assertSame(0, Task::find($in['task_id'])->events()->where('type', 'sent_email')->count());
        $this->assertSame('nuevo', $lead->fresh()->status);
        $this->assertNull($lead->fresh()->first_contact_at);
    }

    public function test_adjunta_el_pdf_de_las_cotizaciones_del_cliente_del_lead(): void
    {
        $customer = Customer::create([
            'first_name' => 'Ana', 'last_name' => 'Lopez', 'email' => 'ana@empresa.com', 'company' => 'Empresa', 'phone' => '5512345678',
            'document_type' => 'ine', 'source' => 'admin', 'status' => 'active',
        ]);
        $quote = Quote::create([
            'quote_number' => 'COT-2026-0042', 'created_by_user_id' => $this->admin->id, 'customer_id' => $customer->id, 'status' => 'sent', 'guest_name' => 'Ana Lopez',
            'currency' => 'MXN', 'subtotal' => 1000, 'discount_total' => 0, 'tax_rate' => 16, 'tax_total' => 160, 'total' => 1160,
            'notes' => 'NOTA INTERNA SECRETA',
        ]);
        $lead = Lead::create(['source' => 'correo', 'status' => 'nuevo', 'contact_name' => 'Ana', 'contact_email' => 'ana@empresa.com', 'customer_id' => $customer->id, 'last_activity_at' => now()]);

        $email = app(LeadMailService::class)->send($lead, $this->payload([
            'to'        => 'ana@empresa.com',
            'quote_ids' => [$quote->id],
            'files'     => [UploadedFile::fake()->create('ficha.pdf', 20, 'application/pdf')],
        ]), $this->admin);

        $raw = $this->sentMessages()[0]->getOriginalMessage();
        $names = array_map(fn ($a) => $a->getFilename(), $raw->getAttachments());
        $this->assertEqualsCanonicalizing(['COT-2026-0042.pdf', 'ficha.pdf'], $names);
        foreach ($raw->getAttachments() as $a) {
            if ($a->getFilename() === 'COT-2026-0042.pdf') {
                $this->assertStringStartsWith('%PDF', $a->getBody());
            }
        }
        $this->assertEqualsCanonicalizing(['COT-2026-0042.pdf', 'ficha.pdf'], $email->attachments);
        $this->assertSame('NOTA INTERNA SECRETA', $quote->fresh()->notes, 'Las notas internas no se tocan en BD.');
    }

    public function test_una_cotizacion_de_otro_cliente_no_se_adjunta(): void
    {
        $mk = fn (string $email) => Customer::create([
            'first_name' => 'X', 'last_name' => 'Y', 'email' => $email, 'company' => 'E', 'phone' => '5512345678',
            'document_type' => 'ine', 'source' => 'admin', 'status' => 'active',
        ]);
        $mine = $mk('mio@empresa.com');
        $other = $mk('otro@empresa.com');
        $quote = Quote::create(['quote_number' => 'COT-OTRO', 'created_by_user_id' => $this->admin->id, 'customer_id' => $other->id, 'status' => 'sent', 'guest_name' => 'Otro', 'currency' => 'MXN', 'subtotal' => 1, 'discount_total' => 0, 'tax_rate' => 16, 'tax_total' => 0, 'total' => 1]);
        $lead = Lead::create(['source' => 'correo', 'status' => 'nuevo', 'contact_email' => 'mio@empresa.com', 'customer_id' => $mine->id, 'last_activity_at' => now()]);

        try {
            app(LeadMailService::class)->send($lead, $this->payload(['to' => 'mio@empresa.com', 'quote_ids' => [$quote->id]]), $this->admin);
            $this->fail('Debió rechazar la cotización ajena.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('cotización', $e->getMessage());
        }
        $this->assertCount(0, $this->sentMessages());
        $this->assertSame(0, LeadOutboundEmail::count());
    }

    public function test_n8n_no_duplica_un_correo_enviado_desde_la_plataforma(): void
    {
        $in = $this->inbound();
        $lead = Lead::find($in['lead_id']);
        $email = app(LeadMailService::class)->send($lead, $this->payload(), $this->admin);

        // La carpeta Enviados vuelve a ver el mismo mensaje (mismo Message-ID).
        $res = app(LeadService::class)->recordOutboundEmail([
            'message_id' => $email->message_id,
            'from_email' => 'administracion@equiterm.test',
            'to'         => 'cliente@empresa.com',
            'subject'    => 'Re: Cotizar caldera',
            'body'       => 'texto',
            'in_reply_to' => '<in-1@mail.test>',
        ]);

        $this->assertTrue($res['duplicate']);
        $this->assertSame($email->id, $res['outbound_email_id']);
        $this->assertSame(1, LeadOutboundEmail::count());
        $this->assertSame(1, Task::find($in['task_id'])->events()->where('type', 'sent_email')->count());
    }

    // ── Controlador ──────────────────────────────────────────────────────

    public function test_envia_desde_la_bandeja_con_archivos_y_cierra_la_tarea(): void
    {
        $in = $this->inbound();

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.email.send', $in['lead_id']), [
            'to' => 'cliente@empresa.com', 'cc' => 'a@x.com; b@x.com, c@x.com', 'subject' => 'Re: Cotizar caldera', 'body' => 'Hola', 'close_task' => '1',
            'files' => [UploadedFile::fake()->create('ficha.pdf', 50, 'application/pdf')],
        ])
            ->assertRedirect(route('admin.leads-inbox.index', ['lead' => $in['lead_id']]))
            ->assertSessionHas('composer_tab', 'correo')
            ->assertSessionHas('success');

        $mail = LeadOutboundEmail::first();
        $this->assertSame(['cliente@empresa.com', 'a@x.com', 'b@x.com', 'c@x.com'], $mail->recipients);
        $this->assertSame(['ficha.pdf'], $mail->attachments);
        $this->assertSame('closed', Task::find($in['task_id'])->status);
    }

    public function test_validacion_del_formulario_de_correo(): void
    {
        $in = $this->inbound();
        $url = route('admin.leads-inbox.email.send', $in['lead_id']);
        $ok = ['to' => 'cliente@empresa.com', 'subject' => 'Asunto', 'body' => 'Hola'];

        $this->actingAs($this->admin)->post($url, ['to' => 'mal', 'subject' => '', 'body' => ''])
            ->assertSessionHasErrors(['to', 'subject', 'body']);
        $this->actingAs($this->admin)->post($url, $ok + ['cc' => 'bueno@x.com, malo'])->assertSessionHasErrors('cc');
        $this->actingAs($this->admin)->post($url, $ok + ['cc' => 'a@x.com,b@x.com,c@x.com,d@x.com,e@x.com,f@x.com'])->assertSessionHasErrors('cc');
        $this->actingAs($this->admin)->post($url, $ok + ['files' => [
            UploadedFile::fake()->create('a.pdf', 10), UploadedFile::fake()->create('b.pdf', 10),
            UploadedFile::fake()->create('c.pdf', 10), UploadedFile::fake()->create('d.pdf', 10),
        ]])->assertSessionHasErrors('files');
        $this->actingAs($this->admin)->post($url, $ok + ['files' => [UploadedFile::fake()->create('grande.pdf', 11000, 'application/pdf')]])->assertSessionHasErrors('files.0');
        $this->actingAs($this->admin)->post($url, $ok + ['files' => [UploadedFile::fake()->create('virus.exe', 10)]])->assertSessionHasErrors('files.0');

        $this->assertSame(0, LeadOutboundEmail::count());
        $this->assertCount(0, $this->sentMessages());
    }

    public function test_un_error_de_envio_vuelve_con_mensaje_y_conserva_lo_escrito(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'localhost']);
        $in = $this->inbound();

        $this->actingAs($this->admin)->post(route('admin.leads-inbox.email.send', $in['lead_id']), [
            'to' => 'cliente@empresa.com', 'subject' => 'Asunto', 'body' => 'Mi borrador',
        ])
            ->assertRedirect(route('admin.leads-inbox.index', ['lead' => $in['lead_id']]))
            ->assertSessionHas('composer_tab', 'correo')
            ->assertSessionHas('error')
            ->assertSessionHasInput('body', 'Mi borrador');
    }

    public function test_exige_permiso_de_editar_y_limite_de_envios(): void
    {
        $route = Route::getRoutes()->getByName('admin.leads-inbox.email.send');
        $this->assertContains('permission:leads-inbox,edit', $route->middleware());
        $this->assertContains('throttle:10,1', $route->middleware());

        $role = Role::create(['name_role' => 'Lectura', 'name_role_es' => 'Lectura']);
        $user = User::create([
            'first_name' => 'Sin', 'last_name' => 'Permiso', 'position' => 'X', 'phone' => '5555555556',
            'email' => 'sp-' . uniqid() . '@example.com', 'password' => bcrypt('password'), 'status' => 'active',
            'rfc' => 'RFC' . strtoupper(substr(uniqid(), -8)), 'role_id' => $role->id,
        ]);
        $in = $this->inbound();

        $this->actingAs($user)->post(route('admin.leads-inbox.email.send', $in['lead_id']), $this->payload())->assertRedirect();
        $this->assertSame(0, LeadOutboundEmail::count());
        $this->assertCount(0, $this->sentMessages());

        $this->actingAs($user)->getJson(route('admin.leads-inbox.email.template', [$in['lead_id'], 1]))->assertForbidden();
    }

    // ── Buzón propio de la bandeja (administracion@) ─────────────────────

    /** Producción simulada: SMTP general = no-reply@, buzón de la bandeja = administracion@; el transporte se sustituye por array. */
    private function smtpWithInbox(?string $password = 'secreta-123', array $over = []): void
    {
        config([
            'mail.default'                => 'smtp',
            'mail.mailers.smtp.host'      => 'smtp.equiterm.test',
            'mail.mailers.smtp.port'      => 465,
            'mail.mailers.smtp.scheme'    => 'smtps',
            'mail.from.address'           => 'no-reply@equiterm.test',
            'mail.from.name'              => 'Sistema',
            'mail.lead_inbox_transport'   => 'array',
        ]);
        Setting::set('mail.inbox.from_address', $over['from_address'] ?? 'administracion@equiterm.test');
        Setting::set('mail.inbox.from_name', $over['from_name'] ?? 'Administración Equiterm');
        Setting::set('mail.inbox.username', $over['username'] ?? 'administracion@equiterm.test');
        if ($password !== null) {
            Setting::set('mail.inbox.password', Crypt::encryptString($password));
        }
    }

    private function inboxMessages(): array
    {
        return app('mail.manager')->mailer('lead_inbox')->getSymfonyTransport()->messages()->all();
    }

    public function test_con_buzon_configurado_sale_desde_administracion_con_su_reply_to_y_message_id(): void
    {
        $this->smtpWithInbox();
        $in = $this->inbound();
        $lead = Lead::find($in['lead_id']);

        $email = app(LeadMailService::class)->send($lead, $this->payload(), $this->admin);

        $messages = $this->inboxMessages();
        $this->assertCount(1, $messages);
        $raw = $messages[0]->getOriginalMessage();
        $this->assertSame('administracion@equiterm.test', $raw->getFrom()[0]->getAddress());
        $this->assertSame('Administración Equiterm', $raw->getFrom()[0]->getName());
        $this->assertSame('administracion@equiterm.test', $raw->getReplyTo()[0]->getAddress());
        $this->assertSame($email->message_id, $raw->getHeaders()->get('Message-ID')->getBodyAsString());
        $this->assertMatchesRegularExpression('/^<[0-9a-f-]{36}@equiterm\.test>$/', $email->message_id);
        $this->assertSame('administracion@equiterm.test', $email->from_email);

        // El mailer en tiempo de ejecución: servidor general + credenciales del buzón.
        $cfg = config('mail.mailers.lead_inbox');
        $this->assertSame('smtp.equiterm.test', $cfg['host']);
        $this->assertSame(465, $cfg['port']);
        $this->assertSame('smtps', $cfg['scheme']);
        $this->assertSame('administracion@equiterm.test', $cfg['username']);
        $this->assertSame('secreta-123', $cfg['password']);
        $this->assertSame(20, $cfg['timeout']);

        // El SMTP general (no-reply@) no se usa para esta respuesta.
        $this->assertSame('no-reply@equiterm.test', config('mail.from.address'));
        $this->assertSame([], app('mail.manager')->mailer('array')->getSymfonyTransport()->messages()->all());
    }

    public function test_sin_buzon_configurado_usa_el_remitente_general_y_lo_avisa(): void
    {
        // En producción simulada (smtp) sin buzón: remitente general y aviso.
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.equiterm.test', 'mail.from.address' => 'no-reply@equiterm.test']);
        $svc = app(LeadMailService::class);
        $this->assertNull($svc->inboxMailbox());
        $this->assertTrue($svc->sender()['uses_default']);
        $this->assertSame('no-reply@equiterm.test', $svc->sender()['address']);
        $this->assertNull($svc->smtpProblem());

        $in = $this->inbound();
        $ctx = app(LeadInboxController::class)->emailCompose(Lead::find($in['lead_id']));
        $this->assertTrue($ctx['uses_default_sender']);
        $this->assertSame('no-reply@equiterm.test', $ctx['sender_address']);

        // Con transporte array (no smtp) el correo sale como antes, con el remitente general.
        config(['mail.default' => 'array', 'mail.from.address' => 'administracion@equiterm.test']);
        Setting::set('mail.inbox.from_address', 'otra@equiterm.test');
        Setting::set('mail.inbox.username', 'otra@equiterm.test');
        Setting::set('mail.inbox.password', Crypt::encryptString('x'));
        $this->assertNull($svc->inboxMailbox());
        app(LeadMailService::class)->send(Lead::find($in['lead_id']), $this->payload(), $this->admin);
        $this->assertSame('administracion@equiterm.test', $this->sentMessages()[0]->getOriginalMessage()->getFrom()[0]->getAddress());
    }

    public function test_buzon_incompleto_o_con_password_indescifrable_cuenta_como_no_configurado(): void
    {
        $svc = app(LeadMailService::class);

        // Contraseña que no se puede descifrar (otra APP_KEY / valor corrupto).
        $this->smtpWithInbox(null);
        Setting::set('mail.inbox.password', 'esto-no-es-un-payload-cifrado');
        $this->assertNull($svc->inboxMailbox());
        $this->assertTrue($svc->sender()['uses_default']);
        $in = $this->inbound();
        $ctx = app(LeadInboxController::class)->emailCompose(Lead::find($in['lead_id']));
        $this->assertTrue($ctx['uses_default_sender']);
        $this->assertSame('no-reply@equiterm.test', $ctx['sender_address']);

        // Falta cada uno de los datos.
        foreach (['mail.inbox.from_address', 'mail.inbox.username', 'mail.inbox.password'] as $key) {
            $this->smtpWithInbox();
            $this->assertNotNull($svc->inboxMailbox(), 'Debe estar configurado antes de quitar ' . $key);
            Setting::set($key, null);
            $this->assertNull($svc->inboxMailbox(), 'Sin ' . $key . ' no debe usarse el buzón.');
        }
    }

    public function test_el_compositor_muestra_el_buzon_y_sin_el_muestra_el_aviso(): void
    {
        $in = $this->inbound();
        $lead = Lead::find($in['lead_id']);

        $this->smtpWithInbox();
        $ctx = app(LeadInboxController::class)->emailCompose($lead);
        $this->assertTrue($ctx['can_send']);
        $this->assertFalse($ctx['uses_default_sender']);
        $this->assertSame('administracion@equiterm.test', $ctx['sender_address']);
        $this->assertSame('Administración Equiterm <administracion@equiterm.test>', $ctx['from']);

        $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['lead' => $lead->id]))
            ->assertOk()
            ->assertDontSee('Configura el buzón de la bandeja');

        Setting::set('mail.inbox.password', null);
        $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['lead' => $lead->id]))
            ->assertOk()
            ->assertSee('Se enviará desde no-reply@equiterm.test.', false)
            ->assertSee('Configura el buzón de la bandeja', false)
            ->assertSee(route('admin.integrations.index'), false);
    }

    public function test_el_redactor_de_correo_vive_en_un_modal_con_el_resumen_de_la_conversacion(): void
    {
        $in = $this->inbound();
        $lead = Lead::find($in['lead_id']);

        $html = $this->actingAs($this->admin)->get(route('admin.leads-inbox.index', ['lead' => $lead->id]))
            ->assertOk()
            ->assertSee('data-lwm', false)
            ->assertSee('data-lwm-chat', false)
            ->assertSee('data-lw-mail-open', false)
            ->getContent();

        // El formulario ya no es un panel del compositor: solo existe dentro del modal.
        $this->assertStringNotContainsString('data-pane="correo"', $html);
        $this->assertSame(1, substr_count($html, 'data-lwe-form data-lwe-template-url'));
        $this->assertLessThan(strpos($html, 'data-lwe-form data-lwe-template-url'), strpos($html, 'data-lwm-chat'));
    }

    public function test_el_modal_solo_se_reabre_si_el_envio_fallo(): void
    {
        $in = $this->inbound();
        $lead = Lead::find($in['lead_id']);
        $url = route('admin.leads-inbox.index', ['lead' => $lead->id]);

        // Envio correcto: vuelve con composer_tab=correo pero sin error, el modal queda cerrado.
        $this->actingAs($this->admin)->withSession(['composer_tab' => 'correo', 'success' => 'Correo enviado a x.'])->get($url)
            ->assertOk()->assertDontSee("if (document.readyState === 'complete') open();", false);

        // Envio fallido: se reabre con lo que el usuario habia escrito.
        $this->actingAs($this->admin)->withSession(['composer_tab' => 'correo', 'error' => 'SMTP caido'])->get($url)
            ->assertOk()->assertSee("if (document.readyState === 'complete') open();", false);
    }

    public function test_integraciones_nunca_imprime_la_contrasena_del_buzon(): void
    {
        $this->smtpWithInbox('Super-Secreta-XYZ');
        $encrypted = (string) Setting::get('mail.inbox.password');

        $res = $this->actingAs($this->admin)->get(route('admin.integrations.index'))->assertOk();
        $html = $res->getContent();
        $this->assertStringNotContainsString('Super-Secreta-XYZ', $html);
        $this->assertStringNotContainsString($encrypted, $html);
        $this->assertStringContainsString('Contraseña guardada', $html);
        $this->assertStringContainsString('Buzón de la bandeja de leads', $html);
        $this->assertStringContainsString('value="administracion@equiterm.test"', $html);
    }

    public function test_guardar_con_password_vacia_conserva_la_del_buzon_y_con_valor_la_reemplaza(): void
    {
        $this->smtpWithInbox('vieja-123');
        $stored = fn () => Setting::where('key', 'mail.inbox.password')->value('value');
        $before = $stored();
        $form = [
            'mail_inbox_from_address' => 'administracion@equiterm.test', 'mail_inbox_from_name' => 'Admin',
            'mail_inbox_username' => 'administracion@equiterm.test', 'mail_inbox_password' => '',
        ];

        $this->actingAs($this->admin)->put(route('admin.integrations.update'), $form)->assertSessionHasNoErrors();
        $this->assertSame($before, $stored());
        $this->assertSame('vieja-123', Crypt::decryptString($stored()));
        $this->assertSame('Admin', Setting::where('key', 'mail.inbox.from_name')->value('value'));

        $this->actingAs($this->admin)->put(route('admin.integrations.update'), ['mail_inbox_password' => 'nueva-456'] + $form)->assertSessionHasNoErrors();
        $this->assertNotSame($before, $stored());
        $this->assertSame('nueva-456', Crypt::decryptString($stored()));
        $this->assertStringNotContainsString('nueva-456', (string) $stored());
    }

    public function test_validacion_de_los_campos_del_buzon(): void
    {
        $this->actingAs($this->admin)->put(route('admin.integrations.update'), [
            'mail_inbox_from_address' => 'no-es-correo',
            'mail_inbox_username'     => str_repeat('a', 191),
            'mail_inbox_password'     => str_repeat('p', 256),
        ])->assertSessionHasErrors(['mail_inbox_from_address', 'mail_inbox_username', 'mail_inbox_password']);
    }

    // ── Plantillas y contexto ────────────────────────────────────────────

    public function test_plantilla_renderizada_para_el_lead_en_texto_plano(): void
    {
        $in = $this->inbound();
        Lead::find($in['lead_id'])->update(['contact_name' => 'Carlos Herrera', 'contact_company' => 'Calderas del Norte']);
        $tpl = EmailTemplate::create([
            'name' => 'Seguimiento', 'type' => 'manual', 'subject' => 'Hola {{contact.name}}',
            'html_body' => '<p>Estimado {{contact.name}} de {{contact.company}}</p><p>Quedamos atentos&nbsp;<a href="https://equiterm.test/x">aquí</a></p><style>p{color:red}</style>',
        ]);

        $this->actingAs($this->admin)->getJson(route('admin.leads-inbox.email.template', [$in['lead_id'], $tpl->id]))
            ->assertOk()
            ->assertJsonPath('subject', 'Hola Carlos Herrera')
            ->assertJsonPath('body', "Estimado Carlos Herrera de Calderas del Norte\nQuedamos atentos aquí (https://equiterm.test/x)");

        $this->actingAs($this->admin)->getJson(route('admin.leads-inbox.email.template', [$in['lead_id'], 9999]))->assertNotFound();
    }

    public function test_contexto_del_compositor(): void
    {
        Setting::set('mail.signature', 'Firma Equiterm');
        EmailTemplate::create(['name' => 'Plantilla A', 'type' => 'manual', 'subject' => 's', 'html_body' => 'b']);
        $in = $this->inbound();
        $lead = Lead::find($in['lead_id']);
        $c = app(LeadInboxController::class);

        $ctx = $c->emailCompose($lead);
        $this->assertTrue($ctx['can_send']);
        $this->assertNull($ctx['reason']);
        $this->assertSame('cliente@empresa.com', $ctx['to']);
        $this->assertSame('Re: Cotizar caldera', $ctx['subject']);
        $this->assertSame('Firma Equiterm', $ctx['signature']);
        $this->assertSame('Equiterm Industries <administracion@equiterm.test>', $ctx['from']);
        $this->assertContains('Plantilla A', array_column($ctx['templates'], 'name'));
        $this->assertSame([], $ctx['quotes']);
        $this->assertSame($in['task_id'], $ctx['open_task']['id']);
        $this->assertSame('<in-1@mail.test>', $ctx['in_reply_to']);

        // Sin doble "Re:".
        LeadInboundEmail::where('lead_id', $lead->id)->update(['subject' => 'RE: Cotizar caldera']);
        $this->assertSame('RE: Cotizar caldera', $c->emailCompose($lead)['subject']);

        // Sin correo en el lead.
        $lead->update(['contact_email' => null]);
        $ctx = $c->emailCompose($lead->fresh());
        $this->assertFalse($ctx['can_send']);
        $this->assertStringContainsString('correo', $ctx['reason']);

        // SMTP sin configurar.
        $lead->update(['contact_email' => 'cliente@empresa.com']);
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'localhost']);
        $ctx = $c->emailCompose($lead->fresh());
        $this->assertFalse($ctx['can_send']);
        $this->assertStringContainsString('SMTP', $ctx['reason']);
    }

    public function test_la_linea_de_tiempo_marca_direccion_remitente_y_origen_de_los_correos(): void
    {
        $in = $this->inbound();
        $lead = Lead::find($in['lead_id']);
        app(LeadMailService::class)->send($lead, $this->payload(), $this->admin);
        LeadOutboundEmail::create(['message_id' => '<ext@x>', 'lead_id' => $lead->id, 'to_email' => 'cliente@empresa.com', 'subject' => 'Desde Enviados', 'sent_at' => now()->subDay(), 'source' => 'external']);

        $m = new ReflectionMethod(LeadInboxController::class, 'timeline');
        $items = collect($m->invoke(app(LeadInboxController::class), $lead->fresh()))->where('type', 'correo')->values();

        $this->assertCount(3, $items);
        $received = $items->firstWhere('dir', 'in');
        $this->assertSame('Cotizar caldera', $received['subject']);
        $this->assertNull($received['sent_by']);
        $this->assertNotNull($received['email_id']);
        $platform = $items->firstWhere('source', 'platform');
        $this->assertSame('out', $platform['dir']);
        $this->assertSame('Laura Mendoza', $platform['sent_by']);
        $this->assertSame('Re: Cotizar caldera', $platform['subject']);
        $external = $items->firstWhere('source', 'external');
        $this->assertNull($external['sent_by']);
        $this->assertStringStartsWith('Correo recibido', $received['title']);
        $this->assertStringStartsWith('Correo enviado', $platform['title']);
    }

    public function test_el_historial_de_la_tarea_muestra_quien_envio_el_correo(): void
    {
        $in = $this->inbound();
        app(LeadMailService::class)->send(Lead::find($in['lead_id']), $this->payload(), $this->admin);

        $this->actingAs($this->admin)->get(route('admin.tasks.show', $in['task_id']))
            ->assertOk()
            ->assertSee('Laura Mendoza');
    }
}
