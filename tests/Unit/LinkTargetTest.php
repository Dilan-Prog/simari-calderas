<?php

namespace Tests\Unit;

use App\Models\ServicePage;
use App\Support\LinkTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesProductBlockFixtures;
use Tests\TestCase;

class LinkTargetTest extends TestCase
{
    use RefreshDatabase, CreatesProductBlockFixtures;

    private const ENTITY_TYPES = ['collection', 'service_page', 'product', 'category', 'subcategory', 'child_category'];

    // ---------- normalize ----------

    public function test_normalize_returns_null_for_empty_or_missing_type(): void
    {
        $this->assertNull(LinkTarget::normalize(null));
        $this->assertNull(LinkTarget::normalize([]));
        $this->assertNull(LinkTarget::normalize(['type' => '']));
        $this->assertNull(LinkTarget::normalize(['id' => 5]));
    }

    public function test_normalize_returns_null_for_invalid_type(): void
    {
        $this->assertNull(LinkTarget::normalize(['type' => 'banana', 'id' => 1]));
        $this->assertNull(LinkTarget::normalize(['type' => 'javascript', 'url' => 'x']));
    }

    public function test_normalize_home_needs_no_extra_data(): void
    {
        $this->assertSame(
            ['type' => 'home', 'id' => null, 'url' => null, 'new_tab' => false],
            LinkTarget::normalize(['type' => 'home'])
        );
    }

    public function test_normalize_entity_types_cast_id_to_int(): void
    {
        foreach (self::ENTITY_TYPES as $type) {
            $link = LinkTarget::normalize(['type' => $type, 'id' => '12', 'url' => 'ignored']);

            $this->assertSame($type, $link['type']);
            $this->assertSame(12, $link['id'], $type);
            $this->assertNull($link['url'], $type);
        }
    }

    public function test_normalize_entity_without_id_is_null(): void
    {
        foreach (self::ENTITY_TYPES as $type) {
            $this->assertNull(LinkTarget::normalize(['type' => $type]), "$type sin id");
            $this->assertNull(LinkTarget::normalize(['type' => $type, 'id' => '']), "$type id vacio");
            $this->assertNull(LinkTarget::normalize(['type' => $type, 'id' => 0]), "$type id 0");
        }
    }

    public function test_normalize_custom_requires_url_and_trims(): void
    {
        $this->assertNull(LinkTarget::normalize(['type' => 'custom']));
        $this->assertNull(LinkTarget::normalize(['type' => 'custom', 'url' => '   ']));

        $link = LinkTarget::normalize(['type' => 'custom', 'url' => '  https://example.com/x  ']);
        $this->assertSame('https://example.com/x', $link['url']);
        $this->assertNull($link['id']);
    }

    public function test_normalize_custom_truncates_url_to_2048(): void
    {
        $link = LinkTarget::normalize(['type' => 'custom', 'url' => 'https://e.com/' . str_repeat('a', 3000)]);

        $this->assertSame(2048, mb_strlen($link['url']));
    }

    public function test_normalize_new_tab_flag(): void
    {
        $this->assertTrue(LinkTarget::normalize(['type' => 'home', 'new_tab' => '1'])['new_tab']);
        $this->assertTrue(LinkTarget::normalize(['type' => 'home', 'new_tab' => true])['new_tab']);
        $this->assertFalse(LinkTarget::normalize(['type' => 'home', 'new_tab' => '0'])['new_tab']);
        $this->assertFalse(LinkTarget::normalize(['type' => 'home'])['new_tab']);
    }

    // ---------- resolve ----------

    public function test_resolve_null_and_empty(): void
    {
        $this->assertNull(LinkTarget::resolve(null));
        $this->assertNull(LinkTarget::resolve([]));
        $this->assertNull(LinkTarget::resolve(['id' => 1]));
        $this->assertNull(LinkTarget::resolve(['type' => 'unknown', 'id' => 1]));
    }

    public function test_resolve_home(): void
    {
        $this->assertSame(route('home'), LinkTarget::resolve(['type' => 'home']));
    }

