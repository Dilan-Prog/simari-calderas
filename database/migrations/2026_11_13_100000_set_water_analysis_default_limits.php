<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Límites (mín./máx.) de los parámetros del Reporte de Análisis Químico del portal de clientes
 * (pH y alcalinidad 10.5–11.5; dureza 80–120 ppm; conductividad 2,300–3,850 µS/cm; STD 1,500–2,500 ppm).
 * El personal puede seguir ajustándolos desde el panel; esta migración solo fija los valores iniciales.
 */
return new class extends Migration
{
    private const LIMITS = [
        'hardness'     => [80, 120],
        'ph'           => [10.5, 11.5],
        'alkalinity'   => [10.5, 11.5],
        'conductivity' => [2300, 3850],
        'tds'          => [1500, 2500],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('water_analysis_parameters')) {
            return;
        }

        foreach (self::LIMITS as $code => [$min, $max]) {
            DB::table('water_analysis_parameters')->where('code', $code)->update([
                'min_value' => $min,
                'max_value' => $max,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Sin reversa: los límites son datos que el personal edita.
    }
};
