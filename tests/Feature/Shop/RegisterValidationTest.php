<?php

namespace Tests\Feature\Shop;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Registro de clientes de la tienda: campos obligatorios marcados y caracteres validados. */
class RegisterValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function payload(array $over = []): array
    {
        return array_merge([
            'first_name' => 'María José', 'last_name' => "O'Connor-Peña", 'company' => 'Calderas & Co., S.A. de C.V.',
            'email' => 'maria' . uniqid() . '@example.com', 'phone' => '+52 (449) 123-4567',
            'password' => 'Secreta-2026', 'password_confirmation' => 'Secreta-2026', 'terms' => '1',
        ], $over);
    }

    public function test_el_formulario_marca_los_campos_obligatorios(): void
    {
        $html = $this->get(route('shop.register'))->assertOk()->getContent();

        foreach (['reg_first_name', 'reg_last_name', 'reg_email', 'reg_phone', 'reg_password', 'reg_password_confirmation'] as $id) {
            $this->assertMatchesRegularExpression('/<label for="' . $id . '">[^<]*<span class="auth-req"/', $html, "$id sin asterisco");
        }
        $this->assertStringContainsString('Campos obligatorios', $html);
        $this->assertMatchesRegularExpression('/<label for="reg_company">Empresa <small class="auth-optional">\(opcional\)/', $html);
    }

    public function test_registro_valido_con_acentos_apostrofos_y_razon_social(): void
    {
        $this->post(route('shop.register.store'), $this->payload())->assertRedirect(route('shop.account'));

        $this->assertDatabaseHas('customers', ['first_name' => 'María José', 'last_name' => "O'Connor-Peña"]);
    }

    /** @dataProvider camposInvalidos */
    public function test_rechaza_caracteres_no_permitidos(string $campo, string $valor): void
    {
        $this->from(route('shop.register'))
            ->post(route('shop.register.store'), $this->payload([$campo => $valor]))
            ->assertSessionHasErrors($campo);

        $this->assertSame(0, Customer::count());
    }

    public static function camposInvalidos(): array
    {
        return [
            'nombre con #$%' => ['first_name', 'Dilan #$%'],
            'nombre con números' => ['first_name', 'Dilan2'],
            'apellido con símbolos' => ['last_name', 'Garcia@!'],
            'empresa con #$%' => ['company', 'Asherl #$%'],
            'teléfono con letras' => ['phone', '449abc4567'],
            'teléfono con símbolos' => ['phone', '449#123$4567'],
            'teléfono muy corto' => ['phone', '12345'],
            'teléfono muy largo' => ['phone', '1234567890123456'],
        ];
    }

    public function test_los_obligatorios_vacios_dan_mensaje_por_campo(): void
    {
        $this->from(route('shop.register'))
            ->post(route('shop.register.store'), ['first_name' => '', 'last_name' => '', 'email' => '', 'phone' => '', 'password' => ''])
            ->assertSessionHasErrors(['first_name', 'last_name', 'email', 'phone', 'password', 'terms']);
    }

    public function test_exige_aceptar_terminos(): void
    {
        $data = $this->payload();
        unset($data['terms']);

        $this->from(route('shop.register'))->post(route('shop.register.store'), $data)->assertSessionHasErrors('terms');
        $this->assertSame(0, Customer::count());
    }

    public function test_la_empresa_es_opcional(): void
    {
        $this->post(route('shop.register.store'), $this->payload(['company' => '']))->assertRedirect(route('shop.account'));
    }
}
