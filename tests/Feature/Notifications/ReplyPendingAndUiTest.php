<?php

namespace Tests\Feature\Notifications;

use App\Models\AdminNotification;
use App\Models\Lead;
use App\Models\Role;
use App\Models\WhatsappAccount;
use App\Services\LeadService;
use App\Services\NotificationService;
use App\Services\WhatsappService;
use App\Support\Notif;

class ReplyPendingAndUiTest extends NotificationTestCase
{
    public function test_mensaje_entrante_fija_reply_pending_incluso_en_lead_nuevo(): void
    {
        $account = WhatsappAccount::create(['name' => 'QR', 'phone_number' => '+52 55 9876 5432', 'connection_type' => 'baileys_qr', 'is_active' => true]);
        app(WhatsappService::class)->ingestInboundMessage($account, '5215512345678', ['content' => 'Hola', 'external_message_id' => 'm1']);

        $lead = Lead::firstOrFail();
        $this->assertNotNull($lead->reply_pending_at);
        $this->assertSame([$lead->id], Notif::hotLeadIds());
    }

    public function test_correo_fija_reply_pending_y_el_correo_enviado_lo_limpia(): void
    {
        $svc = app(LeadService::class);
        $svc->captureFromEmail(['message_id' => '<1@x>', 'email' => 'ana@x.com', 'name' => 'Ana', 'subject' => 'Uno', 'message' => 'Hola']);
        $lead = Lead::firstOrFail();
        $this->assertNotNull($lead->fresh()->reply_pending_at);

        $svc->recordOutboundEmail(['message_id' => '<out@x>', 'to' => 'ana@x.com', 'in_reply_to' => '<1@x>', 'subject' => 'Re', 'body' => 'ok']);

        $this->assertNull($lead->fresh()->reply_pending_at);
    }

    public function test_nota_y_abrir_el_lead_limpian_reply_pending_y_marcan_leidas_sus_notificaciones(): void
    {
        $admin = $this->admin();
        $lead = Lead::create(['source' => 'whatsapp', 'status' => 'nuevo', 'contact_name' => 'Ana']);
        $other = Lead::create(['source' => 'manual', 'status' => 'nuevo', 'contact_name' => 'Beto']);
        Lead::whereIn('id', [$lead->id, $other->id])->update(['reply_pending_at' => now()]);
        app(NotificationService::class)->notifyLead($lead, 'lead_reply', 'Correo', 'x');
        $this->assertSame(1, AdminNotification::where('user_id', $admin->id)->whereNull('read_at')->count());

        // nota interna = respuesta de un usuario
        $this->actingAs($admin)->post(route('admin.leads-inbox.notes.store', $lead), ['body' => 'llamé'])->assertRedirect();
        $this->assertNull($lead->fresh()->reply_pending_at);
        $this->assertNotNull($other->fresh()->reply_pending_at);

        // abrir un lead desde su aviso
        app(NotificationService::class)->notifyLead($other, 'lead_reply', 'Correo', 'y');
        Lead::whereKey($lead->id)->update(['reply_pending_at' => now()]);
        $unreadBefore = AdminNotification::where('user_id', $admin->id)->whereNull('read_at')->count();

        $this->actingAs($admin)->get(route('admin.leads-inbox.index', ['lead' => $lead->id]))->assertOk();

        $this->assertNull($lead->fresh()->reply_pending_at);
        $this->assertNotNull($other->fresh()->reply_pending_at);
        $this->assertSame(0, AdminNotification::where('user_id', $admin->id)->where('subject_id', $lead->id)->whereNull('read_at')->count());
        $this->assertLessThan($unreadBefore, AdminNotification::where('user_id', $admin->id)->whereNull('read_at')->count());
    }

    public function test_roles_muestra_el_grupo_notificaciones_y_guarda_los_permisos(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.roles.index'))->assertOk()
            ->assertSee('Notificaciones')
            ->assertSee('data-module="notif-lead-entrante"', false)
            ->assertSee('Respuesta de lead')
            ->assertSee('data-module="notif-tarea"', false);

        $this->actingAs($admin)->post(route('admin.roles.store'), [
            'name_role' => 'Vendedor', 'permissions' => ['notif-lead-entrante:view', 'notif-tarea:view'],
        ])->assertRedirect();

        $role = Role::where('name_role', 'Vendedor')->firstOrFail();
        $this->assertTrue($role->hasPermission('notif-lead-entrante'));
        $this->assertTrue($role->hasPermission('notif-tarea'));
        $this->assertFalse($role->hasPermission('notif-lead-respuesta'));
    }

    public function test_formulario_de_usuario_guarda_y_devuelve_las_copias(): void
    {
        $admin = $this->admin();
        $a = $this->user([], 'A');
        $b = $this->user([], 'B');
        $role = $a->role;

        $this->actingAs($admin)->putJson(route('admin.users.update', $a->id), [
            'first_name' => 'A', 'last_name' => 'Prueba', 'email' => $a->email, 'position' => 'X', 'phone' => '5555555555',
            'status' => 'active', 'rfc' => $a->rfc, 'role_id' => $role->id,
            'notification_copies' => [$b->id, $a->id],
        ])->assertOk();

        $this->assertSame([$b->id], $a->notificationCopies()->pluck('users.id')->all());
        $this->actingAs($admin)->getJson(route('admin.users.show', $a->id))->assertJson(['notification_copy_ids' => [$b->id]]);
    }
}
