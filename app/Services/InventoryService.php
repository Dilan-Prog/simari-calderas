<?php

namespace App\Services;

use App\Models\InventoryLog;
use App\Models\InventoryMovement;
use App\Models\Products;
use App\Models\Setting;
use App\Models\Warehouse;
use App\Models\WarehouseProductStock;
use Illuminate\Support\Facades\DB;

/**
 * Único punto del sistema que debe tocar `products.stock` de aquí en
 * adelante (aparte del bulk-edit ya existente de Productos, que sigue
 * modificando `products.stock` directamente y queda fuera de este alcance).
 * Toda entrada/salida/ajuste de inventario pasa por applyMovement(), que dej
 * un rastro completo: InventoryMovement (el hecho), InventoryLog (quién/por
 * qué) y WarehouseProductStock (el saldo acumulado por almacén).
 */
class InventoryService
{
    /**
     * Registra un movimiento de inventario y aplica su efecto al saldo del
     * almacén y al stock global del producto, todo en una transacción.
     *
     * Convención de signo para $delta (el cambio real aplicado al saldo):
     * - 'entrada': +$quantity (siempre suma, $quantity debe venir positivo).
     * - 'salida': -$quantity (siempre resta, $quantity debe venir positivo).
     * - 'ajuste': el llamador decide el sentido. Aquí $quantity se interpreta
     *   como una magnitud sin signo (siempre positiva, ya validada aguas
     *   arriba) y quien invoca applyMovement() para un ajuste negativo debe
     *   pasar $quantity ya en valor absoluto y confiar en que el propio
     *   registro (notes/reference) documenta la dirección — en la práctica,
     *   dentro de este módulo, bulkEditSave() en InventoryController es quien
     *   decide el signo antes de llamar aquí, pasando siempre abs($delta) y
     *   registrando dos tipos de ajuste posibles solo a través de quantity
     *   positiva/negativa cuando el caller la pasa directamente en negativo.
     *   Para mantener esto inequívoco, applyMovement() acepta $quantity con
     *   signo para 'ajuste' (positivo = sube el saldo, negativo = lo baja) y
     *   siempre exige $quantity > 0 para 'entrada'/'salida'.
     */
    public function applyMovement(
        int $warehouseId,
        int $productId,
        string $type,
        int $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null
    ): InventoryMovement {
        if (!in_array($type, ['entrada', 'salida', 'ajuste'], true)) {
            throw new \InvalidArgumentException("Tipo de movimiento no soportado: {$type}.");
        }

        return DB::transaction(function () use ($warehouseId, $productId, $type, $quantity, $referenceType, $referenceId, $notes) {
            $delta = match ($type) {
                'entrada' => abs($quantity),
                'salida' => -abs($quantity),
                // 'ajuste': el signo de $quantity manda tal cual (puede subir o bajar el saldo).
                'ajuste' => $quantity,
            };

            $movement = new InventoryMovement([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'movement_type' => $type,
                // La columna es un entero simple (sin signo garantizado por
                // el esquema, pero MySQL int sí admite negativos) — se
                // guarda tal cual la magnitud pedida por el caller, el signo
                // real que se aplicó al saldo vive en $delta.
                'quantity' => $quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
            ]);
            $movement->created_at = now();
            $movement->save();

            InventoryLog::create([
                'inventory_movement_id' => $movement->id,
                'action' => 'created',
                'reason' => $notes,
                'performed_by_user_id' => auth()->id(),
                'created_at' => now(),
            ]);

            $stock = WarehouseProductStock::firstOrCreate(
                ['warehouse_id' => $warehouseId, 'product_id' => $productId],
                ['quantity' => 0]
            );
            $stock->increment('quantity', $delta);

            if ($delta >= 0) {
                Products::where('id', $productId)->increment('stock', $delta);
            } else {
                Products::where('id', $productId)->decrement('stock', abs($delta));
            }

            return $movement;
        });
    }

    /**
     * Almacén por defecto para operaciones que no especifican uno
     * explícitamente (ej. edición masiva sin almacén seleccionado). Lee el
     * setting 'inventory.default_warehouse_id' (sembrado por la migración de
     * datos 2026_08_10_090200) con fallback al primer almacén activo.
     */
    public function defaultWarehouseId(): int
    {
        $configured = Setting::get('inventory.default_warehouse_id');

        if ($configured && Warehouse::where('id', $configured)->exists()) {
            return (int) $configured;
        }

        $fallback = Warehouse::where('is_active', true)->orderBy('id')->value('id');

        if (!$fallback) {
            throw new \RuntimeException('No hay ningún almacén activo configurado en el sistema.');
        }

        return (int) $fallback;
    }
}
