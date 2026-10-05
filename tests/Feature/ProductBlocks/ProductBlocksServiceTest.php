<?php

namespace Tests\Feature\ProductBlocks;

use App\Models\HomeSection;
use App\Models\HomeSectionSlide;
use App\Models\ProductSectionAssignment;
use App\Services\ProductBlocks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesProductBlockFixtures;
use Tests\TestCase;

class ProductBlocksServiceTest extends TestCase
{
    use RefreshDatabase, CreatesProductBlockFixtures;

    // ---------- forProduct ----------

    public function test_for_product_returns_sections_in_assignment_order(): void
    {
        $product = $this->makeProduct();
        $a = $this->makeTemplate(['name' => 'A']);
        $b = $this->makeCustom(['name' => 'B']);
        $c = $this->makeTemplate(['name' => 'C']);

        // Se crean fuera de orden para comprobar que manda sort_order
        $this->assign($product, $a, 2);
        $this->assign($product, $b, 0);
        $this->assign($product, $c, 1);

        $result = ProductBlocks::forProduct($product);

        $this->assertSame([$b->id, $c->id, $a->id], $result['stack']->pluck('id')->all());
        $this->assertTrue($result['sidebar']->isEmpty());
    }

    public function test_for_product_breaks_sort_order_ties_by_assignment_id(): void
    {
        $product = $this->makeProduct();
        $a = $this->makeTemplate();
        $b = $this->makeTemplate();

        $this->assign($product, $b, 0);
        $this->assign($product, $a, 0);

        $this->assertSame([$b->id, $a->id], ProductBlocks::forProduct($product)['stack']->pluck('id')->all());
    }

    public function test_for_product_excludes_invisible_assignments(): void
    {
        $product = $this->makeProduct();
        $visible = $this->makeTemplate();
        $hidden = $this->makeTemplate();

        $this->assign($product, $visible, 0, true);
        $this->assign($product, $hidden, 1, false);

        $this->assertSame([$visible->id], ProductBlocks::forProduct($product)['stack']->pluck('id')->all());
    }

    public function test_for_product_excludes_inactive_sections(): void
    {
        $product = $this->makeProduct();
        $active = $this->makeTemplate();
        $inactiveTemplate = $this->makeTemplate(['is_active' => false]);
        $inactiveCustom = $this->makeCustom(['is_active' => false]);

        $this->assign($product, $active, 0);
        $this->assign($product, $inactiveTemplate, 1);
        $this->assign($product, $inactiveCustom, 2);

        $result = ProductBlocks::forProduct($product);

        $this->assertSame([$active->id], $result['stack']->pluck('id')->all());
        $this->assertTrue($result['sidebar']->isEmpty());
    }

    public function test_for_product_splits_zones_keeping_order_within_each(): void
    {
        $product = $this->makeProduct();
        $stack1 = $this->makeTemplate(['zone' => HomeSection::ZONE_STACK]);
        $side1 = $this->makeTemplate(['zone' => HomeSection::ZONE_SIDEBAR]);
        $stack2 = $this->makeCustom(['zone' => HomeSection::ZONE_STACK]);
        $side2 = $this->makeCustom(['zone' => HomeSection::ZONE_SIDEBAR]);

        $this->assign($product, $stack1, 0);
        $this->assign($product, $side1, 1);
        $this->assign($product, $stack2, 2);
        $this->assign($product, $side2, 3);

        $result = ProductBlocks::forProduct($product);

        $this->assertSame([$stack1->id, $stack2->id], $result['stack']->pluck('id')->all());
        $this->assertSame([$side1->id, $side2->id], $result['sidebar']->pluck('id')->all());
        // Colecciones reindexadas (0..n) para poder iterar/indexar con seguridad
        $this->assertSame([0, 1], $result['stack']->keys()->all());
        $this->assertSame([0, 1], $result['sidebar']->keys()->all());
    }

