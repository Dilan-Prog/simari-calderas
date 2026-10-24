<?php

namespace Tests\Feature\Crm;

use App\Models\Lead;
use App\Models\Task;
use App\Services\LeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadArrivalTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_lead_nuevo_crea_su_tarea_en_tareas(): void
    {
        $lead = app(LeadService::class)->capture(['source' => 'formulario', 'name' => 'Pedrito', 'phone' => '4491234567']);

        $task = Task::where('taskable_type', Lead::class)->where('taskable_id', $lead->id)->firstOrFail();
        $this->assertStringContainsString('Llegó un lead nuevo por', $task->title);
        $this->assertStringContainsString('Pedrito', $task->title);
        $this->assertSame('open', $task->status);
        $this->assertSame('alta', $task->priority);
        $this->assertNotNull($task->due_at);
    }

    public function test_el_mismo_lead_no_duplica_la_tarea_ni_el_manual_la_crea(): void
    {
        $svc = app(LeadService::class);
        $svc->capture(['source' => 'formulario', 'name' => 'Pedrito', 'phone' => '4491234567']);
        $svc->capture(['source' => 'formulario', 'name' => 'Pedrito', 'phone' => '4491234567']);
        $this->assertSame(1, Task::count());

        $svc->capture(['source' => 'manual', 'name' => 'Ana', 'phone' => '4497654321']);
        $this->assertSame(1, Task::count());
    }

    public function test_el_correo_solo_genera_su_propia_tarea(): void
    {
        app(LeadService::class)->captureFromEmail(['message_id' => '<a@x>', 'email' => 'carlos@sigma.com', 'name' => 'Carlos', 'subject' => 'Cotizar']);

        $this->assertSame(1, Task::count());
        $this->assertStringContainsString('Atender solicitud por correo', Task::first()->title);
    }
}
