<?php

namespace Tests\Feature\Api\Crm;

use App\Models\ApiClient;
use App\Models\Customer;
use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DealContactControllerTest extends TestCase
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
            'name' => 'Negocio con contactos',
        ]);
    }

    private function customer(): Customer
    {
        return Customer::create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'email' => 'juan-' . uniqid() . '@example.com',
            'phone' => '5555555555',
            'document_type' => 'RFC',
            'status' => 'active',
            'source' => 'api',
            'company' => 'Acme',
        ]);
    }

    public function test_index_requires_token(): void
    {
        $deal = $this->deal();

        $this->getJson("/api/v1/deals/{$deal->id}/contacts")->assertStatus(401);
    }

    public function test_store_requires_deals_write_ability(): void
    {
        $deal = $this->deal();
        $customer = $this->customer();

        $token = $this->apiClient(['deals:read']);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/deals/{$deal->id}/contacts", [
                'customer_id' => $customer->id,
                'role' => 'Comprador',
            ])
            ->assertStatus(403);
    }

    public function test_store_attaches_contact_with_deals_write_ability(): void
    {
        $deal = $this->deal();
        $customer = $this->customer();

        $token = $this->apiClient(['deals:write']);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/deals/{$deal->id}/contacts", [
                'customer_id' => $customer->id,
                'role' => 'Comprador',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.customer_id', $customer->id)
            ->assertJsonPath('data.role', 'Comprador');

        $this->assertDatabaseHas('deal_contacts', [
            'deal_id' => $deal->id,
            'customer_id' => $customer->id,
            'role' => 'Comprador',
        ]);
    }

    public function test_index_lists_contacts_with_deals_read_ability(): void
    {
        $deal = $this->deal();
        $customer = $this->customer();

        $deal->contacts()->attach($customer->id, ['role' => 'Contacto principal']);

        $token = $this->apiClient(['deals:read']);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/deals/{$deal->id}/contacts");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.customer_id', $customer->id)
            ->assertJsonPath('data.0.role', 'Contacto principal');
    }
}
