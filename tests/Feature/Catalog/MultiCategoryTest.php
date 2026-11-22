<?php

namespace Tests\Feature\Catalog;

use App\Models\Category;
use App\Models\Collection;
use App\Models\CollectionRule;
use App\Models\Products;
use App\Models\ShippingRule;
use App\Services\Catalog\CatalogIndex;
use App\Services\Catalog\CatalogParams;
use App\Services\Catalog\CatalogQuery;
use App\Services\SectionProducts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\CreatesCatalogFixtures;
use Tests\TestCase;

/**
 * Producto con una categoría PRINCIPAL (products.category_id) y N
 * ADICIONALES (product_category_map). Las adicionales cuentan en listados,
 * facetas, bloques, colecciones automáticas y búsqueda en vivo; NO en reglas
 * de envío ni migas de pan del producto.
 */
class MultiCategoryTest extends TestCase
{
    use RefreshDatabase, CreatesCatalogFixtures;

    private Category $root;
    private Category $a;
    private Category $parentB;
    private Category $b;
    private Products $multi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetCatalogState();

        // Árbol: Raíz A (principal) | Padre B > B (adicional)
        $this->a = $this->makeCategory('Calderas');
        $this->parentB = $this->makeCategory('Accesorios');
        $this->b = $this->makeCategory('Valvulas', $this->parentB);

