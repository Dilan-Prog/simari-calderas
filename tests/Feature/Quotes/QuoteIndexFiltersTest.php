<?php

namespace Tests\Feature\Quotes;

use App\Models\Customer;
use App\Models\Quote;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteIndexFiltersTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $role = Role::firstOrCreate(['name_role' => 'Administrador'], ['name_role_es' => 'Administrador']);
        $this->admin = User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'A', 'phone' => '5555555555',
            'email' => 'u-' . uniqid() . '@example.com', 'password' => bcrypt('x'), 'status' => 'active',
            'rfc' => 'RFC' . strtoupper(substr(uniqid(), -8)) . random_int(10, 99), 'role_id' => $role->id,
        ]);
    }

    private function quote(string $number, array $over = [], ?string $createdAt = null): Quote
    {
        $q = Quote::create($over + [
            'quote_number' => $number, 'status' => 'draft', 'guest_name' => 'Cliente ' . $number,
            'currency' => 'MXN', 'subtotal' => 100, 'total' => 116, 'created_by_user_id' => $this->admin->id,
        ]);
        if ($createdAt) {
            $q->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();
        }

        return $q;
    }

    private function numbers(array $params): array
    {
        $res = $this->actingAs($this->admin)->get(route('admin.quotes.index', $params))->assertOk();

        return $res->viewData('quotes')->pluck('quote_number')->all();
    }

    public function test_filtra_por_estado(): void
    {
        $this->quote('COT-A', ['status' => 'sent']);
        $this->quote('COT-B', ['status' => 'accepted']);
        $this->quote('COT-C', ['status' => 'expired']);

        $this->assertSame(['COT-A'], $this->numbers(['status' => 'sent']));
        $this->assertSame(['COT-C'], $this->numbers(['status' => 'expired']));
        $this->assertCount(3, $this->numbers([]));
    }

    public function test_el_select_de_estado_dice_vencida_y_se_envia_solo(): void
    {
        $this->quote('COT-A');
        $html = $this->actingAs($this->admin)->get(route('admin.quotes.index'))->assertOk()->getContent();

        $this->assertStringContainsString('>Vencida</option>', $html);
        $this->assertStringNotContainsString('>Expirada<', $html);
        $this->assertStringContainsString('onchange="this.form.submit()"', $html);
    }

    public function test_rango_de_fechas_incluye_el_dia_final_completo(): void
    {
        $this->quote('COT-OLD', [], '2026-08-31 23:59:59');
        $this->quote('COT-INI', [], '2026-09-01 00:00:00');
        $this->quote('COT-FIN', [], '2026-09-30 23:59:59');
        $this->quote('COT-NEW', [], '2026-10-01 00:00:00');

        $found = $this->numbers(['date_from' => '2026-09-01', 'date_to' => '2026-09-30']);
        sort($found);
        $this->assertSame(['COT-FIN', 'COT-INI'], $found);
        $this->assertSame(['COT-NEW'], $this->numbers(['date_from' => '2026-10-01']));
        $this->assertCount(3, $this->numbers(['date_to' => '2026-09-30']));
    }

    public function test_rango_invertido_o_invalido_no_rompe(): void
    {
        $this->quote('COT-X', [], '2026-09-15 10:00:00');

        $this->assertSame(['COT-X'], $this->numbers(['date_from' => '2026-09-30', 'date_to' => '2026-09-01']));
        $this->assertSame(['COT-X'], $this->numbers(['date_from' => 'basura', 'date_to' => '']));
    }

    public function test_busca_por_folio_nombre_empresa_correo_y_telefono(): void
    {
        $this->quote('COT-100', ['guest_name' => 'Maria Lopez', 'guest_company' => 'Acme SA', 'guest_email' => 'maria@acme.test', 'guest_phone' => '5599887766']);
        $this->quote('COT-200', ['guest_name' => 'Otro']);

        foreach (['COT-100', 'Lopez', 'Acme', 'maria@acme', '9988'] as $term) {
            $this->assertSame(['COT-100'], $this->numbers(['search' => $term]), $term);
        }
    }

    public function test_busca_por_cliente_registrado(): void
    {
        $c = Customer::create([
            'first_name' => 'Zulema', 'last_name' => 'Quintero', 'email' => 'zq@example.com', 'phone' => '5511223344',
            'password_hash' => 'x', 'document_type' => 'RFC', 'document_numer' => 'X', 'birth_date' => '1990-01-01',
            'status' => 'active', 'source' => 'web', 'company' => 'Textiles Quintero',
        ]);
        $this->quote('COT-CL', ['customer_id' => $c->id, 'guest_name' => 'Contacto Distinto']);
        $this->quote('COT-NO');

        foreach (['Zulema', 'Quintero', 'Zulema Quintero', 'Textiles', 'zq@example', '1122'] as $term) {
            $this->assertSame(['COT-CL'], $this->numbers(['search' => $term]), $term);
        }
    }

    public function test_filtros_combinados_y_paginacion_conservan_parametros(): void
    {
        for ($i = 1; $i <= 17; $i++) {
            $this->quote(sprintf('COT-S%02d', $i), ['status' => 'sent'], '2026-09-10 12:00:00');
        }
        $this->quote('COT-D', ['status' => 'draft'], '2026-09-10 12:00:00');

        $res = $this->actingAs($this->admin)
            ->get(route('admin.quotes.index', ['status' => 'sent', 'date_from' => '2026-09-01', 'date_to' => '2026-09-30']))
            ->assertOk();
        $this->assertSame(17, $res->viewData('quotes')->total());
        $res->assertSee('17</strong> resultados', false);
        $res->assertSee('Limpiar filtros');
        $res->assertSee('status=sent', false);
        $res->assertSee('page=2', false);
    }

    public function test_sin_filtros_no_muestra_contador(): void
    {
        $this->quote('COT-A');
        $this->actingAs($this->admin)->get(route('admin.quotes.index'))->assertOk()->assertDontSee('quotes-active-filters');
    }
}
