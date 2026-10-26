<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Aviso interno (para el personal) de un evento del portal de clientes: orden de compra subida
 * o lectura de analisis quimico fuera de rango. Se envia de forma sincrona; el llamador captura
 * cualquier fallo de transporte para no afectar la accion del cliente.
 */
class PortalStaffAlertMail extends Mailable
{
    /**
     * @param array<string, string|null> $rows    etiqueta => valor (tabla de datos)
     * @param array<string, string>      $actions etiqueta => URL absoluta (botones/enlaces)
     * @param array<int, string>         $lines   renglones libres bajo la tabla
     * @param array{disk: string, path: string, name: string, mime: string}|null $attachment
     */
    public function __construct(
        public readonly string $subjectLine,
        public readonly string $heading,
        public readonly string $intro,
        public readonly array $rows = [],
        public readonly array $actions = [],
        public readonly array $lines = [],
        public readonly ?array $attachment = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.portal-staff-alert');
    }

    public function attachments(): array
    {
        if ($this->attachment === null) {
            return [];
        }

        return [
            Attachment::fromStorageDisk($this->attachment['disk'], $this->attachment['path'])
                ->as($this->attachment['name'])
                ->withMime($this->attachment['mime']),
        ];
    }
}
