<?php

namespace Tests\Feature\Crm;

use App\Models\ApiClient;
use App\Models\Lead;
use App\Models\LeadInboundEmail;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Support\EmailBodyFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Formato de los correos en el Inbox de correo y cierre de la tarea al responder desde Outlook/webmail.
 */
class LeadEmailFormatAndReplyTest extends TestCase
{
    use RefreshDatabase;

    private const OUTLOOK = "Buenas tardes\nMando mi constancia de situación fiscal\nSaludos\n[Someimage]\n"
        . "\"Si usted no es el receptor interesado de la presente comunicación electrónica se le hace de su conocimiento que la información es privada.\"\n"
        . "________________________________\n"
        . "De: Fernando Perez <f.perez@condumex.com.mx>\n"
        . "Enviado: martes, 10 de noviembre de 2026 11:40 p. m.\n"
        . "Para: administracion@equitermindustries.com.mx\n"
        . "Asunto: CSF ARCOMEX\n"
        . "Buenas tardes\nMensaje anterior muy largo";

    // ── Formateador ─────────────────────────────────────────────────────

    public function test_outlook_separa_lo_escrito_del_historial_y_del_aviso_legal(): void
    {
        $r = EmailBodyFormatter::split(self::OUTLOOK);

        $this->assertStringContainsString('Mando mi constancia de situación fiscal', $r['main']);
        $this->assertStringNotContainsString('Someimage', $r['main']);
        $this->assertStringNotContainsString('Si usted no es el receptor', $r['main']);
        $this->assertStringNotContainsString('De: Fernando', $r['main']);
        $this->assertStringContainsString('Si usted no es el receptor', $r['hidden']);
        $this->assertStringContainsString('Asunto: CSF ARCOMEX', $r['hidden']);
        $this->assertStringContainsString('Mensaje anterior muy largo', $r['hidden']);
    }

    public function test_gmail_escribio_y_lineas_con_mayor_que_se_pliegan(): void
    {
        $r = EmailBodyFormatter::split("Sí, me interesa.\n\nEl mar, 10 nov 2026 a las 23:40, Equiterm <administracion@equitermindustries.com.mx> escribió:\n> Hola\n> ¿Le mando precio?");
        $this->assertSame('Sí, me interesa.', $r['main']);
        $this->assertStringContainsString('escribió:', $r['hidden']);

        $r = EmailBodyFormatter::split("Gracias\nOn Tue, Nov 10, 2026 at 11:40 PM Someone <a@b.com>\nwrote:\n> hi");
        $this->assertSame('Gracias', $r['main']);

        $r = EmailBodyFormatter::split("Respuesta corta\n> cita 1\n> cita 2");
        $this->assertSame('Respuesta corta', $r['main']);
    }

    public function test_un_correo_normal_no_se_recorta_y_se_limpian_saltos_e_imagenes(): void
    {
        $r = EmailBodyFormatter::split("Hola, necesito cotizar.\n\n\n\n\nDe: ahora en adelante pago de contado\n[image001.png]\n[cid:abc123]\nGracias");

        $this->assertSame('', $r['hidden']);
        $this->assertStringNotContainsString("\n\n\n", $r['main']);
        $this->assertStringNotContainsString('image001', $r['main']);
        $this->assertStringNotContainsString('cid:', $r['main']);
        $this->assertStringContainsString('De: ahora en adelante pago de contado', $r['main']);
    }

    public function test_si_solo_hay_historial_se_muestra_completo(): void
    {
        $text = "De: Alguien <a@b.com>\nEnviado: lunes\nPara: x@y.com\nAsunto: Reenvío\nTexto reenviado";
        $r = EmailBodyFormatter::split($text);

        $this->assertSame('', $r['hidden']);
        $this->assertStringContainsString('Texto reenviado', $r['main']);
    }

