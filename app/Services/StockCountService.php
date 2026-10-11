<?php

namespace App\Services;

use App\Models\Products;
use App\Models\StockCount;
use App\Models\StockCountLine;
use App\Models\WarehouseProductStock;
use Illuminate\Support\Facades\DB;

/**
 * Conteo físico: snapshot del saldo del sistema, captura de cantidades,
 * cierre y generación de ajustes (movimientos `ajuste` con signo vía
 * InventoryService) solo para diferencias distintas de cero.
 *
 * Los ajustes aplican la diferencia contra el snapshot (contado - sistema al
 * abrir el conteo): se asume que el almacén no se movió durante el conteo.
 */
class StockCountService
{
    public function __construct(private InventoryService $inventory)
    {
    }

    /**
     * @param string $mode  'all' (productos con saldo en el almacén), 'products' o 'category'
     * @param array  $productIds  para mode=products
     */
    public function create(int $warehouseId, string $mode, array $productIds, ?int $categoryId, ?string $notes, ?int $userId): StockCount
    {
        $balances = WarehouseProductStock::where('warehouse_id', $warehouseId)->pluck('quantity', 'product_id');

        $ids = match ($mode) {
            'products' => array_values(array_unique(array_map('intval', $productIds))),
            'category' => Products::where('category_id', $categoryId)->pluck('id')->all(),
            default => $balances->filter(fn ($q) => $q != 0)->keys()->all(),
        };

        $ids = Products::whereIn('id', $ids)->pluck('id')->all();
        if (!$ids) {
            throw new \DomainException('No hay productos para contar con ese criterio.');
        }

        return DB::transaction(function () use ($warehouseId, $ids, $balances, $notes, $userId) {
            $count = StockCount::create([
                'number' => StockCount::generateNumber(),
                'warehouse_id' => $warehouseId,
                'status' => 'abierto',
                'started_at' => now(),
                'created_by_user_id' => $userId,
                'notes' => $notes,
            ]);

            $rows = [];
            foreach ($ids as $pid) {
                $rows[] = ['product_id' => $pid, 'system_quantity' => (int) ($balances[$pid] ?? 0)];
            }
            $count->lines()->createMany($rows);

            return $count;
        });
    }

    public function saveLine(StockCount $count, int $lineId, ?int $counted): StockCountLine
    {
        return DB::transaction(function () use ($count, $lineId, $counted) {
            $count = StockCount::lockForUpdate()->findOrFail($count->id);
            if (!in_array($count->status, ['abierto', 'en_conteo'], true)) {
                throw new \DomainException('El conteo ya está cerrado.');
            }
            if ($counted !== null && $counted < 0) {
                throw new \DomainException('La cantidad contada no puede ser negativa.');
            }

            $line = $count->lines()->findOrFail($lineId);
            $line->update([
                'counted_quantity' => $counted,
                'difference' => $counted === null ? null : $counted - $line->system_quantity,
            ]);

            if ($count->status === 'abierto') {
                $count->update(['status' => 'en_conteo']);
            }

            return $line;
        });
    }

    /** Cierra el conteo (ya no se captura). Las líneas sin contar no generan ajuste. */
    public function close(StockCount $count, ?int $userId): StockCount
    {
        return DB::transaction(function () use ($count, $userId) {
            $count = StockCount::lockForUpdate()->findOrFail($count->id);
            if (in_array($count->status, ['cerrado', 'ajustado'], true)) {
                return $count;
            }
            if (!$count->lines()->whereNotNull('counted_quantity')->exists()) {
                throw new \DomainException('Captura al menos una cantidad antes de cerrar el conteo.');
            }
            $count->update(['status' => 'cerrado', 'closed_at' => now(), 'closed_by_user_id' => $userId]);

            return $count;
        });
    }

    /** Genera los ajustes de las diferencias distintas de cero. Idempotente: un conteo ajustado no vuelve a ajustar. */
    public function applyAdjustments(StockCount $count): StockCount
    {
        return DB::transaction(function () use ($count) {
            $count = StockCount::lockForUpdate()->findOrFail($count->id);
            if ($count->status === 'ajustado') {
                return $count;
            }
            if ($count->status !== 'cerrado') {
                throw new \DomainException('Cierra el conteo antes de generar ajustes.');
            }

            foreach ($count->lines()->whereNotNull('counted_quantity')->where('difference', '!=', 0)->get() as $line) {
                $this->inventory->applyMovement(
                    $count->warehouse_id, $line->product_id, 'ajuste', (int) $line->difference,
                    StockCount::class, $count->id,
                    "Conteo físico {$count->number}: sistema {$line->system_quantity}, contado {$line->counted_quantity}"
                );
            }

            $count->update(['status' => 'ajustado', 'adjusted_at' => now()]);

            return $count;
        });
    }

    /** Reporte de diferencias valorizadas a costo. */
    public function report(StockCount $count): array
    {
        $lines = $count->lines()->with('product:id,name,sku,cost')->get()->map(function ($l) {
            $cost = (float) ($l->product->cost ?? 0);

            return [
                'line' => $l,
                'cost' => $cost,
                'value' => $l->difference === null ? 0.0 : round($l->difference * $cost, 2),
            ];
        });

        $diffs = $lines->filter(fn ($r) => $r['line']->difference !== null && $r['line']->difference != 0);

        return [
            'rows' => $diffs->values(),
            'total_positive' => round($diffs->where('value', '>', 0)->sum('value'), 2),
            'total_negative' => round($diffs->where('value', '<', 0)->sum('value'), 2),
            'total_net' => round($diffs->sum('value'), 2),
            'counted' => $lines->filter(fn ($r) => $r['line']->counted_quantity !== null)->count(),
            'total' => $lines->count(),
        ];
    }
}
