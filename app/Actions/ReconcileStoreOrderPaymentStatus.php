<?php

namespace App\Actions;

use App\Models\StoreOrder;

/**
 * Se invoca después de CUALQUIER cambio de estatus de un MercadoPagoPayment
 * (respuesta síncrona del Brick o webhook) para reflejar el efecto agregado
 * de los 1-2 cobros del pedido en el estatus del StoreOrder. Como
 * AdvanceStoreOrderStatus ya es un no-op silencioso ante una transición
 * inválida, esta Action es segura de invocar repetidamente (webhook
 * duplicado, carrera entre respuesta síncrona y webhook) sin duplicar
 * avances.
 */
class ReconcileStoreOrderPaymentStatus
{
    public function __invoke(StoreOrder $order): void
    {
        $payments = $order->payments;

        if ($payments->isEmpty()) {
            return;
        }

        if ($payments->every(fn ($payment) => $payment->status === 'approved')) {
            (new AdvanceStoreOrderStatus)($order, 'pagado', 'Todos los cobros de Mercado Pago fueron aprobados.');

            return;
        }

        $hasApproved = $payments->contains(fn ($payment) => $payment->status === 'approved');
        $hasFailed   = $payments->contains(fn ($payment) => in_array($payment->status, ['rejected', 'cancelled'], true));

        if ($hasApproved && $hasFailed) {
            (new AdvanceStoreOrderStatus)($order, 'pago_parcial', 'Uno de los cobros de Mercado Pago fue rechazado o cancelado; el otro ya está aprobado.');
        }
    }
}
