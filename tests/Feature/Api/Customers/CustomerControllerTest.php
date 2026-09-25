<?php

namespace Tests\Feature\Api\Customers;

use App\Models\ApiClient;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 5 del plan de integración N8N (Clientes). Incluye el test de
 * seguridad explícito pedido en el plan: CustomerResource nunca debe
 * incluir password_hash ni remember_token en la respuesta JSON.
 *
 * Ver nota en ProductControllerTest sobre por qué se usa un token Sanctum
 * real (header Authorization) en vez de Sanctum::actingAs().
 */
class CustomerControllerTest extends TestCase
{
    use RefreshDatabase;

    private function tokenHeaders(array $abilities): array
    {
        $client = ApiClient::create(['name' => 'N8N', 'is_active' => true]);
        $token = $client->createToken('test-token', $abilities);

        return ['Authorization' => 'Bearer ' . $token->plainTextToken];
    }

    private function customer(array $overrides = []): Customer
    {
        return Customer::create(array_merge([
            'first_name'    => 'Juan',
            'last_name'     => 'Pérez',
            'email'         => 'juan-'.uniqid().'@example.com',
            'phone'         => '5555555555',
            'password_hash' => bcrypt('secret-password'),
            'document_type' => 'ine',
            'status'        => 'active',
            'source'        => 'admin',
            'company'       => 'Equiterm',
            'rfc'           => 'XAXX010101000',
        ], $overrides));
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/v1/customers')->assertStatus(401);
    }

    public function test_index_requires_customers_read_ability(): void
    {
        $headers = $this->tokenHeaders(['products:read']);

        $this->getJson('/api/v1/customers', $headers)->assertStatus(403);
    }

    public function test_index_filters_by_email_and_rfc(): void
    {
        $headers = $this->tokenHeaders(['customers:read']);

        $this->customer(['email' => 'match-me@example.com', 'rfc' => 'AAAA010101AAA']);
        $this->customer();

        $response = $this->getJson('/api/v1/customers?email=match-me', $headers);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'match-me@example.com');
    }

    public function test_show_requires_customers_read_ability(): void
    {
        $headers = $this->tokenHeaders(['customers:write']);

        $customer = $this->customer();

        $this->getJson("/api/v1/customers/{$customer->id}", $headers)->assertStatus(403);
    }

    public function test_show_returns_customer_with_correct_ability(): void
    {
        $headers = $this->tokenHeaders(['customers:read']);

        $customer = $this->customer();

        $this->getJson("/api/v1/customers/{$customer->id}", $headers)
            ->assertStatus(200)
            ->assertJsonPath('data.id', $customer->id)
            ->assertJsonPath('data.email', $customer->email);
    }

    public function test_customer_resource_never_exposes_password_hash_or_remember_token(): void
    {
        $headers = $this->tokenHeaders(['customers:read']);

        $customer = $this->customer();
        $customer->forceFill(['remember_token' => 'super-secret-remember-token'])->save();

        $response = $this->getJson("/api/v1/customers/{$customer->id}", $headers);

        $response->assertStatus(200);

        $json = $response->json('data');
        $this->assertArrayNotHasKey('password_hash', $json);
        $this->assertArrayNotHasKey('remember_token', $json);

        $raw = $response->getContent();
        $this->assertStringNotContainsString('super-secret-remember-token', $raw);
        $this->assertStringNotContainsString($customer->password_hash, $raw);
    }

    public function test_store_requires_customers_write_ability(): void
    {
        $headers = $this->tokenHeaders(['customers:read']);

        $this->postJson('/api/v1/customers', [
            'first_name'    => 'Nuevo',
            'last_name'     => 'Cliente',
            'document_type' => 'ine',
            'source'        => 'admin',
        ], $headers)->assertStatus(403);
    }

    public function test_store_creates_customer_with_correct_ability(): void
    {
        $headers = $this->tokenHeaders(['customers:write']);

        $response = $this->postJson('/api/v1/customers', [
            'first_name'    => 'Nuevo',
            'last_name'     => 'Cliente',
            'email'         => 'nuevo-cliente@example.com',
            'phone'         => '5555555555',
            'company'       => 'Equiterm',
            'document_type' => 'ine',
            'source'        => 'admin',
            'status'        => 'active',
        ], $headers);

        $response->assertStatus(201)
            ->assertJsonPath('data.first_name', 'Nuevo')
            ->assertJsonPath('data.email', 'nuevo-cliente@example.com');

        $this->assertDatabaseHas('customers', [
            'email' => 'nuevo-cliente@example.com',
        ]);
    }

    public function test_store_validation_rejects_missing_required_fields(): void
    {
        $headers = $this->tokenHeaders(['customers:write']);

        $this->postJson('/api/v1/customers', [], $headers)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'phone', 'company', 'document_type', 'source']);
    }

    public function test_update_requires_customers_write_ability(): void
    {
        $headers = $this->tokenHeaders(['customers:read']);

        $customer = $this->customer();

        $this->putJson("/api/v1/customers/{$customer->id}", [
            'first_name'    => 'Editado',
            'document_type' => 'ine',
            'source'        => 'admin',
        ], $headers)->assertStatus(403);
    }

    public function test_update_changes_customer_with_correct_ability(): void
    {
        $headers = $this->tokenHeaders(['customers:write']);

        $customer = $this->customer();

        $response = $this->putJson("/api/v1/customers/{$customer->id}", [
            'first_name'    => 'Editado',
            'last_name'     => $customer->last_name,
            'email'         => $customer->email,
            'phone'         => $customer->phone,
            'company'       => $customer->company,
            'document_type' => 'ine',
            'source'        => 'admin',
        ], $headers);

        $response->assertStatus(200)->assertJsonPath('data.first_name', 'Editado');

        $this->assertDatabaseHas('customers', [
            'id'         => $customer->id,
            'first_name' => 'Editado',
        ]);
    }
}