    public function test_for_product_ignores_legacy_global_product_page_sections(): void
    {
        $product = $this->makeProduct();
        $legacy = $this->makeSection(['page' => 'product']);
        $home = $this->makeSection(['page' => 'home']);
        $template = $this->makeTemplate();

        // Aunque (por datos sucios) estuvieran asignadas, no deben salir
        $this->assign($product, $legacy, 0);
        $this->assign($product, $home, 1);
        $this->assign($product, $template, 2);

        $this->assertSame([$template->id], ProductBlocks::forProduct($product)['stack']->pluck('id')->all());
    }

    public function test_for_product_only_returns_that_products_blocks(): void
    {
        $product = $this->makeProduct();
        $other = $this->makeProduct();
        $mine = $this->makeTemplate();
        $theirs = $this->makeTemplate();

        $this->assign($product, $mine, 0);
        $this->assign($other, $theirs, 0);

        $this->assertSame([$mine->id], ProductBlocks::forProduct($product)['stack']->pluck('id')->all());
    }

    public function test_for_product_without_assignments_returns_empty_zones(): void
    {
        $result = ProductBlocks::forProduct($this->makeProduct());

        $this->assertTrue($result['stack']->isEmpty());
        $this->assertTrue($result['sidebar']->isEmpty());
    }

    // ---------- applyTemplates: append ----------

    public function test_apply_append_adds_to_the_end_after_existing_blocks(): void
    {
        $product = $this->makeProduct();
        $own = $this->makeCustom();
        $t1 = $this->makeTemplate();
        $t2 = $this->makeTemplate();
        $this->assign($product, $own, 4);

        $result = ProductBlocks::applyTemplates([$product->id], [$t1->id, $t2->id], ProductBlocks::MODE_APPEND);

        $this->assertSame(['products' => 1, 'added' => 2, 'removed' => 0], $result);
        $this->assertSame([$own->id => 4, $t1->id => 5, $t2->id => 6], $this->layoutOf($product));
        $this->assertTrue(ProductSectionAssignment::where('product_id', $product->id)->where('is_visible', false)->doesntExist());
    }

    public function test_apply_append_on_empty_product_starts_at_zero(): void
    {
        $product = $this->makeProduct();
        $t1 = $this->makeTemplate();
        $t2 = $this->makeTemplate();

        ProductBlocks::applyTemplates([$product->id], [$t1->id, $t2->id]);

        $this->assertSame([$t1->id => 0, $t2->id => 1], $this->layoutOf($product));
    }

    public function test_apply_append_is_idempotent(): void
    {
        $product = $this->makeProduct();
        $t1 = $this->makeTemplate();
        $t2 = $this->makeTemplate();

        $first = ProductBlocks::applyTemplates([$product->id], [$t1->id, $t2->id], ProductBlocks::MODE_APPEND);
        $second = ProductBlocks::applyTemplates([$product->id], [$t1->id, $t2->id], ProductBlocks::MODE_APPEND);

        $this->assertSame(2, $first['added']);
        $this->assertSame(0, $second['added']);
        $this->assertSame(2, ProductSectionAssignment::where('product_id', $product->id)->count());
        $this->assertSame([$t1->id => 0, $t2->id => 1], $this->layoutOf($product));
    }

    public function test_apply_append_only_adds_the_missing_ones(): void
    {
        $product = $this->makeProduct();
        $t1 = $this->makeTemplate();
        $t2 = $this->makeTemplate();
        $this->assign($product, $t1, 0);

        $result = ProductBlocks::applyTemplates([$product->id], [$t1->id, $t2->id]);

        $this->assertSame(1, $result['added']);
        $this->assertSame([$t1->id => 0, $t2->id => 1], $this->layoutOf($product));
    }

