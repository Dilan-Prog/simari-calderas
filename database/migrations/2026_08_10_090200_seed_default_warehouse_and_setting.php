<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Migración de datos (no de esquema) — mismo patrón que
    // 2026_08_03_100000_seed_ecommerce_exchange_rate_setting.php: garantiza
    // que exista al menos un almacén y que la clave de configuración del
    // almacén por defecto quede sembrada, sin la cual
    // InventoryService::defaultWarehouseId() no tendría a qué caer.
    public function up(): void
    {
        $warehouseId = DB::table('warehouses')->min('id');

        if (!$warehouseId) {
            $warehouseId = DB::table('warehouses')->insertGetId([
                'name' => 'Almacén Principal',
                'location' => null,
                'is_active' => true,
                'created_at' => now(),
            ]);
        }

        // Mismo motivo que ecommerce.usd_to_mxn_rate: SettingController::update()
        // solo edita claves que ya existen, nunca las crea desde el formulario.
        DB::table('settings')->updateOrInsert(
            ['key' => 'inventory.default_warehouse_id'],
            [
                'value'              => (string) $warehouseId,
                'type'               => 'integer',
                'group_name'         => 'inventory',
                'is_public'          => false,
                'updated_by_user_id' => null,
            ]
        );
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'inventory.default_warehouse_id')->delete();
        // No se revierte el almacén insertado: para entonces puede ya tener
        // movimientos/stock asociados (FK restrict), igual que otras
        // migraciones de seed del proyecto no revierten datos de negocio.
    }
};
