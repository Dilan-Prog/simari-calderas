<?php

namespace App\Actions;

use App\Models\MercadoPagoPayment;
use App\Models\MercadoPagoPaymentStatusLog;
use App\Support\MercadoPagoPaymentStatus;
use Illuminate\Support\Facades\DB;

/**
 * Avanza el estatus de un MercadoPagoPayment desde un flujo automático (no
 * un form de usuario) — por eso, a diferencia de un cambio manual, una
 * transición inválida es un no-op silencioso en vez de lanzar un error:
 * quien la invoca (respuesta síncrona del Brick o el webhook) no debe
 * romperse si el pago ya avanzó por otro camino.
 */
class AdvanceMercadoPagoPaymentStatus
{
    public function __invoke(MercadoPagoPayment $payment, string $toStatus, string $note, string $source, ?string $statusDetail = null): bool
    {
        if (! in_array($toStatus, MercadoPagoPaymentStatus::allowedTransitions($payment->status), true)) {
            return false;
        }

        DB::transaction(function () use ($payment, $toStatus, $note, $source, $statusDetail) {
            MercadoPagoPaymentStatusLog::create([
                'mercado_pago_payment_id' => $payment->id,
                'from_status'             => $payment->status,
                'to_status'               => $toStatus,
                'note'                    => $note,
                'source'                  => $source,
                'changed_by_user_id'      => auth()->id(),
            ]);

            $payment->status = $toStatus;

            if ($statusDetail !== null) {
                $payment->status_detail = $statusDetail;
            }

            if ($toStatus === 'approved' && $payment->paid_at === null) {
                $payment->paid_at = now();
            }

            $payment->save();
        });

        return true;
    }
}
