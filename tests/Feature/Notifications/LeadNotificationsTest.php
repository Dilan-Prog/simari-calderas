<?php

namespace Tests\Feature\Notifications;

use App\Models\AdminNotification;
use App\Models\Lead;
use App\Models\WhatsappAccount;
use App\Services\LeadService;
use App\Services\WhatsappService;

class LeadNotificationsTest extends NotificationTestCase
{
    public function test_lead_nuevo_por_cada_canal_crea_aviso_persistente(): void
    {
        $u = $this->user(['notif-lead-entrante']);
        $svc = app(LeadService::class);

        $svc->capture(['source' => 'formulario', 'name' => 'Beto', 'email' => 'beto@x.com']);
        $svc->capture(['source' => 'manual', 'name' => 'Caro', 'phone' => '5511112222']);
        $svc->captureFromEmail(['message_id' => '<1@x>', 'email' => 'dani@x.com', 'name' => 'Dani', 'subject' => 'Hola', 'message' => 'Cotizar']);

        $titles = AdminNotification::where('user_id', $u->id)->where('type', 'lead_incoming')->orderBy('id')->pluck('title')->all();
        $this->assertSame(['Lead nuevo · Formulario', 'Lead nuevo · Manual', 'Lead nuevo · Correo'], $titles);

        $n = AdminNotification::where('title', 'Lead nuevo · Formulario')->first();
        $lead = Lead::where('contact_email', 'beto@x.com')->first();
        $this->assertSame(route('admin.leads-inbox.index', ['lead' => $lead->id]), $n->url);
        $this->assertSame('lead', $n->subject_type);
        $this->assertStringContainsString('Beto', $n->body);
        $this->assertNull($n->read_at);
    }

    public function test_whatsapp_nuevo_lead_avisa_entrante_y_el_segundo_mensaje_avisa_respuesta(): void
    {
        $in = $this->user(['notif-lead-entrante'], 'Entra');
        $re = $this->user(['notif-lead-respuesta'], 'Resp');
        $account = WhatsappAccount::create(['name' => 'QR', 'phone_number' => '+52 55 9876 5432', 'connection_type' => 'baileys_qr', 'is_active' => true]);
        $wa = app(WhatsappService::class);

        $wa->ingestInboundMessage($account, '5215512345678', ['content' => 'Hola, quiero una caldera', 'external_message_id' => 'm1']);
        $this->assertSame(1, AdminNotification::where('user_id', $in->id)->where('type', 'lead_incoming')->count());
        $this->assertSame(0, AdminNotification::where('user_id', $re->id)->count());

        $long = str_repeat('palabra ', 40);
        $wa->ingestInboundMessage($account, '5215512345678', ['content' => $long, 'external_message_id' => 'm2']);
        // el mismo mensaje procesado dos veces no duplica el aviso
        $wa->ingestInboundMessage($account, '5215512345678', ['content' => $long, 'external_message_id' => 'm2']);

        $replies = AdminNotification::where('user_id', $re->id)->where('type', 'lead_reply')->get();
        $this->assertCount(1, $replies);
        $this->assertSame('Embudo de Venta WhatsApp', $replies[0]->title);
        $this->assertStringStartsWith('5215512345678 dice: palabra', $replies[0]->body);
        $this->assertLessThanOrEqual(mb_strlen('5215512345678 dice: ') + 140, mb_strlen($replies[0]->body));
        $this->assertStringEndsWith('…', $replies[0]->body);
    }

    public function test_webhook_de_meta_para_lead_existente_notifica(): void
    {
        $re = $this->user(['notif-lead-respuesta']);
        WhatsappAccount::create([
            'name' => 'Meta', 'phone_number' => '+52 55 1234 5678', 'phone_number_id' => '1234567890',
            'provider' => 'meta_cloud_api', 'webhook_verify_token' => 'tok', 'is_active' => true,
        ]);
        $payload = fn (string $id, string $text) => ['object' => 'whatsapp_business_account', 'entry' => [['id' => 'W', 'changes' => [['field' => 'messages', 'value' => [
            'messaging_product' => 'whatsapp', 'metadata' => ['phone_number_id' => '1234567890'],
            'contacts' => [['profile' => ['name' => 'Juan'], 'wa_id' => '5215512345678']],
            'messages' => [['from' => '5215512345678', 'id' => $id, 'timestamp' => (string) now()->timestamp, 'text' => ['body' => $text], 'type' => 'text']],
        ]]]]]];

        $this->postJson('/whatsapp/webhook', $payload('wamid.A', 'primero'))->assertOk();
        $this->postJson('/whatsapp/webhook', $payload('wamid.B', 'segundo'))->assertOk();

        $n = AdminNotification::where('user_id', $re->id)->where('type', 'lead_reply')->first();
        $this->assertNotNull($n);
        $this->assertStringContainsString('dice: segundo', $n->body);
    }

    public function test_correo_a_lead_existente_avisa_respuesta_y_es_idempotente(): void
    {
        $re = $this->user(['notif-lead-respuesta']);
        $svc = app(LeadService::class);
        $svc->captureFromEmail(['message_id' => '<1@x>', 'email' => 'ana@x.com', 'name' => 'Ana', 'subject' => 'Uno', 'message' => 'Primero']);
        $svc->captureFromEmail(['message_id' => '<2@x>', 'email' => 'ana@x.com', 'name' => 'Ana', 'subject' => 'Dos', 'message' => 'Segundo mensaje']);
        $svc->captureFromEmail(['message_id' => '<2@x>', 'email' => 'ana@x.com', 'name' => 'Ana', 'subject' => 'Dos', 'message' => 'Segundo mensaje']);

        $rows = AdminNotification::where('user_id', $re->id)->get();
        $this->assertCount(1, $rows);
        $this->assertSame('Correo', $rows[0]->title);
        $this->assertSame('Ana dice: Segundo mensaje', $rows[0]->body);
    }
}