    public function test_resolve_custom_allows_safe_schemes(): void
    {
        $urls = ['https://example.com/a', 'http://example.com', '/ruta/interna', '#ancla', 'mailto:a@b.com', 'tel:+5215555555555', 'HTTPS://EXAMPLE.COM'];

        foreach ($urls as $url) {
            $this->assertSame($url, LinkTarget::resolve(['type' => 'custom', 'url' => $url]), $url);
        }
    }

    public function test_resolve_custom_rejects_dangerous_or_empty_urls(): void
    {
        $urls = ['javascript:alert(1)', 'JaVaScRiPt:alert(1)', '  javascript:alert(1)', 'data:text/html,<script>', 'vbscript:x', 'ftp://x.com', 'example.com', '', '   '];

        foreach ($urls as $url) {
            $this->assertNull(LinkTarget::resolve(['type' => 'custom', 'url' => $url]), "'$url'");
        }

        $this->assertNull(LinkTarget::resolve(['type' => 'custom']));
    }

    public function test_resolve_product(): void
    {
        $product = $this->makeProduct();

        $this->assertSame(
            route('product.show', $product->slug),
            LinkTarget::resolve(['type' => 'product', 'id' => $product->id])
        );
    }

    public function test_resolve_product_inactive_unpublished_or_deleted_is_null(): void
    {
        $inactive = $this->makeProduct(['is_active' => false]);
        $unpublished = $this->makeProduct(['publish_on_website' => false]);
        $deleted = $this->makeProduct();
        $deletedId = $deleted->id;
        $deleted->delete();

        $this->assertNull(LinkTarget::resolve(['type' => 'product', 'id' => $inactive->id]));
        $this->assertNull(LinkTarget::resolve(['type' => 'product', 'id' => $unpublished->id]));
        $this->assertNull(LinkTarget::resolve(['type' => 'product', 'id' => $deletedId]));
        $this->assertNull(LinkTarget::resolve(['type' => 'product', 'id' => null]));
    }

    public function test_resolve_collection(): void
    {
        $collection = $this->makeCollection();
        $inactive = $this->makeCollection(['is_active' => false]);
        $gone = $this->makeCollection();
        $goneId = $gone->id;
        $gone->delete();

        $this->assertSame(route('collection.show', $collection->slug), LinkTarget::resolve(['type' => 'collection', 'id' => $collection->id]));
        $this->assertNull(LinkTarget::resolve(['type' => 'collection', 'id' => $inactive->id]));
        $this->assertNull(LinkTarget::resolve(['type' => 'collection', 'id' => $goneId]));
    }

    public function test_resolve_category_subcategory_and_child_category(): void
    {
        $category = $this->makeCategory();
        $sub = $this->makeCategory(['parent_id' => $category->id]);
        $child = $this->makeCategory(['parent_id' => $sub->id]);

        foreach (['category' => $category, 'subcategory' => $sub, 'child_category' => $child] as $type => $model) {
            $this->assertSame(
                route('catalog.category', $model->slug),
                LinkTarget::resolve(['type' => $type, 'id' => $model->id]),
                $type
            );
        }
    }

    public function test_resolve_category_types_inactive_or_deleted_are_null(): void
    {
        $inactive = $this->makeCategory(['is_active' => false]);
        $gone = $this->makeCategory();
        $goneId = $gone->id;
        $gone->delete();

        foreach (['category', 'subcategory', 'child_category'] as $type) {
            $this->assertNull(LinkTarget::resolve(['type' => $type, 'id' => $inactive->id]), "$type inactiva");
            $this->assertNull(LinkTarget::resolve(['type' => $type, 'id' => $goneId]), "$type borrada");
        }
    }

