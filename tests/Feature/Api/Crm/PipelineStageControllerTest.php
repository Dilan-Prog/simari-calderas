<?php

namespace Tests\Feature\Api\Crm;

use App\Models\ApiClient;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PipelineStageControllerTest extends TestCase
{
    use RefreshDatabase;

    private function apiClient(array $abilities): string
    {
        $client = ApiClient::create(['name' => 'N8N Test', 'is_active' => true]);

        return $client->createToken('test', $abilities)->plainTextToken;
    }

    public function test_index_requires_token(): void
    {
        $this->getJson('/api/v1/pipeline-stages')->assertStatus(401);
    }

    public function test_index_requires_deals_read_ability(): void
    {
        $token = $this->apiClient(['tasks:write']);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/pipeline-stages')
            ->assertStatus(403);
    }

    public function test_index_lists_stages_filtered_by_pipeline_with_deals_read_ability(): void
    {
        $pipeline = Pipeline::create(['name' => 'Ventas', 'is_active' => true]);
        $otherPipeline = Pipeline::create(['name' => 'Otro', 'is_active' => true]);

        PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Nuevo',
            'slug' => 'nuevo',
            'order' => 1,
        ]);

        PipelineStage::create([
            'pipeline_id' => $otherPipeline->id,
            'name' => 'Etapa de otro pipeline',
            'slug' => 'otra',
            'order' => 1,
        ]);

        $token = $this->apiClient(['deals:read']);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/pipeline-stages?pipeline_id={$pipeline->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Nuevo');
    }
}
