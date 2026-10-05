<?php

namespace Tests\Feature\ProductBlocks;

use App\Models\HomeSection;
use App\Models\ProductSectionAssignment;
use App\Models\Products;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesProductBlockFixtures;
use Tests\TestCase;

class ProductBlocksCascadeTest extends TestCase
{
    use RefreshDatabase, CreatesProductBlockFixtures;

    public function test_deleting_a_template_deletes_its_assignments_only(): void
    {
        $p1 = $this->makeProduct();
        $p2 = $this->makeProduct();
        $template = $this->makeTemplate();
        $kept = $this->makeTemplate();

        $this->assign($p1, $template, 0);
        $this->assign($p2, $template, 0);
        $this->assign($p1, $kept, 1);

        $template->delete();

        $this->assertSame(0, ProductSectionAssignment::where('home_section_id', $template->id)->count());
        $this->assertSame(1, ProductSectionAssignment::count());
        $this->assertSame([$kept->id], $this->orderOf($p1));
        // Los productos no se tocan
        $this->assertNotNull(Products::find($p1->id));
        $this->assertNotNull(Products::find($p2->id));
    }

    public function test_deleting_a_custom_section_deletes_its_assignment(): void
    {
        $product = $this->makeProduct();
        $custom = $this->makeCustom();
        $this->assign($product, $custom, 0);

        $custom->delete();

        $this->assertSame(0, ProductSectionAssignment::count());
    }

    public function test_deleting_a_product_deletes_its_assignments_but_not_the_sections(): void
    {
        $product = $this->makeProduct();
        $other = $this->makeProduct();
        $template = $this->makeTemplate();
        $custom = $this->makeCustom();

        $this->assign($product, $template, 0);
        $this->assign($product, $custom, 1);
        $this->assign($other, $template, 0);

        $product->delete();

        $this->assertSame(0, ProductSectionAssignment::where('product_id', $product->id)->count());
        $this->assertSame(1, ProductSectionAssignment::count());
        $this->assertSame([$template->id], $this->orderOf($other));
        // Las secciones (plantilla compartida y la propia) siguen existiendo
        $this->assertNotNull(HomeSection::find($template->id));
        $this->assertNotNull(HomeSection::find($custom->id));
    }

    public function test_unique_assignment_per_product_and_section(): void
    {
        $product = $this->makeProduct();
        $template = $this->makeTemplate();
        $this->assign($product, $template, 0);

        $this->expectException(\Illuminate\Database\QueryException::class);

        $this->assign($product, $template, 1);
    }
}
