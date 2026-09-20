<?php

namespace App\Services\MercadoPago;

use MercadoPago\Payer;
use MercadoPago\Payment;
use MercadoPago\SDK;
use RuntimeException;

/**
 * Wrapper delgado sobre el SDK oficial de Mercado Pago (mercadopago/dx-php,
 * v2.6.2 -- la última compatible con el platform.php lock de este proyecto
 * en PHP 8.1; la serie 3.x del mismo paquete requiere PHP >= 8.2 y usa una
 * API distinta con namespaces MercadoPago\Client\*, que NO es la que está
 * instalada aquí). API real confirmada leyendo vendor/mercadopago/dx-php
 * tras el composer require: se inicializa con
 * `MercadoPago\SDK::setAccessToken()`, y los recursos son "entities"
 * (MercadoPago\Payment) que se llenan por propiedad y se persisten con
 * ->save() / se leen con ::find_by_id().
 *
 * CRÍTICO DE SEGURIDAD: este servicio nunca recibe ni maneja número de
 * tarjeta/CVV/fecha de expiración -- solo el `token` ya tokenizado
 * client-side por el SDK JS del Brick. La respuesta de la API de pagos de
 * MP tampoco regresa esos datos, pero normalize() igual filtra
 * recursivamente cualquier llave que empiece con "card" antes de devolver
 * el payload crudo (para raw_last_response).
 *
 * NOTA SOBRE LA API DE ORDERS (rol checkout_pro): createOrder()/fetchOrder()
 * no usan una entidad modelada del SDK como el resto de esta clase --
 * llaman directo a POST/GET /v1/orders vía el cliente REST genérico
 * `MercadoPago\SDK::post()`/`::get()`, ya incluido en dx-php v2.6.2. Es el
 * mismo motivo que createPayment() no usa una API más nueva: la 3.x de
 * este SDK (la única que sí modela Order vía OrderClient) requiere
 * PHP >= 8.2 y este proyecto tiene un platform.php lock en 8.1.10 (ver
 * composer.json) -- instalarla rompería producción. La API de Orders
 * reemplaza a la de Preferences (createPreference(), eliminada), que
 * Mercado Pago marca como "a descontinuar" en su propio panel.
 */
class MercadoPagoPaymentService
{
    /**
     * "api" (Checkout API, usada por createPayment()/el CardForm de
     * Tarjeta) o "checkout_pro" (usada por createOrder()) -- cada una
     * es una Aplicación distinta en el panel de desarrolladores de Mercado
     * Pago, con su propio Public Key/Access Token (ver config/services.php
     * y /admin/integraciones). SDK::setAccessToken() es estático/global en
     * este SDK (mercadopago/dx-php), así que solo puede haber un rol activo
     * a la vez -- nunca hace falta más de uno dentro de una misma petición
     * (cada endpoint de MercadoPagoCheckoutController usa exactamente un
     * rol de principio a fin).
     */
    public function __construct(private readonly string $role = 'api')
    {
        SDK::setAccessToken((string) config("services.mercadopago.{$role}.access_token"));
    }

    /**
     * Public Key del rol indicado -- la necesita el frontend (CardForm o el
     * SDK JS) para inicializar `new MercadoPago(publicKey, ...)`. Es un
     * dato público (no sensible, a diferencia del access_token), por eso es
     * un helper estático simple en vez de requerir instanciar el servicio.
     */
    public static function publicKeyFor(string $role): ?string
    {
        return config("services.mercadopago.{$role}.public_key");
    }

    /**
     * Intenta obtener un pago probando cada rol configurado, en orden,
     * hasta que uno tenga acceso -- usado cuando no se sabe de antemano
     * bajo qué Aplicación se creó el pago (notificaciones webhook,
     * reconciliación en thanks()): el payload de MP no indica la
     * Aplicación de origen, así que no hay forma de elegir el rol correcto
     * de antemano. Se detiene en el primer éxito; si todos fallan, relanza
     * la última excepción real (para no ocultar un error genuino de red/
     * credenciales detrás de un "ningún rol funcionó" genérico).
     */
    public static function fetchPaymentTryingAllRoles(string $mpPaymentId): array
    {
        $lastException = null;

        foreach (config('services.mercadopago.roles', ['api']) as $role) {
            try {
                return (new self($role))->fetchPayment($mpPaymentId);
            } catch (\Throwable $e) {
                $lastException = $e;
            }
        }

        throw $lastException ?? new RuntimeException("No se pudo obtener el pago {$mpPaymentId} con ningún rol de Mercado Pago configurado.");
    }

