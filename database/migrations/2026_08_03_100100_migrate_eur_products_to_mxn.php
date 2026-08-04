<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * EUR se quita del catálogo de monedas soportadas (nunca tuvo tipo de
     * cambio real, y la conversión nueva es específicamente USD↔MXN) —
     * cualquier producto ya marcado EUR se reclasifica a MXN, el default
     * histórico, para no dejar un valor de enum que la validación ya no acepta.
     */
    public function up(): void
    {
        DB::table('products')->where('currency', 'EUR')->update(['currency' => 'MXN']);
    }

    public function down(): void
    {
        // Irreversible a propósito: no hay forma de saber cuáles eran EUR originalmente.
    }
};
