<?php

/*
 | Conversiones a Google Ads vía Data Manager API (módulo "Conversiones a Google").
 | Las credenciales NO viven aquí: se leen del módulo Credenciales (tipo
 | "google_data_manager", payload JSON) o, si no existe, de la tabla settings
 | (claves google_ads.*). Ver App\Services\Google\DataManagerUploader.
 */
return [
    // Endpoint de ingesta de eventos (configurable por si Google cambia la versión).
    'endpoint'       => env('GOOGLE_DATA_MANAGER_ENDPOINT', 'https://datamanager.googleapis.com/v1/events:ingest'),
    'token_endpoint' => env('GOOGLE_OAUTH_TOKEN_ENDPOINT', 'https://oauth2.googleapis.com/token'),

    // Tipo de Credential que guarda: client_id, client_secret, refresh_token,
    // account_id (cliente Google Ads), login_account_id (opcional, MCC),
    // conversion_actions: {cotizado: "ID", venta_ganada: "ID"}.
    'credential_type' => 'google_data_manager',

    // IDs de acción de conversión por defecto (si no vienen en la credencial).
    'conversion_actions' => [
        'cotizado'     => env('GOOGLE_CONVERSION_ACTION_COTIZADO'),
        'venta_ganada' => env('GOOGLE_CONVERSION_ACTION_VENTA_GANADA'),
    ],

    // Etapas de pipeline que cuentan como "Cotizado" (slug) además de las cuyo nombre contenga "cotizad".
    'quoted_stage_slugs' => ['cotizado', 'cotizada', 'cotizacion', 'quoted', 'propuesta-enviada'],

    'gclid_window_days' => 90,
    'max_attempts'      => 3,
    'backoff_base_seconds' => 120, // espera = base * 2^(intento-1)
    'expiring_days'     => 14,     // "por vencer" = GCLID que vence en <= N días
    'timezone'          => 'America/Mexico_City',
    'http_timeout'      => 20,
];
