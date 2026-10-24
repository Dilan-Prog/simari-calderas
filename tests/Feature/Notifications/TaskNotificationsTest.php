<?php

namespace Tests\Feature\Notifications;

use App\Models\AdminNotification;
use App\Models\Task;
use App\Support\Notif;

class TaskNotificationsTest extends NotificationTestCase
{
    public function test_movimientos_desde_el_panel_notifican_al_responsable_pero_no_al_actor(): void
    {
        $actor = $this->admin();
        $assignee = $this->user(['notif-tarea'], 'Resp');

        $this->actingAs($actor)->post(route('admin.tasks.store'), ['title' => 'Revisar bomba', 'assigned_to' => $assignee->id])->assertRedirect();
        $task = Task::firstOrFail();
        $this->assertSame(1, AdminNotification::where('user_id', $assignee->id)->count());

        $this->actingAs($actor)->put(route('admin.tasks.update', $task), ['status' => 'closed'])->assertRedirect();
        $this->actingAs($actor)->post(route('admin.tasks.notes.store', $task), ['body' => 'listo'])->assertRedirect();

        $mine = AdminNotification::where('user_id', $assignee->id)->orderBy('id')->get();
        $this->assertCount(3, $mine);
        $this->assertSame('Revisar bomba', $mine[1]->title);
        $this->assertStringContainsString('Estatus actualizado', $mine[1]->body);
        $this->assertSame(0, AdminNotification::where('user_id', $actor->id)->count());
    }

    public function test_tarea_creada_sin_sesion_avisa_y_abrirla_marca_leida_y_registra_primera_vista(): void
    {
        $assignee = $this->user(['notif-tarea'], 'Resp');
        $task = Task::create(['title' => 'Auto', 'status' => 'open', 'assigned_to' => $assignee->id]);
        $this->assertSame(1, AdminNotification::where('user_id', $assignee->id)->whereNull('read_at')->count());
        $this->assertSame([$task->id], Notif::hotTaskIds($assignee));

        Notif::flush();
        $this->actingAs($assignee)->get(route('admin.tasks.show', $task))->assertOk();

        $this->assertSame(1, $task->events()->where('type', 'viewed')->count());
        // la apertura marcó leído lo anterior (lo que se genera por la propia vista es del actor y no le llega)
        $this->assertSame(0, AdminNotification::where('user_id', $assignee->id)->whereNull('read_at')->count());

        $this->actingAs($assignee)->get(route('admin.tasks.show', $task))->assertOk();
        $this->assertSame(1, $task->events()->where('type', 'viewed')->count());
    }
}
