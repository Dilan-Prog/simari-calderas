<?php

namespace App\Mail;

use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Symfony\Component\Mime\Email;

/**
 * Respuesta de un usuario del panel a un lead (bandeja de leads). Multipart: texto plano + HTML
 * (el texto que escribió el usuario escapado con saltos de línea, más la firma de Integraciones).
 * Lleva Message-ID propio y los encabezados de hilo (In-Reply-To / References) para que el
 * cliente la vea como respuesta de su correo y para poder deduplicar contra n8n.
 */
class LeadReplyMail extends Mailable
{
    /**
     * @param  array<int,array{data:string,name:string,mime:string}>  $files  adjuntos ya leídos en memoria
     */
    public function __construct(
        public string $fromAddress,
        public ?string $fromName,
        public string $mailSubject,
        public string $bodyText,
        public string $signatureText,
        public string $messageId,
        public ?string $inReplyTo = null,
        public array $files = [],
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
        return new Content(
            view: 'emails.lead-reply',
            with: [
                'bodyHtml'      => nl2br(e($this->bodyText), false),
                'signatureHtml' => $this->signatureText !== '' ? nl2br(e($this->signatureText), false) : '',
            ],
        );
    }

    /** @return array<int,Attachment> */
    public function attachments(): array
    {
        return array_map(
            fn (array $f) => Attachment::fromData(fn () => $f['data'], $f['name'])->withMime($f['mime']),
            $this->files
        );
    }

    /** Texto plano del correo (cuerpo + firma). */
    public function plainText(): string
    {
        return $this->bodyText . ($this->signatureText !== '' ? "\n\n" . $this->signatureText : '');
    }

    public function build(): static
    {
        $this->withSymfonyMessage(function (Email $message) {
            $message->text($this->plainText());

            $headers = $message->getHeaders();
            $headers->remove('Message-ID');
            $headers->addIdHeader('Message-ID', trim($this->messageId, '<>'));

            if ($this->inReplyTo !== null && $this->inReplyTo !== '') {
                try {
                    $id = trim($this->inReplyTo, '<> ');
                    $headers->addIdHeader('In-Reply-To', $id);
                    $headers->addIdHeader('References', $id);
                } catch (\Throwable) {
                    // Message-ID ajeno con formato raro: se envía sin encabezados de hilo.
                }
            }
        });

        return $this;
    }
}
