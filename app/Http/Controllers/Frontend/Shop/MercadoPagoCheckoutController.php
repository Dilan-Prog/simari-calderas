<?php

namespace App\Http\Controllers\Frontend\Shop;

use App\Actions\AdvanceMercadoPagoPaymentStatus;
use App\Actions\ReconcileStoreOrderPaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\MercadoPagoPayment;
use App\Models\StoreOrder;
use App\Services\MercadoPago\MercadoPagoPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

/**
 * Flujo de pago público de Mercado Pago (Payment Brick), consumido después
 * de CheckoutController::confirm() para pedidos cuyo método de pago es un
 * procesador 'mercadopago'. Cada StoreOrder puede tener 1 o 2 filas en
 * mercado_pago_payments (split MSI / no-MSI, ver
 * CheckoutController::createMercadoPagoPaymentSlots()) -- este controller
 * solo cobra cada fila, nunca decide cómo se agruparon.
 */
class MercadoPagoCheckoutController extends Controller
{
    /**
     * Muestra el/los Payment Brick(s) pendientes de un pedido. Guardado por
     * sesión (checkout.mp_order_id, fijado en CheckoutController::confirm()
     * justo antes del redirect a esta ruta) para que adivinar un
     * order_number ajeno en la URL no exponga el pedido de otro cliente.
     */
    public function show(StoreOrder $order)
    {
        abort_unless(session('checkout.mp_order_id') === $order->id, 404);

        $payments = $order->payments()->orderBy('charge_group')->get();

        return view('frontend.shop.checkout.pay-mercadopago', [
            'order'     => $order,
            'payments'  => $payments,
            // Página de reintento de Tarjeta (CardForm) -- siempre rol 'api'.
            'publicKey' => MercadoPagoPaymentService::publicKeyFor('api'),
            'retryOnly' => null,
        ]);
    }

