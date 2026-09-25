<?php

namespace Tests\Feature\Api\Crm;

use App\Models\ApiClient;
use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * store() exige que taskable_type sea uno de los `model` registrados en
 * config/automatable_modules.php (ver comentario en
 * App\Http\Controllers\Api\Crm\TaskController) -- App\Models\Deal está
 * registrado bajo la llave 'deal', así que se usa aquí como caso feliz.
 */
class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    private function apiClient(array $abilities): string
    {
        $client = ApiClient::create(['name' => 'N8N Test', 'is_active' => true]);

        return $client->createToken('test', $abilities)->plainTextToken;
    }

    private function deal(): Deal
    {
        $pipeline = Pipeline::create(['name' => 'Ventas', 'is_active' => true]);

        $stage = PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Nuevo',
            'slug' => 'nuevo',
            'order' => 1,
        ]);

        return Deal::create([
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'name' => 'Negocio para tarea',
        ]);
    }

    public function test_store_requires_token(): void
    {
        $this->postJson('/api/v1/tasks', [])->assertStatus(401);
    }

    public function test_store_requires_tasks_write_ability(): void
    {
        $deal = $this->deal();
        $token = $this->apiClient(['deals:read']);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/tasks', [
                'taskable_type' => Deal::class,
                'taskable_id' => $deal->id,
                'title' => 'Llamar al cliente',
            ])
            ->assertStatus(403);
    }

    public function test_store_creates_task_with_tasks_write_ability(): void
    {
        $deal = $this->deal();
        $token = $this->apiClient(['tasks:write']);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/tasks', [
                'taskable_type' => Deal::class,
                'taskable_id' => $deal->id,
                'title' => 'Llamar al cliente',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Llamar al cliente')
            ->assertJsonPath('data.taskable_type', Deal::class)
            ->assertJsonPath('data.taskable_id', $deal->id);

        $this->assertDatabaseHas('tasks', [
            'taskable_type' => Deal::class,
            'taskable_id' => $deal->id,
            'title' => 'Llamar al cliente',
        ]);
    }

    public function test_store_rejects_taskable_type_not_in_automatable_modules(): void
    {
        $token = $this->apiClient(['tasks:write']);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/tasks', [
                'taskable_type' => \App\Models\ApiClient::class,
                'taskable_id' => 1,
                'title' => 'No debería crearse',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('taskable_type');
    }

    public function test_index_requires_deals_read_ability(): void
    {
        $deal = $this->deal();
        $token = $this->apiClient(['tasks:write']);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/tasks?taskable_type=" . urlencode(Deal::class) . "&taskable_id={$deal->id}")
            ->assertStatus(403);
    }

    public function test_index_lists_tasks_for_taskable_with_deals_read_ability(): void
    {
        $deal = $this->deal();

        Task::create([
            'taskable_type' => Deal::class,
            'taskable_id' => $deal->id,
            'title' => 'Tarea existente',
        ]);

        $token = $this->apiClient(['deals:read']);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/tasks?taskable_type=" . urlencode(Deal::class) . "&taskable_id={$deal->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Tarea existente');
    }
}
