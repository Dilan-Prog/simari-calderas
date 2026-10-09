<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Índices para el catálogo y el cálculo de ventas. Idempotente: no crea un
     * índice si ya existe uno cuyas primeras columnas son esas (las FK ya
     * generan índices implícitos en MySQL).
     */
    private array $indexes = [
        ['store_orders', ['status', 'created_at']],
        ['store_order_items', ['product_id']],
        ['store_order_items', ['store_order_id', 'product_id']],
        ['products', ['is_active', 'publish_on_website', 'category_id']],
        ['products', ['brand_id']],
    ];

    public function up(): void
    {
        foreach ($this->indexes as [$table, $columns]) {
            if (!Schema::hasTable($table) || $this->hasIndexOn($table, $columns)) {
                continue;
            }

            Schema::table($table, function ($t) use ($columns) {
                $t->index($columns, $this->indexName($columns));
            });
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as [$table, $columns]) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $name = $table . '_' . $this->indexName($columns);
            $exists = collect(DB::select("SHOW INDEX FROM `{$table}`"))->contains(fn ($i) => $i->Key_name === $this->indexName($columns));
            if ($exists) {
                Schema::table($table, fn ($t) => $t->dropIndex($this->indexName($columns)));
            }
        }
    }

    private function indexName(array $columns): string
    {
        return 'catalog_' . implode('_', $columns) . '_idx';
    }

    /** ¿Hay ya un índice cuyas primeras N columnas son exactamente $columns? */
    private function hasIndexOn(string $table, array $columns): bool
    {
        $byIndex = [];
        foreach (DB::select("SHOW INDEX FROM `{$table}`") as $row) {
            $byIndex[$row->Key_name][(int) $row->Seq_in_index] = $row->Column_name;
        }

        foreach ($byIndex as $cols) {
            ksort($cols);
            if (array_slice(array_values($cols), 0, count($columns)) === $columns) {
                return true;
            }
        }

        return false;
    }
};
