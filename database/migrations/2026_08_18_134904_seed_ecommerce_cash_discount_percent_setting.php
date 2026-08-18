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
        // tabla -- nunca las crea desde el formulario -- así que esta clave
        // tiene que sembrarse por migración para que la sección "Ecommerce"
        // de Configuración del Sitio pueda editarla. Default 0 = "sin
        // descuento configurado" (el panel de Medios de Pago del
        // product-detail se degrada correctamente en ese caso).
        DB::table('settings')->updateOrInsert(
            ['key' => 'ecommerce.cash_discount_percent'],
            [
                'value'              => '0',
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
        DB::table('settings')->where('key', 'ecommerce.cash_discount_percent')->delete();
    }
};
