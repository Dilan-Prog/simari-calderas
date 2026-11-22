<?php

namespace Tests\Feature\Products;

use App\Exports\ProductsExport;
use App\Imports\ProductsImport;
use App\Imports\ProductsUpdateImport;
use App\Models\Brand;
use App\Models\Products;
use App\Models\Role;
use App\Models\User;
use App\Services\Products\ProductFilters;
use App\Support\CategoryPaths;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Concerns\CreatesBadgeFixtures;
use Tests\TestCase;

/**
 * Admin de "Categorías adicionales" (product_category_map): formulario de
 * crear/editar, filtro del listado, acción masiva y Excel (export/import).
 */
class AdditionalCategoriesAdminTest extends TestCase
{
    use CreatesBadgeFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetCatalogState();
    }

    private function adminUser(): User
    {
        $role = Role::create(['name_role' => 'Administrador', 'name_role_es' => 'Administrador']);

        return User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'Administrador',
            'phone' => '5555555555', 'email' => 'admin-' . uniqid() . '@example.com',
            'password' => bcrypt('password'), 'status' => 'active',
            'rfc' => 'XAXX010101000', 'role_id' => $role->id,
        ]);
    }

    private function brand(string $name = null): Brand
    {
        $name ??= 'Marca ' . uniqid();

        return Brand::create(['name' => $name, 'slug' => \Illuminate\Support\Str::slug($name) . '-' . uniqid(), 'is_active' => true]);
    }

    private function extraIds(Products $p): array
    {
        return $p->fresh()->additionalCategories()->pluck('product_categories.id')->sort()->values()->all();
    }

    // ── Formulario ──────────────────────────────────────────────────────

    public function test_store_guarda_adicionales_y_excluye_la_principal(): void
    {
        $principal = $this->makeCategory(['name' => 'Calderas']);
        $a = $this->makeCategory(['name' => 'Ofertas']);
        $b = $this->makeCategory(['name' => 'Industriales']);

        $this->actingAs($this->adminUser())->post(route('admin.products.store'), [
            'name' => 'Caldera X', 'sku' => 'SKU-X1', 'price' => 100,
            'category_id' => $principal->id, 'brand_id' => $this->brand()->id,
            'additional_categories_present' => '1',
            'additional_category_ids' => [$a->id, $principal->id, $b->id, $a->id],
        ])->assertSessionHasNoErrors();

        $p = Products::where('sku', 'SKU-X1')->firstOrFail();
        $this->assertSame(collect([$a->id, $b->id])->sort()->values()->all(), $this->extraIds($p));
    }

    public function test_store_valida_ids_inexistentes(): void
    {
        $this->actingAs($this->adminUser())->post(route('admin.products.store'), [
            'name' => 'Caldera X', 'sku' => 'SKU-X2', 'price' => 100,
            'category_id' => $this->makeCategory()->id, 'brand_id' => $this->brand()->id,
            'additional_category_ids' => [999999],
        ])->assertSessionHasErrors('additional_category_ids.0');
    }

    public function test_update_reemplaza_vacia_y_respeta_peticiones_sin_el_campo(): void
    {
        $admin = $this->adminUser();
        $principal = $this->makeCategory();
        $a = $this->makeCategory();
        $b = $this->makeCategory();
        $brand = $this->brand();
        $p = $this->makeProduct(['category_id' => $principal->id, 'brand_id' => $brand->id]);
        $p->syncAdditionalCategories([$a->id]);

        $base = ['name' => $p->name, 'sku' => $p->sku, 'price' => 100, 'brand_id' => $brand->id];

        // Cambiar la principal a una que estaba como adicional: sale de las adicionales.
        $this->actingAs($admin)->put(route('admin.products.update', $p->id), $base + [
            'category_id' => $a->id,
            'additional_categories_present' => '1',
            'additional_category_ids' => [$a->id, $b->id],
        ])->assertSessionHasNoErrors();
        $this->assertSame([$b->id], $this->extraIds($p));

        // Sin el campo (ni la marca): no se tocan.
        $this->actingAs($admin)->put(route('admin.products.update', $p->id), $base + ['category_id' => $a->id])
            ->assertSessionHasNoErrors();
        $this->assertSame([$b->id], $this->extraIds($p));

        // Con la marca pero sin chips: se vacían.
        $this->actingAs($admin)->put(route('admin.products.update', $p->id), $base + [
            'category_id' => $a->id, 'additional_categories_present' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertSame([], $this->extraIds($p));
    }

    public function test_formulario_de_edicion_precarga_el_selector(): void
    {
        $extra = $this->makeCategory(['name' => 'Categoria Extra Unica']);
        $p = $this->makeProduct(['brand_id' => $this->brand()->id]);
        $p->syncAdditionalCategories([$extra->id]);

        $html = $this->withoutVite()->actingAs($this->adminUser())
            ->get(route('admin.products.edit', $p->id))
            ->assertOk()->getContent();

        $this->assertStringContainsString('Categorías adicionales', $html);
        $this->assertStringContainsString('data-selected=\'[' . $extra->id . ']\'', $html);
        $this->assertStringContainsString('La categoría principal define las migas de pan', $html);
    }

    // ── Listado ─────────────────────────────────────────────────────────

    public function test_filtro_por_categoria_incluye_adicionales_y_descendientes(): void
    {
        $root = $this->makeCategory(['name' => 'Raiz']);
        $child = $this->makeCategory(['name' => 'Hija', 'parent_id' => $root->id]);
        $other = $this->makeCategory(['name' => 'Otra']);

        $this->makeProduct(['name' => 'Principal', 'category_id' => $root->id]);
        $viaChild = $this->makeProduct(['name' => 'Via-hija', 'category_id' => $other->id]);
        $viaChild->syncAdditionalCategories([$child->id]);
        $this->makeProduct(['name' => 'Fuera', 'category_id' => $other->id]);

        $q = Products::query();
        ProductFilters::apply($q, Request::create('/x', 'GET', ['category_id' => $root->id]));
        $this->assertSame(['Principal', 'Via-hija'], $q->orderBy('name')->pluck('name')->all());

        $html = $this->withoutVite()->actingAs($this->adminUser())
            ->get(route('admin.products.index', ['category_id' => $root->id]))
            ->assertOk()->getContent();
        $this->assertStringContainsString('Via-hija', $html);
        $this->assertStringContainsString('prod-addcat-badge', $html);
        $this->assertStringContainsString('También en: Raiz › Hija', $html);
    }

    public function test_accion_masiva_agrega_y_quita_sin_reemplazar(): void
    {
        $admin = $this->adminUser();
        $keep = $this->makeCategory();
        $new = $this->makeCategory();
        $p1 = $this->makeProduct();
        $p2 = $this->makeProduct(['category_id' => $new->id]); // su principal = $new
        $p1->syncAdditionalCategories([$keep->id]);

        $this->actingAs($admin)->postJson(route('admin.products.bulk-additional-categories'), [
            'ids' => [$p1->id, $p2->id], 'category_ids' => [$new->id], 'mode' => 'add',
        ])->assertOk()->assertJson(['success' => true, 'changed' => 1, 'skipped_principal' => 1]);

        $this->assertSame(collect([$keep->id, $new->id])->sort()->values()->all(), $this->extraIds($p1));
        $this->assertSame([], $this->extraIds($p2));

        // Repetir "Agregar" no duplica.
        $this->actingAs($admin)->postJson(route('admin.products.bulk-additional-categories'), [
            'ids' => [$p1->id], 'category_ids' => [$new->id], 'mode' => 'add',
        ])->assertOk()->assertJson(['changed' => 0]);

        $this->actingAs($admin)->postJson(route('admin.products.bulk-additional-categories'), [
            'ids' => [$p1->id, $p2->id], 'category_ids' => [$new->id], 'mode' => 'remove',
        ])->assertOk()->assertJson(['changed' => 1]);
        $this->assertSame([$keep->id], $this->extraIds($p1));

        $this->actingAs($admin)->postJson(route('admin.products.bulk-additional-categories'), [
            'ids' => [$p1->id], 'category_ids' => [], 'mode' => 'add',
        ])->assertStatus(422);
    }

    // ── Excel ───────────────────────────────────────────────────────────

    public function test_export_incluye_columna_con_rutas_completas(): void
    {
        $root = $this->makeCategory(['name' => 'Calderas']);
        $child = $this->makeCategory(['name' => 'Industriales', 'parent_id' => $root->id]);
        $other = $this->makeCategory(['name' => 'Ofertas']);
        $p = $this->makeProduct(['brand_id' => $this->brand()->id]);
        $p->syncAdditionalCategories([$child->id, $other->id]);

        $export = new ProductsExport([$p->id]);
        $headings = $export->headings();
        $this->assertSame('Categorías Adicionales', end($headings));

        $row = $export->map($export->query()->firstOrFail());
        $this->assertSame('Calderas > Industriales | Ofertas', end($row));
        $this->assertCount(count($headings), $row);
    }

    public function test_resolucion_de_nombres_rutas_y_slugs(): void
    {
        $root = $this->makeCategory(['name' => 'Tratamiento de Agua', 'slug' => 'tratamiento-de-agua']);
        $child = $this->makeCategory(['name' => 'Filtros', 'slug' => 'tratamiento-de-agua/filtros', 'parent_id' => $root->id]);
        $pool = $this->makeCategory(['name' => 'Piscinas', 'slug' => 'piscinas']);
        $this->makeCategory(['name' => 'Filtros', 'slug' => 'piscinas/filtros', 'parent_id' => $pool->id]);
        $paths = new CategoryPaths();

        $this->assertSame($root->id, $paths->resolve('  TRATAMIENTO de agua '));
        $this->assertSame($child->id, $paths->resolve('Tratamiento de Agua > Filtros'));
        $this->assertSame($child->id, $paths->resolve('tratamiento de agua › filtros'));
        $this->assertSame($child->id, $paths->resolve('tratamiento-de-agua/filtros'));
        $this->assertNull($paths->resolve('Filtros')); // ambiguo: dos ramas
        $this->assertSame($pool->id, $paths->resolve('piscinas'));
        $this->assertNull($paths->resolve('No existe'));
    }

    /** Escribe un .xlsx con hoja "Productos" y devuelve la ruta. */
    private function xlsx(array $headings, array $rows): string
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Productos');
        $sheet->fromArray(array_merge([$headings], $rows), null, 'A1', true);
        $path = tempnam(sys_get_temp_dir(), 'imp') . '.xlsx';
        (new Xlsx($book))->save($path);

        return $path;
    }

    public function test_import_de_creacion_asigna_adicionales_y_avisa_las_desconocidas(): void
    {
        $principal = $this->makeCategory(['name' => 'Calderas']);
        $extra = $this->makeCategory(['name' => 'Energía Solar']);
        $brand = $this->brand('Simari');

        $import = new ProductsImport();
        Excel::import($import, $this->xlsx(
            ['Nombre', 'SKU', 'Categoría Principal', 'Marca', 'Precio', 'Categorías Adicionales'],
            [['Caldera Import', 'IMP-1', 'Calderas', 'Simari', 1000, 'energia solar | Calderas | Inventada']]
        ));

        $p = Products::where('sku', 'IMP-1')->firstOrFail();
        $this->assertSame([$extra->id], $this->extraIds($p));
        $this->assertSame(1, $import->createdCount);
        $this->assertCount(1, $import->additionalCategoryWarnings);
        $this->assertSame('Inventada', $import->additionalCategoryWarnings[0]['value']);
        $this->assertSame($principal->id, (int) $p->category_id);
        $this->assertSame($brand->id, (int) $p->brand_id);
    }

    public function test_import_de_actualizacion_reemplaza_solo_si_la_columna_existe(): void
    {
        $a = $this->makeCategory(['name' => 'Ofertas']);
        $b = $this->makeCategory(['name' => 'Industriales']);
        $p = $this->makeProduct(['sku' => 'UPD-1']);
        $p->syncAdditionalCategories([$a->id]);

        // Sin la columna: no se tocan.
        Excel::import(new ProductsUpdateImport(), $this->xlsx(['SKU', 'Nombre'], [['UPD-1', 'Nuevo nombre']]));
        $this->assertSame('Nuevo nombre', $p->fresh()->name);
        $this->assertSame([$a->id], $this->extraIds($p));

        // Con la columna: reemplaza.
        Excel::import(new ProductsUpdateImport(), $this->xlsx(['SKU', 'Categorías Adicionales'], [['UPD-1', 'industriales']]));
        $this->assertSame([$b->id], $this->extraIds($p));

        // Solo valores desconocidos: avisa y conserva las actuales.
        $import = new ProductsUpdateImport();
        Excel::import($import, $this->xlsx(['SKU', 'Categorías Adicionales'], [['UPD-1', 'Typo']]));
        $this->assertSame([$b->id], $this->extraIds($p));
        $this->assertCount(1, $import->additionalCategoryWarnings);

        // Columna presente y celda vacía: las quita todas.
        Excel::import(new ProductsUpdateImport(), $this->xlsx(['SKU', 'Categorías Adicionales', 'Nombre'], [['UPD-1', '', 'Otro']]));
        $this->assertSame([], $this->extraIds($p));
    }
}
