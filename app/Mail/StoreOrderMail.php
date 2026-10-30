<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Symfony\Component\Mime\Email;

/**
 * Correo automático de un pedido de la tienda (recibido, pagado, enviado, entregado, cancelado).
 * Multipart: HTML (emails.store-order) + texto plano con el mismo contenido. Las respuestas del
 * cliente llegan al remitente (el buzón de la bandeja de leads si está configurado).
 *
 * @param  array<string,mixed>  $data  arreglo que arma StoreOrderMailService::viewData()
 */
class StoreOrderMail extends Mailable
{
    public function __construct(
        public string $fromAddress,
        public ?string $fromName,
        public string $mailSubject,
        public array $data,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->fromAddress, (string) $this->fromName),
            replyTo: [new Address($this->fromAddress, (string) $this->fromName)],
            subject: $this->mailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.store-order', with: $this->data);
    }

    public function build(): static
    {
        $this->withSymfonyMessage(function (Email $message) {
            $message->text($this->plainText());
        });

        return $this;
    }

    /** Mismo contenido que el HTML, en texto plano. */
    public function plainText(): string
    {
        $d = $this->data;
        $lines = [$d['heading'], '', $d['intro_text'], ''];

        $lines[] = 'Pedido ' . $d['order_number'] . ($d['order_date'] ? ' · ' . $d['order_date'] : '');
        foreach ($d['items'] as $item) {
            $lines[] = '- ' . $item['name'] . ' x' . $item['quantity'] . ' ... ' . $item['total'];
        }
        $lines[] = 'Subtotal: ' . $d['subtotal'];
        $lines[] = 'IVA: ' . $d['tax'];
        $lines[] = 'Envío: ' . $d['shipping'];
        $lines[] = 'Total: ' . $d['total'];

        if (! empty($d['payment_rows'])) {
            $lines[] = '';
            $lines[] = $d['payment_title'];
            foreach ($d['payment_rows'] as $label => $value) {
                $lines[] = $label . ': ' . $value;
            }
        }
        if (! empty($d['payment_note'])) {
            $lines[] = $d['payment_note'];
        }

        if (! empty($d['tracking'])) {
            $lines[] = '';
            $lines[] = 'Envío';
            foreach (['carrier' => 'Paquetería', 'guia' => 'Guía', 'eta' => 'Entrega estimada', 'url' => 'Seguimiento'] as $key => $label) {
                if (! empty($d['tracking'][$key])) {
                    $lines[] = $label . ': ' . $d['tracking'][$key];
                }
            }
        }

        $lines[] = '';
        $lines[] = 'Enviar a: ' . $d['ship_to_name'] . ', ' . implode(', ', array_filter($d['ship_to_lines']));

        if ($d['footer'] !== '') {
            $lines[] = '';
            $lines[] = $d['footer'];
        }

        return implode("\n", $lines);
    }
}
