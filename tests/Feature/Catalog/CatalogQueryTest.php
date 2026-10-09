<?php

namespace Tests\Feature\Catalog;

use App\Models\ProductImage;
use App\Models\ProductSalesStat;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCatalogFixtures;
use Tests\TestCase;

class CatalogQueryTest extends TestCase
{
    use RefreshDatabase, CreatesCatalogFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetCatalogState();
    }

    // ── Alcance contextual ──────────────────────────────────────────────────

    public function test_root_lists_only_level_one_categories_and_hides_zero_counts(): void
    {
        $a = $this->makeCategory('Calderas');
        $a1 = $this->makeCategory('Hijas con productos', $a);
        $a2 = $this->makeCategory('Hija vacia', $a);
        $inactive = $this->makeCategory('Raiz inactiva', null, ['is_active' => false]);
        $empty = $this->makeCategory('Raiz sin productos');

        $this->makeProduct($a);
        $this->makeProduct($a1);
        $this->makeProduct($a1);
        $this->makeProduct($inactive);

        $result = $this->runCatalog();

        $items = $result->facets['categories']['items'];
        $this->assertSame(['Calderas'], array_column($items, 'name'), 'Solo nivel 1 activas y con productos');
        $this->assertSame(3, $items[0]['count'], 'El conteo es del subárbol');
        $this->assertNull($result->facets['categories']['current']);
        $this->assertNull($result->facets['categories']['back']);
        // Un producto de categoría inactiva SÍ aparece en /catalogo aunque no en el sidebar.
        $this->assertSame(4, $result->total);
        $this->assertSame(url('/catalogo/' . $a->slug), $items[0]['url']);
    }

    public function test_scope_shows_direct_children_with_subtree_counts_and_back_link(): void
    {
        $a = $this->makeCategory('Calderas');
        $a1 = $this->makeCategory('Hija uno', $a);
        $a11 = $this->makeCategory('Nieta', $a1);
        $a2 = $this->makeCategory('Hija vacia', $a);

        $this->makeProduct($a);
        $this->makeProduct($a1);
        $this->makeProduct($a11);

        $result = $this->runCatalog([], $a);

        $this->assertSame(3, $result->total);
        $this->assertSame(['name' => 'Calderas', 'count' => 3], $result->facets['categories']['current']);
        $items = $result->facets['categories']['items'];
        $this->assertSame(['Hija uno'], array_column($items, 'name'));
        $this->assertSame(2, $items[0]['count'], 'Hija uno cuenta su subárbol (propio + nieta)');
        $this->assertSame('‹ Todas las categorías', $result->facets['categories']['back']['label']);
        $this->assertSame(route('catalog.index'), $result->facets['categories']['back']['url']);

        $nested = $this->runCatalog([], $a1);
        $this->assertSame(2, $nested->total);
        $this->assertSame('‹ Calderas', $nested->facets['categories']['back']['label']);
        $this->assertSame(route('catalog.category', $a->slug), $nested->facets['categories']['back']['url']);
    }

    public function test_homonymous_categories_under_different_parents_do_not_duplicate(): void
    {
        $x = $this->makeCategory('Calentadores X');
        $y = $this->makeCategory('Calentadores Y');
        $xMini = $this->makeCategory('MiniMasster Pro', $x);
        $yMini = $this->makeCategory('MiniMasster Pro', $y);
        $this->makeProduct($xMini);
        $this->makeProduct($yMini);
        $this->makeProduct($yMini);

        $root = $this->runCatalog();
        $names = array_column($root->facets['categories']['items'], 'name');
        $this->assertEqualsCanonicalizing(['Calentadores X', 'Calentadores Y'], $names);
        $this->assertCount(2, $names, 'No debe aparecer ninguna "MiniMasster Pro" en /catalogo');

        $scoped = $this->runCatalog([], $y);
        $this->assertSame(['MiniMasster Pro'], array_column($scoped->facets['categories']['items'], 'name'));
        $this->assertSame(2, $scoped->facets['categories']['items'][0]['count']);
        $this->assertSame(2, $scoped->total);
    }

    public function test_scope_includes_inactive_descendants_like_ids_with_children(): void
    {
        $a = $this->makeCategory('Raiz');
        $inactiveChild = $this->makeCategory('Hija inactiva', $a, ['is_active' => false]);
        $grandchild = $this->makeCategory('Nieta', $inactiveChild);
        $this->makeProduct($grandchild);

        $result = $this->runCatalog([], $a);

        $this->assertSame(1, $result->total);
        $this->assertSame([], $result->facets['categories']['items'], 'La hija inactiva no se muestra');
        $this->assertEqualsCanonicalizing($a->idsWithChildren(), array_values(\App\Services\Catalog\CatalogIndex::subtreeIds(
            \App\Services\Catalog\CatalogIndex::get(), $a->id
        )));
    }

    public function test_unpublished_and_inactive_products_are_excluded(): void
    {
        $c = $this->makeCategory();
        $this->makeProduct($c);
        $this->makeProduct($c, ['is_active' => false]);
        $this->makeProduct($c, ['publish_on_website' => false]);

        $this->assertSame(1, $this->runCatalog()->total);
    }

    public function test_page_models_are_eager_loaded_in_order(): void
    {
        $c = $this->makeCategory();
        $brand = $this->makeBrand('Marca');
        $p = $this->makeProduct($c, ['brand_id' => $brand->id]);
        ProductImage::create(['product_id' => $p->id, 'image_url' => 'https://x.test/b.jpg', 'sort_order' => 2]);
        ProductImage::create(['product_id' => $p->id, 'image_url' => 'https://x.test/a.jpg', 'sort_order' => 1]);

        $first = $this->runCatalog()->paginator->first();

        $this->assertTrue($first->relationLoaded('brand'));
        $this->assertTrue($first->relationLoaded('category'));
        $this->assertTrue($first->relationLoaded('images'));
        $this->assertSame(['https://x.test/a.jpg', 'https://x.test/b.jpg'], $first->images->pluck('image_url')->all());
    }

    // ── Facetas ──────────────────────────────────────────────────────────────

    public function test_brand_counts_ignore_own_filter_but_react_to_price(): void
    {
        $c = $this->makeCategory();
        $b1 = $this->makeBrand('Alfa');
        $b2 = $this->makeBrand('Beta');
        $b3 = $this->makeBrand('Gamma');
        $this->makeProduct($c, ['brand_id' => $b1->id, 'price' => 100]);
        $this->makeProduct($c, ['brand_id' => $b1->id, 'price' => 5000]);
        $this->makeProduct($c, ['brand_id' => $b2->id, 'price' => 200]);

        $plain = $this->runCatalog();
        $this->assertSame(['Alfa', 'Beta'], array_column($plain->facets['brands'], 'name'), 'Marca sin productos oculta; orden por conteo');
        $this->assertSame([2, 1], array_column($plain->facets['brands'], 'count'));

        // Marcar una marca NO cambia el conteo de las marcas (excluye su propio filtro).
        $withBrand = $this->runCatalog(['marca' => [$b1->id]]);
        $this->assertSame(2, $withBrand->total);
        $alfa = $this->facetItem($withBrand->facets['brands'], 'id', $b1->id);
        $beta = $this->facetItem($withBrand->facets['brands'], 'id', $b2->id);
        $this->assertTrue($alfa['selected']);
        $this->assertSame(2, $alfa['count']);
        $this->assertSame(1, $beta['count']);

        // Fijar un precio SÍ cambia los conteos de marcas.
        $withPrice = $this->runCatalog(['precio_max' => 1000]);
        $this->assertSame(1, $this->facetItem($withPrice->facets['brands'], 'id', $b1->id)['count']);
        $this->assertSame(1, $this->facetItem($withPrice->facets['brands'], 'id', $b2->id)['count']);

        $both = $this->runCatalog(['marca' => [$b1->id], 'precio_max' => 1000]);
        $this->assertSame(1, $both->total);
        $this->assertSame(1, $this->facetItem($both->facets['brands'], 'id', $b2->id)['count']);

        // Una marca seleccionada con 0 resultados sigue visible para poder desmarcarla.
        $zero = $this->runCatalog(['marca' => [$b3->id]]);
        $this->assertSame(0, $zero->total);
        $gamma = $this->facetItem($zero->facets['brands'], 'id', $b3->id);
        $this->assertNotNull($gamma);
        $this->assertTrue($gamma['selected']);
        $this->assertSame(0, $gamma['count']);
    }

    public function test_price_uses_base_price_in_mxn_with_usd_and_included_tax(): void
    {
        Setting::set('ecommerce.usd_to_mxn_rate', 20);
        Setting::set('ecommerce.iva_rate', 16);
        $c = $this->makeCategory();

        // 100 USD = 2,000 MXN sin IVA. Su columna cruda "price" vale 100.
        $usd = $this->makeProduct($c, ['price' => 100, 'currency' => 'USD']);
        // 1,160 con IVA incluido => 1,000 sin IVA.
        $withTax = $this->makeProduct($c, ['price' => 1160, 'price_includes_tax' => true]);
        // 1,500 sin IVA.
        $plain = $this->makeProduct($c, ['price' => 1500]);
        // 100 USD con IVA incluido => 2,000 - 275.86 = 1,724.14 sin IVA.
        $usdTax = $this->makeProduct($c, ['price' => 100, 'currency' => 'USD', 'price_includes_tax' => true]);

        $window = $this->runCatalog(['precio_min' => 1500, 'precio_max' => 1800]);
        $this->assertEqualsCanonicalizing([$plain->id, $usdTax->id], $this->pageIds($window));

        $asc = $this->runCatalog(['orden' => 'precio_asc']);
        $this->assertSame([$withTax->id, $plain->id, $usdTax->id, $usd->id], $this->pageIds($asc));

        $desc = $this->runCatalog(['orden' => 'precio_desc']);
        $this->assertSame([$usd->id, $usdTax->id, $plain->id, $withTax->id], $this->pageIds($desc));

        $this->assertSame(1000, $asc->facets['price']['min']);
        $this->assertSame(2000, $asc->facets['price']['max']);
    }

    public function test_free_shipping_and_availability_filters(): void
    {
        $c = $this->makeCategory();
        $free = $this->makeProduct($c);
        $paid = $this->makeProduct($c, ['shipping_cost' => 50]);
        $out = $this->makeProduct($c, ['availability' => 'out_of_stock', 'stock' => 5]);
        $onOrderEmpty = $this->makeProduct($c, ['availability' => 'on_order', 'stock' => 0]);
        $onOrderStock = $this->makeProduct($c, ['availability' => 'on_order', 'stock' => 4]);
        $zero = $this->makeProduct($c, ['stock' => 0]);

        Setting::set('catalog.fast_shipping_label', 'Rapidito');

        $plain = $this->runCatalog();
        $stock = $this->facetItem($plain->facets['availability'], 'key', 'stock');
        $fast = $this->facetItem($plain->facets['availability'], 'key', '24_48h');
        $this->assertSame(3, $stock['count']);
        $this->assertSame(2, $fast['count']);
        $this->assertSame('Rapidito', $fast['label']);
        $this->assertSame('disp[]', $fast['inputName']);
        $this->assertSame(5, $plain->facets['free_shipping']['count']);
        $this->assertSame('envio_gratis', $plain->facets['free_shipping']['inputName']);

        $this->assertEqualsCanonicalizing([$free->id, $paid->id, $onOrderStock->id], $this->pageIds($this->runCatalog(['disp' => ['stock']])));
        $this->assertEqualsCanonicalizing([$free->id, $paid->id], $this->pageIds($this->runCatalog(['disp' => ['24_48h']])));
        $this->assertEqualsCanonicalizing([$free->id, $paid->id], $this->pageIds($this->runCatalog(['disp' => ['stock', '24_48h']])), 'AND entre disponibilidades');
        $this->assertEqualsCanonicalizing(
            [$free->id, $out->id, $onOrderEmpty->id, $onOrderStock->id, $zero->id],
            $this->pageIds($this->runCatalog(['envio_gratis' => '1']))
        );

        // AND entre disponibilidad y envío gratis.
        $combined = $this->runCatalog(['disp' => ['stock'], 'envio_gratis' => '1']);
        $this->assertEqualsCanonicalizing([$free->id, $onOrderStock->id], $this->pageIds($combined));
        // Los conteos de la faceta de envío ignoran su propio filtro: dentro de "stock" hay 3 con envío... gratis 2.
        $this->assertSame(2, $combined->facets['free_shipping']['count']);
        $this->assertSame(2, $this->facetItem($combined->facets['availability'], 'key', 'stock')['count']);
    }

    public function test_shipping_rules_are_respected_for_free_shipping(): void
    {
        $c = $this->makeCategory();
        $other = $this->makeCategory('Otra');
        $this->makeProduct($c);
        $this->makeProduct($other);
        \App\Models\ShippingRule::create(['category_id' => $c->id, 'shipping_cost' => 99, 'is_active' => true]);

        $this->assertSame(1, $this->runCatalog(['envio_gratis' => '1'])->total);
    }

    public function test_tech_groups_use_or_within_group_and_and_between_groups(): void
    {
        $parent = $this->makeCategory('Controles');
        $child = $this->makeCategory('Temperatura', $parent);
        $type = $this->makeFilterGroup($child, 'Tipo', ['Termopar' => 'Termopar', 'RTD' => 'RTD', 'Otro' => 'Ninguna']);
        $volt = $this->makeFilterGroup($parent, 'Voltaje', ['12 V' => '12V', '24 V' => '24V']);

        $t12 = $this->makeProduct($child, ['tags' => ['TERMOPAR', '12V']]);
        $t24 = $this->makeProduct($child, ['tags' => ['termopar', '24v']]);
        $r12 = $this->makeProduct($child, ['tags' => ['rtd', '12v']]);
        $none = $this->makeProduct($child, ['tags' => ['sin relacion']]);

        $tId = $type['group']->id;
        $vId = $volt['group']->id;
        $termopar = $type['options']['Termopar']->id;
        $rtd = $type['options']['RTD']->id;
        $v24 = $volt['options']['24 V']->id;
        $v12 = $volt['options']['12 V']->id;

        // OR dentro del grupo.
        $or = $this->runCatalog(['f' => [$tId => [$termopar, $rtd]]], $child);
        $this->assertEqualsCanonicalizing([$t12->id, $t24->id, $r12->id], $this->pageIds($or));

        // AND entre grupos.
        $and = $this->runCatalog(['f' => [$tId => [$termopar, $rtd], $vId => [$v24]]], $child);
        $this->assertSame([$t24->id], $this->pageIds($and));

        // Conteos facetados: cada grupo ignora su propio filtro pero respeta el del otro.
        $groups = collect($and->facets['tech'])->keyBy('id');
        $typeCounts = collect($groups[$tId]['options'])->pluck('count', 'id');
        $this->assertSame(1, $typeCounts[$termopar], 'Con voltaje 24 V: 1 Termopar');
        $this->assertSame(0, $typeCounts[$rtd], 'y 0 RTD (seleccionada, por eso sigue visible)');
        $voltCounts = collect($groups[$vId]['options'])->pluck('count', 'id');
        $this->assertSame(2, $voltCounts[$v12]); // t12 y r12 (cumplen tipo termopar/rtd)
        $this->assertSame(1, $voltCounts[$v24]);
        $this->assertSame(['Voltaje', 'Tipo'], $groups->pluck('name')->values()->all(), 'Grupo heredado del padre primero');

        // Opción sin productos: oculta salvo que esté seleccionada.
        $otherId = $type['options']['Otro']->id;
        $hidden = $this->runCatalog([], $child);
        $this->assertNull($this->facetItem(collect($hidden->facets['tech'])->keyBy('id')[$tId]['options'], 'id', $otherId));
        $selected = $this->runCatalog(['f' => [$tId => [$otherId]]], $child);
        $this->assertSame(0, $selected->total);
        $kept = $this->facetItem(collect($selected->facets['tech'])->keyBy('id')[$tId]['options'], 'id', $otherId);
        $this->assertNotNull($kept);
        $this->assertTrue($kept['selected']);
        $this->assertSame("f[{$tId}][]", $kept['inputName']);
    }

    public function test_tech_groups_apply_to_own_category_and_descendants_not_to_parent_siblings_or_root(): void
    {
        $parent = $this->makeCategory('Madre');
        $child = $this->makeCategory('Hija', $parent);
        $grandchild = $this->makeCategory('Nieta', $child);
        $sibling = $this->makeCategory('Hermana', $parent);

        $onChild = $this->makeFilterGroup($child, 'Solo hija', ['A' => 'tag-a']);
        $onParent = $this->makeFilterGroup($parent, 'En madre', ['M' => 'tag-m']);

        foreach ([$parent, $child, $grandchild, $sibling] as $cat) {
            $this->makeProduct($cat, ['tags' => ['tag-a', 'tag-m']]);
        }

        $groupNames = fn ($result) => array_column($result->facets['tech'], 'name');

        $this->assertSame(['En madre'], $groupNames($this->runCatalog([], $parent)), 'La madre no hereda el grupo de la hija');
        $this->assertSame(['En madre', 'Solo hija'], $groupNames($this->runCatalog([], $child)));
        $this->assertSame(['En madre', 'Solo hija'], $groupNames($this->runCatalog([], $grandchild)), 'Heredado a descendientes');
        $this->assertSame(['En madre'], $groupNames($this->runCatalog([], $sibling)), 'La hermana no ve el grupo de la hija');
        $this->assertSame([], $groupNames($this->runCatalog()), '/catalogo no muestra filtros técnicos');

        // Un f[] de un grupo que no aplica a la ruta se ignora sin error.
        $ignored = $this->runCatalog(['f' => [$onChild['group']->id => [$onChild['options']['A']->id]]], $parent);
        $this->assertSame(0, $ignored->params->activeCount());
        $this->assertSame(4, $ignored->total);

        // Y desde /catalogo, también.
        $root = $this->runCatalog(['f' => [$onParent['group']->id => [$onParent['options']['M']->id]]]);
        $this->assertSame([], $root->params->tech);
    }

    public function test_inactive_groups_and_options_are_not_offered(): void
    {
        $c = $this->makeCategory();
        $g = $this->makeFilterGroup($c, 'Activo', ['Si' => 'si', 'No' => 'no']);
        $inactiveGroup = $this->makeFilterGroup($c, 'Inactivo', ['X' => 'x'], ['is_active' => false]);
        $g['options']['No']->update(['is_active' => false]);
        $this->makeProduct($c, ['tags' => ['si', 'no', 'x']]);

        $groups = collect($this->runCatalog([], $c)->facets['tech']);
        $this->assertSame(['Activo'], $groups->pluck('name')->all());
        $this->assertSame(['Si'], array_column($groups[0]['options'], 'label'));
    }

    public function test_price_ranges_use_pretty_cuts_with_faceted_counts(): void
    {
        $c = $this->makeCategory();
        $b = $this->makeBrand('Barata');
        foreach ([100, 200, 300, 1000, 2000, 3000, 10000, 20000, 30000] as $price) {
            $this->makeProduct($c, ['price' => $price, 'brand_id' => in_array($price, [100, 200, 300]) ? $b->id : null]);
        }

        $plain = $this->runCatalog();
        $ranges = $plain->facets['price']['ranges'];
        $this->assertSame(['Hasta $250', '$250 a $2,500', 'Más de $2,500'], array_column($ranges, 'label'));
        $this->assertSame([2, 3, 4], array_column($ranges, 'count'));
        $this->assertSame([null, 250, 2500], array_column($ranges, 'min'));
        $this->assertSame([250, 2500, null], array_column($ranges, 'max'));
        $this->assertSame(100, $plain->facets['price']['min']);
        $this->assertSame(30000, $plain->facets['price']['max']);

        // Con marca: el conteo cambia, los límites NO se mueven; el rango vacío se oculta.
        $branded = $this->runCatalog(['marca' => [$b->id]]);
        $this->assertSame(['Hasta $250', '$250 a $2,500'], array_column($branded->facets['price']['ranges'], 'label'));
        $this->assertSame([2, 1], array_column($branded->facets['price']['ranges'], 'count'));

        // Rango activo y su href (alternar).
        $active = $this->runCatalog(['precio_min' => 250, 'precio_max' => 2500]);
        $mid = $this->facetItem($active->facets['price']['ranges'], 'label', '$250 a $2,500');
        $this->assertTrue($mid['active']);
        $this->assertStringNotContainsString('precio_', $mid['href']);
        $this->assertSame(3, $active->total);
        $this->assertSame(['min' => 250, 'max' => 2500], $active->facets['price']['selected']);

        // Los límites inclusivos usan el mismo predicado que el filtro.
        $edge = $this->runCatalog(['precio_min' => 100, 'precio_max' => 300]);
        $this->assertSame(3, $edge->total);
    }

    public function test_ranges_are_empty_with_few_products(): void
    {
        $c = $this->makeCategory();
        $this->makeProduct($c, ['price' => 500]);
        $this->assertSame([], $this->runCatalog()->facets['price']['ranges'], 'Un solo producto');

        $this->makeProduct($c, ['price' => 500]);
        $this->assertSame([], $this->runCatalog()->facets['price']['ranges'], 'Mismo precio');

        $this->makeProduct($c, ['price' => 520]);
        $ranges = $this->runCatalog()->facets['price']['ranges'];
        $this->assertSame(['Hasta $500', 'Más de $500'], array_column($ranges, 'label'));
        // Límites inclusivos con el mismo predicado que el filtro: los de $500 caen en ambos.
        $this->assertSame([2, 3], array_column($ranges, 'count'));
    }

    // ── Orden ────────────────────────────────────────────────────────────────

    public function test_order_whitelist_and_every_order_key(): void
    {
        $c = $this->makeCategory();
        $a = $this->makeProduct($c, ['price' => 100, 'created_at' => '2024-01-01 00:00:00']);
        $b = $this->makeProduct($c, ['price' => 200, 'compare_price' => 400, 'is_featured' => true, 'created_at' => '2024-03-01 00:00:00']);
        $cc = $this->makeProduct($c, ['price' => 300, 'compare_price' => 330, 'created_at' => '2024-02-01 00:00:00']);
        $d = $this->makeProduct($c, ['price' => 400, 'created_at' => '2024-04-01 00:00:00']);
        ProductSalesStat::create(['product_id' => $cc->id, 'units' => 10]);
        ProductSalesStat::create(['product_id' => $b->id, 'units' => 5]);
        ProductSalesStat::create(['product_id' => $d->id, 'units' => 5]);

        $order = fn (string $key) => $this->pageIds($this->runCatalog(['orden' => $key]));

        $this->assertSame([$b->id, $d->id, $cc->id, $a->id], $order('relevancia'));
        $this->assertSame([$a->id, $b->id, $cc->id, $d->id], $order('precio_asc'));
        $this->assertSame([$d->id, $cc->id, $b->id, $a->id], $order('precio_desc'));
        $this->assertSame([$d->id, $b->id, $cc->id, $a->id], $order('nuevos'));
        $this->assertSame([$cc->id, $b->id, $d->id, $a->id], $order('vendidos'), 'u DESC y desempate por relevancia');
        $this->assertSame([$b->id, $cc->id, $d->id, $a->id], $order('descuento'));

        // Fuera de la whitelist => relevancia, sin error.
        $bad = $this->runCatalog(['orden' => 'DROP TABLE']);
        $this->assertSame('relevancia', $bad->params->order);
        $this->assertSame([$b->id, $d->id, $cc->id, $a->id], $this->pageIds($bad));
        $this->assertSame('relevancia', collect($bad->sortOptions)->firstWhere('selected', true)['key']);
        $this->assertSame(
            ['relevancia', 'descuento', 'precio_asc', 'precio_desc', 'vendidos', 'nuevos'],
            array_column($bad->sortOptions, 'key')
        );
        $this->assertFalse($bad->meta['noindex']);
    }

    public function test_discount_order_is_by_percentage_in_mxn_and_ignores_inverted_data(): void
    {
        $c = $this->makeCategory();
        $bigAbsolute = $this->makeProduct($c, ['price' => 10000, 'compare_price' => 11000]); // 9 %, $1,000
        $bigPercent = $this->makeProduct($c, ['price' => 100, 'compare_price' => 150]);      // 33 %, $50
        $inverted = $this->makeProduct($c, ['price' => 500, 'compare_price' => 300]);        // sin descuento
        $none = $this->makeProduct($c, ['price' => 50]);

        $ids = $this->pageIds($this->runCatalog(['orden' => 'descuento']));

        $this->assertSame($bigPercent->id, $ids[0]);
        $this->assertSame($bigAbsolute->id, $ids[1]);
        $this->assertEqualsCanonicalizing([$inverted->id, $none->id], array_slice($ids, 2));

        $row = collect(\App\Services\Catalog\CatalogIndex::get()['rows'])->keyBy('id');
        $this->assertSame(33, $row[$bigPercent->id]['d']);
        $this->assertNull($row[$inverted->id]['d']);
    }

    // ── Búsqueda ─────────────────────────────────────────────────────────────

    public function test_q_escapes_like_wildcards_and_searches_name_sku_description_and_brand(): void
    {
        $c = $this->makeCategory();
        $pct = $this->makeProduct($c, ['name' => '100% algodon']);
        $x = $this->makeProduct($c, ['name' => '100X algodon']);
        $under = $this->makeProduct($c, ['name' => 'a_b conector']);
        $axb = $this->makeProduct($c, ['name' => 'axb conector']);
        $sku = $this->makeProduct($c, ['name' => 'Sin relacion', 'sku' => 'ZZ-9999']);
        $desc = $this->makeProduct($c, ['name' => 'Otro', 'description' => 'incluye valvula especial']);
        $brand = $this->makeBrand('Zeta Corp');
        $branded = $this->makeProduct($c, ['name' => 'Plano', 'brand_id' => $brand->id]);

        $ids = fn (string $q) => $this->pageIds($this->runCatalog(['q' => $q]));

        $this->assertSame([$pct->id], $ids('100%'));
        $this->assertSame([$under->id], $ids('a_b'));
        $this->assertEqualsCanonicalizing([$pct->id, $x->id], $ids('algodon'));
        $this->assertSame([$sku->id], $ids('zz-9999'));
        $this->assertSame([$desc->id], $ids('valvula'));
        $this->assertSame([$branded->id], $ids('zeta'));
        $this->assertSame([], $ids('a\\b'), 'La barra invertida no rompe el LIKE');

        // q de 1 carácter se ignora; q se recorta a 80.
        $short = $this->runCatalog(['q' => 'a']);
        $this->assertSame('', $short->params->q);
        $this->assertSame(7, $short->total);
        $this->assertSame(80, mb_strlen($this->runCatalog(['q' => str_repeat('x', 200)])->params->q));
    }

    public function test_q_combines_with_other_filters_and_facets_count_only_matches(): void
    {
        $c = $this->makeCategory();
        $b1 = $this->makeBrand('Uno');
        $b2 = $this->makeBrand('Dos');
        $this->makeProduct($c, ['name' => 'valvula grande', 'brand_id' => $b1->id]);
        $this->makeProduct($c, ['name' => 'valvula chica', 'brand_id' => $b2->id]);
        $this->makeProduct($c, ['name' => 'tubo', 'brand_id' => $b2->id]);

        $result = $this->runCatalog(['q' => 'valvula']);

        $this->assertSame(2, $result->total);
        $this->assertSame(1, $this->facetItem($result->facets['brands'], 'id', $b2->id)['count'], 'No cuenta "tubo"');
        $this->assertSame(1, $result->meta['activeCount']);
        $this->assertTrue($result->meta['noindex']);
    }

    // ── Paginación ───────────────────────────────────────────────────────────

    public function test_pagination_is_24_per_page_and_out_of_range_adjusts_to_last_with_noindex(): void
    {
        $c = $this->makeCategory();
        for ($i = 0; $i < 30; $i++) {
            $this->makeProduct($c);
        }

        $first = $this->runCatalog();
        $this->assertSame(24, $first->paginator->count());
        $this->assertSame(30, $first->total);
        $this->assertSame(30, $first->paginator->total());
        $this->assertSame(2, $first->meta['pages']);
        $this->assertFalse($first->meta['noindex']);
        $this->assertSame(route('catalog.index'), $first->meta['canonicalUrl']);
        $this->assertSame('30 resultados', $first->meta['liveMessage']);

        $second = $this->runCatalog(['page' => 2]);
        $this->assertSame(6, $second->paginator->count());
        $this->assertSame(2, $second->meta['page']);
        $this->assertFalse($second->meta['noindex']);
        $this->assertSame(route('catalog.index') . '?page=2', $second->meta['canonicalUrl'], 'Autorreferenciado');
        $this->assertSame(route('catalog.index') . '?page=2', $second->meta['stateUrl']);
        $this->assertEmpty(array_intersect($this->pageIds($first), $this->pageIds($second)));

        $far = $this->runCatalog(['page' => 99]);
        $this->assertSame(2, $far->meta['page']);
        $this->assertSame(6, $far->paginator->count());
        $this->assertTrue($far->meta['noindex']);
        $this->assertSame(route('catalog.index'), $far->meta['canonicalUrl']);

        $this->assertSame(1, $this->runCatalog(['page' => 'abc'])->meta['page']);
        $this->assertSame(1, $this->runCatalog(['page' => 0])->meta['page']);
        $this->assertSame(1, $this->runCatalog(['page' => -4])->meta['page']);
    }

    public function test_empty_and_single_result_messages(): void
    {
        $c = $this->makeCategory();
        $this->assertSame('Sin resultados', $this->runCatalog()->meta['liveMessage']);
        $this->makeProduct($c);
        $this->assertSame('1 resultado', $this->runCatalog()->meta['liveMessage']);
    }

    // ── Normalización de parámetros ──────────────────────────────────────────

    public function test_params_whitelist_and_normalization(): void
    {
        $c = $this->makeCategory();
        $brand = $this->makeBrand('Real');

        $params = fn (array $q, $scope = null) => \App\Services\Catalog\CatalogParams::fromRequest(
            \Illuminate\Http\Request::create('/catalogo', 'GET', $q),
            $scope
        );

        // Escalar donde se espera arreglo => arreglo; elementos no enteros se descartan.
        $this->assertSame([$brand->id], $params(['marca' => (string) $brand->id])->brands);
        $this->assertSame([$brand->id], $params(['marca' => ['abc', '-1', "{$brand->id}", '99999', ['x'], '1.5']])->brands);

        // Precio: enteros, rango y swap.
        $swap = $params(['precio_min' => '5000', 'precio_max' => '100']);
        $this->assertSame([100, 5000], [$swap->priceMin, $swap->priceMax]);
        $this->assertNull($params(['precio_min' => 'abc'])->priceMin);
        $this->assertNull($params(['precio_max' => '99999999999'])->priceMax);
        $this->assertNull($params(['precio_max' => '10000001'])->priceMax);
        $this->assertSame(10000000, $params(['precio_max' => '10000000'])->priceMax);
        $this->assertSame(10, $params(['precio_min' => '10.5'])->priceMin);
        $this->assertNull($params(['precio_min' => ['1']])->priceMin);
        $this->assertNull($params(['precio_min' => '0'])->priceMin, 'Desde 0 no filtra');
        $this->assertSame(0, $params(['precio_max' => '0'])->priceMax);

        // Disponibilidad.
        $this->assertSame(['stock'], $params(['disp' => 'stock'])->availability);
        $this->assertSame(['stock', '24_48h'], $params(['disp' => ['24_48h', 'foo', 'stock', 'stock']])->availability);
        $this->assertSame([], $params(['disp' => ['foo', ['x']]])->availability);

        // Envío gratis.
        $this->assertTrue($params(['envio_gratis' => '1'])->freeShipping);
        $this->assertFalse($params(['envio_gratis' => '0'])->freeShipping);
        $this->assertFalse($params(['envio_gratis' => 'yes'])->freeShipping);
        $this->assertFalse($params(['envio_gratis' => ['1']])->freeShipping);

        // q
        $this->assertSame('', $params(['q' => ['x', 'y']])->q);
        $this->assertSame('', $params(['q' => ' a '])->q);
        $this->assertSame('ab cd', $params(['q' => "  ab \n\t cd "])->q);

        // orden / page
        $this->assertSame('relevancia', $params(['orden' => ['precio_asc']])->order);
        $this->assertSame('precio_asc', $params(['orden' => 'precio_asc'])->order);
        $this->assertSame(1, $params(['page' => ['2']])->page);

        // f escalar o sin categoría => ignorado.
        $this->assertSame([], $params(['f' => '5'], $c)->tech);

        // Máximo 30 marcas.
        $ids = [];
        for ($i = 0; $i < 35; $i++) {
            $ids[] = $this->makeBrand('Marca ' . $i)->id;
        }
        $this->assertCount(30, $params(['marca' => $ids])->brands);
    }

    public function test_params_to_query_is_canonical_without_defaults(): void
    {
        $brandB = $this->makeBrand('B');
        $brandA = $this->makeBrand('A');

        $p = \App\Services\Catalog\CatalogParams::fromRequest(\Illuminate\Http\Request::create('/catalogo', 'GET', [
            'orden' => 'relevancia', 'page' => '1', 'marca' => [$brandB->id, $brandA->id],
            'disp' => ['24_48h', 'stock'], 'envio_gratis' => '1', 'q' => 'hola', 'precio_min' => '10',
        ]));

        $this->assertSame([
            'q' => 'hola', 'marca' => [$brandB->id, $brandA->id], 'precio_min' => 10,
            'disp' => ['stock', '24_48h'], 'envio_gratis' => 1,
        ], $p->toQuery());
        $this->assertSame(7, $p->activeCount(), 'q + 2 marcas + precio + 2 disp + envío');
        $this->assertSame(
            'q=hola&marca[]=' . min($brandA->id, $brandB->id) . '&marca[]=' . max($brandA->id, $brandB->id)
            . '&precio_min=10&disp[]=stock&disp[]=24_48h&envio_gratis=1',
            \App\Services\Catalog\CatalogParams::buildQueryString($p->toQuery())
        );
    }

    // ── href, chips y DTO ────────────────────────────────────────────────────

    public function test_hrefs_toggle_state_and_never_carry_page_or_defaults(): void
    {
        $c = $this->makeCategory();
        $b1 = $this->makeBrand('Alfa');
        $b2 = $this->makeBrand('Beta');
        for ($i = 0; $i < 30; $i++) {
            $this->makeProduct($c, ['brand_id' => $i < 26 ? $b1->id : $b2->id, 'price' => 100 + $i]);
        }
        $base = route('catalog.index');

        $result = $this->runCatalog(['marca' => [$b1->id], 'page' => 2, 'orden' => 'precio_asc']);

        $alfa = $this->facetItem($result->facets['brands'], 'id', $b1->id);
        $beta = $this->facetItem($result->facets['brands'], 'id', $b2->id);
        $this->assertSame("{$base}?orden=precio_asc", $alfa['href'], 'Desmarcar: sin marca y sin page');
        $this->assertSame("{$base}?marca[]={$b1->id}&marca[]={$b2->id}&orden=precio_asc", $beta['href']);
        $this->assertStringNotContainsString('page', $beta['href']);

        $byKey = collect($result->sortOptions)->keyBy('key');
        $this->assertSame("{$base}?marca[]={$b1->id}", $byKey['relevancia']['href'], 'Relevancia no lleva orden=');
        $this->assertSame("{$base}?marca[]={$b1->id}&orden=vendidos", $byKey['vendidos']['href']);
        $this->assertTrue($byKey['precio_asc']['selected']);

        $this->assertSame([['label' => 'Alfa', 'removeHref' => "{$base}?orden=precio_asc"]], $result->chips);
        $this->assertSame("{$base}?orden=precio_asc", $result->clearHref);
        $this->assertSame($result->params->activeCount(), count($result->chips));
        $this->assertSame("{$base}?marca[]={$b1->id}&orden=precio_asc&page=2", $result->meta['stateUrl']);
        $this->assertSame(1, $result->meta['activeCount']);
        $this->assertTrue($result->meta['noindex']);
        $this->assertSame($base, $result->meta['canonicalUrl']);
    }

    public function test_price_form_and_category_links_keep_the_right_params(): void
    {
        $parent = $this->makeCategory('Madre');
        $child = $this->makeCategory('Hija', $parent);
        $brand = $this->makeBrand('Alfa');
        $this->makeProduct($child, ['brand_id' => $brand->id, 'price' => 100]);
        $this->makeProduct($child, ['brand_id' => $brand->id, 'price' => 900]);

        $result = $this->runCatalog([
            'marca' => [$brand->id], 'precio_min' => 50, 'precio_max' => 500, 'orden' => 'nuevos',
            'q' => 'Producto', 'disp' => ['stock'], 'envio_gratis' => '1',
        ], $parent);

        $price = $result->facets['price'];
        $this->assertSame(route('catalog.category', $parent->slug), $price['formAction']);
        $this->assertSame([
            'q'         => 'Producto',
            'marca[0]'  => (string) $brand->id,
            'disp[0]'   => 'stock',
            'envio_gratis' => '1',
            'orden'     => 'nuevos',
        ], $price['hidden']);

        // Al cambiar de categoría se conservan q/marca/disp/envío/orden y se descarta el precio.
        $item = $result->facets['categories']['items'][0];
        $this->assertStringStartsWith(route('catalog.category', $child->slug) . '?', $item['url']);
        $this->assertStringContainsString("marca[]={$brand->id}", $item['url']);
        $this->assertStringContainsString('orden=nuevos', $item['url']);
        $this->assertStringContainsString('q=Producto', $item['url']);
        $this->assertStringContainsString('envio_gratis=1', $item['url']);
        $this->assertStringNotContainsString('precio_', $item['url']);
        $this->assertStringNotContainsString('page', $item['url']);

        $labels = array_column($result->chips, 'label');
        $this->assertContains('$50 a $500', $labels);
        $this->assertContains('En stock', $labels);
        $this->assertContains('Envío gratis', $labels);
    }

    public function test_search_method_returns_flat_category_and_brand_counts(): void
    {
        $a = $this->makeCategory('Zeta');
        $b = $this->makeCategory('Alfa');
        $brand = $this->makeBrand('Marca X');
        $this->makeProduct($a, ['name' => 'valvula uno', 'brand_id' => $brand->id]);
        $this->makeProduct($a, ['name' => 'valvula dos']);
        $this->makeProduct($b, ['name' => 'valvula tres']);
        $this->makeProduct($b, ['name' => 'tubo']);

        $params = \App\Services\Catalog\CatalogParams::fromRequest(\Illuminate\Http\Request::create('/buscar-en-vivo', 'GET', ['q' => 'valvula']));
        $found = app(\App\Services\Catalog\CatalogQuery::class)->search($params, 2);

        $this->assertSame(3, $found['total']);
        $this->assertCount(2, $found['products']);
        $this->assertSame([['id' => $b->id, 'name' => 'Alfa', 'count' => 1], ['id' => $a->id, 'name' => 'Zeta', 'count' => 2]], $found['categories']);
        $this->assertSame([['id' => $brand->id, 'name' => 'Marca X', 'count' => 1]], $found['brands']);
    }
}
