<?php

namespace App\Services;

use App\Mail\StoreOrderMail;
use App\Models\Delivery;
use App\Models\PaymentMethod;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\StoreOrder;
use App\Models\StoreOrderEmail;
use App\Models\StoreOrderItem;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Correos automáticos de los pedidos de la tienda (Ecommerce > Correos de pedidos).
 *
 * Cuándo salen (los dispara StoreOrderEmailObserver, siempre después del commit):
 *  - created_offline: al crear el pedido con un método que no se cobra en línea (transferencia,
 *    referencia, crédito...). Los pedidos de Mercado Pago no mandan este: avisan al confirmarse el pago.
 *  - paid / shipped / delivered / cancelled: cuando el estatus del pedido cambia a ese valor.
 *
 * Todo está apagado hasta que se activa "Enviar correos de pedidos" en el panel. Cada evento sale
 * una sola vez por pedido (un registro `sent` en store_order_emails); reenviar es manual.
 * Un fallo de correo nunca debe romper el checkout ni el cambio de estatus: se registra y se sigue.
 *
 * Remitente: el mismo de la bandeja de leads (Integraciones > Buzón de la bandeja) si está
 * configurado, para que las respuestas del cliente lleguen ahí; si no, el remitente general.
 */
class StoreOrderMailService
{
    public const EVENTS = ['created_offline', 'paid', 'shipped', 'delivered', 'cancelled'];

    private const STATUS_EVENTS = [
        'pagado'    => 'paid',
        'enviado'   => 'shipped',
        'entregado' => 'delivered',
        'cancelado' => 'cancelled',
    ];

    private const VIGENCIAS = ['24h' => '24 horas', '48h' => '48 horas', '72h' => '72 horas', '7dias' => '7 días'];

    public function __construct(private LeadMailService $mail)
    {
    }

    // ── Configuración ────────────────────────────────────────────────────

    public function enabled(): bool
    {
        return (bool) Setting::get('order_emails.enabled', false);
    }

    /** @return array{enabled:bool,subject:string,heading:string,intro:string} */
    public function eventSettings(string $event): array
    {
        $defaults = (array) config("order_emails.events.$event", []);

        return [
            'enabled' => (bool) Setting::get("order_emails.$event.enabled", true),
            'subject' => (string) (Setting::get("order_emails.$event.subject") ?: ($defaults['subject'] ?? '')),
            'heading' => (string) (Setting::get("order_emails.$event.heading") ?: ($defaults['heading'] ?? '')),
            'intro'   => (string) (Setting::get("order_emails.$event.intro") ?: ($defaults['intro'] ?? '')),
        ];
    }

    public function footer(): string
    {
        return trim((string) Setting::get('order_emails.footer', ''));
    }

    public function copyTo(): string
    {
        return trim((string) Setting::get('order_emails.copy_to', ''));
    }

    // ── Disparadores ─────────────────────────────────────────────────────

    public function handleCreated(StoreOrder $order): void
    {
        if (! $this->enabled() || $this->paidOnline($order)) {
            return;
        }

        $this->dispatch($order, 'created_offline');
    }

    public function handleStatusChanged(StoreOrder $order): void
    {
        $event = self::STATUS_EVENTS[$order->status] ?? null;
        if ($event === null || ! $this->enabled()) {
            return;
        }

        $this->dispatch($order, $event);
    }

    /** Pedido que se cobra en línea con Mercado Pago: se avisa cuando el pago se aprueba, no al crearlo. */
    private function paidOnline(StoreOrder $order): bool
    {
        $method = $order->paymentMethod;

        return $method !== null
            && in_array($method->type, ['pasarela', 'digital'], true)
            && ($method->details['processor'] ?? null) === 'mercadopago';
    }

    private function dispatch(StoreOrder $order, string $event): ?StoreOrderEmail
    {
        if (! $this->eventSettings($event)['enabled']) {
            return null;
        }

        $already = StoreOrderEmail::where('store_order_id', $order->id)
            ->where('event', $event)
            ->where('status', StoreOrderEmail::STATUS_SENT)
            ->exists();
        if ($already) {
            return null;
        }

        return $this->send($order, $event);
    }

    // ── Envío ────────────────────────────────────────────────────────────

    /** Envía y registra el resultado (enviado, fallido u omitido). Nunca lanza. */
    public function send(StoreOrder $order, string $event, ?int $userId = null): StoreOrderEmail
    {
        $to = strtolower(trim((string) $order->contact_email));

        $log = new StoreOrderEmail([
            'store_order_id'  => $order->id,
            'event'           => $event,
            'to_email'        => $to !== '' ? $to : null,
            'sent_by_user_id' => $userId,
        ]);

        try {
            if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $log->status = StoreOrderEmail::STATUS_SKIPPED;
                $log->error = 'El pedido no tiene un correo válido.';
            } else {
                $log->subject = $this->deliver($order, $event, $to);
                $log->status = StoreOrderEmail::STATUS_SENT;
                $log->sent_at = now();
            }
        } catch (\Throwable $e) {
            report($e);
            $log->status = StoreOrderEmail::STATUS_FAILED;
            $log->error = Str::limit(trim((string) $e->getMessage()), 300);
        }

