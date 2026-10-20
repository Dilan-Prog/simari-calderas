<?php

namespace Tests\Feature\Crm;

use App\Models\ApiClient;
use App\Models\Lead;
use App\Models\LeadInboundEmail;
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
}
