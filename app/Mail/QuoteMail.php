<?php

namespace App\Mail;

use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class QuoteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Quote $quote) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Cotización {$this->quote->quote_number} - Equiterm Industries",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quote',
        );
    }

    public function attachments(): array
    {
        $pdf = Pdf::loadView('admin.quotes.pdf', ['quote' => $this->quote])
            ->setPaper('a4', 'portrait');

        return [
            Attachment::fromData(
                fn () => $pdf->output(),
                "{$this->quote->quote_number}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}