    /**
     * Crea un pago (POST /v1/payments vía el SDK). $idempotencyKey va como
     * header X-Idempotency-Key -- el SDK expone esto vía
     * Payment::setCustomHeader(), que es la única forma real (confirmada en
     * vendor/mercadopago/dx-php/src/MercadoPago/Manager.php) de fijar un
     * valor propio en vez de dejar que el SDK autogenere uno por request.
     *
     * @param array{token:?string, transaction_amount:float, installments:int, payment_method_id:string, payer:array, external_reference:string, description:string} $params
     * @return array{id:mixed, status:?string, status_detail:?string, payment_method_id:?string, payment_type_id:?string, raw:array}
     */
    public function createPayment(array $params, string $idempotencyKey): array
    {
        Payment::setCustomHeader('x-idempotency-key', $idempotencyKey);

        $payer = new Payer();
        $payer->email = $params['payer']['email'] ?? null;

        // 'identification' es opcional a propósito: México (mercado principal
        // de esta tienda) no la exige para pagos con tarjeta como sí lo hacen
        // AR/BR -- se implementa opcional en ambos lados (backend y
        // frontend) hasta confirmar el comportamiento real en sandbox.
        // Payer::$identification acepta un array PHP plano sin coerción de
        // tipo (@Attribute() @var object en el SDK).
        if (! empty($params['payer']['identification']['type']) && ! empty($params['payer']['identification']['number'])) {
            $payer->identification = [
                'type'   => (string) $params['payer']['identification']['type'],
                'number' => (string) $params['payer']['identification']['number'],
            ];
        }

        $payment = new Payment();
        $payment->transaction_amount = (float) $params['transaction_amount'];

        // Solo tarjeta (crédito/débito) manda un token tokenizado. Wallet
        // Purchase (saldo/tarjetas guardadas) y Mercado Crédito ("Meses sin
        // Tarjeta") no lo generan -- forzar un string vacío ahí rompería la
        // solicitud contra la API real de Mercado Pago.
        if (! empty($params['token'])) {
            $payment->token = (string) $params['token'];
        }

        $payment->installments = (int) $params['installments'];
        $payment->payment_method_id = (string) $params['payment_method_id'];
        $payment->payer = $payer;
        $payment->external_reference = $params['external_reference'] ?? null;
        $payment->description = $params['description'] ?? null;

        $saved = $payment->save();

        if (! $saved) {
            throw new RuntimeException(
                'Mercado Pago rechazó la solicitud de pago: ' . $this->errorMessage($payment)
            );
        }

        return $this->normalize($payment);
    }

    /**
     * Crea una Order de Checkout Pro (POST /v1/orders) -- reemplazo de
     * createPreference()/API de Preferences, que Mercado Pago marca como "a
     * descontinuar" en su propio panel. Se usa MercadoPago\SDK::post()
     * (cliente REST genérico ya incluido en dx-php v2.6.2) en vez de una
     * entidad modelada, porque la 3.x de este SDK (la única que sí modela
     * Order vía OrderClient) requiere PHP >= 8.2 y este proyecto tiene un
     * platform.php lock en 8.1.10 (ver composer.json) -- instalarla rompería
     * producción.
     *
     * @param array{items: array<array{id:string,title:string,quantity:int,unit_price:float}>, external_reference:string, back_urls: array{success:string,pending:string,failure:string}, payer_email:?string} $params
     * @return array{id:string, checkout_url:?string, status:string, status_detail:?string}
     */
    public function createOrder(array $params, string $idempotencyKey): array
    {
        $totalAmount = array_sum(array_map(fn ($i) => $i['unit_price'] * $i['quantity'], $params['items']));

        // 'unit_measure'/'total_amount' por item NO se aceptan aquí --
        // confirmado en sandbox real: MP rechaza la Order con
        // "additionalProperties 'unit_measure', 'total_amount' not allowed"
        // para este type=online/processing_mode=manual (aunque el ejemplo de
        // otra variante de la doc sí los incluye). Solo title/unit_price/
        // quantity por item.
        $items = array_map(fn ($i) => [
            'title'      => (string) $i['title'],
            'unit_price' => number_format((float) $i['unit_price'], 2, '.', ''),
            'quantity'   => (int) $i['quantity'],
        ], $params['items']);

        $body = [
            'type'                => 'online',
            'processing_mode'     => 'manual',
            'total_amount'        => number_format($totalAmount, 2, '.', ''),
            'external_reference'  => $params['external_reference'],
            'capture_mode'        => 'automatic_async',
            'items'               => $items,
            'config'              => [
                'online' => [
                    'success_url' => $params['back_urls']['success'],
                    'failure_url' => $params['back_urls']['failure'],
                    'pending_url' => $params['back_urls']['pending'],
                    'auto_return' => 'approved',
                ],
            ],
        ];

        if (! empty($params['payer_email'])) {
            $body['payer'] = ['email' => $params['payer_email']];
        }

        $response = SDK::post('/v1/orders', [
            'json_data' => $body,
            'headers'   => ['X-Idempotency-Key' => $idempotencyKey],
        ]);

        if (($response['code'] ?? 0) >= 300) {
            throw new RuntimeException('Mercado Pago rechazó la creación de la orden: ' . json_encode($response['body'] ?? []));
        }

        $order = $response['body'];

        return [
            'id'            => (string) $order['id'],
            'checkout_url'  => $order['checkout_url'] ?? null,
            'status'        => (string) ($order['status'] ?? 'created'),
            'status_detail' => $order['status_detail'] ?? null,
        ];
    }

