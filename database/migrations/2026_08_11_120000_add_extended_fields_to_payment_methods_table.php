<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extiende `payment_methods` de 3 a 6 tipos (transferencia, pasarela,
     * referencia, credito, digital, otro). Puramente aditiva: la tabla
     * tiene una FK viva entrante (`store_orders.payment_method_id` →
     * `payment_methods.id`, nullOnDelete), así que no se puede dropear ni
     * recrear — mismo criterio que
     * `2026_08_10_110000_create_payment_methods_table`.
     *
     * El tipo `efectivo_contra_entrega` desaparece de la lista válida del
     * controller; `referencia` lo reemplaza conceptualmente. El data-fix
     * de abajo remapea filas existentes antes de agregar columnas, para
     * que no queden con un `type` que ya no pasa validación al reeditarlas.
     */
    public function up(): void
    {
        DB::table('payment_methods')
            ->where('type', 'efectivo_contra_entrega')
            ->update(['type' => 'referencia']);

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->json('channels')->nullable()->after('details');
            $table->decimal('min_amount', 12, 2)->nullable()->after('channels');
            $table->decimal('max_amount', 12, 2)->nullable()->after('min_amount');
            $table->boolean('allows_invoicing')->default(false)->after('max_amount');
        });
    }

    /**
     * Best-effort: quita las columnas agregadas. No revierte el data-fix
     * de `type` (mismo criterio que otras migraciones del proyecto).
     */
    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn(['description', 'channels', 'min_amount', 'max_amount', 'allows_invoicing']);
        });
    }
};
