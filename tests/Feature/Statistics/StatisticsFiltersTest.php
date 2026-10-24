<?php

namespace Tests\Feature\Statistics;

use App\Models\Quote;
use App\Models\Role;
use App\Models\StoreOrder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Filtros por rango y comparación contra el periodo anterior, de punta a
 * punta (ruta -> controlador -> servicio -> vista) con fechas conocidas.
 */
class StatisticsFiltersTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Carbon::setTestNow(Carbon::parse('2026-10-24 12:00:00', 'America/Mexico_City'));

        $role = Role::firstOrCreate(['name_role' => 'Administrador'], ['name_role_es' => 'Administrador']);
        $this->admin = User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'A', 'phone' => '5555555555',
            'email' => 'u-' . uniqid() . '@example.com', 'password' => bcrypt('x'), 'status' => 'active',
            'rfc' => 'RFC' . strtoupper(substr(uniqid(), -8)) . random_int(10, 99), 'role_id' => $role->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function quote(string $when, string $status, float $total): Quote
    {
        static $n = 0;
        $n++;

        $quote = Quote::forceCreate([
            'quote_number' => 'COT-T' . $n,
            'created_by_user_id' => $this->admin->id,
            'status' => $status,
            'guest_name' => 'Cliente ' . $n,
            'subtotal' => $total,
            'total' => $total,
        ]);
        $quote->forceFill(['created_at' => Carbon::parse($when, 'America/Mexico_City'), 'updated_at' => Carbon::parse($when, 'America/Mexico_City')])->saveQuietly();

        return $quote;
    }

    private function storeOrder(string $when, float $total): StoreOrder
    {
        static $n = 0;
        $n++;

        $order = StoreOrder::forceCreate([
            'order_number' => 'PW-T' . $n,
            'contact_name' => 'Web ' . $n,
            'contact_email' => "web$n@example.com",
            'contact_phone' => '5555555555',
            'shipping_address_line1' => 'Calle 1', 'shipping_city' => 'CDMX', 'shipping_state' => 'CDMX',
            'shipping_postal_code' => '01000', 'shipping_country' => 'MX',
            'subtotal' => $total, 'total' => $total, 'tax_total' => 0, 'shipping_total' => 0, 'discount_total' => 0,
            'status' => 'pagado',
            'requires_invoice' => $n % 2 === 0,
        ]);
        $order->forceFill(['created_at' => Carbon::parse($when, 'America/Mexico_City'), 'updated_at' => Carbon::parse($when, 'America/Mexico_City')])->saveQuietly();

        return $order;
    }

    /** @return array<string, array> KPIs por etiqueta */
    private function kpis($response): array
    {
        $out = [];
        foreach ($response->viewData('kpis') as $kpi) {
            $out[$kpi['label']] = $kpi;
        }

        return $out;
    }

    private function seedVentas(): void
    {
        // Octubre 2026 (actual): 3 cotizaciones, 2 aceptadas.
        $this->quote('2026-10-01 00:00:00', 'accepted', 1000);   // primer instante del mes
        $this->quote('2026-10-15 10:00:00', 'accepted', 2000);
        $this->quote('2026-10-20 10:00:00', 'sent', 500);
        // Septiembre 2026 (anterior): 1–24 sep cuenta para la comparación; el 28 no.
        $this->quote('2026-09-01 08:00:00', 'accepted', 1500);
        $this->quote('2026-09-24 23:59:59', 'sent', 500);        // último instante del avance (24 sep)
        $this->quote('2026-09-28 10:00:00', 'accepted', 9000);   // fuera del avance de 24 días
        $this->quote('2026-08-31 23:59:59', 'accepted', 7777);   // fuera del mes anterior
    }

    public function test_el_rango_personalizado_filtra_inclusive_en_los_bordes(): void
    {
        $this->seedVentas();

        // 1 oct 00:00:00 a 15 oct 10:00:00 → entran las dos primeras; la del 20 no.
        $r = $this->actingAs($this->admin)->get('/admin/estadisticas/ventas?date_from=2026-10-01&date_to=2026-10-15');
        $r->assertOk();
        $k = $this->kpis($r);
        $this->assertSame('$3.0k', $k['Monto cotizado']['value']);
        $this->assertSame('$3.0k', $k['Monto aceptado']['value']);

        // Un solo día.
        $r = $this->actingAs($this->admin)->get('/admin/estadisticas/ventas?date_from=2026-10-20&date_to=2026-10-20');
        $this->assertSame('$500', $this->kpis($r)['Monto cotizado']['value']);

        // desde > hasta se ordena en vez de dar vacío.
        $r = $this->actingAs($this->admin)->get('/admin/estadisticas/ventas?date_from=2026-10-15&date_to=2026-10-01');
        $this->assertSame('$3.0k', $this->kpis($r)['Monto cotizado']['value']);

        // Rango que cruza de año, sin datos: cero, sin error.
        $r = $this->actingAs($this->admin)->get('/admin/estadisticas/ventas?date_from=2025-12-20&date_to=2026-01-09');
        $r->assertOk();
        $this->assertSame('$0', $this->kpis($r)['Monto cotizado']['value']);
    }

    public function test_el_preset_mes_filtra_y_el_selector_personalizado_manda_sobre_el_preset(): void
    {
        $this->seedVentas();

        $r = $this->actingAs($this->admin)->get('/admin/estadisticas/ventas?period=mes');
        $this->assertSame('$3.5k', $this->kpis($r)['Monto cotizado']['value']);

        // date_from/date_to ganan sobre period.
        $r = $this->actingAs($this->admin)->get('/admin/estadisticas/ventas?period=mes&date_from=2026-09-01&date_to=2026-09-30');
        $this->assertSame('custom', $r->viewData('period')['period']);
        $this->assertSame('$11.0k', $this->kpis($r)['Monto cotizado']['value']);
    }

    public function test_comparacion_de_mes_muestra_variacion_y_valor_previo_del_mismo_avance(): void
    {
        $this->seedVentas();

        $r = $this->actingAs($this->admin)->get('/admin/estadisticas/ventas?period=mes&compare=1');
        $r->assertOk();
        $k = $this->kpis($r);

        // Actual: 3,500 cotizado / 3,000 aceptado. Previo (1–24 sep): 2,000 / 1,500.
        $this->assertSame('$3.5k', $k['Monto cotizado']['value']);
        $this->assertSame('+75.0%', $k['Monto cotizado']['trend']);
        $this->assertTrue($k['Monto cotizado']['trendUp']);
        $this->assertStringContainsString('$2.0k', $k['Monto cotizado']['hint']);
        $this->assertStringContainsString('mes anterior', $k['Monto cotizado']['hint']);

        $this->assertSame('+100.0%', $k['Monto aceptado']['trend']);

        // Conversión: actual 2/3 = 66.7%; previa 1/2 = 50.0% → +16.7 pp.
        $this->assertSame('66.7%', $k['Tasa de conversión']['value']);
        $this->assertSame('+16.7 pp', $k['Tasa de conversión']['trend']);

        // La barra de filtros muestra contra qué se compara.
        $label = $r->viewData('period')['compareRangeLabel'];
        $this->assertStringContainsString('01 sep', $label);
        $this->assertStringContainsString('24 sep', $label);
        $r->assertSee('Comparando', false);
        $r->assertSee($label, false);
    }

    public function test_sin_comparacion_los_kpis_conservan_su_texto_estatico(): void
    {
        $this->seedVentas();

        $k = $this->kpis($this->actingAs($this->admin)->get('/admin/estadisticas/ventas?period=mes'));
        $this->assertSame('3 cotizaciones creadas', $k['Monto cotizado']['trend']);
        $this->assertStringNotContainsString('vs ', $k['Monto cotizado']['hint']);
    }

    public function test_comparacion_con_rango_personalizado_de_mes_completo(): void
    {
        $this->seedVentas();

        // Septiembre completo vs agosto completo (7,777 en el 31 ago).
        $r = $this->actingAs($this->admin)->get('/admin/estadisticas/ventas?date_from=2026-09-01&date_to=2026-09-30&compare=1');
        $k = $this->kpis($r);
        $this->assertSame('$11.0k', $k['Monto cotizado']['value']);
        $this->assertSame('+41.4%', $k['Monto cotizado']['trend']); // (11000 - 7777) / 7777
        $this->assertSame('mes anterior', $r->viewData('period')['compareLabel']);
    }

    public function test_comparacion_sin_base_previa_no_divide_entre_cero(): void
    {
        $this->quote('2026-10-10 10:00:00', 'accepted', 1000);

        $k = $this->kpis($this->actingAs($this->admin)->get('/admin/estadisticas/ventas?period=mes&compare=1'));
        $this->assertSame('Nuevo', $k['Monto cotizado']['trend']);
        $this->assertTrue($k['Monto cotizado']['trendUp']);
    }

    public function test_tienda_filtra_y_compara(): void
    {
        $this->storeOrder('2026-10-05 09:00:00', 400);
        $this->storeOrder('2026-10-06 09:00:00', 600);
        $this->storeOrder('2026-09-10 09:00:00', 250);
        $this->storeOrder('2026-09-29 09:00:00', 5000); // fuera del avance 1–24 sep

        $r = $this->actingAs($this->admin)->get('/admin/estadisticas/tienda?period=mes&compare=1');
        $r->assertOk();
        $k = $this->kpis($r);

        $this->assertSame('$1.0k', $k['Ingreso de tienda']['value']);
        $this->assertSame('+300.0%', $k['Ingreso de tienda']['trend']);   // 1000 vs 250
        $this->assertSame('2', $k['Pedidos web']['value']);
        $this->assertSame('+100.0%', $k['Pedidos web']['trend']);          // 2 vs 1
        $this->assertSame('$500', $k['Ticket promedio']['value']);
        $this->assertSame('+100.0%', $k['Ticket promedio']['trend']);     // 500 vs 250
    }

    public function test_resumen_compara_el_ingreso_combinado_y_conserva_el_filtro_en_las_tarjetas(): void
    {
        $this->quote('2026-10-02 10:00:00', 'accepted', 1000);
        $this->storeOrder('2026-10-03 10:00:00', 500);
        $this->quote('2026-09-02 10:00:00', 'accepted', 500);

        $r = $this->actingAs($this->admin)->get('/admin/estadisticas?period=mes&compare=1');
        $r->assertOk();
        $k = $this->kpis($r);
        $this->assertSame('$1.5k', $k['Ingreso combinado']['value']);
        $this->assertSame('+200.0%', $k['Ingreso combinado']['trend']);

        // Las tarjetas "Entrar a un dominio" llevan el filtro a la sección.
        $r->assertSee('/admin/estadisticas/ventas?period=mes&amp;compare=1', false);
    }

    public function test_el_toggle_de_comparar_conserva_el_periodo_elegido(): void
    {
        // El toggle hace form.submit(): sin un input period oculto, el envío
        // no llevaba el periodo y volvía a "Este mes".
        $r = $this->actingAs($this->admin)->get('/admin/estadisticas/ventas?period=trimestre');
        $r->assertSee('<input type="hidden" name="period" value="trimestre">', false);
        $r->assertSee('data-carry-query="period=trimestre"', false);

        $r = $this->actingAs($this->admin)->get('/admin/estadisticas/ventas?date_from=2026-09-01&date_to=2026-09-30&compare=1');
        $r->assertSee('<input type="hidden" name="period" value="">', false);
        $r->assertSee('name="date_from" id="statsDateFrom" value="2026-09-01"', false);
        $r->assertSee('data-carry-query="date_from=2026-09-01&amp;date_to=2026-09-30&amp;compare=1"', false);
    }

    public function test_todas_las_secciones_cargan_con_comparacion_y_con_datos(): void
    {
        $this->seedVentas();
        $this->storeOrder('2026-10-05 09:00:00', 400);

        foreach (['', '/ventas', '/embudo', '/compras', '/servicios', '/reportes', '/inventario', '/tienda', '/marketing'] as $s) {
            foreach (['?period=mes&compare=1', '?period=hoy&compare=1', '?date_from=2026-09-01&date_to=2026-10-24&compare=1', '?period=anio'] as $q) {
                $this->actingAs($this->admin)->get('/admin/estadisticas' . $s . $q)->assertOk();
            }
        }
    }

    public function test_parametros_basura_no_producen_error(): void
    {
        foreach ([
            '?period[]=x', '?period=%27%3B--', '?compare=abc', '?compare[]=1', '?date_from[]=2026-01-01&date_to=2026-01-02',
            '?date_from=2026-02-30&date_to=2026-03-01', '?date_from=0001-01-01&date_to=9999-12-31', '?date_from=1&date_to=2',
        ] as $q) {
            $this->actingAs($this->admin)->get('/admin/estadisticas/ventas' . $q)->assertOk();
        }

        $this->actingAs($this->admin)->get('/admin/estadisticas/nope')->assertNotFound();
    }
}