    public function test_apply_to_many_products_and_dedupes_product_ids(): void
    {
        $p1 = $this->makeProduct();
        $p2 = $this->makeProduct();
        $t = $this->makeTemplate();

        $result = ProductBlocks::applyTemplates([$p1->id, $p2->id, $p1->id, (string) $p2->id], [$t->id]);

        $this->assertSame(['products' => 2, 'added' => 2, 'removed' => 0], $result);
        $this->assertSame(2, ProductSectionAssignment::where('home_section_id', $t->id)->count());
    }

    public function test_apply_ignores_non_template_and_unknown_section_ids(): void
    {
        $product = $this->makeProduct();
        $custom = $this->makeCustom();
        $legacy = $this->makeSection(['page' => 'product']);
        $home = $this->makeSection(['page' => 'home']);
        $template = $this->makeTemplate();

        $result = ProductBlocks::applyTemplates([$product->id], [$custom->id, $legacy->id, $home->id, 999999, $template->id]);

        $this->assertSame(1, $result['added']);
        $this->assertSame([$template->id => 0], $this->layoutOf($product));
    }

    public function test_apply_skips_unknown_products(): void
    {
        $product = $this->makeProduct();
        $t = $this->makeTemplate();

        $result = ProductBlocks::applyTemplates([$product->id, 999999], [$t->id]);

        $this->assertSame(1, $result['added']);
        $this->assertSame(1, ProductSectionAssignment::count());
    }

    // ---------- applyTemplates: prepend ----------

    public function test_apply_prepend_puts_new_first_and_shifts_the_rest(): void
    {
        $product = $this->makeProduct();
        $a = $this->makeTemplate();
        $b = $this->makeCustom();
        $new = $this->makeTemplate();
        $this->assign($product, $a, 0);
        $this->assign($product, $b, 1);

        $result = ProductBlocks::applyTemplates([$product->id], [$new->id], ProductBlocks::MODE_PREPEND);

        $this->assertSame(1, $result['added']);
        $this->assertSame([$new->id => 0, $a->id => 1, $b->id => 2], $this->layoutOf($product));
    }

    public function test_apply_prepend_several_keeps_given_order_and_shifts_by_count(): void
    {
        $product = $this->makeProduct();
        $a = $this->makeTemplate();
        $new1 = $this->makeTemplate();
        $new2 = $this->makeTemplate();
        $this->assign($product, $a, 3);

        ProductBlocks::applyTemplates([$product->id], [$new1->id, $new2->id], ProductBlocks::MODE_PREPEND);

        $layout = $this->layoutOf($product);
        $this->assertSame([$new1->id, $new2->id, $a->id], array_keys($layout));
        $this->assertSame([$new1->id => 0, $new2->id => 1, $a->id => 5], $layout);
    }

    public function test_apply_prepend_does_not_shift_when_nothing_new_is_added(): void
    {
        $product = $this->makeProduct();
        $a = $this->makeTemplate();
        $this->assign($product, $a, 2);

        $result = ProductBlocks::applyTemplates([$product->id], [$a->id], ProductBlocks::MODE_PREPEND);

        $this->assertSame(0, $result['added']);
        $this->assertSame([$a->id => 2], $this->layoutOf($product));
    }

    // ---------- applyTemplates: replace ----------

    public function test_apply_replace_swaps_templates_but_keeps_own_sections(): void
    {
        $product = $this->makeProduct();
        $other = $this->makeProduct();
        $oldTemplate = $this->makeTemplate();
        $own = $this->makeCustom();
        $newTemplate = $this->makeTemplate();
        $this->assign($product, $oldTemplate, 0);
        $this->assign($product, $own, 1);
        $this->assign($other, $oldTemplate, 0);

        $result = ProductBlocks::applyTemplates([$product->id], [$newTemplate->id], ProductBlocks::MODE_REPLACE);

        $this->assertSame(['products' => 1, 'added' => 1, 'removed' => 1], $result);
        $this->assertSame([$own->id, $newTemplate->id], $this->orderOf($product));
        $this->assertFalse(ProductSectionAssignment::where('product_id', $product->id)->where('home_section_id', $oldTemplate->id)->exists());
        // El otro producto conserva la plantilla vieja
        $this->assertTrue(ProductSectionAssignment::where('product_id', $other->id)->where('home_section_id', $oldTemplate->id)->exists());
        // La seccion propia sigue existiendo y asignada
        $this->assertTrue(ProductSectionAssignment::where('product_id', $product->id)->where('home_section_id', $own->id)->exists());
    }