    /**
     * Cobra UN slot de pago (charge_group) con el token que ya generó el
     * SDK JS del Brick client-side. Nunca recibe ni ve número de
     * tarjeta/CVV/expiración -- solo el token tokenizado.
     */
    public function charge(Request $request, StoreOrder $order, MercadoPagoPayment $payment)
    {
        abort_unless($payment->store_order_id === $order->id, 404);

        // Este endpoint lo consumen 2 páginas con modelos de acceso
        // distintos: show() (checkout normal, exige la sesión de
        // checkout.mp_order_id) y retry() (link FIRMADO, pensado para
        // funcionar sin esa sesión -- ej. el cliente vuelve días después
        // desde un correo). Por eso este guard de sesión solo aplica a un
        // cobro "pending" (primer intento, solo alcanzable en la práctica
        // vía show(), que ya exige la sesión) -- uno rejected/cancelled es
        // justo el caso que retry() habilita legítimamente sin sesión.
        // Antes no había NINGÚN guard aquí, ni para el caso "pending": con
        // order_number secuencial + payment.id autoincremental, cualquiera
        // podía adivinar el par de un pedido ajeno recién creado e
        // intentar cobrarlo con su propia tarjeta.
        if ($payment->status === 'pending') {
            abort_unless(session('checkout.mp_order_id') === $order->id, 404);
        }

        if ($payment->status === 'approved') {
            return response()->json([
                'status' => 'error',
                'message' => 'Este cobro ya fue aprobado.',
            ], 422);
        }

        // 'token' es nullable a propósito: solo lo genera el flujo de
        // tarjeta (crédito/débito). Wallet Purchase (saldo/tarjetas guardadas
        // en la cuenta del cliente) y Mercado Crédito ("Meses sin Tarjeta de
        // Mercado Pago") no tokenizan una tarjeta -- MercadoPagoPaymentService
        // solo asigna $payment->token cuando viene presente.
        $data = $request->validate([
            'token'              => ['nullable', 'string'],
            'payment_method_id'  => ['required', 'string'],
            'installments'       => ['required', 'integer', 'min:1'],
            'payer'              => ['required', 'array'],
            'payer.email'        => ['required', 'email'],
            // Opcional a propósito: México no exige identification en pagos
            // con tarjeta (a diferencia de AR/BR) -- ver comentario en
            // MercadoPagoPaymentService::createPayment().
            'payer.identification'         => ['nullable', 'array'],
            'payer.identification.type'    => ['required_with:payer.identification', 'string', 'max:10'],
            'payer.identification.number'  => ['required_with:payer.identification', 'string', 'max:30'],
        ]);

        // Defensa en profundidad: un cobro sin MSI SIEMPRE va a 1 exhibición
        // en la API de MP, sin importar qué installments haya mandado el
        // cliente (el Brick no debería ofrecer MSI ahí, pero nunca se
        // confía en el request para esto).
        $installments = $payment->includes_msi ? (int) $data['installments'] : 1;

        $idempotencyKey = (string) Str::uuid();
        $payment->update([
            'attempts'        => $payment->attempts + 1,
            'idempotency_key' => $idempotencyKey,
        ]);

        try {
            $result = (new MercadoPagoPaymentService('api'))->createPayment([
                'token'               => $data['token'] ?? null,
                'transaction_amount'  => (float) $payment->amount,
                'installments'        => $installments,
                'payment_method_id'   => $data['payment_method_id'],
                'payer'               => [
                    'email'          => $data['payer']['email'],
                    'identification' => $data['payer']['identification'] ?? null,
                ],
                // Separador "_" a propósito -- order_number ya trae guiones
                // (PW-YYYY-XXXX), así que ProcessMercadoPagoWebhookJob::
                // findByExternalReference() parte por "_" para no ambigüar
                // con los guiones propios del folio. No se usa ":" porque la
                // API de Orders (rol checkout_pro) rechaza external_reference
                // con ":" (confirmado en sandbox real: "does not match
                // pattern") -- se usa el mismo separador en ambos roles para
                // no tener 2 formatos distintos.
                'external_reference'  => $order->order_number . '_' . $payment->charge_group,
                'description'         => 'Pedido ' . $order->order_number . ' (' . ($payment->includes_msi ? 'MSI' : 'contado') . ')',
            ], $idempotencyKey);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status'        => 'error',
                'status_detail' => 'No se pudo procesar el pago, intenta con otra tarjeta.',
            ], 502);
        }

        $payment->forceFill([
            'mp_payment_id'      => $result['id'],
            'mp_preference_id'   => $result['raw']['preference_id'] ?? null,
            'mp_payment_method'  => $result['payment_method_id'],
            'mp_payment_type'    => $result['payment_type_id'],
            'raw_last_response'  => $result['raw'],
        ])->save();

        (new AdvanceMercadoPagoPaymentStatus())(
            $payment,
            (string) $result['status'],
            'Respuesta síncrona de la API de Mercado Pago.',
            'api_confirm',
            $result['status_detail'] ?? null
        );

        (new ReconcileStoreOrderPaymentStatus())($order);

        $response = [
            'status'        => $result['status'],
            'status_detail' => $result['status_detail'],
        ];

        $allApproved = $order->payments()->where('status', '!=', 'approved')->doesntExist();

        // in_process/authorized/pending (ej. la tarjeta de prueba "CONT" en
        // sandbox, o una revisión manual real) NO es un rechazo -- es un
        // resultado válido en el que ya se cobró/intentó el único cobro del
        // pedido y no queda nada más que hacer aquí, mismo criterio que
        // Transferencia SPEI (pedido registrado, pago aún no confirmado).
        // Split MSI (2 cobros) se deja fuera a propósito: el 2º cobro sigue
        // bloqueado hasta que el 1º quede aprobado (ver mountCardFormsInto()
        // en mercadopago-checkout.js) -- mandar al cliente a "confirmado"
        // antes de eso lo dejaría sin poder completar ese 2º cobro nunca.
        $isSinglePaymentPending = $order->payments()->count() === 1
            && in_array($result['status'], ['in_process', 'authorized', 'pending'], true);

        if ($allApproved || $isSinglePaymentPending) {
            $response['redirect'] = route('checkout.payment.mercadopago.thanks', $order->order_number);
        }

        return response()->json($response);
    }

    /**
     * Misma vista de confirmación que usan hoy el resto de métodos de pago
     * (CheckoutController::confirm() la renderiza directo por POST). Dos
     * caminos llegan aquí por GET:
     *  1. El Brick embebido, solo cuando TODOS los cobros ya quedaron
     *     aprobados (charge() solo manda 'redirect' en ese caso) -- sin
     *     query params, nada que reconciliar.
     *  2. El back_url de Checkout Pro (checkoutPro() abajo), que redirige
     *     aquí SIEMPRE (éxito, pendiente o fallo) con ?payment_id=... --
     *     aquí sí hay que confirmar el estatus real contra la API antes de
     *     mostrar nada (nunca confiar en el query string ni en el ?status=
     *     que manda Mercado Pago, mismo criterio que el webhook).
     *
     * Nunca se le dice "confirmado" a un pedido cuyo pago realmente falló --
     * antes se mostraba "¡Pedido confirmado!" con una alerta de error
     * encima, contradictorio y confuso. Ahora un fallo real (rechazado/
     * cancelado, o si ni siquiera se pudo confirmar el estatus) nunca
     * renderiza esta vista: se responde JSON con `failed:true` cuando el
     * popup de Checkout Pro pregunta en segundo plano (ver
     * openCheckoutProPopup() en checkout-payment.js, que entonces NUNCA
     * navega y solo muestra un modal de reintento sobre la misma página de
     * checkout), o se redirige de vuelta a esa misma página (respaldo para
     * cuando el navegador bloqueó el popup y hubo que navegar la pestaña
     * completa). in_process/authorized/pending SÍ cuentan como
     * "confirmado" -- son el resultado normal de métodos asíncronos de
     * Checkout Pro (OXXO, SPEI, Mercado Crédito), mismo criterio que ya se
     * usa para Transferencia SPEI clásica.
     */
    public function thanks(Request $request, StoreOrder $order)
    {
        // Mercado Pago manda literalmente "null" (string, no ausente) en
        // estos query params cuando el cliente cancela Checkout Pro sin
        // llegar a intentar un pago -- confirmado probando el link "Volver a
        // la tienda" en vivo. !empty()/?? no lo detectan porque "null" no
        // es un string vacío; hay que descartarlo a mano o se dispara un
        // fetchPayment("null") que siempre falla y confunde al cliente con
        // un mensaje de error que no aplica.
        $mpPaymentId = collect([$request->query('payment_id'), $request->query('collection_id')])
            ->first(fn ($value) => filled($value) && $value !== 'null');
        $failed = false;
        $verified = false; // ¿de verdad se reconcilió algo contra la API en esta visita?

        if ($mpPaymentId) {
            $payment = $order->payments()->where('status', '!=', 'approved')->first();

            if ($payment) {
                try {
                    // No se sabe de antemano bajo qué rol (Tarjeta/api o
                    // Checkout Pro) se creó este pago -- el query string de
                    // retorno de MP no lo indica. Se prueba cada rol
                    // configurado hasta que uno tenga acceso.
                    $result = MercadoPagoPaymentService::fetchPaymentTryingAllRoles((string) $mpPaymentId);

                    $payment->forceFill([
                        'mp_payment_id'     => $result['id'],
                        'mp_payment_method' => $result['payment_method_id'],
                        'mp_payment_type'   => $result['payment_type_id'],
                        'raw_last_response' => $result['raw'],
                    ])->save();

                    (new AdvanceMercadoPagoPaymentStatus())(
                        $payment,
                        (string) $result['status'],
                        'Retorno de Checkout Pro (confirmado vía API, nunca por el query string de la URL de retorno).',
                        'api_confirm',
                        $result['status_detail'] ?? null
                    );

                    (new ReconcileStoreOrderPaymentStatus())($order);

                    $failed = in_array($result['status'], ['rejected', 'cancelled'], true);
                    $verified = true;
                } catch (Throwable $e) {
                    report($e);
                    $failed = true;
                    $verified = true;
                }
            }
        } else {
            // Checkout Pro basado en la nueva Orders API regresa ?order_id=...
            // en vez de ?payment_id=/?collection_id= cuando el pago se hizo
            // por ahí (Wallet/Efectivo/Transferencia/Mercado Crédito) --
            // mismo cuidado con el "null" literal que arriba.
            $mpOrderId = $request->query('order_id');

            if (filled($mpOrderId) && $mpOrderId !== 'null') {
                $payment = $order->payments()->where('status', '!=', 'approved')->first();

                if ($payment) {
                    try {
                        $result = MercadoPagoPaymentService::fetchOrderTryingAllRoles((string) $mpOrderId);

                        $payment->forceFill([
                            'mp_payment_id'     => $result['id'],
                            'mp_payment_method' => $result['payment_method_id'],
                            'mp_payment_type'   => $result['payment_type_id'],
                            'raw_last_response' => $result['raw'],
                        ])->save();

                        (new AdvanceMercadoPagoPaymentStatus())(
                            $payment,
                            (string) $result['status'],
                            'Retorno de Checkout Pro (confirmado vía API, nunca por el query string de la URL de retorno).',
                            'api_confirm',
                            $result['status_detail'] ?? null
                        );

                        (new ReconcileStoreOrderPaymentStatus())($order);

                        // La Orders API de Checkout Pro deja la Orden en
                        // status_detail "waiting_retry" (status "pending" ya
                        // traducido, ver translateOrderStatus()) cuando el
                        // intento de pago más reciente fue rechazado pero la
                        // Orden en sí sigue abierta para reintentar con otra
                        // tarjeta (por eso MP muestra "Pagar con otro medio"
                        // en vez de cerrar el flujo) -- a diferencia de un
                        // pago realmente pendiente (OXXO/SPEI, esperando que
                        // el cliente pague después), aquí NO se cobró nada y
                        // el cliente necesita reintentar YA, así que cuenta
                        // como fallo igual que rejected/cancelled.
                        $failed = in_array($result['status'], ['rejected', 'cancelled'], true)
                            || $result['status_detail'] === 'waiting_retry';
                        $verified = true;
                    } catch (Throwable $e) {
                        report($e);
                        $failed = true;
                        $verified = true;
                    }
                }
            }
        }

        // Ninguna de las dos ramas de arriba pudo verificar nada contra la
        // API en esta visita (ej. MP mandó todo en el string literal "null"
        // -- pasa cuando el cliente le da "Volver" tras un error como "no
        // tienes suficiente dinero" sin llegar a intentar un pago real). Si
        // además el pedido no tiene ya un pago aprobado de antes (otro
        // intento, un webhook que llegó primero), no hay ninguna base para
        // decir "confirmado" -- mismo criterio ya aplicado a un rechazo real
        // y al cierre manual del popup.
        if (! $verified && $order->payments()->where('status', 'approved')->doesntExist()) {
            $failed = true;
        }

        if ($request->wantsJson()) {
            return response()->json(['failed' => $failed]);
        }

        if ($failed) {
            // Respaldo para cuando el navegador bloqueó el popup de Checkout
            // Pro y hubo que navegar la pestaña completa (ver el fallback en
            // openCheckoutProPopup() en checkout-payment.js) -- el caso común
            // (popup normal) nunca llega aquí, se resuelve en JS sin navegar
            // nada (ver el mismo archivo). El carrito de este pedido ya se
            // vació al crearlo, así que no hay a dónde "regresar" con los
            // mismos datos -- se usa el mismo banner de error que ya
            // renderiza esta página para cualquier otro fallo.
            return redirect()->route('checkout.index')
                ->with('error', 'Tu pago con Mercado Pago no se completó. Contáctanos para continuar con tu pedido ' . $order->order_number . '.');
        }

        // confirmation.blade.php espera $storeOrder (no $order) -- así se
        // llama la variable en el resto de métodos de pago.
        return view('frontend.shop.checkout.confirmation', [
            'storeOrder' => $order->fresh(),
        ]);
    }

    /**
     * Crea una preferencia de Checkout Pro y redirige (fuera de nuestro
     * sitio) para que el cliente pague con Wallet, Efectivo, Transferencia o
     * Mercado Crédito -- métodos que el Payment Brick no ofrece de forma
     * confiable a un comprador anónimo/en sandbox. Tarjeta sigue siendo
     * exclusiva del Brick embebido (charge() arriba); por eso esto solo
     * aplica cuando el pedido tiene un único cobro (sin split MSI, que de
     * cualquier forma es un concepto exclusivo de tarjeta).
     */
    public function checkoutPro(StoreOrder $order, MercadoPagoPayment $payment)
    {
        abort_unless($payment->store_order_id === $order->id, 404);
        abort_unless(session('checkout.mp_order_id') === $order->id, 404);

        if ($payment->status === 'approved') {
            return response()->json(['message' => 'Este cobro ya fue aprobado.'], 422);
        }

        if ($order->payments()->count() > 1) {
            return response()->json(['message' => 'Esta opción no está disponible para pedidos con más de un cobro.'], 422);
        }

        // Antes era Str::uuid() (nueva en cada llamada) -- si este endpoint
        // se llamara 2 veces para el MISMO intento (doble clic que burle el
        // disable del botón, reintento de red), Mercado Pago no tenía forma
        // de reconocerlo como la misma operación y creaba 2 Órdenes reales.
        // attempts se incrementa aquí (mismo patrón que charge()) para que
        // la clave sea estable dentro de ESTE intento pero cambie en un
        // reintento genuino después de un rechazo (attempts avanza).
        $payment->increment('attempts');
        $idempotencyKey = hash('sha256', $payment->id . '_' . $payment->attempts);

        try {
            $result = (new MercadoPagoPaymentService('checkout_pro'))->createOrder([
                'items' => [[
                    'id'         => $order->order_number,
                    'title'      => 'Pedido ' . $order->order_number,
                    'quantity'   => 1,
                    'unit_price' => (float) $payment->amount,
                ]],
                // Mismo separador "_" que charge() -- ProcessMercadoPagoWebhookJob
                // y el reconcile de thanks() dependen de este formato. No se
                // usa ":" porque la API de Orders rechaza external_reference
                // con ":" (confirmado en sandbox real).
                'external_reference' => $order->order_number . '_' . $payment->charge_group,
                'back_urls' => [
                    'success' => route('checkout.payment.mercadopago.thanks', $order->order_number),
                    'pending' => route('checkout.payment.mercadopago.thanks', $order->order_number),
                    'failure' => route('checkout.payment.mercadopago.thanks', $order->order_number),
                ],
                'payer_email' => $order->contact_email ?? null,
            ], $idempotencyKey);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'No se pudo iniciar el pago con Mercado Pago. Intenta de nuevo.'], 502);
        }

        $payment->update(['mp_preference_id' => $result['id']]);

        return response()->json(['redirectUrl' => $result['checkout_url']]);
    }

    /**
     * Reintento de un cobro rechazado/cancelado, vía URL firmada (el
     * middleware 'signed' se aplica en la ruta). Renderiza la misma vista
     * del Brick pero en "modo reintento": solo ese cobro es interactivo, el
     * otro (si existe y ya está aprobado) se muestra de solo lectura.
     */
    public function retry(StoreOrder $order, MercadoPagoPayment $payment)
    {
        abort_unless($payment->store_order_id === $order->id, 404);

        if (! in_array($payment->status, ['rejected', 'cancelled'], true)) {
            return redirect()
                ->route('checkout.payment.mercadopago.thanks', $order->order_number)
                ->with('info', 'Este cobro ya no está disponible para reintento.');
        }

        $payments = $order->payments()->orderBy('charge_group')->get();

        return view('frontend.shop.checkout.pay-mercadopago', [
            'order'     => $order,
            'payments'  => $payments,
            // Mismo criterio que show() -- el reintento siempre es de Tarjeta.
            'publicKey' => MercadoPagoPaymentService::publicKeyFor('api'),
            'retryOnly' => $payment->id,
        ]);
    }
}
