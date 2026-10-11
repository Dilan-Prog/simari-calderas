<?php

/*
 | Módulos nuevos del ERP (Operaciones). Alimenta tres cosas:
 |   1) el grupo "Operaciones" del sidebar,
 |   2) los tiles del Launchpad (/admin/operaciones),
 |   3) la barra de comandos Ctrl+K.
 |
 | Cada entrada:
 |   permission : clave del módulo en config/modules.php (permiso)
 |   label      : texto
 |   route      : ruta admin del índice. Si la ruta aún no existe, la entrada se oculta sola.
 |   area       : área del launchpad
 |   icon       : contenido SVG (path) de 24x24 estilo lucide
 |   kpi        : (opcional) clase de servicio con método estático kpi(): ?array{value,label,tone}
 |                tone: ok | warn | bad | neutral
 */
return [
    'areas' => [
        'planeacion' => 'Planeación',
        'produccion' => 'Producción',
        'logistica'  => 'Logística',
        'finanzas'   => 'Finanzas',
        'almacen'    => 'Almacén',
        'ventas'     => 'Ventas',
    ],

    'items' => [
        ['permission' => 'projects',            'label' => 'Proyectos',                'route' => 'admin.projects.index',            'area' => 'planeacion', 'icon' => '<path d="M3 3v18h18"/><path d="M7 14l4-4 3 3 5-6"/>'],
        ['permission' => 'boms',                'label' => 'Listas de materiales',     'route' => 'admin.boms.index',                'area' => 'planeacion', 'icon' => '<path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/>'],
        ['permission' => 'mrp',                 'label' => 'MRP · Materiales',         'route' => 'admin.mrp.index',                 'area' => 'planeacion', 'icon' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.3 7 12 12l8.7-5"/><path d="M12 22V12"/>'],

        ['permission' => 'production-orders',   'label' => 'Órdenes de producción',    'route' => 'admin.production-orders.index',   'area' => 'produccion', 'icon' => '<path d="M2 20h20"/><path d="M5 20V8l5 4V8l5 4V4h4v16"/>'],
        ['permission' => 'service-calendar',    'label' => 'Calendario de servicios',  'route' => 'admin.service-calendar.index',    'area' => 'produccion', 'icon' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>'],

        ['permission' => 'pickups',             'label' => 'Recolecciones',            'route' => 'admin.pickups.index',             'area' => 'logistica',  'icon' => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>'],

        ['permission' => 'receivables',         'label' => 'Cuentas por cobrar',       'route' => 'admin.receivables.index',         'area' => 'finanzas',   'icon' => '<path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>'],
        ['permission' => 'payables',            'label' => 'Cuentas por pagar',        'route' => 'admin.payables.index',            'area' => 'finanzas',   'icon' => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>'],
        ['permission' => 'payments',            'label' => 'Pagos',                    'route' => 'admin.payments.index',            'area' => 'finanzas',   'icon' => '<path d="M20 6 9 17l-5-5"/>'],

        ['permission' => 'warehouse-transfers', 'label' => 'Traspasos de almacén',     'route' => 'admin.warehouse-transfers.index', 'area' => 'almacen',    'icon' => '<path d="M17 3l4 4-4 4"/><path d="M3 7h18"/><path d="M7 21l-4-4 4-4"/><path d="M21 17H3"/>'],
        ['permission' => 'stock-counts',        'label' => 'Conteos físicos',          'route' => 'admin.stock-counts.index',        'area' => 'almacen',    'icon' => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>'],

        ['permission' => 'leads-inbox',         'label' => 'Bandeja de leads',         'route' => 'admin.leads-inbox.index',         'area' => 'ventas',     'icon' => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>'],
        ['permission' => 'google-conversions',  'label' => 'Conversiones a Google',    'route' => 'admin.google-conversions.index',  'area' => 'ventas',     'icon' => '<path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/>'],
    ],
];