    public function test_apply_replace_with_same_template_reassigns_it(): void
    {
        $product = $this->makeProduct();
        $t = $this->makeTemplate();
        $own = $this->makeCustom();
        $this->assign($product, $t, 0);
        $this->assign($product, $own, 1);

        $result = ProductBlocks::applyTemplates([$product->id], [$t->id], ProductBlocks::MODE_REPLACE);

        $this->assertSame(1, $result['removed']);
        $this->assertSame(1, $result['added']);
        $this->assertEqualsCanonicalizing([$t->id, $own->id], $this->orderOf($product));
        $this->assertSame(2, ProductSectionAssignment::where('product_id', $product->id)->count());
    }

    public function test_apply_replace_with_empty_selection_just_clears_templates(): void
    {
        $product = $this->makeProduct();
        $t = $this->makeTemplate();
        $own = $this->makeCustom();
        $this->assign($product, $t, 0);
        $this->assign($product, $own, 1);

        $result = ProductBlocks::applyTemplates([$product->id], [], ProductBlocks::MODE_REPLACE);

        $this->assertSame(['products' => 1, 'added' => 0, 'removed' => 1], $result);
        $this->assertSame([$own->id], $this->orderOf($product));
    }

    // ---------- applyTemplates: remove ----------

    public function test_apply_remove_only_removes_the_given_templates(): void
    {
        $product = $this->makeProduct();
        $other = $this->makeProduct();
        $t1 = $this->makeTemplate();
        $t2 = $this->makeTemplate();
        $own = $this->makeCustom();
        $this->assign($product, $t1, 0);
        $this->assign($product, $t2, 1);
        $this->assign($product, $own, 2);
        $this->assign($other, $t1, 0);

        $result = ProductBlocks::applyTemplates([$product->id], [$t1->id], ProductBlocks::MODE_REMOVE);

        $this->assertSame(['products' => 1, 'added' => 0, 'removed' => 1], $result);
        $this->assertSame([$t2->id, $own->id], $this->orderOf($product));
        $this->assertTrue(ProductSectionAssignment::where('product_id', $other->id)->where('home_section_id', $t1->id)->exists());
    }

    public function test_apply_remove_cannot_remove_custom_sections_nor_missing_ones(): void
    {
        $product = $this->makeProduct();
        $own = $this->makeCustom();
        $t = $this->makeTemplate();
        $this->assign($product, $own, 0);

        $result = ProductBlocks::applyTemplates([$product->id], [$own->id, $t->id], ProductBlocks::MODE_REMOVE);

        $this->assertSame(0, $result['removed']);
        $this->assertSame([$own->id], $this->orderOf($product));
    }

    // ---------- usageCounts ----------

    public function test_usage_counts_counts_products_per_section(): void
    {
        $p1 = $this->makeProduct();
        $p2 = $this->makeProduct();
        $p3 = $this->makeProduct();
        $shared = $this->makeTemplate();
        $single = $this->makeTemplate();
        $unused = $this->makeTemplate();
        $custom = $this->makeCustom();

        $this->assign($p1, $shared, 0);
        $this->assign($p2, $shared, 0);
        $this->assign($p3, $shared, 0);
        $this->assign($p1, $single, 1);
        $this->assign($p1, $custom, 2);

        $counts = ProductBlocks::usageCounts();

        $this->assertSame(3, (int) $counts[$shared->id]);
        $this->assertSame(1, (int) $counts[$single->id]);
        $this->assertSame(1, (int) $counts[$custom->id]);
        $this->assertFalse($counts->has($unused->id));
    }

