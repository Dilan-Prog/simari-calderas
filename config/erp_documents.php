<?php

/*
 | Registro de tipos de documento del "Flujo del documento" (estilo SAP).
 | La clave es lo que se guarda en document_links.from_type / to_type.
 |
 |  model  : clase Eloquent (si la clase aún no existe, el tipo se ignora)
 |  label  : nombre legible
 |  number : columna con el folio
 |  status : columna de estatus (opcional)
 |  route  : ruta admin de detalle (recibe el id); null = sin enlace
 |  stage  : orden aproximado en la cadena (para ordenar el flujo)
 */
return [
    'quote'                => ['model' => \App\Models\Quote::class,                  'label' => 'Cotización',          'number' => 'quote_number',   'status' => 'status', 'route' => 'admin.quotes.show',                   'stage' => 10],
    'project'              => ['model' => \App\Models\Project::class,                'label' => 'Proyecto',            'number' => 'project_number', 'status' => 'status', 'route' => 'admin.projects.show',                 'stage' => 20],
    'sales_order'          => ['model' => \App\Models\SalesOrder::class,             'label' => 'Pedido',              'number' => 'order_number',   'status' => 'status', 'route' => 'admin.sales-orders.show',             'stage' => 30],
    'material_requirement' => ['model' => \App\Models\MaterialRequirement::class,    'label' => 'Requerimiento',       'number' => 'number',         'status' => 'status', 'route' => 'admin.mrp.show',                      'stage' => 40],
    'production_order'     => ['model' => \App\Models\ProductionOrder::class,        'label' => 'Orden de producción', 'number' => 'number',         'status' => 'status', 'route' => 'admin.production-orders.show',        'stage' => 45],
    'purchase_order'       => ['model' => \App\Models\PurchaseOrder::class,          'label' => 'Orden de compra',     'number' => 'po_number',      'status' => 'status', 'route' => 'purchase-orders.show',                'stage' => 50],
    'technical_service'    => ['model' => \App\Models\TechnicalService::class,       'label' => 'Servicio técnico',    'number' => 'service_number', 'status' => 'status', 'route' => 'technical-services.show',             'stage' => 55],
    'material_delivery'    => ['model' => \App\Models\MaterialDeliveryReport::class, 'label' => 'Entrega de material', 'number' => 'report_number',  'status' => 'status', 'route' => 'admin.material-delivery-reports.show', 'stage' => 60],
    'shipment'             => ['model' => \App\Models\Shipment::class,               'label' => 'Envío',               'number' => 'guia',           'status' => 'status', 'route' => 'admin.shipments.show',                'stage' => 70],
    'pickup'               => ['model' => \App\Models\Pickup::class,                 'label' => 'Recolección',         'number' => 'number',         'status' => 'status', 'route' => 'admin.pickups.show',                  'stage' => 72],
    'receivable'           => ['model' => \App\Models\Receivable::class,             'label' => 'Cuenta por cobrar',   'number' => 'number',         'status' => 'status', 'route' => 'admin.receivables.show',              'stage' => 80],
    'payable'              => ['model' => \App\Models\Payable::class,                'label' => 'Cuenta por pagar',    'number' => 'number',         'status' => 'status', 'route' => 'admin.payables.show',                 'stage' => 85],
    'payment'              => ['model' => \App\Models\Payment::class,                'label' => 'Pago',                'number' => 'number',         'status' => 'status', 'route' => 'admin.payments.show',                 'stage' => 90],
];
