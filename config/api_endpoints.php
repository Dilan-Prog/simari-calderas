<?php

/**
 * Catálogo de referencia (solo lectura, no se usa para autorizar nada) de
 * qué endpoint(s) real(es) habilita cada ability de config/api_abilities.php
 * -- se muestra en el panel "Integraciones > API / N8N", tanto al generar un
 * token nuevo (junto a cada checkbox) como en cada token ya existente (como
 * guía de qué puede llamar ESE token concreto, con sus parámetros), para que
 * al armar un flujo de N8N quede claro qué ruta/parámetros usar sin tener
 * que ir a leer los controllers de routes/api/*.php.
 *
 * Mismo criterio que api_abilities.php: agregar una ruta/ability nueva es
 * una entrada aquí también -- no se genera automáticamente desde
 * Route::getRoutes() a propósito, para no acoplar este archivo (puramente
 * informativo) al árbol de rutas real.
 *
 * Estructura: 'ability' => [ ['method' => 'GET', 'path' => '/ruta' (relativa
 * a /api/v1), 'params' => 'descripción corta, * = requerido', 'note' =>
 * opcional, detalle de comportamiento no obvio], ... ].
 */

return [
    'workflows:read' => [
        ['method' => 'GET', 'path' => '/workflows', 'params' => 'sin parámetros -- lista workflows activos'],
        ['method' => 'GET', 'path' => '/workflow-enrollments/{id}', 'params' => 'sin parámetros'],
    ],
    'workflows:trigger' => [
        ['method' => 'POST', 'path' => '/workflows/{workflow}/enroll', 'params' => 'enrollable_id* (integer), context (objeto libre, opcional)'],
    ],

    'deals:read' => [
        ['method' => 'GET', 'path' => '/deals', 'params' => 'sin parámetros requeridos'],
        ['method' => 'GET', 'path' => '/deals/{deal}', 'params' => 'sin parámetros'],
        ['method' => 'GET', 'path' => '/deals/{deal}/contacts', 'params' => 'sin parámetros'],
        ['method' => 'GET', 'path' => '/pipeline-stages', 'params' => 'sin parámetros'],
        ['method' => 'GET', 'path' => '/tasks', 'params' => 'sin parámetros'],
    ],
    'deals:write' => [
        ['method' => 'POST', 'path' => '/deals', 'params' => 'name*, pipeline_stage_id*, value, customer_id'],
        ['method' => 'PUT', 'path' => '/deals/{deal}', 'params' => 'mismos campos que crear'],
        ['method' => 'POST', 'path' => '/deals/{deal}/move-stage', 'params' => 'pipeline_stage_id*'],
        ['method' => 'POST', 'path' => '/deals/{deal}/contacts', 'params' => 'name*, email, phone'],
    ],
    'tasks:write' => [
        ['method' => 'POST', 'path' => '/tasks', 'params' => 'title*, taskable_type*, taskable_id*, assigned_to, description, due_at, status', 'note' => 'taskable_type debe ser un FQCN registrado en config/automatable_modules.php (ej. App\\\\Models\\\\Quote)'],
    ],

    'quotes:read' => [
        ['method' => 'GET', 'path' => '/quotes', 'params' => 'status, customer_id, per_page (query, todos opcionales)'],
        ['method' => 'GET', 'path' => '/quotes/{id}', 'params' => 'sin parámetros -- incluye items y sales_orders generados'],
    ],
    'quotes:write' => [
        ['method' => 'POST', 'path' => '/quotes', 'params' => 'customer_id*, guest_name*, tax_rate*, currency* (MXN|USD), exchange_rate*, items* (array: product_name*, quantity*, unit_price*, line_total* por cada uno), guest_email, guest_phone, guest_company, guest_rfc, valid_until, discount_total, isr_retention_rate, notes, terms_conditions'],
        ['method' => 'POST', 'path' => '/quotes/{quote}/accept', 'params' => 'sin body', 'note' => 'idempotente -- genera el Pedido de venta (SalesOrder) automáticamente la primera vez; si ya estaba aceptada, no hace nada de nuevo'],
    ],
    'sales-orders:read' => [
        ['method' => 'GET', 'path' => '/sales-orders', 'params' => 'status, customer_id, quote_id, per_page (query, opcionales)', 'note' => 'solo lectura -- se generan automáticamente al aceptar una cotización, no vía API'],
        ['method' => 'GET', 'path' => '/sales-orders/{id}', 'params' => 'sin parámetros'],
    ],
    'store-orders:read' => [
        ['method' => 'GET', 'path' => '/store-orders', 'params' => 'status, customer_id, per_page (query, opcionales)', 'note' => 'pedidos de la tienda en línea (carrito/checkout del sitio público), no de cotización'],
        ['method' => 'GET', 'path' => '/store-orders/{id}', 'params' => 'sin parámetros'],
    ],

    'products:read' => [
        ['method' => 'GET', 'path' => '/products', 'params' => 'sin parámetros requeridos'],
        ['method' => 'GET', 'path' => '/products/{product}', 'params' => 'sin parámetros'],
    ],
    'collections:read' => [
        ['method' => 'GET', 'path' => '/collections', 'params' => 'sin parámetros requeridos'],
        ['method' => 'GET', 'path' => '/collections/{collection}', 'params' => 'sin parámetros'],
    ],

    'customers:read' => [
        ['method' => 'GET', 'path' => '/customers', 'params' => 'email, rfc, status, per_page (query, todos opcionales)'],
        ['method' => 'GET', 'path' => '/customers/{id}', 'params' => 'sin parámetros'],
        ['method' => 'GET', 'path' => '/customers/{id}/addresses', 'params' => 'sin parámetros'],
    ],
    'customers:write' => [
        ['method' => 'POST', 'path' => '/customers', 'params' => 'first_name*, company*, phone*, document_type* (ine|pasaporte|curp|cfdi), source* (web|whatsapp|admin|campaña|referido), last_name, email, rfc, tipo_persona (fisica|moral), status (active|inactive|suspended), notes'],
        ['method' => 'PUT', 'path' => '/customers/{id}', 'params' => 'mismos campos que crear, completos'],
    ],

    'whatsapp:read' => [
        ['method' => 'GET', 'path' => '/whatsapp/conversations', 'params' => 'sin parámetros requeridos'],
    ],
    'whatsapp:send' => [
        ['method' => 'POST', 'path' => '/whatsapp/accounts/{account}/send', 'params' => 'to*, message* (o template, según cuenta)'],
    ],

    'email-campaigns:trigger' => [
        ['method' => 'POST', 'path' => '/email-campaigns/{campaign}/send', 'params' => 'sin body', 'note' => 'dispara el envío a todos los destinatarios suscritos de una campaña ya creada en el admin -- no crea la campaña'],
    ],
    'email-campaigns:read' => [
        ['method' => 'GET', 'path' => '/email-sends', 'params' => 'campaign_id, per_page (query, opcionales)'],
    ],
    'email-templates:read' => [
        ['method' => 'GET', 'path' => '/email-templates', 'params' => 'type (other|secuencia|transaccional), per_page (query, opcionales)'],
        ['method' => 'GET', 'path' => '/email-templates/{id}', 'params' => 'sin parámetros', 'note' => 'incluye html_body completo (con variables {{...}} sin sustituir)'],
    ],
];