    public function test_los_enlaces_se_convierten_y_el_html_se_escapa(): void
    {
        $html = (string) EmailBodyFormatter::html("Mira https://equitermindustries.com.mx/cat?a=1&b=2. <script>alert(1)</script> javascript:alert(1)");

        $this->assertStringContainsString('<a href="https://equitermindustries.com.mx/cat?a=1&amp;b=2" target="_blank" rel="noopener noreferrer nofollow">', $html);
        $this->assertStringContainsString('</a>.', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('href="javascript', $html);
    }

    public function test_la_vista_previa_no_arrastra_el_historial(): void
    {
        $this->assertSame('Buenas tardes Mando mi constancia de situación fiscal Saludos', EmailBodyFormatter::preview(self::OUTLOOK));
    }

    // ── Pantalla ────────────────────────────────────────────────────────

    public function test_la_conversacion_muestra_lo_escrito_y_pliega_el_historial(): void
    {
        $this->withoutVite();
        $role = Role::firstOrCreate(['name_role' => 'Administrador'], ['name_role_es' => 'Administrador']);
        $user = User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'Administrador',
            'phone' => '5555555555', 'email' => 'u-' . uniqid() . '@example.com',
            'password' => bcrypt('password'), 'status' => 'active', 'rfc' => 'RFC' . strtoupper(substr(uniqid(), -8)), 'role_id' => $role->id,
        ]);
        $this->actingAs($user);

        $lead = Lead::create(['source' => 'correo', 'status' => 'nuevo', 'contact_email' => 'f.perez@condumex.com.mx', 'contact_name' => 'Fernando Perez', 'last_activity_at' => now()]);
        LeadInboundEmail::create([
            'message_id' => '<m1@x>', 'lead_id' => $lead->id, 'from_email' => 'f.perez@condumex.com.mx', 'subject' => 'CSF ARCOMEX',
            'excerpt' => self::OUTLOOK . "\nhttps://ejemplo.com/doc.", 'received_at' => now(),
        ]);

        $res = $this->get(route('admin.leads-inbox.index', ['inbox' => 'correo', 'lead' => $lead->id]));

        $res->assertOk();
        $res->assertSee('Mando mi constancia de situación fiscal');
        $res->assertSee('Ver historial y texto completo del correo');
        $res->assertSee('data-lw-more="in-', false);
        $res->assertDontSee('Someimage');
    }

    // ── Responder desde Outlook ─────────────────────────────────────────

    private function inbound(): array
    {
        Sanctum::actingAs(ApiClient::create(['name' => 'n8n', 'is_active' => true]), ['leads:write']);
        $r = $this->postJson('/api/v1/leads', [
            'message_id' => '<in1@mail.test>', 'email' => 'cliente@empresa.com', 'name' => 'Cliente', 'subject' => 'Caldera', 'message' => 'Quiero cotizar',
        ])->assertCreated();

        return [Lead::find($r->json('data.lead_id')), Task::find($r->json('data.task_id'))];
    }

    public function test_responder_desde_outlook_cierra_la_tarea_y_pasa_el_lead_a_contactado(): void
    {
        [$lead, $task] = $this->inbound();
        $this->assertSame('nuevo', $lead->status);

        $this->postJson('/api/v1/leads/outbound-email', [
            'message_id' => '<out1@mail.test>', 'to' => 'cliente@empresa.com', 'subject' => 'Re: Caldera',
            'body' => 'Le mando la cotización', 'in_reply_to' => '<in1@mail.test>', 'sent_at' => now()->addMinutes(5)->toIso8601String(),
        ])->assertCreated();

        $task->refresh();
        $this->assertSame('closed', $task->status);
        $this->assertNotNull($task->closed_at);
        $this->assertSame('contactado', $lead->fresh()->status);
        $this->assertNull($lead->fresh()->reply_pending_at);
    }

    public function test_close_task_false_deja_la_tarea_abierta(): void
    {
        [$lead, $task] = $this->inbound();

        $this->postJson('/api/v1/leads/outbound-email', [
            'message_id' => '<out2@mail.test>', 'to' => 'cliente@empresa.com', 'subject' => 'Re: Caldera',
            'body' => 'Seguimos', 'in_reply_to' => '<in1@mail.test>', 'close_task' => false, 'sent_at' => now()->addMinutes(5)->toIso8601String(),
        ])->assertCreated();

        $this->assertSame('open', $task->fresh()->status);
    }

    public function test_un_correo_enviado_antes_de_la_tarea_no_la_cierra(): void
    {
        [$lead, $task] = $this->inbound();

        $this->postJson('/api/v1/leads/outbound-email', [
            'message_id' => '<out3@mail.test>', 'to' => 'cliente@empresa.com', 'subject' => 'Antiguo',
            'body' => 'Enviado hace meses', 'sent_at' => now()->subDays(30)->toIso8601String(),
        ])->assertCreated();

        $this->assertSame('open', $task->fresh()->status);
    }
}
