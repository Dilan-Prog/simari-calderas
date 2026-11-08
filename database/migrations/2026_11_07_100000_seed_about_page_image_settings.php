<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fotos de la página pública /nosotros: una clave por foto, vacía al inicio
 * (la página muestra un recuadro neutro mientras no haya URL). Se editan en
 * Configuración del Sitio > Página Nosotros (fotos). Mismo patrón EAV que
 * 2026_08_30_140002_seed_pool_calculator_settings.php.
 */
return new class extends Migration
{
    private const SLOTS = [
        'hero', 'hero_2', 'hero_3',
        'area_1', 'area_2', 'area_3',
        'metodo',
        'sector_1', 'sector_2', 'sector_3', 'sector_4',
    ];

    public function up(): void
    {
        foreach (self::SLOTS as $slot) {
            $key = 'about.image_' . $slot;

            // Si alguien ya capturó una URL, no se pisa.
            if (DB::table('settings')->where('key', $key)->exists()) {
                continue;
            }

            DB::table('settings')->insert([
                'key'                => $key,
                'value'              => '',
                'type'               => 'string',
                'group_name'         => 'about',
                'is_public'          => true,
                'updated_by_user_id' => null,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('group_name', 'about')->delete();
    }
};
