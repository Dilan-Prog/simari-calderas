<?php

namespace Tests\Feature\Catalog;

use App\Models\Redirect;
use App\Services\Catalog\CatalogResult;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\CreatesCatalogFixtures;
use Tests\TestCase;

class CatalogEndpointTest extends TestCase
{
    use RefreshDatabase, CreatesCatalogFixtures;

    private const JSON_KEYS = [
        'ok', 'total', 'liveMessage', 'title', 'url', 'page', 'pages', 'activeCount', 'noindex',
        'sidebarHtml', 'chipsHtml', 'toolbarHtml', 'productsHtml', 'paginationHtml',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetCatalogState();
    }

    private function ajax(string $uri)
    {
        return $this->getJson($uri);
    }

    // ── HTML ─────────────────────────────────────────────────────────────────

    public function test_html_catalog_renders_with_the_result_dto_and_vary_header(): void
    {
        $category = $this->makeCategory('Calderas');
        $this->makeProduct($category, ['name' => 'Caldera Uno']);

        $response = $this->get('/catalogo');

        $response->assertOk();
        $response->assertHeader('Vary', 'Accept');
        $response->assertViewIs('frontend.shop.catalog.index');
        $response->assertViewHas('result', fn ($r) => $r instanceof CatalogResult && $r->total === 1);
        $response->assertViewHas('category', null);
        $response->assertSee('Caldera Uno');

        $scoped = $this->get('/catalogo/' . $category->slug);
        $scoped->assertOk();
        $scoped->assertViewHas('category', fn ($c) => $c->id === $category->id);
    }

    public function test_html_with_hostile_params_never_errors(): void
    {
        $category = $this->makeCategory();
        $this->makeProduct($category);

        $this->get('/catalogo?q[]=x&orden[]=y&marca=abc&page[]=1&precio_min[]=1&precio_max=zz&f=5&disp=zzz&envio_gratis[]=1&categoria=nope')
            ->assertOk();
        $this->get('/catalogo/' . $category->slug . '?f[a][b][c]=1&f[0]=1&marca[]=%00&q=%E0%80')
            ->assertOk();
        $this->getJson('/catalogo?page=99999&f[1][]=2')->assertOk()->assertJsonPath('ok', true);
    }

    public function test_unknown_or_inactive_category_returns_404_and_old_slug_redirects(): void
    {
        $inactive = $this->makeCategory('Inactiva', null, ['is_active' => false]);

        $this->get('/catalogo/no-existe')->assertNotFound();
        $this->get('/catalogo/' . $inactive->slug)->assertNotFound();

        Redirect::record('/catalogo/slug-viejo', '/catalogo/slug-nuevo');
        $this->get('/catalogo/slug-viejo')->assertStatus(301)->assertRedirect('/catalogo/slug-nuevo');
    }

    // ── JSON ─────────────────────────────────────────────────────────────────

