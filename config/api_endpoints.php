<?php

/**
 * Catálogo de referencia (solo lectura, no se usa para autorizar nada) de
 * qué endpoint(s) real(es) habilita cada ability de config/api_abilities.php
 * -- se muestra en el panel "Integraciones > API / N8N" junto a cada
 * checkbox de abilities, para que al armar un flujo de N8N quede claro qué
 * ruta llamar sin tener que ir a leer routes/api/*.php.
 *
 * Mismo criterio que api_abilities.php: agregar una ruta/ability nueva es
 * una entrada aquí también -- no se genera automáticamente desde
 * Route::getRoutes() a propósito, para no acoplar este archivo (puramente
 * informativo) al árbol de rutas real.
 *
 * Estructura: 'ability' => ['METODO /ruta', ...] (ruta relativa a /api/v1).
 */

return [
    'workflows:read' => [
        'GET /workflows',
        'GET /workflow-enrollments/{id}',
    ],
    'workflows:trigger' => [
        'POST /workflows/{workflow}/enroll',
    ],

    'deals:read' => [
        'GET /deals',
        'GET /deals/{deal}',
        'GET /deals/{deal}/contacts',
        'GET /pipeline-stages',
        'GET /tasks',
    ],
    'deals:write' => [
        'POST /deals',
        'PUT /deals/{deal}',
        'POST /deals/{deal}/move-stage',
        'POST /deals/{deal}/contacts',
    ],
    'tasks:write' => [
        'POST /tasks',
    ],

    'quotes:read' => [
        'GET /quotes',
        'GET /quotes/{quote}',
    ],
    'quotes:write' => [
        'POST /quotes',
        'POST /quotes/{quote}/accept',
    ],
    'sales-orders:read' => [
        'GET /sales-orders',
        'GET /sales-orders/{salesOrder}',
    ],
    'store-orders:read' => [
        'GET /store-orders',
        'GET /store-orders/{storeOrder}',
    ],

    'products:read' => [
        'GET /products',
        'GET /products/{product}',
    ],
    'collections:read' => [
        'GET /collections',
        'GET /collections/{collection}',
    ],

    'customers:read' => [
        'GET /customers',
        'GET /customers/{customer}',
        'GET /customers/{customer}/addresses',
    ],
    'customers:write' => [
        'POST /customers',
        'PUT /customers/{customer}',
    ],

    'whatsapp:read' => [
        'GET /whatsapp/conversations',
    ],
    'whatsapp:send' => [
        'POST /whatsapp/accounts/{account}/send',
    ],

    'email-campaigns:trigger' => [
        'POST /email-campaigns/{campaign}/send',
    ],
    'email-campaigns:read' => [
        'GET /email-sends',
    ],
];
