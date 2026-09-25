<?php

namespace Tests\Feature\Api\Customers;

use App\Models\ApiClient;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ver nota en ProductControllerTest sobre por qué se usa un token Sanctum
 * real (header Authorization) en vez de Sanctum::actingAs().
 */
class CustomerAddressControllerTest extends TestCase
{
    use RefreshDatabase;

    private function tokenHeaders(array $abilities): array
    {
        $client = ApiClient::create(['name' => 'N8N', 'is_active' => true]);
        $token = $client->createToken('test-token', $abilities);

        return ['Authorization' => 'Bearer ' . $token->plainTextToken];
    }

    private function customer(): Customer
    {
        return Customer::create([
            'first_name'    => 'Juan',
            'last_name'     => 'Pérez',
            'email'         => 'juan-'.uniqid().'@example.com',
            'phone'         => '5555555555',
            'password_hash' => bcrypt('secret-password'),
            'document_type' => 'ine',
            'status'        => 'active',
            'source'        => 'admin',
            'company'       => 'Equiterm',
        ]);
    }

    public function test_index_requires_authentication(): void
    {
        $customer = $this->customer();

        $this->getJson("/api/v1/customers/{$customer->id}/addresses")->assertStatus(401);
    }

    public function test_index_requires_customers_read_ability(): void
    {
        $headers = $this->tokenHeaders(['products:read']);

        $customer = $this->customer();

        $this->getJson("/api/v1/customers/{$customer->id}/addresses", $headers)->assertStatus(403);
    }

    public function test_index_returns_addresses_with_correct_ability(): void
    {
        $headers = $this->tokenHeaders(['customers:read']);

        $customer = $this->customer();
        CustomerAddress::create([
            'customer_id'    => $customer->id,
            'label'          => 'fiscal',
            'recipient_name' => 'Juan Pérez',
            'phone'          => '5555555555',
            'country'        => 'México',
            'state'          => 'CDMX',
            'city'           => 'CDMX',
            'postal_code'    => '01000',
            'address_line1'  => 'Calle Falsa 123',
            'is_default'     => true,
        ]);

        $response = $this->getJson("/api/v1/customers/{$customer->id}/addresses", $headers);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.customer_id', $customer->id)
            ->assertJsonPath('data.0.is_default', true);
    }
}
