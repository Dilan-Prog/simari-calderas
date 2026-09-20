<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Token del webhook de Hostinger "Agentic Mail" (Authorization: Bearer),
    // mostrado una sola vez al crear el webhook en su panel. Se compara en
    // EmailBounceWebhookController::receive() para rechazar llamadas que no
    // vengan de Hostinger.
    'hostinger_mail' => [
        'webhook_token' => env('HOSTINGER_MAIL_WEBHOOK_TOKEN'),
    ],

    // Microservicio Node.js de WhatsApp vía Baileys/QR (whatsapp-qr-service/,
    // repo aparte) — segundo tipo de conexión junto a meta_cloud_api, ver
    // WhatsappBaileysService y WhatsappQrWebhookController. `secret` se envía
    // como "Authorization: Bearer <secret>" en ambas direcciones.
    'whatsapp_qr' => [
        'url' => env('WHATSAPP_QR_SERVICE_URL'),
        'secret' => env('WHATSAPP_QR_SHARED_SECRET'),
    ],

    // Credenciales de Mercado Pago. Cada "rol" es una Aplicación distinta
    // registrada en el panel de desarrolladores de Mercado Pago -- MP emite
    // un Public Key + Access Token (y opcionalmente un Webhook Secret)
    // *por aplicación*, no uno solo por cuenta: "api" es la app de Checkout
    // API (usada por el CardForm de Tarjeta) y "checkout_pro" es la app de
    // Checkout Pro (usada por el redirect de Wallet/Efectivo/Transferencia).
    // Agrega un valor más a 'roles' (y su propio sub-array abajo) si en el
    // futuro se necesita una 3ra aplicación -- app/Services/MercadoPago/
    // MercadoPagoPaymentService.php y el panel /admin/integraciones ya
    // iteran esta lista genéricamente, sin nombres de rol hardcodeados salvo
    // aquí y en IntegrationController::$mercadoPagoRoles (las etiquetas
    // legibles para el formulario).
    //
    // En producción se configuran desde /admin/integraciones
    // (MercadoPagoSettingsServiceProvider las sobreescribe en config() si
    // hay valores guardados en Setting, por rol); el .env queda como
    // respaldo para desarrollo local.
    'mercadopago' => [
        'roles' => ['api', 'checkout_pro'],
        'api' => [
            'mode' => env('MERCADOPAGO_API_MODE', 'sandbox'),
            'access_token' => env('MERCADOPAGO_API_ACCESS_TOKEN'),
            'public_key' => env('MERCADOPAGO_API_PUBLIC_KEY'),
            'webhook_secret' => env('MERCADOPAGO_API_WEBHOOK_SECRET'),
        ],
        'checkout_pro' => [
            'mode' => env('MERCADOPAGO_CHECKOUT_PRO_MODE', 'sandbox'),
            'access_token' => env('MERCADOPAGO_CHECKOUT_PRO_ACCESS_TOKEN'),
            'public_key' => env('MERCADOPAGO_CHECKOUT_PRO_PUBLIC_KEY'),
            'webhook_secret' => env('MERCADOPAGO_CHECKOUT_PRO_WEBHOOK_SECRET'),
        ],
    ],

];
