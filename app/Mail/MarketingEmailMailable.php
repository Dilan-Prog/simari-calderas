<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MarketingEmailMailable extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array{subject: string, html: string} $rendered
     */
    public function __construct(public readonly array $rendered) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->rendered['subject'],
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->rendered['html'],
        );
    }
}
