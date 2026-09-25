<?php

namespace Tests\Feature\Api\Catalog;

use App\Models\ApiClient;
use App\Models\Category;
use App\Models\Products;
use App\Models\Warehouse;
use App\Models\WarehouseProductStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 5 del plan de integración N8N (Catálogo). Cubre por endpoint: 401 sin
 * token, 403 con ability incorrecta, 200 con la ability correcta
 * (products:read), y que show() embebe el stock por almacén.
 *
 * NOTA: usa un token Sanctum real (header Authorization: Bearer ...) en vez
 * de Sanctum::actingAs() -- ApiClient (app/Models/ApiClient.php, fuera de mi
 * dominio en esta fase) no implementa
 * Illuminate\Contracts\Auth\Authenticatable, y actingAs() llama
 * Guard::setUser(), que exige ese contrato explícitamente (TypeError si no).
 * El flujo real de auth:sanctum (Illuminate\Auth\RequestGuard::user(), ver
 * vendor/laravel/framework/src/Illuminate/Auth/RequestGuard.php) nunca pasa
 * por setUser() -- solo asigna el resultado del callback -- así que un
 * request con un Bearer token real prueba el mismo camino que producción,
 * sin depender de actingAs().
 */
class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private function tokenHeaders(array $abilities, bool $active = true): array
    {
        $client = ApiClient::create(['name' => 'N8N', 'is_active' => $active]);
        $token = $client->createToken('test-token', $abilities);

        return ['Authorization' => 'Bearer ' . $token->plainTextToken];
    }

    private function category(): Category
    {
        return Category::create(['name' => 'Bombas de calor', 'slug' => 'bombas-de-calor-'.uniqid()]);
    }

    private function product(Category $category, array $overrides = []): Products
    {
        return Products::create(array_merge([
            'category_id' => $category->id,
            'name'        => 'Bomba de calor 5HP',
            'slug'        => 'bomba-de-calor-5hp-'.uniqid(),
            'sku'         => 'SKU-'.uniqid(),
            'price'       => 25999.00,
            'stock'       => 10,
        ], $overrides));
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/v1/products')->assertStatus(401);
    }

    public function test_index_requires_products_read_ability(): void
    {
        $headers = $this->tokenHeaders(['customers:read']);

        $this->getJson('/api/v1/products', $headers)->assertStatus(403);
    }

    public function test_index_returns_products_with_correct_ability(): void
    {
        $headers = $this->tokenHeaders(['products:read']);

        $category = $this->category();
        $this->product($category, ['sku' => 'FILTER-ME']);
        $this->product($category);

        $response = $this->getJson('/api/v1/products?sku=FILTER-ME', $headers);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sku', 'FILTER-ME');
    }

    public function test_show_requires_products_read_ability(): void
    {
        $headers = $this->tokenHeaders(['customers:write']);

        $product = $this->product($this->category());

        $this->getJson("/api/v1/products/{$product->id}", $headers)->assertStatus(403);
    }

    public function test_show_embeds_stock_by_warehouse(): void
    {
        $headers = $this->tokenHeaders(['products:read']);

        $product = $this->product($this->category());
        $warehouse = Warehouse::create(['name' => 'Bodega Central']);
        WarehouseProductStock::create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 7,
        ]);

        $response = $this->getJson("/api/v1/products/{$product->id}", $headers);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.stock_by_warehouse.0.warehouse_id', $warehouse->id)
            ->assertJsonPath('data.stock_by_warehouse.0.quantity', 7);
    }

    public function test_inactive_api_client_is_rejected(): void
    {
        $headers = $this->tokenHeaders(['products:read'], active: false);

        $this->getJson('/api/v1/products', $headers)->assertStatus(403);
    }
}
