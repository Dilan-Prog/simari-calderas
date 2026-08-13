<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // La tabla `shipments` ya existe: es la tabla huérfana del legado
        // 2026_04_19_* (orders/orders_items/orders_status_history/shipments/
        // shipments_items) — sin modelo, controller ni ruta que la use, y
        // vacía en producción. Se libera el nombre para el módulo nuevo de
        // Envíos moviendo las tablas legado a `legacy_*`, sin perder datos
        // (no hay ninguno) ni tocar el resto del subsistema huérfano.
        if (Schema::hasTable('shipments') && ! Schema::hasColumn('shipments', 'store_order_id')) {
            if (Schema::hasTable('shipments_items')) {
                Schema::rename('shipments_items', 'legacy_shipments_items');
            }
            Schema::rename('shipments', 'legacy_shipments');
        }

        // Renombrar una tabla en MySQL no renombra sus constraints de FK, así
        // que `legacy_shipments`/`legacy_shipments_items` siguen cargando los
        // nombres originales `shipments_*_foreign` — chocarían con las FKs de
        // la tabla `shipments` nueva. Se eliminan (tabla huérfana sin
        // integridad referencial que preservar) en vez de renombrarlas, ya
        // que MySQL no soporta renombrar constraints FOREIGN KEY in-place.
        $this->dropForeignIfExists('legacy_shipments', 'shipments_carrier_id_foreign');
        $this->dropForeignIfExists('legacy_shipments', 'shipments_order_id_foreign');
        $this->dropForeignIfExists('legacy_shipments_items', 'shipments_items_shipment_id_foreign');
        $this->dropForeignIfExists('legacy_shipments_items', 'shipments_items_order_item_id_foreign');

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_order_id')->unique()->constrained('store_orders')->cascadeOnDelete();
            $table->foreignId('carrier_id')->nullable()->constrained('carriers')->restrictOnDelete();
            $table->string('guia', 80);
            $table->string('status', 20)->default('preparando');
            $table->date('fecha_envio')->nullable();
            $table->date('eta')->nullable();
            $table->unsignedSmallInteger('bultos')->default(1);
            $table->decimal('peso', 8, 2)->nullable();
            $table->decimal('costo', 10, 2)->nullable();
            $table->string('motivo', 20)->nullable();
            $table->text('incidencia_note')->nullable();
            $table->foreignId('authorized_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->timestamps();
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');

        if (Schema::hasTable('legacy_shipments')) {
            Schema::rename('legacy_shipments', 'shipments');
        }
        if (Schema::hasTable('legacy_shipments_items')) {
            Schema::rename('legacy_shipments_items', 'shipments_items');
        }
    }

    private function dropForeignIfExists(string $table, string $constraint): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        if ($exists) {
            Schema::table($table, fn (Blueprint $t) => $t->dropForeign($constraint));
        }
    }
};
