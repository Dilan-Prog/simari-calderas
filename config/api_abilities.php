<?php

/**
 * Catálogo central de abilities (scopes) para tokens Sanctum emitidos a
 * ApiClient (App\Http\Controllers\Backend\IntegrationController, panel
 * "Integraciones > API / N8N"). Mismo espíritu que config/automatable_modules.php:
 * una sola fuente de verdad para (a) el formulario donde se elige qué puede
 * hacer un token nuevo y (b) el middleware `token.ability:xxx`
 * (App\Http\Middleware\EnsureTokenAbility) que protege cada ruta de
 * routes/api.php.
 *
 * Agregar una ability nueva es una entrada aquí + usar `token.ability:esa-ability`
 * en la ruta correspondiente -- no requiere tocar el formulario de emisión.
 *
 * Estructura: 'grupo' => ['ability' => 'Etiqueta legible'].
 */

return [
    'Workflows' => [
        'workflows:read'    => 'Leer workflows e inscripciones',
        'workflows:trigger' => 'Inscribir/disparar workflows',
    ],

    'CRM' => [
        'deals:read'   => 'Leer negocios (deals)',
        'deals:write'  => 'Crear/editar negocios y mover de etapa',
        'tasks:write'  => 'Crear tareas',
    ],

    'Cotizaciones y Pedidos' => [
        'quotes:read'       => 'Leer cotizaciones',
        'quotes:write'      => 'Crear cotizaciones, aceptarlas y registrar recordatorios enviados',
        'sales-orders:read' => 'Leer pedidos de venta',
        'store-orders:read' => 'Leer pedidos de la tienda en línea',
    ],

    'Catálogo' => [
        'products:read'    => 'Leer catálogo de productos y stock',
        'collections:read' => 'Leer colecciones',
    ],

    'Clientes' => [
        'customers:read'  => 'Leer clientes',
        'customers:write' => 'Crear/editar clientes',
    ],

    'WhatsApp' => [
        'whatsapp:read' => 'Leer conversaciones y mensajes',
        'whatsapp:send' => 'Enviar mensajes de WhatsApp',
    ],

    'Marketing' => [
        'email-campaigns:trigger' => 'Disparar envío de campañas de correo',
        'email-campaigns:read'    => 'Leer estadísticas de envíos/aperturas/clicks',
        'email-templates:read'    => 'Leer plantillas de correo electrónico',
    ],
];
