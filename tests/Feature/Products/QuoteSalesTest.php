<?php

namespace Tests\Feature\Products;

use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Role;
use App\Models\User;
use App\Support\QuoteSales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\CreatesProductBlockFixtures;
use Tests\TestCase;

/**
 * "+N unidades vendidas": suma de las cantidades de un producto en las líneas de
 * cotizaciones ACEPTADAS. Ej.: 10 servicios con 5 controladores DC1010 en total
 * => "+5 unidades vendidas".
 */
class QuoteSalesTest extends TestCase
{
    use RefreshDatabase;
    use CreatesProductBlockFixtures;

    private ?User $user = null;
    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        QuoteSales::forget();
    }

    private function user(): User
    {
        if ($this->user) {
            return $this->user;
        }

        $role = Role::create(['name_role' => 'Administrador', 'name_role_es' => 'Administrador']);

        return $this->user = User::create([
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

    private function quote(string $status): Quote
    {
        return Quote::create([
            'quote_number'       => 'QS-' . str_pad((string) ++$this->seq, 5, '0', STR_PAD_LEFT),
            'created_by_user_id' => $this->user()->id,
            'status'             => $status,
            'guest_name'         => 'Cliente de prueba',
        ]);
    }

    private function line(Quote $quote, ?int $productId, int $quantity, ?int $servicePageId = null): QuoteItem
    {
        return QuoteItem::create([
            'quote_id'         => $quote->id,
            'product_id'       => $productId,
            'service_page_id'  => $servicePageId,
            'product_name'     => 'Linea',
            'quantity'         => $quantity,
            'unit_price'       => 100,
            'discount_percent' => 0,
            'tax_percent'      => 16,
            'line_total'       => 100 * $quantity,
            'sort_order'       => 0,
        ]);
    }

    public function test_suma_las_cantidades_de_las_cotizaciones_aceptadas(): void
    {
        $dc1010 = $this->makeProduct();
        // 10 servicios aceptados; en total 5 controladores (3 + 2, en dos cotizaciones distintas)
        $this->line($this->quote('accepted'), $dc1010->id, 3);
        $this->line($this->quote('accepted'), $dc1010->id, 2);

        $this->assertSame(5, QuoteSales::unitsFor($dc1010->id));
        $this->assertSame(5, $dc1010->fresh()->accepted_quote_units);
    }

    public function test_solo_cuentan_las_aceptadas_y_las_lineas_con_producto(): void
    {
        $p = $this->makeProduct();
        $other = $this->makeProduct();

        $this->line($this->quote('accepted'), $p->id, 4);
        foreach (['draft', 'sent', 'rejected', 'expired'] as $status) {
            $this->line($this->quote($status), $p->id, 100);
        }
        // Línea de servicio (sin producto) dentro de una cotización aceptada
        $this->line($this->quote('accepted'), null, 50);

        $this->assertSame(4, QuoteSales::unitsFor($p->id));
        $this->assertSame(0, QuoteSales::unitsFor($other->id));
    }

    public function test_cada_producto_suma_lo_suyo(): void
    {
        $a = $this->makeProduct();
        $b = $this->makeProduct();
        $quote = $this->quote('accepted');
        $this->line($quote, $a->id, 7);
        $this->line($quote, $b->id, 2);
        $this->line($quote, $a->id, 1);

        $this->assertSame(8, QuoteSales::unitsFor($a->id));
        $this->assertSame(2, QuoteSales::unitsFor($b->id));
    }

    public function test_la_cache_se_actualiza_al_aceptar_una_cotizacion_o_cambiar_una_linea(): void
    {
        $p = $this->makeProduct();
        $quote = $this->quote('sent');
        $item = $this->line($quote, $p->id, 3);

        $this->assertSame(0, QuoteSales::unitsFor($p->id)); // calienta la caché

        $quote->update(['status' => 'accepted']);
        $this->assertSame(3, QuoteSales::unitsFor($p->id));

        $item->update(['quantity' => 6]);
        $this->assertSame(6, QuoteSales::unitsFor($p->id));

        $quote->update(['status' => 'rejected']);
        $this->assertSame(0, QuoteSales::unitsFor($p->id));

        $item->delete();
        $quote->update(['status' => 'accepted']);
        $this->assertSame(0, QuoteSales::unitsFor($p->id));
    }

    public function test_el_texto_va_en_singular_y_plural_con_separador_de_miles(): void
    {
        $this->assertSame('+1 unidad vendida', QuoteSales::label(1));
        $this->assertSame('+5 unidades vendidas', QuoteSales::label(5));
        $this->assertSame('+1,250 unidades vendidas', QuoteSales::label(1250));
    }

    private function eyebrow(Quote|null $unused = null, ?string $slug = null): string
    {
        $html = $this->get(route('product.show', $slug))->assertOk()->getContent();
        preg_match('/<div class="product-price-box__eyebrow.*?<\/div>/s', $html, $m);
        $this->assertNotEmpty($m, 'No se encontró la barra de estado/SKU');

        return $m[0];
    }

    public function test_la_ficha_muestra_la_insignia_con_el_total(): void
    {
        $p = $this->makeProduct();
        $this->line($this->quote('accepted'), $p->id, 5);

        $bar = $this->eyebrow(null, $p->slug);

        $this->assertStringContainsString('product-price-box__sold', $bar);
        $this->assertStringContainsString('+5 unidades vendidas', $bar);
    }

    public function test_la_ficha_no_muestra_la_insignia_sin_ventas(): void
    {
        $p = $this->makeProduct();
        $this->line($this->quote('draft'), $p->id, 5);

        $bar = $this->eyebrow(null, $p->slug);

        $this->assertStringNotContainsString('product-price-box__sold', $bar);
        $this->assertStringNotContainsString('vendid', $bar);
    }

    public function test_la_ficha_usa_singular_con_una_unidad(): void
    {
        $p = $this->makeProduct();
        $this->line($this->quote('accepted'), $p->id, 1);

        $this->assertStringContainsString('+1 unidad vendida', $this->eyebrow(null, $p->slug));
    }
}
