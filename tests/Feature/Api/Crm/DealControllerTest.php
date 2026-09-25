<?php

namespace Tests\Feature\Api\Crm;

use App\Models\ApiClient;
use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 3 (API N8N) — CRM / Deals. Cubre por endpoint: 401 sin token, 403 con
 * ability incorrecta, 200/201 con ability correcta (convención de
 * tests/Feature/Deals/* y tests/Feature/Workflows/*, ver comentario en
 * routes/api/crm.php).
 */
class DealControllerTest extends TestCase
{
    use RefreshDatabase;

    private function apiClient(array $abilities): array
    {
        $client = ApiClient::create([
            'name' => 'N8N Test',
            'is_active' => true,
        ]);

        $token = $client->createToken('test', $abilities)->plainTextToken;

        return [$client, $token];
    }

    private function pipelineWithStages(): array
    {
        $pipeline = Pipeline::create([
            'name' => 'Ventas',
            'is_default' => true,
            'is_active' => true,
        ]);

        $stageA = PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Nuevo',
            'slug' => 'nuevo',
            'order' => 1,
        ]);

        $stageB = PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Ganado',
            'slug' => 'ganado',
            'order' => 2,
            'is_won' => true,
        ]);

        return [$pipeline, $stageA, $stageB];
    }

    public function test_index_requires_token(): void
    {
        $this->getJson('/api/v1/deals')->assertStatus(401);
    }

    public function test_index_requires_deals_read_ability(): void
    {
        [, $token] = $this->apiClient(['tasks:write']);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/deals')
            ->assertStatus(403);
    }

    public function test_index_lists_deals_with_deals_read_ability(): void
    {
        [$pipeline, $stageA] = $this->pipelineWithStages();

        Deal::create([
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stageA->id,
            'name' => 'Negocio de prueba',
        ]);

        [, $token] = $this->apiClient(['deals:read']);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/deals');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Negocio de prueba');
    }

    public function test_store_requires_deals_write_ability(): void
    {
        [$pipeline] = $this->pipelineWithStages();

        [, $token] = $this->apiClient(['deals:read']);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/deals', [
                'pipeline_id' => $pipeline->id,
                'name' => 'Nuevo negocio',
            ])
            ->assertStatus(403);
    }

    public function test_store_creates_deal_with_deals_write_ability(): void
    {
        [$pipeline, $stageA] = $this->pipelineWithStages();

        [, $token] = $this->apiClient(['deals:write']);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/deals', [
                'pipeline_id' => $pipeline->id,
                'name' => 'Nuevo negocio',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Nuevo negocio')
            ->assertJsonPath('data.pipeline_stage_id', $stageA->id);

        $this->assertDatabaseHas('deals', ['name' => 'Nuevo negocio']);
    }

    public function test_move_stage_moves_deal_with_deals_write_ability(): void
    {
        [$pipeline, $stageA, $stageB] = $this->pipelineWithStages();

        $deal = Deal::create([
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stageA->id,
            'name' => 'Negocio a mover',
        ]);

        [, $token] = $this->apiClient(['deals:write']);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/deals/{$deal->id}/move-stage", [
                'pipeline_stage_id' => $stageB->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.pipeline_stage_id', $stageB->id)
            ->assertJsonPath('data.status', 'won');

        $this->assertDatabaseHas('deal_stage_history', [
            'deal_id' => $deal->id,
            'from_stage_id' => $stageA->id,
            'to_stage_id' => $stageB->id,
        ]);
    }

    public function test_move_stage_requires_deals_write_ability(): void
    {
        [$pipeline, $stageA, $stageB] = $this->pipelineWithStages();

        $deal = Deal::create([
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stageA->id,
            'name' => 'Negocio a mover',
        ]);

        [, $token] = $this->apiClient(['deals:read']);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/deals/{$deal->id}/move-stage", [
                'pipeline_stage_id' => $stageB->id,
            ])
            ->assertStatus(403);
    }

    public function test_show_returns_deal_with_deals_read_ability(): void
    {
        [$pipeline, $stageA] = $this->pipelineWithStages();

        $deal = Deal::create([
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stageA->id,
            'name' => 'Negocio show',
        ]);

        [, $token] = $this->apiClient(['deals:read']);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/deals/{$deal->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $deal->id);
    }

    public function test_update_requires_deals_write_ability(): void
    {
        [$pipeline, $stageA] = $this->pipelineWithStages();

        $deal = Deal::create([
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stageA->id,
            'name' => 'Negocio original',
        ]);

        [, $token] = $this->apiClient(['deals:read']);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/deals/{$deal->id}", ['name' => 'Renombrado'])
            ->assertStatus(403);
    }

    public function test_update_edits_deal_with_deals_write_ability(): void
    {
        [$pipeline, $stageA] = $this->pipelineWithStages();

        $deal = Deal::create([
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stageA->id,
            'name' => 'Negocio original',
        ]);

        [, $token] = $this->apiClient(['deals:write']);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/deals/{$deal->id}", ['name' => 'Renombrado'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Renombrado');
    }
}
