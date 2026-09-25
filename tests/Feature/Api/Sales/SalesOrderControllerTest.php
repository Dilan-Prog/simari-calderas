<?php

namespace Tests\Feature\Api\Sales;

use App\Models\ApiClient;
use App\Models\Customer;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre la Fase 4 del plan de integración N8N (Cotizaciones y Pedidos):
 * app/Http/Controllers/Api/Sales/SalesOrderController.php, solo lectura por
 * ahora (el plan es explícito en esto), ability sales-orders:read.
 *
 * NOTA sobre auth en tests: ver comentario en QuoteControllerTest -- se usa
 * un token Bearer real (ApiClient::createToken()) en vez de
 * Sanctum::actingAs(), porque ApiClient deliberadamente no implementa
 * Authenticatable (solo necesario para ese helper de test, no para el guard
 * real en producción).
 */
class SalesOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    private function adminUser(): User
    {
        if ($this->admin) {
            return $this->admin;
        }

        $role = Role::create([
            'name_role' => 'Administrador',
            'name_role_es' => 'Administrador',
        ]);

        return $this->admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'position' => 'Administrador',
            'phone' => '5555555555',
            'email' => 'admin-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'rfc' => 'XAXX010101000',
            'role_id' => $role->id,
        ]);
    }

    private function authenticateWithAbilities(array $abilities): ApiClient
    {
        $apiClient = ApiClient::create([
            'name' => 'N8N Test',
            'is_active' => true,
            'created_by' => $this->adminUser()->id,
        ]);

        $token = $apiClient->createToken('test-token', $abilities);
        $this->withToken($token->plainTextToken);

        return $apiClient;
    }

    private function customer(): Customer
    {
        return Customer::create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'email' => 'juan-' . uniqid() . '@example.com',
            'phone' => '5555555555',
            'password_hash' => bcrypt('secret'),
            'document_type' => 'RFC',
            'document_numer' => 'XAXX010101000',
            'birth_date' => '1990-01-01',
            'status' => 'active',
            'source' => 'admin',
            'company' => 'ACME',
        ]);
    }

    private function salesOrder(Customer $customer): SalesOrder
    {
        $order = SalesOrder::create([
            'order_number' => 'PED-2026-0001',
            'customer_id' => $customer->id,
            'status' => 'pendiente',
        ]);

        SalesOrderItem::create([
            'sales_order_id' => $order->id,
            'product_name' => 'Bomba de calor 5HP',
            'quantity_ordered' => 1,
            'quantity_delivered' => 0,
            'sort_order' => 0,
        ]);

        return $order;
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/v1/sales-orders')->assertStatus(401);
    }

    public function test_index_requires_sales_orders_read_ability(): void
    {
        $this->authenticateWithAbilities(['quotes:read']);

        $this->getJson('/api/v1/sales-orders')->assertStatus(403);
    }

    public function test_index_lists_sales_orders_with_correct_ability(): void
    {
        $customer = $this->customer();
        $this->salesOrder($customer);

        $this->authenticateWithAbilities(['sales-orders:read']);

        $this->getJson('/api/v1/sales-orders')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.order_number', 'PED-2026-0001');
    }

    public function test_show_requires_authentication(): void
    {
        $customer = $this->customer();
        $order = $this->salesOrder($customer);

        $this->getJson("/api/v1/sales-orders/{$order->id}")->assertStatus(401);
    }

    public function test_show_requires_sales_orders_read_ability(): void
    {
        $customer = $this->customer();
        $order = $this->salesOrder($customer);

        $this->authenticateWithAbilities(['quotes:read']);

        $this->getJson("/api/v1/sales-orders/{$order->id}")->assertStatus(403);
    }

    public function test_show_returns_sales_order_with_items(): void
    {
        $customer = $this->customer();
        $order = $this->salesOrder($customer);

        $this->authenticateWithAbilities(['sales-orders:read']);

        $this->getJson("/api/v1/sales-orders/{$order->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonCount(1, 'data.items');
    }
}
