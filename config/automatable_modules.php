<?php

/**
 * Registro central de módulos automatizables (Fase 16).
 *
 * Array asociativo indexado por el `type` de Workflow (mismo string libre
 * que ya usa `Workflow.type` en BD, sin enum/whitelist). Cada entrada
 * describe un modelo de negocio que puede disparar/participar en
 * automatizaciones:
 *
 *   - model              FQCN del modelo Eloquent.
 *   - label              Etiqueta legible para UI (selector de módulo, etc.).
 *   - group              Agrupación visual: 'CRM' | 'Ecommerce' | 'Servicios' | 'ERP'.
 *   - supports_stale     bool. Si el módulo soporta el trigger "sin actividad
 *                        en N horas" (comando automatable-modules:tick).
 *   - stale_field        string|null. Columna datetime usada para calcular
 *                        "obsoleto" cuando supports_stale=true.
 *   - customer_relation  string|null. Nombre de la relación Eloquent hacia
 *                        Customer, si existe, para que acciones genéricas
 *                        (p. ej. send_email) puedan resolver el destinatario
 *                        sin hardcodear el modelo.
 *   - relations          array. Nombres de relaciones Eloquent a exponer
 *                        como variables anidadas (p. ej. para tokens
 *                        {{ customer.email }} en Fase 17+).
 *
 * Agregar un módulo nuevo a partir de ahora es una entrada de configuración,
 * no código nuevo: AutomatableModelObserver (genérico) se registra en cada
 * modelo del registro desde AppServiceProvider::boot().
 */

