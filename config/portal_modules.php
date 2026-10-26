<?php

/*
|--------------------------------------------------------------------------
| Módulos del portal de clientes (/customer)
|--------------------------------------------------------------------------
| Catálogo de módulos sobre los que se asignan permisos a los roles de los
| usuarios del portal (equivalente a config/modules.php del admin).
|
| El titular de la cuenta (el registro Customer) siempre tiene todos los
| permisos; los roles solo aplican a los usuarios adicionales de su equipo.
|
| 'actions' => acciones que tiene sentido asignar en ese módulo.
| 'owner_only' => solo el titular puede usarlo (no se ofrece en los roles).
*/

return [
    'service-requests' => [
        'name' => 'Solicitar servicio',
        'actions' => ['view', 'create'],
    ],
    'service-reports' => [
        'name' => 'Reportes de servicio',
        'actions' => ['view'],
    ],
    'technical-services' => [
        'name' => 'Servicios técnicos',
        'actions' => ['view'],
    ],
    'calendar' => [
        'name' => 'Calendario',
        'actions' => ['view'],
    ],
    'history' => [
        'name' => 'Historial de servicios',
        'actions' => ['view'],
    ],
    'quotes' => [
        'name' => 'Cotizaciones',
        'actions' => ['view'],
    ],
    'deliveries' => [
        'name' => 'Entregas de material',
        'actions' => ['view'],
    ],
    'timeline' => [
        'name' => 'Mi expediente',
        'actions' => ['view'],
    ],
    'monitoring' => [
        'name' => 'Monitoreo',
        'actions' => ['view', 'create', 'edit', 'delete'],
    ],
    'equipment' => [
        'name' => 'Mis equipos',
        'actions' => ['view', 'create', 'edit', 'delete'],
    ],
    'orders' => [
        'name' => 'Mis pedidos',
        'actions' => ['view'],
    ],
    'addresses' => [
        'name' => 'Direcciones',
        'actions' => ['view', 'create', 'edit', 'delete'],
    ],
    'water-analysis' => [
        'name' => 'Reporte de análisis químico',
        'actions' => ['view', 'create', 'edit', 'delete'],
    ],
    'purchase-orders' => [
        'name' => 'Órdenes de compra',
        'actions' => ['view', 'create', 'delete'],
    ],
    'team' => [
        'name' => 'Usuarios y roles',
        'actions' => ['view', 'create', 'edit', 'delete'],
        'owner_only' => true,
    ],
];