        $log->save();

        return $log;
    }

    /**
     * Correo de prueba con un pedido de ejemplo (nunca datos de un cliente real). No se registra.
     *
     * @throws RuntimeException con mensaje en español si no se puede enviar
     */
    public function sendSample(string $event, string $to): void
    {
        $this->deliver($this->sampleOrder($event), $event, $to, '[PRUEBA] ');
    }

    /**
     * @throws RuntimeException
     * @return string asunto con el que salió
     */
    private function deliver(StoreOrder $order, string $event, string $to, string $subjectPrefix = ''): string
    {
        if ($problem = $this->mail->smtpProblem()) {
            throw new RuntimeException($problem);
        }

        $sender = $this->mail->sender();
        $data = $this->viewData($order, $event);
        $subject = $subjectPrefix . $data['subject'];

        $mailable = new StoreOrderMail($sender['address'], $sender['name'], $subject, $data);

        $pending = $sender['mailbox'] ? $this->mail->inboxMailer($sender['mailbox'])->to($to) : Mail::to($to);
        if ($bcc = $this->copyRecipients($to)) {
            $pending->bcc($bcc);
        }
        $pending->send($mailable);

        return $subject;
    }

    /** @return array<int,string> */
    private function copyRecipients(string $to): array
    {
        try {
            return $this->mail->parseCc($this->copyTo(), $to);
        } catch (RuntimeException) {
            return []; // un correo de copia mal capturado no debe impedir el aviso al cliente
        }
    }

    // ── Contenido ────────────────────────────────────────────────────────

    /** @return array<string,mixed> lo que consumen la vista emails.store-order y StoreOrderMail */
    public function viewData(StoreOrder $order, string $event): array
    {
        if ($order->exists) {
            $order->loadMissing(['items', 'paymentMethod', 'shipment.carrier']);
        }

        $settings = $this->eventSettings($event);
        $shipment = $order->shipment;
        $tracking = $event === 'shipped' ? $this->tracking($shipment) : null;

        $tokens = [
            '{{order_number}}'    => (string) $order->order_number,
            '{{customer_name}}'   => (string) $order->contact_name,
            '{{total}}'           => $this->money($order->total) . ' ' . ($order->currency ?: 'MXN'),
            '{{payment_method}}'  => (string) ($order->paymentMethod?->name ?? ''),
            '{{carrier}}'         => (string) ($shipment?->carrier?->name ?? ''),
            '{{tracking_number}}' => (string) ($shipment?->guia ?? ''),
        ];

        $introText = strtr($settings['intro'], $tokens);
        [$paymentTitle, $paymentRows, $paymentNote] = $event === 'created_offline'
            ? $this->paymentInstructions($order)
            : [null, [], null];

        $currency = $order->currency ?: 'MXN';

        return [
            'subject'        => strtr($settings['subject'], $tokens),
            'heading'        => strtr($settings['heading'], $tokens),
            'intro_text'     => $introText,
            'intro_html'     => nl2br(e($introText), false),
            'company'        => 'Equiterm Industries',
            'order_number'   => (string) $order->order_number,
            'order_date'     => $order->created_at?->locale('es')->isoFormat('D MMM YYYY'),
            'items'          => $order->items->map(fn (StoreOrderItem $i) => [
                'name'     => (string) $i->product_name,
                'quantity' => (int) $i->quantity,
                'total'    => $this->money($i->line_total),
            ])->values()->all(),
            'subtotal'       => $this->money($order->subtotal),
            'tax'            => $this->money($order->tax_total),
            'shipping'       => (float) $order->shipping_total > 0 ? $this->money($order->shipping_total) : 'Gratis',
            'total'          => $this->money($order->total) . ' ' . $currency,
            'payment_method' => (string) ($order->paymentMethod?->name ?? 'No especificado'),
            'payment_title'  => $paymentTitle,
            'payment_rows'   => $paymentRows,
            'payment_note'   => $paymentNote,
            'tracking'       => $tracking,
            'ship_to_name'   => (string) $order->contact_name,
            'ship_to_lines'  => [
                trim($order->shipping_address_line1 . ($order->shipping_address_line2 ? ', ' . $order->shipping_address_line2 : '')),
                trim($order->shipping_city . ', ' . $order->shipping_state . ($order->shipping_postal_code ? ', C.P. ' . $order->shipping_postal_code : ''), ', '),
                $order->shipping_reference ? 'Referencias: ' . $order->shipping_reference : null,
            ],
            'footer'         => $this->footer(),
        ];
    }

    /** @return array{0:?string,1:array<string,string>,2:?string} título, filas y nota del bloque de pago */
    private function paymentInstructions(StoreOrder $order): array
    {
        $method = $order->paymentMethod;
        if ($method === null) {
            return [null, [], null];
        }

        $details = (array) ($method->details ?? []);
        $amount = $this->money($order->total) . ' ' . ($order->currency ?: 'MXN');
        $rows = [];
        $note = $method->description ?: null;

        switch ($method->type) {
            case 'transferencia':
                $rows = array_filter([
                    'Banco'        => $method->bank_name,
                    'Beneficiario' => $method->beneficiary_name,
                    'CLABE'        => $method->clabe,
                    'Cuenta'       => $method->account_number,
                    'Moneda'       => $details['moneda'] ?? null,
                    'SWIFT/ABA'    => $details['swift_aba'] ?? null,
                ], fn ($v) => filled($v));
                $rows['Referencia'] = (string) $order->order_number;
                $rows['Importe'] = $amount;
                $note = trim(($note ? $note . "\n" : '') . 'Cuando pagues, responde a este correo con tu comprobante para confirmar tu pedido.');

                return ['Datos para tu transferencia', $rows, $note];

            case 'referencia':
                $rows = array_filter([
                    'Convenio' => $details['convenio'] ?? null,
                    'Vigencia' => self::VIGENCIAS[$details['vigencia'] ?? ''] ?? null,
                ], fn ($v) => filled($v));
                $rows['Referencia'] = (string) $order->order_number;
                $rows['Importe'] = $amount;
                $note = trim(($details['instrucciones'] ?? '') . ($note ? "\n" . $note : '')) ?: null;

                return ['Datos para tu pago', $rows, $note];

            case 'credito':
                if (! empty($details['plazo_credito'])) {
                    $rows['Plazo de crédito'] = $details['plazo_credito'] . ' días';
                }
                if (! empty($details['requiere_aprobacion'])) {
                    $note = trim(($note ? $note . "\n" : '') . 'Tu pedido está sujeto a la aprobación de tu línea de crédito; te confirmaremos a la brevedad.');
                }

                return $rows || $note ? ['Pago a crédito', $rows, $note] : [null, [], null];

            default:
                return $note ? ['Forma de pago: ' . $method->name, [], $note] : [null, [], null];
        }
    }

    /** @return array{carrier:?string,guia:?string,eta:?string,url:?string}|null */
    private function tracking(?Shipment $shipment): ?array
    {
        if ($shipment === null) {
            return null;
        }

        $guia = trim((string) $shipment->guia);
        $template = (string) $shipment->carrier?->tracking_url_template;

        $url = null;
        if ($guia !== '' && preg_match('#^https?://#i', $template)) {
            $clean = rawurlencode(str_replace(' ', '', $guia));
            $url = str_contains($template, '{guia}') ? str_replace('{guia}', $clean, $template) : $template . $clean;
        }

        $data = [
            'carrier' => $shipment->carrier?->name,
            'guia'    => $guia !== '' ? $guia : null,
            'eta'     => $shipment->eta?->locale('es')->isoFormat('D MMM YYYY'),
            'url'     => $url,
        ];

        return array_filter($data) ? $data : null;
    }

    private function money(mixed $amount): string
    {
        return '$' . number_format((float) $amount, 2);
    }

    // ── Pedido de ejemplo (vista previa y correo de prueba) ──────────────

    public function sampleOrder(string $event = 'created_offline'): StoreOrder
    {
        $method = PaymentMethod::where('is_active', true)->orderBy('sort_order')->first()
            ?? new PaymentMethod([
                'type' => 'transferencia', 'name' => 'Transferencia bancaria', 'bank_name' => 'Banco de ejemplo',
                'beneficiary_name' => 'Equiterm Industries', 'clabe' => '000000000000000000',
            ]);

        $order = new StoreOrder();
        $order->forceFill([
            'order_number'           => 'PW-EJEMPLO-0001',
            'contact_name'           => 'Cliente de ejemplo',
            'contact_email'          => 'cliente@ejemplo.com',
            'shipping_address_line1' => 'Calle Ejemplo 123',
            'shipping_address_line2' => 'Col. Centro',
            'shipping_city'          => 'Aguascalientes',
            'shipping_state'         => 'Aguascalientes',
            'shipping_postal_code'   => '20000',
            'subtotal'               => 1000,
            'shipping_total'         => 0,
            'tax_total'              => 160,
            'total'                  => 1160,
            'currency'               => 'MXN',
            'status'                 => 'pendiente_pago',
        ]);
        $order->created_at = now();

        $item = new StoreOrderItem();
        $item->forceFill(['product_name' => 'Producto de ejemplo', 'product_sku' => 'EJ-001', 'quantity' => 1, 'unit_price' => 1000, 'line_total' => 1000]);

        $order->setRelation('items', collect([$item]));
        $order->setRelation('paymentMethod', $method);

        $shipment = null;
        if ($event === 'shipped') {
            $shipment = new Shipment();
            $shipment->forceFill(['guia' => '1234567890', 'eta' => now()->addDays(3)]);
            $carrier = new Delivery();
            $carrier->forceFill(['name' => 'Paquetería de ejemplo', 'tracking_url_template' => 'https://rastreo.ejemplo.com/?guia=']);
            $shipment->setRelation('carrier', $carrier);
        }
        $order->setRelation('shipment', $shipment);

        return $order;
    }
}
