<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Mismo motivo que la clave ecommerce.iva_rate: SettingController::update()
        // solo edita claves que ya existen, nunca las crea desde el formulario.
        DB::table('settings')->updateOrInsert(
            ['key' => 'ecommerce.usd_to_mxn_rate'],
            [
                'value'              => '17.50',
                'type'               => 'decimal',
                'group_name'         => 'ecommerce',
                'is_public'          => true,
                'updated_by_user_id' => null,
            ]
        );
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'ecommerce.usd_to_mxn_rate')->delete();
    }
};