    /**
     * GET /v1/payments/{id} real contra la API -- nunca se debe confiar en
     * el status que venga de un webhook sin pasar por aquí primero.
     */
    public function fetchPayment(string $mpPaymentId): array
    {
        $payment = Payment::find_by_id($mpPaymentId);

        if (! $payment) {
            throw new RuntimeException("El pago {$mpPaymentId} no existe en Mercado Pago.");
        }

        return $this->normalize($payment);
    }

    /**
     * GET /v1/orders/{id} real contra la API -- equivalente de fetchPayment()
     * para el rol checkout_pro (Orders API). Normaliza al mismo shape que
     * fetchPayment() ({id, status, status_detail, payment_method_id,
     * payment_type_id, raw}) para que thanks()/ProcessMercadoPagoWebhookJob no
     * necesiten ramas nuevas más allá de "qué fetch llamar".
     */
    public function fetchOrder(string $mpOrderId): array
    {
        $response = SDK::get("/v1/orders/{$mpOrderId}");

        if (($response['code'] ?? 0) >= 300) {
            throw new RuntimeException("La orden {$mpOrderId} no existe en Mercado Pago.");
        }

        $order = $this->stripCardFields($response['body'] ?? []);

        return [
            'id'                => $order['id'] ?? $mpOrderId,
            'status'            => $this->translateOrderStatus($order['status'] ?? null, $order['status_detail'] ?? null),
            'status_detail'     => $order['status_detail'] ?? null,
            'payment_method_id' => $order['transactions']['payments'][0]['payment_method']['id'] ?? null, // best-effort, verificar shape real en sandbox
            'payment_type_id'   => $order['transactions']['payments'][0]['payment_method']['type'] ?? null, // best-effort, verificar shape real en sandbox
            'raw'               => $order,
        ];
    }

    /**
     * Traduce el vocabulario de estatus de la API de Orders
     * (created/processing/action_required/processed/canceled/expired/failed/
     * charged_back/refunded, cada uno con su status_detail) al vocabulario
     * interno ya usado por App\Support\MercadoPagoPaymentStatus -- no hace
     * falta agregar ningún estatus nuevo a ese Support, esta tabla alcanza
     * para representar todos los reales de Orders.
     */
    private function translateOrderStatus(?string $status, ?string $statusDetail): string
    {
        return match (true) {
            $status === 'processed' && $statusDetail === 'accredited' => 'approved',
            $status === 'processed'        => 'approved', // partially_refunded u otro detail de processed -- se sigue tratando como aprobado, el detalle queda en status_detail
            $status === 'processing'       => 'in_process',
            $status === 'action_required'  => 'pending',
            $status === 'canceled'         => 'cancelled',
            $status === 'expired'          => 'cancelled',
            $status === 'failed'           => 'rejected',
            $status === 'charged_back'     => 'charged_back',
            $status === 'refunded'         => 'refunded',
            default                        => 'pending',
        };
    }

    /**
     * Mismo patrón que fetchPaymentTryingAllRoles() -- prueba cada rol
     * configurado hasta que uno tenga acceso, para el caso "no se sabe de
     * antemano bajo qué Aplicación se creó esta Order" (webhook/redirect).
     */
    public static function fetchOrderTryingAllRoles(string $mpOrderId): array
    {
        $lastException = null;

        foreach (config('services.mercadopago.roles', ['api']) as $role) {
            try {
                return (new self($role))->fetchOrder($mpOrderId);
            } catch (\Throwable $e) {
                $lastException = $e;
            }
        }

        throw $lastException ?? new RuntimeException("No se pudo obtener la orden {$mpOrderId} con ningún rol de Mercado Pago configurado.");
    }

    private function errorMessage(\MercadoPago\Entity $entity): string
    {
        $error = $entity->Error();

        if (is_object($error) && method_exists($error, 'getMessage')) {
            return (string) $error->getMessage();
        }

        if (is_array($error) && isset($error['message'])) {
            return (string) $error['message'];
        }

        return 'error desconocido';
    }

    private function normalize(Payment $payment): array
    {
        // Round-trip por json para aplanar objetos anidados (payer,
        // payment_method, point_of_interaction, etc.) a arrays simples antes
        // de filtrar y persistir en raw_last_response.
        $raw = json_decode(json_encode($payment->toArray()), true) ?: [];
        $raw = $this->stripCardFields($raw);

        return [
            'id'                 => $payment->id,
            'status'             => $payment->status,
            'status_detail'      => $payment->status_detail,
            'payment_method_id'  => $payment->payment_method_id,
            'payment_type_id'    => $payment->payment_type_id,
            'raw'                => $raw,
        ];
    }

    /**
     * Quita recursivamente cualquier llave que empiece con "card" en
     * cualquier nivel del array -- defensa en profundidad además de que MP
     * ya no regresa PAN/CVV/expiración en la respuesta de pago.
     */
    private function stripCardFields(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_string($key) && stripos($key, 'card') === 0) {
                continue;
            }

            $result[$key] = is_array($value) ? $this->stripCardFields($value) : $value;
        }

        return $result;
    }
}
