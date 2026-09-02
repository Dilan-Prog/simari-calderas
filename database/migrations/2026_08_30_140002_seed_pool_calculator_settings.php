<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Config admin de la calculadora de bombas de calor para alberca — mismo
 * patrón EAV ya usado por ecommerce.iva_rate/cookie_banner.* (ver
 * 2026_07_30_120100_seed_ecommerce_iva_rate_setting.php). Valores default
 * tomados del roadmap del usuario; deben calibrarse/verificarse antes de
 * publicar (tarifa DAC real, T ambiente de diseño con datos del SMN).
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            [
                'key'   => 'pool_calculator.tarifa_kwh',
                'value' => '5.50',
                'type'  => 'decimal',
            ],
            [
                'key'   => 'pool_calculator.cop_nominal',
                'value' => '5.5',
                'type'  => 'decimal',
            ],
            [
                'key'   => 'pool_calculator.horas_operacion_dia',
                'value' => '10',
                'type'  => 'decimal',
            ],
            [
                'key'   => 'pool_calculator.ciudades_temp_ambiente',
                'value' => json_encode([
                    'CDMX' => 9, 'Toluca' => 6, 'Puebla' => 8, 'Queretaro' => 11,
                    'Guadalajara' => 12, 'Leon' => 10, 'Aguascalientes' => 10,
                    'Monterrey' => 13, 'Cuernavaca' => 16, 'Merida' => 21,
                    'Cancun' => 22, 'Vallarta' => 21, 'Tijuana' => 11, 'Otra' => 12,
                ]),
                'type'  => 'json',
            ],
        ];

        foreach ($rows as $row) {
            DB::table('settings')->updateOrInsert(
                ['key' => $row['key']],
                [
                    'value'              => $row['value'],
                    'type'               => $row['type'],
                    'group_name'         => 'pool_calculator',
                    'is_public'          => true,
                    'updated_by_user_id' => null,
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('group_name', 'pool_calculator')->delete();
    }
};
