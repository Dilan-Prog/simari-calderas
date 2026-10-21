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
        // QR-AISLADO · OBLIGATORIO NO TOCAR · INICIO
        // Vía WhatsApp Web/Baileys (QR, NO oficial). DESACTIVADA a propósito: este proyecto usa SOLO la API
        // oficial de Meta (Cloud API). Se deja COMENTADA para no interferir con el código original.
        // No la borres ni la reactives sin consultar. Para volver a usarla: descomentar todos los bloques
        // marcados con "QR-AISLADO" (buscar con grep -rn "QR-AISLADO" app routes resources config tests).
        // Webhook público del microservicio Node.js de WhatsApp vía
        // Baileys/QR (whatsapp-qr-service/) -- mismo criterio que el
        // webhook de Meta arriba: llamado directamente por el
        // microservicio, sin sesión ni token CSRF.
        // 'whatsapp-qr/webhook',
        // QR-AISLADO · OBLIGATORIO NO TOCAR · FIN
        // Webhook del buzón de correo (Hostinger "Agentic Mail" / hMail) —
        // llamado directamente por Hostinger sin sesión ni token CSRF.
        'webhooks/email-bounce',
        // Webhook de notificaciones de Mercado Pago — Mercado Pago llama
        // esto directamente sin sesión ni token CSRF, la verificación de
        // identidad la hace MercadoPagoWebhookController vía x-signature.
        'webhooks/mercadopago',
    ];
}