        $this->multi = $this->makeProduct($this->a, ['name' => 'Caldera Multi']);
        $this->multi->syncAdditionalCategories([$this->b->id]);
    }

    private function ids($products): array
    {
        return collect($products)->pluck('id')->all();
    }

    public function test_index_row_has_all_category_ids(): void
    {
        $row = collect(CatalogIndex::get()['rows'])->firstWhere('id', $this->multi->id);

        $this->assertSame($this->a->id, $row['c']);
        $this->assertSame([$this->a->id, $this->b->id], $row['cs']);
    }

    public function test_appears_on_additional_category_and_its_parent(): void
    {
        $this->assertContains($this->multi->id, $this->pageIds($this->runCatalog([], $this->b)));
        $this->assertContains($this->multi->id, $this->pageIds($this->runCatalog([], $this->parentB)));
        $this->assertContains($this->multi->id, $this->pageIds($this->runCatalog([], $this->a)));

        $this->get('/catalogo/' . $this->b->slug)->assertOk()->assertSee('Caldera Multi');
    }

    public function test_facet_counts_do_not_double_count_within_same_subtree(): void
    {
        // Principal y adicional bajo el mismo padre: cuenta 1 en el padre.
        $b2 = $this->makeCategory('Bombas', $this->parentB);
        $both = $this->makeProduct($b2, ['name' => 'Bomba Doble']);
        $both->syncAdditionalCategories([$this->b->id]);

        $root = $this->runCatalog();
        $items = $root->facets['categories']['items'];
        $this->assertSame(1, $this->facetItem($items, 'id', $this->a->id)['count']);
        $this->assertSame(2, $this->facetItem($items, 'id', $this->parentB->id)['count'], 'Multi + Bomba Doble, sin duplicar');
        $this->assertSame(2, $root->total);

        $scoped = $this->runCatalog([], $this->parentB);
        $this->assertSame(2, $scoped->total);
        $this->assertSame(2, $this->facetItem($scoped->facets['categories']['items'], 'id', $this->b->id)['count']);
        $this->assertSame(1, $this->facetItem($scoped->facets['categories']['items'], 'id', $b2->id)['count']);
    }

    public function test_live_search_counts_additional_categories(): void
    {
        $request = Request::create('/buscar-en-vivo', 'GET', ['q' => 'Multi']);
        $out = app(CatalogQuery::class)->search(CatalogParams::fromRequest($request));

        $this->assertSame(1, $out['total']);
        $byId = collect($out['categories'])->keyBy('id');
        $this->assertSame(1, $byId[$this->a->id]['count']);
        $this->assertSame(1, $byId[$this->b->id]['count']);
    }

    public function test_section_products_category_source_includes_additional(): void
    {
        $other = $this->makeProduct($this->makeCategory('Otra'));

        $carousel = SectionProducts::resolve(['source' => 'category', 'category_id' => $this->b->id], null, 10);
        $this->assertSame([$this->multi->id], $this->ids($carousel));

        // Padre de B (descendientes) y contexto de página sin category_id.
        $grid = SectionProducts::resolve(['source' => 'category'], $this->parentB, 10);
        $this->assertSame([$this->multi->id], $this->ids($grid));
        $this->assertNotContains($other->id, $this->ids($grid));
    }

    public function test_automatic_collection_category_rule_includes_additional(): void
    {
        $collection = Collection::create([
            'name' => 'Válvulas', 'slug' => 'valvulas', 'type' => 'automatic', 'match_type' => 'all', 'is_active' => true,
        ]);
        CollectionRule::create(['collection_id' => $collection->id, 'field' => 'category_id', 'operator' => 'equals', 'value' => (string) $this->b->id]);
        $this->makeProduct($this->makeCategory('Otra'));

        $this->assertSame([$this->multi->id], $this->ids($collection->fresh('rules')->resolveProducts()));
    }

    public function test_public_api_category_filter_includes_additional(): void
    {
        $this->makeProduct($this->makeCategory('Otra'));
        $client = \App\Models\ApiClient::create(['name' => 'N8N', 'is_active' => true]);
        $token = $client->createToken('t', ['products:read'])->plainTextToken;

        $response = $this->getJson('/api/v1/products?category_id=' . $this->b->id, ['Authorization' => 'Bearer ' . $token]);

        $response->assertOk();
        $this->assertSame([$this->multi->id], array_column($response->json('data'), 'id'));
    }

    public function test_shipping_rule_uses_principal_category_only(): void
    {
        ShippingRule::create(['category_id' => $this->b->id, 'shipping_cost' => 999, 'is_active' => true]);
        Cache::driver('array')->forget('shipping_rules.active');
        $this->assertNull($this->multi->fresh()->shippingInfo()['source'], 'La regla de la adicional B no aplica');

        ShippingRule::create(['category_id' => $this->a->id, 'shipping_cost' => 150, 'is_active' => true]);
        Cache::driver('array')->forget('shipping_rules.active');
        $info = $this->multi->fresh()->shippingInfo();
        $this->assertNotNull($info['source']);
        $this->assertEquals(150, $info['cost']);
    }

    public function test_product_page_breadcrumb_uses_principal_chain_only(): void
    {
        $html = $this->get('/producto/' . $this->multi->slug)->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $crumbs = collect($m[1])->map(fn ($j) => json_decode($j, true))
            ->first(fn ($d) => ($d['@type'] ?? null) === 'BreadcrumbList');
        $this->assertNotNull($crumbs);

        $names = array_column($crumbs['itemListElement'], 'name');
        $this->assertContains('Calderas', $names);
        $this->assertNotContains('Valvulas', $names);
        $this->assertNotContains('Accesorios', $names);
    }

    public function test_product_without_additional_behaves_as_before(): void
    {
        $plain = $this->makeProduct($this->b, ['name' => 'Valvula Simple']);

        $row = collect(CatalogIndex::get()['rows'])->firstWhere('id', $plain->id);
        $this->assertSame([$this->b->id], $row['cs']);
        $this->assertNotContains($plain->id, $this->pageIds($this->runCatalog([], $this->a)));
        $this->assertContains($plain->id, $this->pageIds($this->runCatalog([], $this->parentB)));
    }

    public function test_removing_additional_category_refreshes_catalog(): void
    {
        $this->assertContains($this->multi->id, $this->pageIds($this->runCatalog([], $this->b)));

        $this->multi->syncAdditionalCategories([]);

        $this->assertNotContains($this->multi->id, $this->pageIds($this->runCatalog([], $this->b)));
    }

    public function test_deleting_additional_category_refreshes_catalog(): void
    {
        CatalogIndex::get();
        $this->b->delete();

        $row = collect(CatalogIndex::get()['rows'])->firstWhere('id', $this->multi->id);
        $this->assertSame([$this->a->id], $row['cs']);
    }
}
