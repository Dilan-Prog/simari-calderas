<?php

namespace App\Actions;

use App\Models\StoreOrder;
use App\Models\StoreOrderStatusLog;
use App\Support\StoreOrderStatus;
use Illuminate\Support\Facades\DB;

/**
 * Avanza el estatus de un StoreOrder desde un flujo automático (no un form
 * de usuario) — por eso, a diferencia de StoreOrderController::updateStatus(),
 * una transición inválida es un no-op silencioso en vez de lanzar un error:
 * quien la invoca (p. ej. ShipmentController al registrar/entregar un envío)
 * no debe romperse si la orden ya avanzó por otro camino.
 */
class AdvanceStoreOrderStatus
{
    public function __invoke(StoreOrder $order, string $toStatus, string $note): bool
    {
        if (! in_array($toStatus, StoreOrderStatus::allowedTransitions($order->status), true)) {
            return false;
        }

        DB::transaction(function () use ($order, $toStatus, $note) {
            StoreOrderStatusLog::create([
                'store_order_id'     => $order->id,
                'from_status'        => $order->status,
                'to_status'          => $toStatus,
                'note'               => $note,
                'changed_by_user_id' => auth()->id(),
            ]);

            $order->update(['status' => $toStatus]);
        });

        return true;
    }
}
