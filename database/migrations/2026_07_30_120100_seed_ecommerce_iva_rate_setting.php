<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SettingController::update() solo edita claves que ya existen en la
        // tabla — nunca las crea desde el formulario — así que esta clave
        // tiene que sembrarse por migración para que la sección "Ecommerce"
        // de Configuración del Sitio pueda editarla.
        DB::table('settings')->updateOrInsert(
            ['key' => 'ecommerce.iva_rate'],
            [
                'value'              => '16',
                'type'               => 'decimal',
                'group_name'         => 'ecommerce',
                'is_public'          => true,
                'updated_by_user_id' => null,
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')->where('key', 'ecommerce.iva_rate')->delete();
    }
};
