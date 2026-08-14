<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Deal;
use App\Models\EmailTemplate;
use App\Models\Quote;

class EmailTemplateService
{
    /**
     * Reemplaza los tokens de subject/html_body de una plantilla usando los
     * datos del destinatario (y, opcionalmente, del deal/quote asociado).
     *
     * $recipient es nullable para soportar cotizaciones de invitado (sin
     * Customer registrado, solo guest_email/guest_name) -- en ese caso se
     * usan $guestName/$guestEmail como fallback para {{contact.name}} y
     * {{contact.email}}.
     *
     * IMPORTANTE: este método NO resuelve {{unsubscribe_url}} y lo deja tal
     * cual en el html retornado. Esa responsabilidad es de EmailCampaignService,
     * que crea el EmailSend correspondiente y solo entonces conoce su
     * tracking_token real; EmailCampaignService es también quien inyecta el
     * pixel de tracking en el html, después de crear el EmailSend. Este
     * render() no crea el EmailSend y no tiene forma de conocer ese token.
     */
    public function render(
        EmailTemplate $template,
        ?Customer $recipient,
        ?Deal $deal = null,
        ?Quote $quote = null,
        ?string $guestName = null,
        ?string $guestEmail = null
    ): array {
        $replacements = [
            '{{contact.name}}'    => $recipient !== null ? trim("{$recipient->first_name} {$recipient->last_name}") : (string) $guestName,
            '{{contact.email}}'   => $recipient?->email ?? (string) $guestEmail,
            '{{contact.company}}' => (string) ($recipient?->company ?? ''),
        ];

        // {{deal.amount}} y {{deal.name}} solo se resuelven si hay un deal;
        // si no, se dejan sin reemplazar (tal cual) en el resultado.
        if ($deal !== null) {
            $replacements['{{deal.amount}}'] = number_format((float) $deal->amount, 2);
            $replacements['{{deal.name}}']   = (string) $deal->name;
        }

        // {{quote.*}} solo se resuelven si hay una quote.
        if ($quote !== null) {
            $replacements['{{quote.quote_number}}'] = (string) $quote->quote_number;
            $replacements['{{quote.total}}']        = number_format((float) $quote->total, 2);
            $replacements['{{quote.valid_until}}']  = $quote->valid_until ? $quote->valid_until->format('d/m/Y') : '';
        }

        $subject = str_replace(array_keys($replacements), array_values($replacements), (string) $template->subject);
        $html    = str_replace(array_keys($replacements), array_values($replacements), (string) $template->html_body);

        return [
            'subject' => $subject,
            'html'    => $html,
        ];
    }
}
