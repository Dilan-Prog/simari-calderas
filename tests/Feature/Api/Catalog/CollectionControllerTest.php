<?php

namespace Tests\Feature\Api\Catalog;

use App\Models\ApiClient;
use App\Models\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ver nota en ProductControllerTest sobre por qué se usa un token Sanctum
 * real (header Authorization) en vez de Sanctum::actingAs().
 */
class CollectionControllerTest extends TestCase
{
    use RefreshDatabase;

    private function tokenHeaders(array $abilities): array
    {
        $client = ApiClient::create(['name' => 'N8N', 'is_active' => true]);
        $token = $client->createToken('test-token', $abilities);

        return ['Authorization' => 'Bearer ' . $token->plainTextToken];
    }

    private function collection(array $overrides = []): Collection
    {
        return Collection::create(array_merge([
            'name' => 'Destacados',
            'slug' => 'destacados-'.uniqid(),
            'type' => 'manual',
        ], $overrides));
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/v1/collections')->assertStatus(401);
    }

    public function test_index_requires_collections_read_ability(): void
    {
        $headers = $this->tokenHeaders(['products:read']);

        $this->getJson('/api/v1/collections', $headers)->assertStatus(403);
    }

    public function test_index_returns_collections_with_correct_ability(): void
    {
        $headers = $this->tokenHeaders(['collections:read']);

        $this->collection();

        $response = $this->getJson('/api/v1/collections', $headers);

        $response->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_show_requires_collections_read_ability(): void
    {
        $headers = $this->tokenHeaders(['products:read']);

        $collection = $this->collection();

        $this->getJson("/api/v1/collections/{$collection->id}", $headers)->assertStatus(403);
    }

    public function test_show_returns_collection_with_product_count(): void
    {
        $headers = $this->tokenHeaders(['collections:read']);

        $collection = $this->collection();

        $response = $this->getJson("/api/v1/collections/{$collection->id}", $headers);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $collection->id)
            ->assertJsonPath('data.product_count', 0);
    }
}
