<?php

namespace Tests\Feature\Shop;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Direcciones de la tienda (Mi cuenta): campos validados, avisos en español y modal que se reabre con los errores. */
class CustomerAddressValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        // El registro deja la sesión del cliente iniciada (guard customer).
        $this->post(route('shop.register.store'), [
            'first_name' => 'Ana', 'last_name' => 'Lopez', 'email' => 'ana@example.com', 'phone' => '4491234567',
            'password' => 'Secreta-2026', 'password_confirmation' => 'Secreta-2026', 'terms' => '1',
        ])->assertRedirect(route('shop.account'));
    }

    private function payload(array $over = []): array
    {
        return array_merge([
            'label' => 'Oficina Corporativa', 'recipient_name' => "María O'Connor-Peña", 'phone' => '+52 (449) 123-4567',
            'address_line1' => 'Av. Juárez #123 int. 4', 'address_line2' => 'Col. Centro (frente al parque)',
            'postal_code' => '20000', 'state' => 'Aguascalientes', 'city' => 'Aguascalientes',
        ], $over);
    }

    public function test_direccion_valida_con_numeral_acentos_y_parentesis(): void
    {
        $this->post(route('shop.addresses.store'), $this->payload())->assertSessionHasNoErrors();

        $this->assertDatabaseHas('customer_addresses', ['recipient_name' => "María O'Connor-Peña", 'address_line1' => 'Av. Juárez #123 int. 4']);
    }

    /** @dataProvider camposInvalidos */
    public function test_rechaza_valores_no_permitidos(string $campo, string $valor): void
    {
        $this->from(route('shop.account'))
            ->post(route('shop.addresses.store'), $this->payload([$campo => $valor]))
            ->assertSessionHasErrors($campo);

        $this->assertSame(0, Customer::first()->customer_addresses()->count());
    }

    public static function camposInvalidos(): array
    {
        return [
            'nombre con #$%' => ['recipient_name', 'Ana #$%'],
            'nombre con números' => ['recipient_name', 'Ana2'],
            'ciudad con símbolos' => ['city', 'León@!'],
            'ciudad con números' => ['city', 'León 2'],
            'calle con $%' => ['address_line1', 'Calle $%& 5'],
            'colonia con @' => ['address_line2', 'Col. @Centro'],
            'etiqueta con #$%' => ['label', 'Casa #$%'],
            'teléfono con letras' => ['phone', '449abc4567'],
            'teléfono corto' => ['phone', '12345'],
            'cp con letras' => ['postal_code', '20A00'],
            'cp de 4 dígitos' => ['postal_code', '2000'],
            'cp de 6 dígitos' => ['postal_code', '200000'],
            'estado inventado' => ['state', 'Narnia'],
        ];
    }

    public function test_los_obligatorios_vacios_dan_aviso_en_espanol(): void
    {
        $this->from(route('shop.account'))->post(route('shop.addresses.store'), [])
            ->assertSessionHasErrors(['recipient_name', 'phone', 'postal_code', 'state', 'city', 'address_line1']);

        $msgs = collect(session('errors')->getBag('default')->all());
        foreach ($msgs as $m) {
            $this->assertDoesNotMatchRegularExpression('/\b(field|must|The|is required)\b/', $m, "aviso en ingles: $m");
        }
        $this->assertContains('Escribe la calle y el número.', $msgs->all());
        $this->assertContains('Selecciona un estado.', $msgs->all());
    }

    public function test_etiqueta_colonia_y_referencias_son_opcionales(): void
    {
        $this->post(route('shop.addresses.store'), $this->payload(['label' => '', 'address_line2' => '']))->assertSessionHasNoErrors();
    }

    public function test_el_modal_se_reabre_con_lo_capturado_y_el_error_en_su_campo(): void
    {
        $html = $this->from(route('shop.account'))->followingRedirects()
            ->post(route('shop.addresses.store'), $this->payload(['_am_form' => 'address', '_am_url' => route('shop.addresses.store'), 'phone' => '', 'postal_code' => '12']))
            ->assertOk()->getContent();

        $this->assertStringContainsString("openModal('address-form'", $html);
        $this->assertStringContainsString('Escribe un teléfono de contacto.', $html);
        $this->assertStringContainsString('El código postal debe tener 5 dígitos.', $html);
        $this->assertMatchesRegularExpression('/portal-field is-invalid">\s*<label for="am_phone"/', $html);
        $this->assertStringContainsString('Oficina Corporativa', $html);
    }

    public function test_sin_errores_el_modal_no_se_reabre(): void
    {
        $this->get(route('shop.account'))->assertOk()->assertDontSee("openModal('address-form', {\"title\"", false);
    }
}
