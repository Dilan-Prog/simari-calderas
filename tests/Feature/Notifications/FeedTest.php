<?php

namespace Tests\Feature\Notifications;

use App\Models\AdminNotification;
use App\Models\Lead;
use App\Models\Task;
use App\Services\NotificationService;

class FeedTest extends NotificationTestCase
{
    private function notify(int $n = 1): void
    {
        $lead = Lead::create(['source' => 'manual', 'status' => 'nuevo', 'contact_name' => 'Ana']);
        for ($i = 0; $i < $n; $i++) {
            app(NotificationService::class)->notifyLead($lead, 'lead_incoming', 'Lead nuevo · Manual', 'Ana ' . $i);
        }
    }

    public function test_requiere_sesion(): void
    {
        $this->getJson(route('admin.notifications.feed'))->assertUnauthorized();
    }

    public function test_sin_ningun_permiso_notif_devuelve_vacio(): void
    {
        $viewer = $this->user([], 'Sin');
        $this->user(['notif-lead-entrante']);
        $this->notify(2);

        $this->actingAs($viewer)->getJson(route('admin.notifications.feed'))
            ->assertOk()
            ->assertJson(['unread' => 0, 'latest_id' => 0, 'items' => [], 'hot' => ['leads' => [], 'tasks' => []]]);
    }

    public function test_feed_con_contrato_after_id_y_leer(): void
    {
        $viewer = $this->user(['notif-lead-entrante'], 'Ve');
        $this->notify(3);
        $lead = Lead::first();
        Lead::whereKey($lead->id)->update(['reply_pending_at' => now()]);

        $res = $this->actingAs($viewer)->getJson(route('admin.notifications.feed'))->assertOk();
        $res->assertJsonStructure([
            'unread', 'toast_seconds', 'latest_id',
            'items' => [['id', 'type', 'title', 'body', 'url', 'subject_type', 'subject_id', 'read', 'created_at', 'ago']],
            'hot' => ['leads', 'tasks'],
        ]);
        $this->assertSame(3, $res->json('unread'));
        $this->assertSame(30, $res->json('toast_seconds'));
        $this->assertCount(3, $res->json('items'));
        $this->assertSame('ahora', $res->json('items.0.ago'));
        $this->assertSame([$lead->id], $res->json('hot.leads'));
        $ids = collect($res->json('items'))->pluck('id');
        $this->assertSame($ids->max(), $res->json('latest_id'));
        $this->assertSame($ids->sortDesc()->values()->all(), $ids->all()); // más recientes primero

        $mid = $ids->sort()->values()[0];
        $new = $this->actingAs($viewer)->getJson(route('admin.notifications.feed', ['after_id' => $mid]))->assertOk();
        $this->assertCount(2, $new->json('items'));

        $this->actingAs($viewer)->postJson(route('admin.notifications.read'), ['ids' => [$mid]])->assertOk()->assertJson(['unread' => 2]);
        $this->actingAs($viewer)->postJson(route('admin.notifications.read'), ['all' => true])->assertOk()->assertJson(['unread' => 0]);
    }

    public function test_leer_solo_afecta_las_propias(): void
    {
        $a = $this->user(['notif-lead-entrante'], 'A');
        $b = $this->user(['notif-lead-entrante'], 'B');
        $this->notify(1);
        $bId = AdminNotification::where('user_id', $b->id)->value('id');

        $this->actingAs($a)->postJson(route('admin.notifications.read'), ['ids' => [$bId]])->assertOk();

        $this->assertNull(AdminNotification::find($bId)->read_at);
    }

    public function test_hot_tasks_lista_tareas_con_aviso_no_leido(): void
    {
        $u = $this->user(['notif-tarea'], 'U');
        $task = Task::create(['title' => 'X', 'status' => 'open']);

        $this->actingAs($u)->getJson(route('admin.notifications.feed'))->assertJson(['hot' => ['tasks' => [$task->id]]]);
    }

    public function test_preferencias_valida_rango_y_guarda(): void
    {
        $u = $this->user(['notif-tarea'], 'U');

        $this->actingAs($u)->postJson(route('admin.notifications.prefs'), ['toast_seconds' => 45])->assertOk()->assertJson(['toast_seconds' => 45]);
        $this->assertSame(45, $u->fresh()->notification_toast_seconds);
        $this->actingAs($u)->postJson(route('admin.notifications.prefs'), ['toast_seconds' => 0])->assertOk()->assertJson(['toast_seconds' => 0]);
        $this->actingAs($u)->postJson(route('admin.notifications.prefs'), ['toast_seconds' => 3])->assertStatus(422);
        $this->actingAs($u)->postJson(route('admin.notifications.prefs'), ['toast_seconds' => 301])->assertStatus(422);
        $this->assertSame(0, $u->fresh()->notification_toast_seconds);
    }

    public function test_sin_permiso_las_preferencias_no_se_guardan(): void
    {
        $u = $this->user([], 'Sin');
        $this->actingAs($u)->postJson(route('admin.notifications.prefs'), ['toast_seconds' => 60])->assertOk()->assertJson(['toast_seconds' => 30]);
        $this->assertSame(30, $u->fresh()->notification_toast_seconds);
    }

    public function test_una_copia_sin_permiso_si_ve_lo_que_recibio(): void
    {
        $owner = $this->user(['notif-lead-entrante'], 'Dueño');
        $copy = $this->user([], 'Copia');
        $this->copy($owner, $copy);
        $this->notify(1);

        $this->actingAs($copy)->getJson(route('admin.notifications.feed'))->assertOk()->assertJson(['unread' => 1]);
    }
}
