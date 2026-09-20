<?php

namespace App\Jobs;

use App\Actions\AdvanceMercadoPagoPaymentStatus;
use App\Actions\ReconcileStoreOrderPaymentStatus;
use App\Models\MercadoPagoPayment;
use App\Models\StoreOrder;
use App\Services\MercadoPago\MercadoPagoPaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Procesa una notificación webhook de Mercado Pago de forma asíncrona -- el
 * controller público solo verifica la firma y encola esto, para responder
 * 200 rápido y no arriesgar timeouts (mismo criterio que
 * ProcessWhatsappWebhookJob).
 *
 * Nunca confía en el status que venga en el payload del webhook: siempre
 * hace la llamada server-to-server real vía MercadoPagoPaymentService antes
 * de avanzar cualquier estatus local.
 */
class ProcessMercadoPagoWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  ?string  $mpPaymentId  El id del recurso que vino en el webhook
     *                                -- pese al nombre, no siempre es un id
     *                                de pago: si $resourceType es 'order' es
     *                                un id de orden (Orders API). El nombre
     *                                se conserva por compatibilidad (otro
     *                                código/tests pueden referenciarlo).
     * @param  string  $resourceType  'payment' (webhook clásico de Pagos) u
     *                                'order' (webhook de Orders,
     *                                checkout_pro). Default 'payment' para
     *                                jobs ya encolados antes de este cambio.
     */
    public function __construct(
        private readonly ?string $mpPaymentId,
        private readonly array $payload,
        private readonly string $resourceType = 'payment',
    ) {}

    public function handle(): void
    {
        $mpPaymentId = $this->mpPaymentId ?? ($this->payload['data']['id'] ?? null);

        if (blank($mpPaymentId)) {
            Log::warning('ProcessMercadoPagoWebhookJob: no se pudo resolver mp_payment_id del payload.', [
                'payload' => $this->payload,
            ]);

            return;
        }

        // No se sabe de antemano bajo qué rol (Tarjeta/api o Checkout Pro)
        // se creó este pago/orden -- el payload del webhook no indica la
        // Aplicación de origen. Se prueba cada rol configurado hasta que uno
        // tenga acceso. $resourceType distingue el webhook clásico de Pagos
        // (type=payment, data.id=id de pago) del nuevo de Orders (type=order,
        // data.id=id de orden) -- cada uno se resuelve contra el endpoint que
        // corresponde a su propio recurso.
        $result = $this->resourceType === 'order'
            ? MercadoPagoPaymentService::fetchOrderTryingAllRoles((string) $mpPaymentId)
            : MercadoPagoPaymentService::fetchPaymentTryingAllRoles((string) $mpPaymentId);

        $payment = MercadoPagoPayment::where('mp_payment_id', $mpPaymentId)->first();

        if (!$payment) {
            $payment = $this->findByExternalReference($result['raw']['external_reference'] ?? null);
        }

        if (!$payment) {
            Log::warning('ProcessMercadoPagoWebhookJob: no existe MercadoPagoPayment local para esta notificación.', [
                'mp_payment_id' => $mpPaymentId,
                'external_reference' => $result['raw']['external_reference'] ?? null,
            ]);

            return;
        }

        (new AdvanceMercadoPagoPaymentStatus)(
            $payment,
            $result['status'],
            'Notificación webhook de Mercado Pago, confirmada vía API.',
            'webhook',
            $result['status_detail'] ?? null,
        );

        (new ReconcileStoreOrderPaymentStatus)($payment->storeOrder);
    }

    /**
     * Fallback cuando no existe fila por mp_payment_id todavía (carrera con
     * la respuesta síncrona del checkout) -- external_reference tiene el
     * formato "{order_number}_{charge_group}" (separador "_", no ":" -- la
     * API de Orders del rol checkout_pro rechaza external_reference con ":",
     * confirmado en sandbox real).
     */
    private function findByExternalReference(?string $externalReference): ?MercadoPagoPayment
    {
        if (blank($externalReference) || !str_contains($externalReference, '_')) {
            return null;
        }

        [$orderNumber, $chargeGroup] = explode('_', $externalReference, 2);

        $order = StoreOrder::where('order_number', $orderNumber)->first();

        if (!$order) {
            return null;
        }

        return $order->payments->firstWhere('charge_group', (int) $chargeGroup);
    }
}
