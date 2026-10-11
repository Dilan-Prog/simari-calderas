<?php

namespace Tests\Feature\Boms;

use App\Models\Bom;
use App\Models\Products;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\User;
use App\Services\BomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesBadgeFixtures;
use Tests\TestCase;

class BomServiceTest extends TestCase
{
    use RefreshDatabase, CreatesBadgeFixtures;

    private BomService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new BomService();
    }

    private function line(Products $p, float $qty, float $scrap = 0, ?string $unit = 'pza'): array
    {
        return ['component_product_id' => $p->id, 'quantity' => $qty, 'unit' => $unit, 'scrap_percent' => $scrap];
    }

    private function bom(Products $product, array $lines, array $extra = []): Bom
    {
        return $this->svc->create(array_merge(['product_id' => $product->id, 'is_active' => true], $extra), $lines);
    }

    public function test_explosion_multinivel_con_merma_y_agregacion(): void
    {
        $caldera = $this->makeProduct(['name' => 'Caldera']);
        $sub = $this->makeProduct(['name' => 'Quemador']);
        $acero = $this->makeProduct(['name' => 'Acero', 'cost' => 10]);
        $tornillo = $this->makeProduct(['name' => 'Tornillo', 'cost' => 1]);

        // Quemador: 2 acero (10% merma) + 4 tornillos
        $this->bom($sub, [$this->line($acero, 2, 10), $this->line($tornillo, 4)]);
        // Caldera: 3 quemadores + 1 acero + 10 tornillos (5% merma)
        $this->bom($caldera, [$this->line($sub, 3), $this->line($acero, 1), $this->line($tornillo, 10, 5)]);

        $out = collect($this->svc->explode($caldera, 2))->keyBy('product_id');

        // Acero: 2 * (3*2*1.10 + 1) = 2 * 7.6 = 15.2
        $this->assertEqualsWithDelta(15.2, $out[$acero->id]['quantity'], 0.0001);
        // Tornillo: 2 * (3*4 + 10*1.05) = 2 * 22.5 = 45
        $this->assertEqualsWithDelta(45.0, $out[$tornillo->id]['quantity'], 0.0001);
        $this->assertCount(2, $out);
        $this->assertArrayNotHasKey($sub->id, $out->all()); // subensamble expandido, no hoja
        $this->assertInstanceOf(Products::class, $out[$acero->id]['product']);
        $this->assertSame('pza', $out[$acero->id]['unit']);
    }

    public function test_rendimiento_divide_la_cantidad(): void
    {
        $p = $this->makeProduct();
        $c = $this->makeProduct(['cost' => 5]);
        $this->bom($p, [$this->line($c, 10)], ['yield_quantity' => 5]);

        $out = $this->svc->explode($p, 1);
        $this->assertEqualsWithDelta(2.0, $out[0]['quantity'], 0.0001);
    }

    public function test_producto_sin_receta_vigente_no_explota(): void
    {
        $this->assertSame([], $this->svc->explode($this->makeProduct(), 5));
    }

    public function test_rechaza_componente_igual_al_producto(): void
    {
        $p = $this->makeProduct();
        $this->expectException(ValidationException::class);
        $this->bom($p, [$this->line($p, 1)]);
    }

    public function test_rechaza_ciclos_en_cualquier_nivel(): void
    {
        $a = $this->makeProduct(['name' => 'A']);
        $b = $this->makeProduct(['name' => 'B']);
        $c = $this->makeProduct(['name' => 'C']);
        $this->bom($b, [$this->line($c, 1)]); // B -> C
        $this->bom($a, [$this->line($b, 1)]); // A -> B -> C

        $this->expectException(ValidationException::class);
        $this->bom($c, [$this->line($a, 1)]); // C -> A cerraría A->B->C->A
    }

    public function test_solo_una_vigente_por_producto(): void
    {
        $p = $this->makeProduct();
        $c = $this->makeProduct();
        $b1 = $this->bom($p, [$this->line($c, 1)]);
        $b2 = $this->bom($p, [$this->line($c, 2)]);

        $this->assertSame(2, $b2->version);
        $this->assertFalse($b1->fresh()->is_active);
        $this->assertTrue($b2->fresh()->is_active);

        $this->svc->activate($b1);
        $this->assertTrue($b1->fresh()->is_active);
        $this->assertFalse($b2->fresh()->is_active);
        $this->assertSame(1, Bom::where('product_id', $p->id)->where('is_active', true)->count());
    }

    public function test_duplicar_crea_version_siguiente_no_vigente(): void
    {
        $p = $this->makeProduct();
        $c = $this->makeProduct();
        $b1 = $this->bom($p, [$this->line($c, 3, 2)]);
        $b1->update(['name' => 'Base']);

        $copy = $this->svc->duplicate($b1->fresh('lines'));
        $this->assertSame(2, $copy->version);
        $this->assertFalse($copy->is_active);
        $this->assertCount(1, $copy->lines);
        $this->assertEquals(3, $copy->lines->first()->quantity);
        $this->assertEquals(2, $copy->lines->first()->scrap_percent);
    }

    public function test_costo_estandar_con_merma_subensamble_y_proveedor(): void
    {
        $caldera = $this->makeProduct(['name' => 'Caldera']);
        $sub = $this->makeProduct(['name' => 'Quemador']);
        $acero = $this->makeProduct(['cost' => 10]);
        $tornillo = $this->makeProduct(['cost' => null]); // sin costo propio -> proveedor
        $usd = $this->makeProduct(['cost' => 2, 'currency' => 'USD']);

        $supplier = Supplier::create(['company_name' => 'Prov', 'contact_name' => 'X', 'email' => 'p@example.com', 'phone' => '1', 'status' => 'active']);
        SupplierProduct::create(['supplier_id' => $supplier->id, 'product_id' => $tornillo->id, 'cost' => 3, 'is_primary' => true]);

        // Quemador: 2 acero (10%) = 22 ; 1 tornillo = 3 -> 25
        $this->bom($sub, [$this->line($acero, 2, 10), $this->line($tornillo, 1)]);
        $this->assertEqualsWithDelta(25.0, $this->svc->standardCost($sub), 0.001);

        // Caldera: 2 quemadores (50) + 1 acero (10) + 1 USD (2*17.5=35 con tasa por defecto)
        $this->bom($caldera, [$this->line($sub, 2), $this->line($acero, 1), $this->line($usd, 1)]);
        $rate = Products::exchangeRate();
        $this->assertEqualsWithDelta(60.0 + 2 * $rate, $this->svc->standardCost($caldera), 0.01);
    }

    public function test_profundidad_maxima_8_niveles(): void
    {
        $products = [];
        for ($i = 0; $i < 11; $i++) {
            $products[] = $this->makeProduct(['name' => "N{$i}", 'cost' => 1]);
        }
        // cadena: N0 -> N1 -> ... -> N10
        for ($i = 10; $i > 0; $i--) {
            $this->bom($products[$i - 1], [$this->line($products[$i], 1)]);
        }

        $out = $this->svc->explode($products[0], 1);
        // Profundidad máxima 8: se detiene en N8 (hoja forzada), nunca llega a N10
        $this->assertCount(1, $out);
        $this->assertSame($products[8]->id, $out[0]['product_id']);
    }

    // ───────────────────────── HTTP ─────────────────────────

    private function admin(): User
    {
        $role = Role::create(['name_role' => 'Administrador', 'name_role_es' => 'Administrador']);

        return User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'Administrador',
            'phone' => '5555555555', 'email' => 'admin-' . uniqid() . '@example.com',
            'password' => bcrypt('password'), 'status' => 'active', 'rfc' => 'XAXX010101000', 'role_id' => $role->id,
        ]);
    }

    public function test_pantallas_y_flujo_http(): void
    {
        $this->withoutVite();
        $admin = $this->admin();
        $p = $this->makeProduct(['name' => 'Caldera X']);
        $c = $this->makeProduct(['name' => 'Valvula', 'cost' => 50]);

        $this->actingAs($admin)->post(route('admin.boms.store'), [
            'product_id' => $p->id, 'yield_quantity' => 1, 'is_active' => 1,
            'lines' => [['component_product_id' => $c->id, 'quantity' => 2, 'unit' => 'pza', 'scrap_percent' => 5]],
        ])->assertRedirect();

        $bom = Bom::firstOrFail();
        $this->assertTrue($bom->is_active);

        $this->actingAs($admin)->get(route('admin.boms.index'))->assertOk()->assertSee('Caldera X');
        $this->actingAs($admin)->get(route('admin.boms.show', $bom))->assertOk()->assertSee('Valvula')->assertSee('105.00');
        $this->actingAs($admin)->get(route('admin.boms.edit', $bom))->assertOk();
        $this->actingAs($admin)->get(route('admin.boms.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.boms.search-products', ['q' => 'Valv']))->assertOk()->assertJsonFragment(['name' => 'Valvula']);

        $this->actingAs($admin)->post(route('admin.boms.duplicate', $bom))->assertRedirect();
        $this->assertSame(2, Bom::count());

        // ciclo vía HTTP -> error de validación
        $this->actingAs($admin)->post(route('admin.boms.store'), [
            'product_id' => $c->id, 'yield_quantity' => 1,
            'lines' => [['component_product_id' => $p->id, 'quantity' => 1]],
        ])->assertSessionHasErrors('lines');
    }
}