    public function test_usage_counts_is_empty_without_assignments(): void
    {
        $this->assertTrue(ProductBlocks::usageCounts()->isEmpty());
    }

    // ---------- copyToCustom ----------

    public function test_copy_to_custom_creates_independent_copy_and_repoints_assignment(): void
    {
        $product = $this->makeProduct();
        $other = $this->makeProduct();
        $template = $this->makeTemplate([
            'name'         => 'Garantia',
            'title'        => 'Titulo original',
            'zone'         => HomeSection::ZONE_SIDEBAR,
            'type'         => 'banner',
            'config'       => ['color' => 'red', 'items' => [1, 2]],
            'heading_link' => ['type' => 'home', 'id' => null, 'url' => null, 'new_tab' => false],
            'sort_order'   => 7,
        ]);
        $slide1 = HomeSectionSlide::create(['home_section_id' => $template->id, 'image_url' => 'https://e.com/1.png', 'title' => 'S1', 'sort_order' => 0, 'is_active' => true]);
        HomeSectionSlide::create(['home_section_id' => $template->id, 'image_url' => 'https://e.com/2.png', 'title' => 'S2', 'sort_order' => 1, 'is_active' => false]);

        $this->assign($product, $this->makeTemplate(), 0);
        $assignment = $this->assign($product, $template, 1, false);
        $this->assign($product, $this->makeCustom(), 2);
        $otherAssignment = $this->assign($other, $template, 0);

        $copy = ProductBlocks::copyToCustom($assignment->fresh());

        // La copia es una seccion nueva, propia, con los mismos datos
        $this->assertNotSame($template->id, $copy->id);
        $this->assertSame(HomeSection::PAGE_PRODUCT_CUSTOM, $copy->page);
        $this->assertSame('Garantia (copia)', $copy->name);
        $this->assertSame('Titulo original', $copy->title);
        $this->assertSame(HomeSection::ZONE_SIDEBAR, $copy->zone);
        $this->assertSame('banner', $copy->type);
        $this->assertSame(['color' => 'red', 'items' => [1, 2]], $copy->config);
        $this->assertEquals($template->fresh()->heading_link, $copy->fresh()->heading_link);

        // La asignacion apunta a la copia, misma posicion y visibilidad
        $assignment->refresh();
        $this->assertSame($copy->id, $assignment->home_section_id);
        $this->assertSame(1, $assignment->sort_order);
        $this->assertFalse($assignment->is_visible);
        $this->assertSame(3, ProductSectionAssignment::where('product_id', $product->id)->count());

        // El otro producto sigue ligado a la plantilla
        $this->assertSame($template->id, $otherAssignment->fresh()->home_section_id);

        // Slides copiados (nuevas filas)
        $this->assertSame(2, $copy->slides()->count());
        $this->assertEqualsCanonicalizing(['S1', 'S2'], $copy->slides->pluck('title')->all());
        $this->assertNotContains($slide1->id, $copy->slides->pluck('id')->all());
        $this->assertSame(2, $template->slides()->count());

        // Editar la copia NO cambia la plantilla (ni sus slides)
        $copy->update(['title' => 'Titulo editado', 'config' => ['color' => 'blue'], 'is_active' => false]);
        $copy->slides()->first()->update(['title' => 'Editado']);

        $template->refresh();
        $this->assertSame('Titulo original', $template->title);
        $this->assertSame(['color' => 'red', 'items' => [1, 2]], $template->config);
        $this->assertTrue($template->is_active);
        $this->assertEqualsCanonicalizing(['S1', 'S2'], $template->slides->pluck('title')->all());
        $this->assertSame(HomeSection::PAGE_PRODUCT_TEMPLATE, $template->page);
    }

