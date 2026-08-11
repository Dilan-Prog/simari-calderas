<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\PurchaseOrder;

/**
 * Conecta el flujo de Órdenes de Compra con el módulo de Inventario: cuando
 * una orden queda en estatus 'accepted', sus líneas con producto de catálogo
 * generan movimientos de entrada vía InventoryService. No duplica lógica de
 * saldo/stock — solo orquesta la llamada, todo el efecto real vive en
 * InventoryService::applyMovement().
 */
class PurchaseOrderService
{
    /**
     * Aplica la recepción de stock de una Orden de Compra aceptada: una
     * entrada de inventario por cada línea con product_id. Idempotente — si
     * ya existen movimientos referenciando esta orden, no hace nada (evita
     * duplicar el incremento si el estatus se vuelve a guardar en
     * 'accepted' más de una vez).
     */
    public function applyStockReceipt(PurchaseOrder $order): void
    {
        if ($order->status !== 'accepted') {
            return;
        }

        $alreadyApplied = InventoryMovement::where('reference_type', PurchaseOrder::class)
            ->where('reference_id', $order->id)
            ->exists();

        if ($alreadyApplied) {
            return;
        }

        $inventoryService = app(InventoryService::class);
        $warehouseId = $inventoryService->defaultWarehouseId();

        foreach ($order->items as $item) {
            if (!$item->product_id) {
                continue;
            }

            $inventoryService->applyMovement(
                $warehouseId,
                $item->product_id,
                'entrada',
                $item->quantity,
                PurchaseOrder::class,
                $order->id,
                "Recepción OC {$order->po_number}"
            );
        }
    }
}