    public function test_resolve_service_page_uses_public_path(): void
    {
        $category = $this->makeServicePage(['page_type' => ServicePage::TYPE_CATEGORY, 'slug' => 'mantenimiento-' . uniqid()]);
        $leaf = $this->makeServicePage(['parent_id' => $category->id, 'slug' => 'caldera-' . uniqid()]);
        $orphan = $this->makeServicePage();

        $this->assertSame(
            url('/servicios/' . $category->slug . '/' . $leaf->slug),
            LinkTarget::resolve(['type' => 'service_page', 'id' => $leaf->id])
        );
        $this->assertSame(
            url('/servicio/' . $orphan->slug),
            LinkTarget::resolve(['type' => 'service_page', 'id' => $orphan->id])
        );
    }

    public function test_resolve_service_page_inactive_or_deleted_is_null(): void
    {
        $inactive = $this->makeServicePage(['is_active' => false]);
        $gone = $this->makeServicePage();
        $goneId = $gone->id;
        $gone->delete();

        $this->assertNull(LinkTarget::resolve(['type' => 'service_page', 'id' => $inactive->id]));
        $this->assertNull(LinkTarget::resolve(['type' => 'service_page', 'id' => $goneId]));
    }

    // ---------- isBroken ----------

    public function test_is_broken(): void
    {
        $product = $this->makeProduct();
        $inactive = $this->makeCollection(['is_active' => false]);

        $this->assertFalse((bool) LinkTarget::isBroken(null));
        $this->assertFalse((bool) LinkTarget::isBroken([]));
        $this->assertFalse((bool) LinkTarget::isBroken(['type' => 'home']));
        $this->assertFalse((bool) LinkTarget::isBroken(['type' => 'custom', 'url' => 'https://example.com']));
        $this->assertFalse((bool) LinkTarget::isBroken(['type' => 'product', 'id' => $product->id]));

        $this->assertTrue(LinkTarget::isBroken(['type' => 'product', 'id' => 999999]));
        $this->assertTrue(LinkTarget::isBroken(['type' => 'collection', 'id' => $inactive->id]));
        $this->assertTrue(LinkTarget::isBroken(['type' => 'custom', 'url' => 'javascript:alert(1)']));
    }

    // ---------- label ----------

    public function test_label(): void
    {
        $product = $this->makeProduct(['name' => 'Caldera X']);
        $collection = $this->makeCollection(['name' => 'Ofertas']);
        $category = $this->makeCategory(['name' => 'Bombas']);
        $service = $this->makeServicePage(['name' => 'Limpieza']);
        $inactiveProduct = $this->makeProduct(['name' => 'Inactivo', 'is_active' => false]);

        $this->assertNull(LinkTarget::label(null));
        $this->assertNull(LinkTarget::label([]));
        $this->assertSame('Inicio', LinkTarget::label(['type' => 'home']));
        $this->assertSame('URL personalizada: https://e.com', LinkTarget::label(['type' => 'custom', 'url' => 'https://e.com']));
        $this->assertSame('Producto específico: Caldera X', LinkTarget::label(['type' => 'product', 'id' => $product->id]));
        $this->assertSame('Colección: Ofertas', LinkTarget::label(['type' => 'collection', 'id' => $collection->id]));
        $this->assertSame('Categoría: Bombas', LinkTarget::label(['type' => 'category', 'id' => $category->id]));
        $this->assertSame('Subcategoría: Bombas', LinkTarget::label(['type' => 'subcategory', 'id' => $category->id]));
        $this->assertSame('Categoría hija: Bombas', LinkTarget::label(['type' => 'child_category', 'id' => $category->id]));
        $this->assertSame('Servicio: Limpieza', LinkTarget::label(['type' => 'service_page', 'id' => $service->id]));
        // label no filtra por activo: el admin ve el nombre aunque el enlace este roto
        $this->assertSame('Producto específico: Inactivo', LinkTarget::label(['type' => 'product', 'id' => $inactiveProduct->id]));
        $this->assertSame('Producto específico (no encontrado)', LinkTarget::label(['type' => 'product', 'id' => 999999]));
        $this->assertSame('URL personalizada (no encontrado)', LinkTarget::label(['type' => 'custom']));
    }
}