    public function test_copy_to_custom_removes_product_from_template_usage(): void
    {
        $product = $this->makeProduct();
        $other = $this->makeProduct();
        $template = $this->makeTemplate();
        $assignment = $this->assign($product, $template, 0);
        $this->assign($other, $template, 0);

        $this->assertSame(2, (int) ProductBlocks::usageCounts()[$template->id]);

        $copy = ProductBlocks::copyToCustom($assignment);

        $this->assertSame(1, (int) ProductBlocks::usageCounts()[$template->id]);
        $this->assertSame(1, (int) ProductBlocks::usageCounts()[$copy->id]);
    }

    public function test_copy_to_custom_without_name_keeps_name_null(): void
    {
        $product = $this->makeProduct();
        $template = $this->makeTemplate(['name' => null]);
        $assignment = $this->assign($product, $template, 0);

        $copy = ProductBlocks::copyToCustom($assignment);

        $this->assertNull($copy->name);
    }

    public function test_copy_to_custom_still_renders_for_the_product(): void
    {
        $product = $this->makeProduct();
        $template = $this->makeTemplate(['zone' => HomeSection::ZONE_SIDEBAR]);
        $assignment = $this->assign($product, $template, 0);

        $copy = ProductBlocks::copyToCustom($assignment);

        $result = ProductBlocks::forProduct($product);
        $this->assertSame([$copy->id], $result['sidebar']->pluck('id')->all());
    }

    // ---------- reorder ----------

    public function test_reorder_sets_order_and_visibility(): void
    {
        $product = $this->makeProduct();
        $a = $this->assign($product, $this->makeTemplate(), 0);
        $b = $this->assign($product, $this->makeCustom(), 1);
        $c = $this->assign($product, $this->makeTemplate(), 2);

        ProductBlocks::reorder($product, [
            ['id' => $c->id],
            ['id' => $a->id, 'is_visible' => false],
            ['id' => $b->id, 'is_visible' => true],
        ]);

        $this->assertSame(0, $c->fresh()->sort_order);
        $this->assertSame(1, $a->fresh()->sort_order);
        $this->assertSame(2, $b->fresh()->sort_order);
        $this->assertFalse($a->fresh()->is_visible);
        $this->assertTrue($b->fresh()->is_visible);
        // Sin is_visible en la fila, no se toca la visibilidad
        $this->assertTrue($c->fresh()->is_visible);
    }

    public function test_reorder_does_not_touch_visibility_when_not_provided(): void
    {
        $product = $this->makeProduct();
        $a = $this->assign($product, $this->makeTemplate(), 0, false);
        $b = $this->assign($product, $this->makeTemplate(), 1, true);

        ProductBlocks::reorder($product, [['id' => $b->id], ['id' => $a->id]]);

        $this->assertFalse($a->fresh()->is_visible);
        $this->assertTrue($b->fresh()->is_visible);
        $this->assertSame(0, $b->fresh()->sort_order);
        $this->assertSame(1, $a->fresh()->sort_order);
    }

    public function test_reorder_ignores_assignments_of_other_products(): void
    {
        $product = $this->makeProduct();
        $other = $this->makeProduct();
        $mine = $this->assign($product, $this->makeTemplate(), 0);
        $foreign = $this->assign($other, $this->makeTemplate(), 5);

        ProductBlocks::reorder($product, [
            ['id' => $foreign->id, 'is_visible' => false],
            ['id' => $mine->id],
        ]);

        $this->assertSame(5, $foreign->fresh()->sort_order);
        $this->assertTrue($foreign->fresh()->is_visible);
        $this->assertSame(1, $mine->fresh()->sort_order);
    }

    public function test_reorder_is_reflected_by_for_product(): void
    {
        $product = $this->makeProduct();
        $s1 = $this->makeTemplate();
        $s2 = $this->makeTemplate();
        $a1 = $this->assign($product, $s1, 0);
        $a2 = $this->assign($product, $s2, 1);

        ProductBlocks::reorder($product, [['id' => $a2->id], ['id' => $a1->id, 'is_visible' => false]]);

        $this->assertSame([$s2->id], ProductBlocks::forProduct($product)['stack']->pluck('id')->all());
    }
}
