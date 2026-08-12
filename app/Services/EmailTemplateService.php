<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Deal;
use App\Models\EmailTemplate;

class EmailTemplateService
{
    /**
     * Reemplaza los tokens de subject/html_body de una plantilla usando los
     * datos del destinatario (y, opcionalmente, del deal asociado).
     *
     * IMPORTANTE: este método NO resuelve {{unsubscribe_url}} y lo deja tal
     * cual en el html retornado. Esa responsabilidad es de EmailCampaignService,
     * que crea el EmailSend correspondiente y solo entonces conoce su
     * tracking_token real; EmailCampaignService es también quien inyecta el
     * pixel de tracking en el html, después de crear el EmailSend. Este
     * render() no crea el EmailSend y no tiene forma de conocer ese token.
     */
    public function render(EmailTemplate $template, Customer $recipient, ?Deal $deal = null): array
    {
        $replacements = [
            '{{contact.name}}'    => trim("{$recipient->first_name} {$recipient->last_name}"),
            '{{contact.email}}'   => (string) $recipient->email,
            '{{contact.company}}' => (string) $recipient->company,
        ];

        // {{deal.amount}} y {{deal.name}} solo se resuelven si hay un deal;
        // si no, se dejan sin reemplazar (tal cual) en el resultado.
        if ($deal !== null) {
            $replacements['{{deal.amount}}'] = number_format((float) $deal->amount, 2);
            $replacements['{{deal.name}}']   = (string) $deal->name;
        }

        $subject = str_replace(array_keys($replacements), array_values($replacements), (string) $template->subject);
        $html    = str_replace(array_keys($replacements), array_values($replacements), (string) $template->html_body);

        return [
            'subject' => $subject,
            'html'    => $html,
        ];
    }
}
