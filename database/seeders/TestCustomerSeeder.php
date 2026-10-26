<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Products;
use App\Models\Quote;
use App\Models\User;
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

        $this->seedQuotes($customer);
    }

    /**
     * Cotizaciones de muestra (una por estado que muestra el portal) para que
     * /customer/cotizaciones se vea con datos. Idempotente: se identifican por su folio COT-DEMO-xxxx.
     */
    private function seedQuotes(Customer $customer): void
    {
        $products = Products::query()->orderBy('id')->limit(6)->get();
        if ($products->isEmpty()) {
            return;
        }
        $creator = User::query()->orderBy('id')->value('id');

        // folio => [estado, dias desde hoy hasta valid_until, dias desde hoy del envio, [[indice de producto, cantidad]]]
        $plans = [
            'COT-DEMO-0001' => ['sent',     +15, -3,  [[0, 20], [1, 10]]],
            'COT-DEMO-0002' => ['accepted', +5,  -12, [[2, 1]]],
            'COT-DEMO-0003' => ['rejected', +10, -20, [[3, 2], [0, 5]]],
            'COT-DEMO-0004' => ['expired',  -8,  -40, [[1, 15]]],
            'COT-DEMO-0005' => ['sent',     +3,  -1,  [[2, 1], [3, 4]]],
        ];

        Quote::withoutEvents(function () use ($plans, $products, $customer, $creator) {
            foreach ($plans as $number => [$status, $validDays, $sentDays, $lines]) {
                $subtotal = 0;
                $rows = [];
                foreach ($lines as $i => [$idx, $qty]) {
                    $product = $products[$idx % $products->count()];
                    $unit = (float) $product->price;
                    $lineTotal = round($unit * $qty, 2);
                    $subtotal += $lineTotal;
                    $rows[] = [
                        'product_id'       => $product->id,
                        'product_name'     => $product->name,
                        'product_sku'      => $product->sku,
                        'quantity'         => $qty,
                        'unit_price'       => $unit,
                        'discount_percent' => 0,
                        'tax_percent'      => 16,
                        'line_total'       => $lineTotal,
                        'sort_order'       => $i,
                    ];
                }
                $subtotal = round($subtotal, 2);
                $tax = round($subtotal * 0.16, 2);
                $sentAt = now()->addDays($sentDays);

                $quote = Quote::updateOrCreate(
                    ['quote_number' => $number],
                    [
                        'created_by_user_id' => $creator,
                        'customer_id'        => $customer->id,
                        'guest_name'         => trim($customer->first_name . ' ' . $customer->last_name),
                        'guest_email'        => $customer->email,
                        'guest_phone'        => $customer->phone,
                        'guest_company'      => $customer->company,
                        'guest_rfc'          => $customer->rfc,
                        'status'             => $status,
                        'currency'           => 'MXN',
                        'exchange_rate'      => 1,
                        'subtotal'           => $subtotal,
                        'discount_total'     => 0,
                        'tax_rate'           => 16,
                        'tax_total'          => $tax,
                        'total'              => round($subtotal + $tax, 2),
                        'valid_until'        => now()->addDays($validDays)->toDateString(),
                        'sent_at'            => $sentAt,
                        'accepted_at'        => $status === 'accepted' ? $sentAt->copy()->addDays(2) : null,
                        'rejected_at'        => $status === 'rejected' ? $sentAt->copy()->addDays(4) : null,
                        'notes'              => 'Cotización de muestra para pruebas del portal de clientes.',
                    ]
                );

                $quote->items()->delete();
                foreach ($rows as $row) {
                    $quote->items()->create($row);
                }
            }
        });
    }
}
