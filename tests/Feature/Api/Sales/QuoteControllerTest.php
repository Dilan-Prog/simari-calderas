<?php

namespace Tests\Feature\Api\Sales;

use App\Models\ApiClient;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Products;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre la Fase 4 del plan de integración N8N (Cotizaciones y Pedidos,
 * ver C:\Users\dilon\.claude\plans\replicated-frolicking-clarke.md):
 * app/Http/Controllers/Api/Sales/QuoteController.php, expuesto en
 * routes/api/quotes.php con las abilities quotes:read / quotes:write.
 *
 * Por endpoint: 401 sin token, 403 con ability incorrecta, 200/201 con la
 * ability correcta. accept() además confirma que se reutiliza
 * QuoteService::processAcceptance() -- el mismo auto-split a SalesOrder que
 * dispara Backend\QuoteController::updateStatus() -- y que es idempotente.
 *
 * NOTA sobre auth en tests: App\Models\ApiClient es deliberadamente una
 * cuenta de servicio que NO implementa Authenticatable (ver comentario en
 * el modelo) -- Sanctum::actingAs() de Sanctum requiere ese contrato
 * (Auth::guard()->setUser() lo exige) y truena con un TypeError contra
 * ApiClient. En runtime real esto nunca pasa porque Laravel\Sanctum\Guard
 * resuelve el usuario sin pasar por setUser(). Por eso aquí se emite un
 * token real (ApiClient::createToken()) y se manda como Bearer, ejercitando
 * el mismo camino de autenticación que un caller real como N8N.
 */
class QuoteControllerTest extends TestCase
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

    /**
     * Crea un ApiClient activo, emite un token real con las abilities
     * pedidas y lo manda como Bearer en las siguientes requests del test.
     */
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

    private function quoteWithProductItem(Customer $customer, Products $product): Quote
    {
        $quote = Quote::create([
            'quote_number' => 'COT-2026-0001',
            'created_by_user_id' => $this->adminUser()->id,
            'customer_id' => $customer->id,
            'status' => 'sent',
            'guest_name' => $customer->first_name . ' ' . $customer->last_name,
            'currency' => 'MXN',
            'subtotal' => 15000,
            'discount_total' => 0,
            'tax_rate' => 16,
            'tax_total' => 2400,
            'total' => 17400,
        ]);

        QuoteItem::create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'quantity' => 1,
            'unit_price' => 15000,
            'line_total' => 15000,
            'sort_order' => 0,
        ]);

        return $quote;
    }

    // ── index ────────────────────────────────────────────────────────────

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/v1/quotes')->assertStatus(401);
    }

    public function test_index_requires_quotes_read_ability(): void
    {
        $this->authenticateWithAbilities(['quotes:write']);

        $this->getJson('/api/v1/quotes')->assertStatus(403);
    }

    public function test_index_lists_quotes_with_quotes_read_ability(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $this->quoteWithProductItem($customer, $product);

        $this->authenticateWithAbilities(['quotes:read']);

        $this->getJson('/api/v1/quotes')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.quote_number', 'COT-2026-0001');
    }

    // ── show ─────────────────────────────────────────────────────────────

    public function test_show_returns_quote_with_items(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $quote = $this->quoteWithProductItem($customer, $product);

        $this->authenticateWithAbilities(['quotes:read']);

        $this->getJson("/api/v1/quotes/{$quote->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $quote->id)
            ->assertJsonCount(1, 'data.items');
    }

    // ── store ────────────────────────────────────────────────────────────

    public function test_store_requires_quotes_write_ability(): void
    {
        $this->authenticateWithAbilities(['quotes:read']);

        $this->postJson('/api/v1/quotes', [])->assertStatus(403);
    }

    public function test_store_creates_a_quote_with_quotes_write_ability(): void
    {
        $customer = $this->customer();
        $product = $this->product();

        $this->authenticateWithAbilities(['quotes:write']);

        $response = $this->postJson('/api/v1/quotes', [
            'customer_id' => $customer->id,
            'guest_name' => 'Juan Pérez',
            'tax_rate' => 16,
            'currency' => 'MXN',
            'exchange_rate' => 18.5,
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => 2,
                    'unit_price' => 15000,
                    'line_total' => 30000,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.customer_id', $customer->id)
            ->assertJsonCount(1, 'data.items');

        $this->assertDatabaseHas('quotes', [
            'id' => $response->json('data.id'),
            'customer_id' => $customer->id,
        ]);
    }

    // ── accept ───────────────────────────────────────────────────────────

    public function test_accept_requires_quotes_write_ability(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $quote = $this->quoteWithProductItem($customer, $product);

        $this->authenticateWithAbilities(['quotes:read']);

        $this->postJson("/api/v1/quotes/{$quote->id}/accept")->assertStatus(403);
    }

    public function test_accept_reuses_quote_service_to_auto_split_a_sales_order(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $quote = $this->quoteWithProductItem($customer, $product);

        $this->authenticateWithAbilities(['quotes:write']);

        $response = $this->postJson("/api/v1/quotes/{$quote->id}/accept");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'accepted');

        $quote->refresh();
        $this->assertSame('accepted', $quote->status);

        // El mismo comportamiento que dispara el botón "Aceptar" del panel
        // (Backend\QuoteController::updateStatus() -> QuoteService::processAcceptance()):
        // una línea con product_id genera un SalesOrder ligado a la cotización.
        $this->assertDatabaseHas('sales_orders', [
            'quote_id' => $quote->id,
            'customer_id' => $customer->id,
            'status' => 'pendiente',
        ]);

        $salesOrder = SalesOrder::where('quote_id', $quote->id)->firstOrFail();
        $this->assertSame(1, $salesOrder->items()->count());
    }

    public function test_accept_is_idempotent_and_does_not_duplicate_the_sales_order(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $quote = $this->quoteWithProductItem($customer, $product);

        $this->authenticateWithAbilities(['quotes:write']);

        $this->postJson("/api/v1/quotes/{$quote->id}/accept")->assertStatus(200);
        $this->postJson("/api/v1/quotes/{$quote->id}/accept")->assertStatus(200);

        $this->assertSame(1, SalesOrder::where('quote_id', $quote->id)->count());
    }
}
