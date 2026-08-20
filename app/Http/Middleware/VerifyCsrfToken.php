<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Webhook público de Meta (WhatsApp Cloud API) — Meta llama esto
        // directamente sin sesión ni token CSRF, la verificación de
        // identidad la hace WhatsappWebhookController vía webhook_verify_token.
        'whatsapp/webhook',
        // Webhook del buzón de correo (Hostinger "Agentic Mail" / hMail) —
        // llamado directamente por Hostinger sin sesión ni token CSRF.
        'webhooks/email-bounce',
    ];
}
