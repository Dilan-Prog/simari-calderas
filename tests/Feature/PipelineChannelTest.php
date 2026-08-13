<?php

namespace Tests\Feature;

use App\Models\Pipeline;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre la Fase 12 del plan de Pipeline/Negocios: los Pipelines ahora tienen
 * un "channel" (deals|whatsapp), se puede elegir al crear, el listado lo
 * distingue, y es inmutable una vez creado (update() lo ignora aunque se
 * mande en el payload).
 */
class PipelineChannelTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $role = Role::create([
            'name_role' => 'Administrador',
            'name_role_es' => 'Administrador',
        ]);

        // No se usa User::factory() aquí: su definition() manda un campo
        // 'name' que no existe en la tabla real (first_name/last_name),
        // una discrepancia preexistente ajena a esta Fase 12 (afecta a
        // cualquier test que use el factory, ver ProfileTest).
        return User::create([
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'position' => 'Administrador',
            'phone' => '5555555555',
            'email' => 'admin-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'rfc' => 'XAXX010101000',
            'role_id' => $role->id,
        ]);
    }

    public function test_new_pipelines_default_to_deals_channel(): void
    {
        // No se manda 'channel' explícitamente: se prueba que el default de
        // columna ('deals') aplica cuando no se especifica. Eloquent no
        // refleja el default de la BD en el modelo tras create() sin pasar
        // el atributo, así que se relee de la BD.
        $pipeline = Pipeline::create(['name' => 'Ventas directas']);

        $this->assertSame(Pipeline::CHANNEL_DEALS, $pipeline->fresh()->channel);
    }

    public function test_a_pipeline_can_be_created_with_whatsapp_channel(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->postJson('/admin/pipelines', [
            'name' => 'Embudo de Venta WhatsApp',
            'channel' => 'whatsapp',
            'is_active' => true,
            'stages' => [
                ['name' => 'Nuevo contacto', 'order' => 0],
                ['name' => 'En conversación', 'order' => 1],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('pipeline.channel', 'whatsapp');

        $this->assertDatabaseHas('pipelines', [
            'name' => 'Embudo de Venta WhatsApp',
            'channel' => 'whatsapp',
        ]);
    }

    public function test_rejects_an_invalid_channel_value(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->postJson('/admin/pipelines', [
            'name' => 'Pipeline inválido',
            'channel' => 'correo',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('channel');
    }

    public function test_the_pipeline_channel_is_immutable_after_creation(): void
    {
        $admin = $this->adminUser();

        $pipeline = Pipeline::create([
            'name' => 'Embudo original',
            'channel' => Pipeline::CHANNEL_WHATSAPP,
        ]);

        // update() no acepta "channel" — aunque se mande en el payload,
        // debe ser ignorado silenciosamente (no un 422/500).
        $response = $this->actingAs($admin)->putJson("/admin/pipelines/{$pipeline->id}", [
            'name' => 'Embudo renombrado',
            'channel' => 'deals',
        ]);

        $response->assertOk();

        $pipeline->refresh();
        $this->assertSame('Embudo renombrado', $pipeline->name);
        $this->assertSame(Pipeline::CHANNEL_WHATSAPP, $pipeline->channel);
    }

    public function test_pipeline_index_listing_distinguishes_channel_in_data(): void
    {
        $admin = $this->adminUser();

        Pipeline::create(['name' => 'Ventas', 'channel' => Pipeline::CHANNEL_DEALS]);
        Pipeline::create(['name' => 'WhatsApp', 'channel' => Pipeline::CHANNEL_WHATSAPP]);

        $response = $this->actingAs($admin)->get('/admin/pipelines');

        $response->assertOk();
        $response->assertSee('Embudo de Venta (WhatsApp)');
        $response->assertSee('Negocios');

        // Nota: se invoca vía Pipeline::query() y no Pipeline::deals() a
        // secas — Pipeline ya tiene una relación de instancia deals()
        // (HasManyThrough hacia Deal), así que el nombre "deals" como scope
        // estático colisiona con ese método si se llama directo sobre la
        // clase (PHP resuelve el método de instancia existente antes de
        // pasar por __callStatic). Como scope encadenado sobre el query
        // builder no hay colisión.
        $this->assertCount(1, Pipeline::query()->whatsapp()->get());
        $this->assertCount(1, Pipeline::query()->deals()->get());
    }
}
