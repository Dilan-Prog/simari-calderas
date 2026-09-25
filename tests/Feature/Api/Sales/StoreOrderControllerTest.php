<?php

namespace Tests\Feature\Api\Sales;

use App\Models\ApiClient;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Products;
use App\Models\Role;
use App\Models\StoreOrder;
use App\Models\StoreOrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre la Fase 4 del plan de integración N8N (Cotizaciones y Pedidos):
 * app/Http/Controllers/Api/Sales/StoreOrderController.php, solo lectura por
 * ahora (el plan es explícito en esto), ability store-orders:read.
 *
 * NOTA sobre auth en tests: ver comentario en QuoteControllerTest -- se usa
 * un token Bearer real (ApiClient::createToken()) en vez de
 * Sanctum::actingAs(), porque ApiClient deliberadamente no implementa
 * Authenticatable (solo necesario para ese helper de test, no para el guard
 * real en producción).
 */
class StoreOrderControllerTest extends TestCase
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

    private function product(): Products
    {
        $category = Category::create([
            'name' => 'Bombas de calor',
            'slug' => 'bombas-de-calor-' . uniqid(),
            'is_active' => true,
        ]);

        return Products::create([
            'category_id' => $category->id,
            'name' => 'Bomba de calor 5HP',
            'slug' => 'bomba-de-calor-5hp-' . uniqid(),
            'sku' => 'BDC-5HP-' . uniqid(),
            'price' => 15000,
            'stock' => 10,
            'is_active' => true,
        ]);
    }

    private function storeOrder(Customer $customer, Products $product): StoreOrder
    {
        $order = StoreOrder::create([
            'order_number' => 'PW-2026-0001',
            'customer_id' => $customer->id,
            'contact_name' => 'Juan Pérez',
            'contact_email' => $customer->email,
            'contact_phone' => '5555555555',
            'shipping_address_line1' => 'Calle Falsa 123',
            'shipping_city' => 'CDMX',
            'shipping_state' => 'CDMX',
            'shipping_postal_code' => '01000',
            'shipping_country' => 'MX',
            'subtotal' => 15000,
            'tax_total' => 2400,
            'total' => 17400,
            'currency' => 'MXN',
            'status' => 'pendiente_pago',
            'requires_invoice' => false,
        ]);

        StoreOrderItem::create([
            'store_order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'quantity' => 1,
            'unit_price' => 15000,
            'line_total' => 15000,
        ]);

        return $order;
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/v1/store-orders')->assertStatus(401);
    }

    public function test_index_requires_store_orders_read_ability(): void
    {
        $this->authenticateWithAbilities(['quotes:read']);

        $this->getJson('/api/v1/store-orders')->assertStatus(403);
    }

    public function test_index_lists_store_orders_with_correct_ability(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $this->storeOrder($customer, $product);

        $this->authenticateWithAbilities(['store-orders:read']);

        $this->getJson('/api/v1/store-orders')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.order_number', 'PW-2026-0001');
    }

    public function test_show_requires_authentication(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $order = $this->storeOrder($customer, $product);

        $this->getJson("/api/v1/store-orders/{$order->id}")->assertStatus(401);
    }

    public function test_show_requires_store_orders_read_ability(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $order = $this->storeOrder($customer, $product);

        $this->authenticateWithAbilities(['quotes:read']);

        $this->getJson("/api/v1/store-orders/{$order->id}")->assertStatus(403);
    }

    public function test_show_returns_store_order_with_items_and_hides_tax_certificate_path(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $order = $this->storeOrder($customer, $product);

        $this->authenticateWithAbilities(['store-orders:read']);

        $response = $this->getJson("/api/v1/store-orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonMissingPath('data.tax_certificate_path');
    }
}
