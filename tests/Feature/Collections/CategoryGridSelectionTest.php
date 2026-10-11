<?php

namespace Tests\Feature\Collections;

use App\Models\Category;
use App\Models\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Bloque "Grid de categorías": muestra exactamente las categorías elegidas, en el orden dado. */
class CategoryGridSelectionTest extends TestCase
{
    use RefreshDatabase;

    private function names(string $html): array
    {
        if (!preg_match('#<section class="category-grid">(.*?)</section>#s', $html, $sec)) {
            return [];
        }
        preg_match_all('#<span>([^<]+)</span>#', $sec[1], $m);

        return $m[1] ?? [];
    }

    public function test_muestra_las_elegidas_de_cualquier_nivel_y_en_orden(): void
    {
        $root = Category::create(['name' => 'Raiz', 'slug' => 'raiz', 'is_active' => true]);
        $child = Category::create(['name' => 'Hija', 'slug' => 'hija', 'is_active' => true, 'parent_id' => $root->id]);
        $other = Category::create(['name' => 'Otra', 'slug' => 'otra', 'is_active' => true]);
        $off = Category::create(['name' => 'Apagada', 'slug' => 'apagada', 'is_active' => false]);

        $c = Collection::create(['name' => 'Col', 'slug' => 'col', 'type' => 'manual', 'is_active' => true]);
        $c->sections()->create(['type' => 'category_grid', 'title' => 'Cats', 'sort_order' => 0, 'is_active' => true,
            'config' => ['category_ids' => [$other->id, $child->id, $off->id, $root->id]]]);

        $res = $this->get('/coleccion/col')->assertOk();

        $this->assertSame(['Otra', 'Hija', 'Raiz'], $this->names($res->getContent()));
    }

    public function test_sin_seleccion_muestra_solo_las_principales_activas(): void
    {
        $root = Category::create(['name' => 'Raiz', 'slug' => 'raiz', 'is_active' => true]);
        Category::create(['name' => 'Hija', 'slug' => 'hija', 'is_active' => true, 'parent_id' => $root->id]);

        $c = Collection::create(['name' => 'Col', 'slug' => 'col', 'type' => 'manual', 'is_active' => true]);
        $c->sections()->create(['type' => 'category_grid', 'title' => 'Cats', 'sort_order' => 0, 'is_active' => true, 'config' => ['category_ids' => []]]);

        $names = $this->names($this->get('/coleccion/col')->assertOk()->getContent());

        $this->assertContains('Raiz', $names);
        $this->assertNotContains('Hija', $names);
    }
}