    public function test_json_response_has_the_frozen_contract_and_headers(): void
    {
        $category = $this->makeCategory('Calderas');
        $brand = $this->makeBrand('Alfa');
        for ($i = 0; $i < 3; $i++) {
            $this->makeProduct($category, ['name' => 'Caldera ' . $i, 'brand_id' => $brand->id]);
        }

        $response = $this->ajax('/catalogo/' . $category->slug);

        $response->assertOk();
        $response->assertHeader('Vary', 'Accept');
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));

        $data = $response->json();
        $this->assertEqualsCanonicalizing(self::JSON_KEYS, array_keys($data));
        $this->assertTrue($data['ok']);
        $this->assertSame(3, $data['total']);
        $this->assertSame('3 resultados', $data['liveMessage']);
        $this->assertSame('Calderas — Equiterm Industries', $data['title']);
        $this->assertSame(route('catalog.category', $category->slug), $data['url']);
        $this->assertSame(1, $data['page']);
        $this->assertSame(1, $data['pages']);
        $this->assertSame(0, $data['activeCount']);
        $this->assertFalse($data['noindex']);
        foreach (['sidebarHtml', 'chipsHtml', 'toolbarHtml', 'productsHtml', 'paginationHtml'] as $key) {
            $this->assertIsString($data[$key], $key);
        }
        $this->assertStringContainsString('Caldera 0', $data['productsHtml']);
    }

    public function test_json_variants_are_noindex_and_clean_page_is_not(): void
    {
        $category = $this->makeCategory();
        $brand = $this->makeBrand('Alfa');
        for ($i = 0; $i < 30; $i++) {
            $this->makeProduct($category, ['brand_id' => $brand->id, 'name' => 'Item ' . $i]);
        }

        $this->assertFalse($this->ajax('/catalogo')->json('noindex'));
        $this->assertFalse($this->ajax('/catalogo?page=2')->json('noindex'), 'Paginación válida es indexable');
        $this->assertTrue($this->ajax('/catalogo?page=9')->json('noindex'), 'Fuera de rango');
        $this->assertSame(2, $this->ajax('/catalogo?page=9')->json('page'));
        $this->assertTrue($this->ajax('/catalogo?orden=precio_asc')->json('noindex'));
        $this->assertTrue($this->ajax('/catalogo?q=Item')->json('noindex'));
        $this->assertTrue($this->ajax('/catalogo?marca[]=' . $brand->id)->json('noindex'));
        $this->assertTrue($this->ajax('/catalogo?envio_gratis=1')->json('noindex'));
        $this->assertFalse($this->ajax('/catalogo?orden=inventado')->json('noindex'), 'Orden inválido = relevancia');

        $filtered = $this->ajax('/catalogo?marca[]=' . $brand->id . '&page=2&basura=1');
        $this->assertSame(1, $filtered->json('activeCount'));
        $this->assertSame(route('catalog.index') . '?marca[]=' . $brand->id . '&page=2', $filtered->json('url'));
        $this->assertSame(2, $filtered->json('pages'));
    }

    public function test_json_empty_state_and_clear_link(): void
    {
        $category = $this->makeCategory();
        $this->makeProduct($category, ['name' => 'Algo']);

        $response = $this->ajax('/catalogo?q=zzzzzz');

        $this->assertSame(0, $response->json('total'));
        $this->assertSame('Sin resultados', $response->json('liveMessage'));
        $this->assertStringContainsString(route('catalog.index'), $response->json('productsHtml'));
        $this->assertStringContainsString('Limpiar filtros', $response->json('productsHtml'));
    }

    public function test_catalog_routes_use_the_catalog_throttle_with_a_lower_cap_for_ajax(): void
    {
        foreach (['catalog.index', 'catalog.category'] as $name) {
            $this->assertContains('throttle:catalog', Route::getRoutes()->getByName($name)->gatherMiddleware(), $name);
        }

        $limiter = RateLimiter::limiter('catalog');
        $this->assertNotNull($limiter);

        $json = Request::create('/catalogo', 'GET', server: ['HTTP_ACCEPT' => 'application/json', 'REMOTE_ADDR' => '10.0.0.9']);
        $html = Request::create('/catalogo', 'GET', server: ['REMOTE_ADDR' => '10.0.0.9']);

        $jsonLimit = $limiter($json);
        $htmlLimit = $limiter($html);
        $this->assertInstanceOf(Limit::class, $jsonLimit);
        $this->assertSame(90, $jsonLimit->maxAttempts);
        $this->assertSame(240, $htmlLimit->maxAttempts);
        $this->assertSame('10.0.0.9', $jsonLimit->key);
    }

    public function test_ajax_requests_are_throttled_after_the_cap(): void
    {
        $this->makeProduct();
        $headers = ['Accept' => 'application/json'];

        for ($i = 0; $i < 90; $i++) {
            // Sin renderizar todo 90 veces: basta con que el middleware cuente.
            $status = $this->withHeaders($headers)->get('/catalogo/no-existe-' . $i)->getStatusCode();
            $this->assertSame(404, $status);
        }

        $this->withHeaders($headers)->get('/catalogo/no-existe-extra')->assertStatus(429);
    }

    // ── Buscador en vivo ─────────────────────────────────────────────────────

    public function test_live_search_keeps_its_json_shape(): void
    {
        $zeta = $this->makeCategory('Zeta');
        $alfa = $this->makeCategory('Alfa');
        $brand = $this->makeBrand('Marca X');
        $this->makeProduct($zeta, ['name' => 'valvula uno', 'brand_id' => $brand->id]);
        $this->makeProduct($zeta, ['name' => 'valvula dos']);
        $this->makeProduct($alfa, ['name' => 'valvula tres']);
        $this->makeProduct($alfa, ['name' => 'tubo']);

        $response = $this->getJson('/buscar-en-vivo?q=valvula&orden=precio_asc');

        $response->assertOk();
        $this->assertEqualsCanonicalizing(['total', 'categories', 'brands', 'productsHtml'], array_keys($response->json()));
        $this->assertSame(3, $response->json('total'));
        $this->assertSame(
            [['id' => $alfa->id, 'name' => 'Alfa', 'count' => 1], ['id' => $zeta->id, 'name' => 'Zeta', 'count' => 2]],
            $response->json('categories')
        );
        $this->assertSame([['id' => $brand->id, 'name' => 'Marca X', 'count' => 1]], $response->json('brands'));
        $this->assertStringContainsString('valvula uno', $response->json('productsHtml'));
        $this->assertStringNotContainsString('tubo', $response->json('productsHtml'));

        // Filtros del overlay: categoria[] / marca[] (como los manda shared.js).
        $byCat = $this->getJson('/buscar-en-vivo?q=valvula&categoria[]=' . $zeta->id);
        $this->assertSame(2, $byCat->json('total'));
        $byBrand = $this->getJson('/buscar-en-vivo?q=valvula&marca[]=' . $brand->id);
        $this->assertSame(1, $byBrand->json('total'));
        // Las categorías se mantienen todas visibles al elegir una (faceta que ignora su propio filtro).
        $this->assertCount(2, $byCat->json('categories'));
    }

    public function test_live_search_requires_a_term_and_limits_to_twelve_cards(): void
    {
        $c = $this->makeCategory();
        for ($i = 0; $i < 15; $i++) {
            $this->makeProduct($c, ['name' => 'bomba ' . $i]);
        }

        $short = $this->getJson('/buscar-en-vivo?q=b');
        $this->assertSame(['total' => 0, 'categories' => [], 'brands' => [], 'productsHtml' => ''], $short->json());
        $this->assertSame(['total' => 0, 'categories' => [], 'brands' => [], 'productsHtml' => ''], $this->getJson('/buscar-en-vivo')->json());

        $found = $this->getJson('/buscar-en-vivo?q=bomba');
        $this->assertSame(15, $found->json('total'));
        $this->assertSame(12, substr_count($found->json('productsHtml'), 'search-overlay__item'));
    }

    public function test_live_search_with_no_matches_renders_the_empty_message(): void
    {
        $this->makeProduct();

        $response = $this->getJson('/buscar-en-vivo?q=nadaquecoincida');

        $this->assertSame(0, $response->json('total'));
        $this->assertStringContainsString('No encontramos productos', $response->json('productsHtml'));
    }
}
