<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Symfony\Component\Mime\Email;

/**
 * Aviso interno al equipo cuando entra un pedido nuevo en la tienda (ver StoreOrderMailService::sendStaff).
 * Multipart: HTML (emails.store-order-staff) + texto plano. Al responder, el equipo le escribe directo al
 * cliente (replyTo = correo del pedido).
 *
 * @param  array<string,mixed>  $data  arreglo que arma StoreOrderMailService::staffViewData()
 */
class StaffNewOrderMail extends Mailable
{
    public function __construct(
        public string $fromAddress,
        public ?string $fromName,
        public string $mailSubject,
        public array $data,
        public ?string $replyToAddress = null,
        public ?string $replyToName = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->fromAddress, (string) $this->fromName),
            replyTo: $this->replyToAddress ? [new Address($this->replyToAddress, (string) $this->replyToName)] : [],
            subject: $this->mailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.store-order-staff', with: $this->data);
    }

    public function build(): static
    {
        $this->withSymfonyMessage(function (Email $message) {
            $message->text($this->plainText());
        });

        return $this;
    }

    public function plainText(): string
    {
        $d = $this->data;
        $c = $d['customer'];

        $lines = [
            $d['heading'],
            '',
            'Pedido ' . $d['order_number'] . ($d['order_date'] ? ' · ' . $d['order_date'] : ''),
            'Estado: ' . $d['status_label'],
            'Método de pago: ' . $d['payment_method'],
            '',
            'Cliente: ' . $c['name'],
            'Correo: ' . $c['email'],
            'Teléfono: ' . ($c['phone'] !== '' ? $c['phone'] : 'No capturado'),
            '',
            'Productos:',
        ];
        foreach ($d['items'] as $item) {
            $lines[] = '- ' . $item['name'] . ($item['sku'] !== '' ? ' (' . $item['sku'] . ')' : '') . ' x' . $item['quantity'] . ' ... ' . $item['total'];
        }
        $lines[] = 'Subtotal: ' . $d['subtotal'];
        $lines[] = 'IVA: ' . $d['tax'];
        $lines[] = 'Envío: ' . $d['shipping'];
        $lines[] = 'Total: ' . $d['total'];
        $lines[] = '';
        $lines[] = 'Enviar a: ' . implode(', ', $d['ship_to_lines']);

        if (! empty($d['admin_url'])) {
            $lines[] = '';
            $lines[] = 'Ver el pedido en el panel: ' . $d['admin_url'];
        }

        return implode("\n", $lines);
    }
}
