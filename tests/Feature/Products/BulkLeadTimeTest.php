<?php

namespace Tests\Feature\Products;

use App\Models\Products;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesProductBlockFixtures;
use Tests\TestCase;

/**
 * Editor por lotes: "Estado de disponibilidad" y "Tiempo de entrega estimado".
 * El tiempo de entrega solo existe con disponibilidad "Bajo pedido" (mismo
 * criterio que el formulario individual).
 */
class BulkLeadTimeTest extends TestCase
{
    use RefreshDatabase;
    use CreatesProductBlockFixtures;

    private function adminUser(): User
    {
        $role = Role::create(['name_role' => 'Administrador', 'name_role_es' => 'Administrador']);

        return User::create([
            'first_name' => 'Admin',
            'last_name'  => 'Test',
            'position'   => 'Administrador',
            'phone'      => '5555555555',
            'email'      => 'admin-' . uniqid() . '@example.com',
            'password'   => bcrypt('password'),
            'status'     => 'active',
            'rfc'        => 'XAXX010101000',
            'role_id'    => $role->id,
        ]);
    }

    /** availability/lead_time_text no son fillable: se asignan por propiedad, como el controller. */
    private function product(string $availability, ?string $leadTime = null): Products
    {
        $p = $this->makeProduct();
        $p->availability = $availability;
        $p->lead_time_text = $leadTime;
        $p->save();

        return Products::findOrFail($p->id);
    }

    private function save(array $changes)
    {
        return $this->actingAs($this->adminUser())->postJson(route('admin.products.bulk.save'), ['changes' => $changes]);
    }

    private function resultFor(array $json, int $id, string $field): array
    {
        foreach ($json['results'] as $r) {
            if ($r['id'] === $id && $r['field'] === $field) {
                return $r;
            }
        }
        $this->fail("Sin resultado para {$id}/{$field}");
    }

    public function test_se_guarda_disponibilidad_y_tiempo_de_entrega_juntos(): void
    {
        $p = $this->product('available');

        $json = $this->save([
            ['id' => $p->id, 'field' => 'availability', 'value' => 'on_order'],
            ['id' => $p->id, 'field' => 'lead_time_text', 'value' => '  5-7 días hábiles  '],
        ])->assertOk()->json();

        $this->assertTrue($this->resultFor($json, $p->id, 'lead_time_text')['ok']);
        $fresh = $p->fresh();
        $this->assertSame('on_order', $fresh->availability);
        $this->assertSame('5-7 días hábiles', $fresh->lead_time_text);
    }

    public function test_se_puede_cambiar_el_tiempo_de_entrega_de_un_producto_ya_bajo_pedido(): void
    {
        $p = $this->product('on_order', '3 días');

        $this->save([['id' => $p->id, 'field' => 'lead_time_text', 'value' => '10 días']])->assertOk();

        $this->assertSame('10 días', $p->fresh()->lead_time_text);
    }

    public function test_rechaza_un_tiempo_de_entrega_para_un_producto_que_no_es_bajo_pedido(): void
    {
        $p = $this->product('available');

        $json = $this->save([['id' => $p->id, 'field' => 'lead_time_text', 'value' => '5 días']])->assertOk()->json();

        $r = $this->resultFor($json, $p->id, 'lead_time_text');
        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('Bajo pedido', $r['error']);
        $this->assertNull($p->fresh()->lead_time_text);
    }

    public function test_dejar_de_ser_bajo_pedido_limpia_el_tiempo_de_entrega(): void
    {
        $p = $this->product('on_order', '5-7 días hábiles');

        $this->save([['id' => $p->id, 'field' => 'availability', 'value' => 'out_of_stock']])->assertOk();

        $fresh = $p->fresh();
        $this->assertSame('out_of_stock', $fresh->availability);
        $this->assertNull($fresh->lead_time_text);
    }

    public function test_vaciar_el_tiempo_de_entrega_es_valido(): void
    {
        $p = $this->product('on_order', '5 días');

        $json = $this->save([['id' => $p->id, 'field' => 'lead_time_text', 'value' => '']])->assertOk()->json();

        $this->assertTrue($this->resultFor($json, $p->id, 'lead_time_text')['ok']);
        $this->assertNull($p->fresh()->lead_time_text);
    }

    public function test_maximo_150_caracteres(): void
    {
        $p = $this->product('on_order');

        $json = $this->save([['id' => $p->id, 'field' => 'lead_time_text', 'value' => str_repeat('a', 151)]])->assertOk()->json();

        $this->assertFalse($this->resultFor($json, $p->id, 'lead_time_text')['ok']);
        $this->assertNull($p->fresh()->lead_time_text);
    }

    public function test_un_lote_mixto_guarda_lo_valido_y_avisa_lo_demas(): void
    {
        $onOrder = $this->product('on_order');
        $available = $this->product('available');

        $json = $this->save([
            ['id' => $onOrder->id, 'field' => 'lead_time_text', 'value' => '2 semanas'],
            ['id' => $available->id, 'field' => 'lead_time_text', 'value' => '2 semanas'],
            ['id' => $available->id, 'field' => 'stock', 'value' => 9],
        ])->assertOk()->json();

        $this->assertTrue($this->resultFor($json, $onOrder->id, 'lead_time_text')['ok']);
        $this->assertFalse($this->resultFor($json, $available->id, 'lead_time_text')['ok']);
        $this->assertTrue($this->resultFor($json, $available->id, 'stock')['ok']);
        $this->assertSame('2 semanas', $onOrder->fresh()->lead_time_text);
        $this->assertNull($available->fresh()->lead_time_text);
        $this->assertSame(9, (int) $available->fresh()->stock);
    }

    public function test_la_pantalla_muestra_las_columnas_y_el_valor_actual(): void
    {
        $this->product('on_order', 'Entrega en 12 días');

        $html = $this->actingAs($this->adminUser())->get(route('admin.products.bulk-edit'))->assertOk()->getContent();

        $this->assertStringContainsString('Estado de disponibilidad', $html);
        $this->assertStringContainsString('Tiempo de entrega estimado', $html);
        $this->assertStringContainsString('Bajo pedido', $html);
        $this->assertStringContainsString('data-field="lead_time_text"', $html);
        $this->assertStringContainsString('Entrega en 12 días', $html);
    }

    public function test_la_columna_se_puede_guardar_en_una_vista(): void
    {
        $this->actingAs($this->adminUser())
            ->postJson(route('admin.products.bulk-edit.views.store'), [
                'name'    => 'Bajo pedido',
                'columns' => ['availability', 'lead_time_text'],
                'widths'  => ['lead_time_text' => 220],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