return [

    // === CRM ===

    'deal' => [
        'model'             => \App\Models\Deal::class,
        'label'             => 'Negocio',
        'group'             => 'CRM',
        'supports_stale'    => true,
        'stale_field'       => 'updated_at',
        'customer_relation' => 'customer',
        'relations'         => ['customer', 'pipeline', 'stage'],
    ],

    'whatsapp_message' => [
        'model'             => \App\Models\WhatsappMessage::class,
        'label'             => 'Mensaje de WhatsApp',
        'group'             => 'CRM',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => ['conversation'],
    ],

    'whatsapp_conversation' => [
        'model'             => \App\Models\WhatsappConversation::class,
        'label'             => 'Conversación de WhatsApp',
        'group'             => 'CRM',
        'supports_stale'    => true,
        'stale_field'       => 'last_message_at',
        'customer_relation' => 'customer',
        'relations'         => ['customer', 'account'],
    ],

    // === ECOMMERCE (Fase 20a - rellenar aqui) ===

    'products' => [
        'model'             => \App\Models\Products::class,
        'label'             => 'Producto',
        'group'             => 'Ecommerce',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => ['category', 'brand', 'images', 'documents'],
    ],

    'category' => [
        'model'             => \App\Models\Category::class,
        'label'             => 'Categoría',
        'group'             => 'Ecommerce',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => ['parent', 'children'],
    ],

    'brand' => [
        'model'             => \App\Models\Brand::class,
        'label'             => 'Marca',
        'group'             => 'Ecommerce',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => [],
    ],

    'collection' => [
        'model'             => \App\Models\Collection::class,
        'label'             => 'Colección',
        'group'             => 'Ecommerce',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => ['rules', 'manualProducts'],
    ],

    'collection_rule' => [
        'model'             => \App\Models\CollectionRule::class,
        'label'             => 'Regla de colección',
        'group'             => 'Ecommerce',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => ['collection'],
    ],

    'product_image' => [
        'model'             => \App\Models\ProductImage::class,
        'label'             => 'Imagen de producto',
        'group'             => 'Ecommerce',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => [],
    ],

    'product_document' => [
        'model'             => \App\Models\ProductDocument::class,
        'label'             => 'Documento de producto',
        'group'             => 'Ecommerce',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => [],
    ],

    'cart' => [
        'model'             => \App\Models\Cart::class,
        'label'             => 'Carrito',
        'group'             => 'Ecommerce',
        'supports_stale'    => true,
        'stale_field'       => 'last_activity_at',
        'customer_relation' => 'customer',
        'relations'         => ['customer', 'items'],
        'extra_fields'      => ['has_email', 'is_still_pending'],
    ],

    'cart_item' => [
        'model'             => \App\Models\CartItem::class,
        'label'             => 'Artículo de carrito',
        'group'             => 'Ecommerce',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => [],
    ],

    'store_order' => [
        'model'             => \App\Models\StoreOrder::class,
        'label'             => 'Pedido de tienda',
        'group'             => 'Ecommerce',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => 'customer',
        'relations'         => ['customer', 'items', 'paymentMethod', 'shipment'],
    ],

    'shipment' => [
        'model'             => \App\Models\Shipment::class,
        'label'             => 'Envío',
        'group'             => 'Ecommerce',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => ['storeOrder', 'carrier'],
    ],

    'carrier' => [
        'model'             => \App\Models\Delivery::class,
        'label'             => 'Transportista',
        'group'             => 'Ecommerce',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => [],
    ],

    'store_order_item' => [
        'model'             => \App\Models\StoreOrderItem::class,
        'label'             => 'Artículo de pedido de tienda',
        'group'             => 'Ecommerce',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => [],
    ],

    'quote' => [
        'model'             => \App\Models\Quote::class,
        'label'             => 'Cotización',
        'group'             => 'Ecommerce',
        'supports_stale'    => true,
        'stale_field'       => 'sent_at',
        'customer_relation' => 'customer',
        'relations'         => ['customer', 'items', 'salesOrders'],
        'extra_fields'      => ['is_expired', 'has_service_items', 'has_product_items', 'has_email', 'has_whatsapp'],
    ],

    'quote_item' => [
        'model'             => \App\Models\QuoteItem::class,
        'label'             => 'Artículo de cotización',
        'group'             => 'Ecommerce',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => [],
    ],

    'sales_order' => [
        'model'             => \App\Models\SalesOrder::class,
        'label'             => 'Orden de venta',
        'group'             => 'Ecommerce',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => 'customer',
        'relations'         => ['customer', 'items', 'quote'],
    ],

    'sales_order_item' => [
        'model'             => \App\Models\SalesOrderItem::class,
        'label'             => 'Artículo de orden de venta',
        'group'             => 'Ecommerce',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => [],
    ],

    'customer' => [
        'model'             => \App\Models\Customer::class,
        'label'             => 'Cliente',
        'group'             => 'Ecommerce',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => ['customer_addresses', 'quotes', 'deals'],
    ],

    'customer_address' => [
        'model'             => \App\Models\CustomerAddress::class,
        'label'             => 'Dirección de cliente',
        'group'             => 'Ecommerce',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => 'customer',
        'relations'         => [],
    ],

    'customer_portal_request' => [
        'model'             => \App\Models\CustomerPortalRequest::class,
        'label'             => 'Solicitud de portal de cliente',
        'group'             => 'Ecommerce',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => 'customer',
        'relations'         => ['customer'],
    ],

    // === SERVICIOS (Fase 20b - rellenar aqui) ===

    'service_page' => [
        'model'             => \App\Models\ServicePage::class,
        'label'             => 'Página de servicio',
        'group'             => 'Servicios',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => ['sections'],
    ],

    'service_section' => [
        'model'             => \App\Models\ServiceSection::class,
        'label'             => 'Sección de página de servicio',
        'group'             => 'Servicios',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => ['servicePage'],
    ],

    'technical_service' => [
        'model'             => \App\Models\TechnicalService::class,
        'label'             => 'Servicio técnico',
        'group'             => 'Servicios',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => 'customer',
        'relations'         => ['customer', 'serviceType', 'fromQuote', 'createdBy'],
    ],

    'service_log' => [
        'model'             => \App\Models\ServiceLog::class,
        'label'             => 'Bitácora de servicio técnico',
        'group'             => 'Servicios',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => ['service', 'performedBy'],
    ],

    'service_material_planned' => [
        'model'             => \App\Models\ServiceMaterialPlanned::class,
        'label'             => 'Material planeado de servicio',
        'group'             => 'Servicios',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => ['service', 'product'],
    ],

    'service_report' => [
        'model'             => \App\Models\ServiceReport::class,
        'label'             => 'Reporte de servicio',
        'group'             => 'Servicios',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => 'customer',
        'relations'         => ['customer', 'service', 'measurements', 'activity', 'customFields', 'images', 'assignedUser', 'createdBy'],
    ],

    'service_report_activity' => [
        'model'             => \App\Models\ServiceReportActivity::class,
        'label'             => 'Actividad de reporte de servicio',
        'group'             => 'Servicios',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => ['report'],
    ],

    'service_report_image' => [
        'model'             => \App\Models\ServiceReportImage::class,
        'label'             => 'Imagen de reporte de servicio',
        'group'             => 'Servicios',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => ['report'],
    ],

    'service_report_measurement' => [
        'model'             => \App\Models\ServiceReportMeasurement::class,
        'label'             => 'Medición de reporte de servicio',
        'group'             => 'Servicios',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => ['report'],
    ],

    'service_report_custom_field' => [
        'model'             => \App\Models\ServiceReportCustomField::class,
        'label'             => 'Campo personalizado de reporte de servicio',
        'group'             => 'Servicios',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => ['report'],
    ],

    // === ERP (Fase 20c - rellenar aqui) ===

    'supplier' => [
        'model'             => \App\Models\Supplier::class,
        'label'             => 'Proveedor',
        'group'             => 'ERP',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => [],
    ],

    'supplier_product' => [
        'model'             => \App\Models\SupplierProduct::class,
        'label'             => 'Producto de proveedor',
        'group'             => 'ERP',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => [],
    ],

    'purchase_order' => [
        'model'             => \App\Models\PurchaseOrder::class,
        'label'             => 'Orden de compra',
        'group'             => 'ERP',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => ['supplier', 'items', 'createdBy'],
    ],

    'purchase_order_item' => [
        'model'             => \App\Models\PurchaseOrderItem::class,
        'label'             => 'Partida de orden de compra',
        'group'             => 'ERP',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => [],
    ],

    'inventory_movement' => [
        'model'             => \App\Models\InventoryMovement::class,
        'label'             => 'Movimiento de inventario',
        'group'             => 'ERP',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => ['warehouse', 'product', 'logs'],
    ],

    'inventory_log' => [
        'model'             => \App\Models\InventoryLog::class,
        'label'             => 'Bitácora de inventario',
        'group'             => 'ERP',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => [],
    ],

    'warehouse_product_stock' => [
        'model'             => \App\Models\WarehouseProductStock::class,
        'label'             => 'Stock de almacén',
        'group'             => 'ERP',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => ['warehouse', 'product'],
    ],

    'chemical_projection' => [
        'model'             => \App\Models\ChemicalProjection::class,
        'label'             => 'Proyección química',
        'group'             => 'ERP',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => 'customer',
        'relations'         => ['customer', 'product'],
    ],

    'material_delivery_report' => [
        'model'             => \App\Models\MaterialDeliveryReport::class,
        'label'             => 'Reporte de entrega de material',
        'group'             => 'ERP',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => 'customer',
        'relations'         => ['customer', 'salesOrder', 'items', 'images', 'createdBy'],
    ],

    'material_delivery_report_item' => [
        'model'             => \App\Models\MaterialDeliveryReportItem::class,
        'label'             => 'Partida de reporte de entrega',
        'group'             => 'ERP',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => [],
    ],

    'material_delivery_report_image' => [
        'model'             => \App\Models\MaterialDeliveryReportImage::class,
        'label'             => 'Imagen de reporte de entrega',
        'group'             => 'ERP',
        'supports_stale'    => false,
        'stale_field'       => null,
        'customer_relation' => null,
        'relations'         => [],
    ],

];
