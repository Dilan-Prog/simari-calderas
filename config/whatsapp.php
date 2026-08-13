<?php

/**
 * Configuración estática del módulo de WhatsApp (Fase 11-13 del plan).
 *
 * `approved_templates`: catálogo fijo de plantillas aprobadas en Meta
 * Business Manager que el Embudo de Venta ofrece para iniciar/reabrir
 * conversaciones fuera de la ventana de 24h. No es un catálogo dinámico
 * sincronizado con Meta (eso requeriría credenciales reales y una llamada
 * al endpoint de plantillas de la Graph API) — decisión confirmada del
 * plan (Fase 13): "de momento una lista fija en config". Editar este
 * arreglo manualmente cuando se aprueben nuevas plantillas.
 */
return [
    'approved_templates' => [
        [
            'name'   => 'saludo_inicial',
            'label'  => 'Saludo inicial',
            'params' => ['Nombre del contacto'],
        ],
        [
            'name'   => 'seguimiento_cotizacion',
            'label'  => 'Seguimiento de cotización',
            'params' => ['Nombre del contacto', 'Folio de cotización'],
        ],
        [
            'name'   => 'confirmacion_pedido',
            'label'  => 'Confirmación de pedido',
            'params' => ['Nombre del contacto', 'Folio de pedido'],
        ],
    ],
];
