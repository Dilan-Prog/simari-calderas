<?php

namespace Tests\Feature\Notifications;

use App\Models\AdminNotification;
use App\Models\Lead;
use App\Models\Task;
use App\Services\NotificationService;

class RecipientsTest extends NotificationTestCase
{
    private function lead(?int $assignedTo = null): Lead
    {
        return Lead::create(['source' => 'manual', 'status' => 'nuevo', 'contact_name' => 'Ana', 'assigned_to' => $assignedTo]);
    }

    public function test_migracion_siembra_permisos_y_los_da_al_administrador(): void
    {
        foreach (['notif-lead-entrante', 'notif-lead-respuesta', 'notif-tarea'] as $m) {
            $this->assertDatabaseHas('permissions', ['module' => $m, 'action' => 'view']);
        }
        $this->assertTrue($this->admin()->hasPermission('notif-tarea'));
    }

    public function test_lead_llega_a_usuarios_con_permiso_y_a_sus_copias_sin_duplicar(): void
    {
        $withPerm = $this->user(['notif-lead-entrante'], 'Con');
        $other = $this->user(['notif-lead-respuesta'], 'Otro tipo');
        $noPerm = $this->user([], 'Sin');
        $copyOnly = $this->user([], 'Copia');
        $both = $this->user(['notif-lead-entrante'], 'Ambos');
        $inactive = $this->user(['notif-lead-entrante'], 'Baja', 'inactive');
        $this->copy($withPerm, $copyOnly);
        $this->copy($both, $copyOnly); // dos orígenes, una sola notificación
        $this->copy($withPerm, $both);

        $n = app(NotificationService::class)->notifyLead($this->lead($withPerm->id), 'lead_incoming', 'Lead nuevo · Manual', 'Ana');

        $this->assertSame(3, $n);
        foreach ([$withPerm, $both, $copyOnly] as $u) {
            $this->assertSame(1, AdminNotification::where('user_id', $u->id)->count(), $u->first_name);
        }
        foreach ([$other, $noPerm, $inactive] as $u) {
            $this->assertSame(0, AdminNotification::where('user_id', $u->id)->count(), $u->first_name);
        }
    }

    public function test_responsable_sin_permiso_del_tipo_no_recibe_el_aviso_de_lead(): void
    {
        $assignee = $this->user([], 'Responsable');
        app(NotificationService::class)->notifyLead($this->lead($assignee->id), 'lead_reply', 'Correo', 'x');

        $this->assertSame(0, AdminNotification::count());
    }

    public function test_tarea_llega_a_asignado_creador_y_permiso_y_nunca_al_actor(): void
    {
        $assignee = $this->user(['notif-tarea'], 'Asignado');
        $creator = $this->user(['notif-tarea'], 'Creador');
        $watcher = $this->user(['notif-tarea'], 'Vigilante');
        $actor = $this->user(['notif-tarea'], 'Actor');
        $stranger = $this->user([], 'Ajeno');

        $task = Task::create(['title' => 'Llamar', 'status' => 'open', 'assigned_to' => $assignee->id]);
        $task->logEvent('created', 'Tarea creada', $creator->id);
        AdminNotification::query()->delete();

        $task->logEvent('note', 'Nota agregada', $actor->id);

        $got = AdminNotification::pluck('user_id')->all();
        sort($got);
        $expected = [$assignee->id, $creator->id, $watcher->id];
        sort($expected);
        $this->assertSame($expected, $got);
        $this->assertNotContains($stranger->id, $got);
        $first = AdminNotification::where('user_id', $assignee->id)->first();
        $this->assertSame('task', $first->type);
        $this->assertSame('task', $first->subject_type);
        $this->assertSame($task->id, (int) $first->subject_id);
        $this->assertSame(route('admin.tasks.show', $task), $first->url);
    }

    public function test_copia_no_recibe_si_es_el_actor(): void
    {
        $watcher = $this->user(['notif-tarea'], 'Vigilante');
        $actor = $this->user([], 'Actor');
        $this->copy($watcher, $actor);

        $task = Task::create(['title' => 'T', 'status' => 'open']);
        AdminNotification::query()->delete();
        $task->logEvent('note', 'Nota', $actor->id);

        $this->assertSame([$watcher->id], AdminNotification::pluck('user_id')->all());
    }

    public function test_dedupe_por_clave_no_repite(): void
    {
        $this->user(['notif-lead-respuesta']);
        $lead = $this->lead();
        $svc = app(NotificationService::class);

        $this->assertSame(1, $svc->notifyLead($lead, 'lead_reply', 'Correo', 'a', 'wa:1'));
        $this->assertSame(0, $svc->notifyLead($lead, 'lead_reply', 'Correo', 'a', 'wa:1'));
        $this->assertSame(1, $svc->notifyLead($lead, 'lead_reply', 'Correo', 'b', 'wa:2'));
    }

    public function test_tipo_desconocido_no_rompe_el_flujo(): void
    {
        $this->assertSame(0, app(NotificationService::class)->notifyLead($this->lead(), 'tipo_inexistente', 't', null));
    }
}
