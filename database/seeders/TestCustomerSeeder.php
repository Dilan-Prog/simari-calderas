<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Cliente de pruebas con datos ficticios, con acceso al portal, para desarrollo local.
 * NO está en DatabaseSeeder a propósito: no debe sembrarse en producción.
 *
 *   php artisan db:seed --class=TestCustomerSeeder
 *
 * Portal de clientes: correo cliente.prueba@example.com, contraseña Prueba-Equiterm-2026
 * (solo para desarrollo; el dominio example.com no recibe correos reales).
 */
class TestCustomerSeeder extends Seeder
{
    public const EMAIL = 'cliente.prueba@example.com';
    public const PASSWORD = 'Prueba-Equiterm-2026';

    public function run(): void
    {
        $customer = Customer::updateOrCreate(
            ['email' => self::EMAIL],
            [
                'first_name'    => 'Cliente',
                'last_name'     => 'De Pruebas',
                'phone'         => '449 000 0000',
                'company'       => 'Industrias Ficticias de Prueba SA de CV',
                'website'       => 'https://example.com',
                'rfc'           => 'XAXX010101000',
                'tipo_persona'  => 'moral',
                'document_type' => 'cfdi',
                'status'        => 'active',
                'source'        => 'admin',
                'portal_access' => true,
                'password_hash' => Hash::make(self::PASSWORD),
                'notes'         => 'Cliente ficticio para pruebas. No es una persona ni una empresa real.',
            ]
        );

        CustomerAddress::updateOrCreate(
            ['customer_id' => $customer->id, 'label' => 'fiscal'],
            [
                'recipient_name' => 'Cliente De Pruebas',
                'phone'          => '449 000 0000',
                'country'        => 'México',
                'state'          => 'Aguascalientes',
                'city'           => 'Aguascalientes',
                'postal_code'    => '20000',
                'address_line1'  => 'Calle Ficticia 123, Col. Centro',
                'is_default'     => true,
            ]
        );
    }
}
