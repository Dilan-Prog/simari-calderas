<?php

use App\Http\Controllers\Admin\GoogleAdsController;
use App\Http\Controllers\Backend\AdminController;
use App\Http\Controllers\Backend\CategoryController;
use App\Http\Controllers\Backend\ClientManageController;
use App\Http\Controllers\Backend\AuditController;
use App\Http\Controllers\Backend\AbandonedCartController;
use App\Http\Controllers\Backend\DevOpsController;
use App\Http\Controllers\Backend\ProductController;
use App\Http\Controllers\Backend\ProductImportExportController;
use App\Http\Controllers\Backend\QuoteController;
use App\Http\Controllers\Backend\ServiceReportController;
use App\Http\Controllers\Backend\TechnicalServiceController;
use App\Http\Controllers\Backend\UserManageController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\BrandController;
use App\Http\Controllers\Backend\DeliveryController;
use App\Http\Controllers\Backend\RoleController;
use App\Http\Controllers\Backend\SupplierManageController;
use App\Http\Controllers\Backend\PurchaseOrderController;
use App\Http\Controllers\Backend\IntegrationController;
use App\Http\Controllers\Backend\SettingController;
use App\Http\Controllers\Backend\HomeSectionController;
use App\Http\Controllers\Backend\MenuController;
use App\Http\Controllers\Backend\CollectionController;
use App\Http\Controllers\Backend\ChemicalPlanningController;
use App\Http\Controllers\Backend\SalesOrderController;
use App\Http\Controllers\Backend\StoreOrderController;
use App\Http\Controllers\Backend\ShipmentController;
use App\Http\Controllers\Backend\ServicePageController;
use App\Http\Controllers\Backend\GalleryController;
use App\Http\Controllers\Backend\DuplicateImageController;
use App\Http\Controllers\Backend\MediaController;
use App\Http\Controllers\Backend\SupplierProductController;
use App\Http\Controllers\Backend\SupplierProductImportExportController;
use App\Http\Controllers\Backend\SupplierProductBulkEditController;
use App\Http\Controllers\Backend\ColumnPreferenceController;
use App\Http\Controllers\Backend\InventoryController;
use App\Http\Controllers\Backend\PaymentMethodController;
use App\Http\Controllers\Backend\ShippingRuleController;
use App\Http\Controllers\Backend\MaterialDeliveryReportController;
use App\Http\Controllers\Backend\PipelineController;
use App\Http\Controllers\Backend\DealController;
use App\Http\Controllers\Backend\WorkflowController;
use App\Http\Controllers\Backend\WorkflowExecutionController;
use App\Http\Controllers\Backend\CredentialController;
use App\Http\Controllers\Backend\WorkflowStepController;
use App\Http\Controllers\Backend\WorkflowVariableController;
use App\Http\Controllers\Backend\WorkflowCanvasNoteController;
use App\Http\Controllers\Backend\ExternalDatabaseController;
use App\Http\Controllers\Backend\WorkflowWebhookCredentialController;
use App\Http\Controllers\Backend\WebhookController;
use App\Http\Controllers\Backend\WorkflowRegisteredWebhookController;
use App\Http\Controllers\Backend\ErpSettingController;
use App\Http\Controllers\Backend\StatisticsController;
use App\Http\Controllers\Backend\EmailCampaignController;
use App\Http\Controllers\Backend\EmailMarketingController;
use App\Http\Controllers\Backend\EmailSequenceController;
use App\Http\Controllers\Backend\EmailTemplateController;
use App\Http\Controllers\Backend\EmailListController;
use App\Http\Controllers\Backend\ReportController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\GoalController;
use App\Http\Controllers\Backend\WhatsappAccountController;
use App\Http\Controllers\Backend\WhatsappFunnelController;
use App\Http\Controllers\Backend\TagController;
use App\Http\Controllers\Backend\TaskController;

// ============================================================
// Dashboard — sin permiso, todos los usuarios autenticados
// ============================================================
Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');


// ============================================================
// Roles
// ============================================================
Route::controller(RoleController::class)
    ->middleware('permission:roles')
    ->group(function () {
        Route::get('/roles', 'index')->name('roles.index');
        Route::get('/roles/mostrar-rol/{id}', 'show')->name('roles.show');
        Route::post('/roles/crear-rol', 'store')->name('roles.store')->middleware('permission:roles,create');
        Route::put('/roles/editar-rol/{id}', 'update')->name('roles.update')->middleware('permission:roles,edit');
        Route::delete('/roles/eliminar-rol/{id}', 'destroy')->name('roles.destroy')->middleware('permission:roles,delete');
    });

// ============================================================
// Usuarios
// ============================================================
Route::controller(UserManageController::class)
    ->middleware('permission:users')
    ->group(function () {
        Route::get('/usuarios', 'index')->name('users.index');
        Route::get('/usuarios/mostrar-usuario/{id}', 'show')->name('users.show');
        Route::post('/usuarios/crear-usuario', 'store')->name('users.store')->middleware('permission:users,create');
        Route::put('/usuarios/editar-usuario/{id}', 'update')->name('users.update')->middleware('permission:users,edit');
        Route::delete('/usuarios/eliminar-usuario/{id}', 'destroy')->name('users.destroy')->middleware('permission:users,delete');
    });

// ============================================================
// Clientes
// ============================================================
Route::controller(ClientManageController::class)
    ->middleware('permission:clients')
    ->group(function () {
        Route::get('/clientes', 'index')->name('clients.index');
        Route::post('/clientes/crear-usuario/', 'store')->name('clients.store')->middleware('permission:clients,create');
        Route::post('/clientes/parse-cfdi', 'parseCfdi')->name('clients.parse-cfdi');
        Route::get('/clientes/editar-cliente/{id}', 'edit')->name('clients.edit')->middleware('permission:clients,edit');
        Route::put('/clientes/editar-cliente/{id}', 'update')->name('clients.update')->middleware('permission:clients,edit');
        Route::delete('/clientes/eliminar-cliente/{id}', 'destroy')->name('clients.destroy')->middleware('permission:clients,delete');
        Route::get('/clientes/informacion/{id}', 'information')->name('clients.information');
        Route::post('/clientes/{id}/acceso', 'grantAccess')->name('clients.grant-access')->middleware('permission:clients,edit');
        Route::patch('/clientes/{id}/estado', 'updateStatus')->name('clients.update-status')->middleware('permission:clients,edit');
        Route::patch('/clientes/{id}/portal', 'updatePortalAccess')->name('clients.update-portal-access')->middleware('permission:clients,edit');
        Route::get('/clientes/{id}/solicitud-portal', 'portalRequestInfo')->name('clients.portal-request');
        Route::get('/clientes/{id}/constancia-portal', 'downloadPortalCertificate')->name('clients.portal-certificate');
    });

// ============================================================
// Proveedores
// ============================================================
Route::controller(SupplierManageController::class)
    ->middleware('permission:suppliers')
    ->group(function () {
        Route::get('/proveedores', 'index')->name('suppliers.index');
        Route::get('/proveedores/mostrar-proveedor/{id}', 'show')->name('suppliers.show');
        Route::post('/proveedores/crear-proveedor', 'store')->name('suppliers.store')->middleware('permission:suppliers,create');
        Route::get('/proveedores/editar-proveedor/{id}', 'edit')->name('suppliers.edit')->middleware('permission:suppliers,edit');
        Route::put('/proveedores/editar-proveedor/{id}', 'update')->name('suppliers.update')->middleware('permission:suppliers,edit');
        Route::delete('/proveedores/eliminar-proveedor/{id}', 'destroy')->name('suppliers.destroy')->middleware('permission:suppliers,delete');
        Route::get('/proveedores/informacion/{id}', 'information')->name('suppliers.information');
    });

// Vínculo proveedor-producto — registrado también aquí (permission:suppliers)
// porque la pantalla de Proveedores necesita poder llamarlo; el middleware
// permission: solo admite un módulo por ruta, así que se duplica el grupo
// en vez de tocarlo. Mismo controlador que la copia bajo permission:products.
Route::controller(SupplierProductController::class)
    ->middleware('permission:suppliers')
    ->prefix('proveedor-productos')
    ->name('supplier-products.')
    ->group(function () {
        Route::post('/', 'store')->name('store')->middleware('permission:suppliers,create');
        Route::put('/{id}', 'update')->name('update')->middleware('permission:suppliers,edit');
        Route::delete('/{id}', 'destroy')->name('destroy')->middleware('permission:suppliers,delete');
    });

// Proveedores Productos — import/export/editar en lote de la relación
// proveedor↔producto (tabla suppliers_products), bajo permission:suppliers
// igual que el resto del módulo de Proveedores.
Route::controller(SupplierProductBulkEditController::class)
    ->middleware('permission:suppliers')
    ->prefix('proveedores/productos/edicion-masiva')
    ->name('suppliers.products.bulk-edit.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/guardar', 'save')->name('save')->middleware('permission:suppliers,edit');
    });

Route::controller(SupplierProductImportExportController::class)
    ->middleware('permission:suppliers')
    ->prefix('proveedores/productos')
    ->name('suppliers.products.')
    ->group(function () {
        Route::get('/plantilla', 'downloadTemplate')->name('import.template');
        Route::post('/importar', 'import')->name('import')->middleware('permission:suppliers,create');
        Route::get('/exportar', 'export')->name('export');
    });

// ============================================================
// Productos
// ============================================================
Route::controller(ProductController::class)
    ->middleware('permission:products')
    ->group(function () {
        Route::get('/productos', 'index')->name('products.index');
        Route::get('/productos/nuevo', 'create')->name('products.create')->middleware('permission:products,create');
        Route::post('/productos/nuevo', 'store')->name('products.store')->middleware('permission:products,create');
        Route::get('/productos/editar/{id}', 'edit')->name('products.edit')->middleware('permission:products,edit');
        Route::put('/productos/editar/{id}', 'update')->name('products.update')->middleware('permission:products,edit');
        Route::delete('/productos/eliminar/{id}', 'destroy')->name('products.destroy')->middleware('permission:products,delete');
        Route::post('/productos/{id}/imagenes/reordenar', 'reorderImages')->name('products.images.reorder')->middleware('permission:products,edit');
        Route::get('/productos/imagenes/biblioteca', 'mediaLibrary')->name('products.images.library');
        Route::get('/productos/etiquetas/buscar', 'tagSuggestions')->name('products.tags.suggestions');
        Route::get('/productos/especificaciones/buscar', 'specNameSuggestions')->name('products.specs.suggestions');
        Route::get('/productos/faq-enlaces/buscar', 'faqLinkSearch')->name('products.faq-links.search');
        Route::post('/productos/bulk', 'bulkUpdate')->name('products.bulk')->middleware('permission:products,edit');
        Route::get('/productos/edicion-masiva', 'bulkEditIndex')->name('products.bulk-edit');
        Route::post('/productos/edicion-masiva/guardar', 'bulkEditSave')->name('products.bulk.save')->middleware('permission:products,edit');
        Route::post('/productos/edicion-masiva/vistas', 'storeBulkEditView')->name('products.bulk-edit.views.store')->middleware('permission:products,create');
        Route::put('/productos/edicion-masiva/vistas/{id}', 'updateBulkEditView')->name('products.bulk-edit.views.update')->middleware('permission:products,edit');
        Route::delete('/productos/edicion-masiva/vistas/{id}', 'destroyBulkEditView')->name('products.bulk-edit.views.destroy')->middleware('permission:products,delete');
        Route::post('/productos/edicion-masiva/asignar-proveedor', 'bulkAssignSupplier')->name('products.bulk-edit.assign-supplier')->middleware('permission:products,edit');
        Route::post('/productos/vistas', 'storeIndexView')->name('products.index-views.store')->middleware('permission:products,create');
        Route::put('/productos/vistas/{id}', 'updateIndexView')->name('products.index-views.update')->middleware('permission:products,edit');
        Route::delete('/productos/vistas/{id}', 'destroyIndexView')->name('products.index-views.destroy')->middleware('permission:products,delete');
    });

// Vínculo proveedor-producto — mismo controlador que la copia bajo
// permission:suppliers (ver arriba), duplicada aquí bajo permission:products
// para que la pantalla de Productos también pueda llamarla. Prefijo y
// nombre distintos (aunque apunten al mismo controlador) para que ambas
// rutas sean físicamente alcanzables y route() no sea ambiguo.
Route::controller(SupplierProductController::class)
    ->middleware('permission:products')
    ->prefix('productos/proveedor-productos')
    ->name('products.supplier-products.')
    ->group(function () {
        Route::post('/', 'store')->name('store')->middleware('permission:products,create');
        Route::put('/{id}', 'update')->name('update')->middleware('permission:products,edit');
        Route::delete('/{id}', 'destroy')->name('destroy')->middleware('permission:products,delete');
    });

// ============================================================
// Media — selector de imágenes compartido (biblioteca / subir / URL)
// ============================================================
Route::controller(MediaController::class)
    ->prefix('media')
    ->name('media.')
    ->group(function () {
        Route::get('/biblioteca', 'library')->name('library');
        Route::post('/subir', 'upload')->name('upload');
    });

// ============================================================
// Productos — Importar / Exportar Excel
// ============================================================
Route::controller(ProductImportExportController::class)
    ->middleware('permission:products')
    ->group(function () {
        Route::get('/productos/importar/plantilla', 'downloadTemplate')->name('products.import.template');
        Route::post('/productos/importar', 'import')->name('products.import')->middleware('permission:products,create');
        Route::get('/productos/importar/plantilla-actualizacion', 'downloadUpdateTemplate')->name('products.import.template.update');
        Route::post('/productos/importar/actualizar', 'importUpdate')->name('products.import.update')->middleware('permission:products,edit');
        Route::get('/productos/exportar', 'export')->name('products.export');
    });

// ============================================================
// Categorías
// ============================================================
Route::controller(CategoryController::class)
    ->middleware('permission:categories')
    ->group(function () {
        Route::get('/categorias', 'index')->name('categories.index');
        Route::post('/categorias/crear', 'store')->name('categories.store')->middleware('permission:categories,create');
        Route::get('/categorias/editar/{id}', 'edit')->name('categories.edit')->middleware('permission:categories,edit');
        Route::put('/categorias/editar/{id}', 'update')->name('categories.update')->middleware('permission:categories,edit');
        Route::delete('/categorias/eliminar/{id}', 'destroy')->name('categories.destroy')->middleware('permission:categories,delete');
    });

// ============================================================
// Marcas
// ============================================================
Route::controller(BrandController::class)
    ->middleware('permission:brands')
    ->group(function () {
        Route::get('/marcas', 'index')->name('brands.index');
        Route::post('/marcas/crear', 'store')->name('brands.store')->middleware('permission:brands,create');
        Route::get('/marcas/editar/{id}', 'edit')->name('brands.edit')->middleware('permission:brands,edit');
        Route::put('/marcas/editar/{id}', 'update')->name('brands.update')->middleware('permission:brands,edit');
        Route::delete('/marcas/eliminar/{id}', 'destroy')->name('brands.destroy')->middleware('permission:brands,delete');
        Route::get('/marcas/edicion-masiva', 'bulkEditIndex')->name('brands.bulk-edit');
        Route::post('/marcas/edicion-masiva/guardar', 'bulkEditSave')->name('brands.bulk.save')->middleware('permission:brands,edit');
        Route::post('/marcas/edicion-masiva/vistas', 'storeBulkEditView')->name('brands.bulk-edit.views.store')->middleware('permission:brands,create');
        Route::put('/marcas/edicion-masiva/vistas/{id}', 'updateBulkEditView')->name('brands.bulk-edit.views.update')->middleware('permission:brands,edit');
        Route::delete('/marcas/edicion-masiva/vistas/{id}', 'destroyBulkEditView')->name('brands.bulk-edit.views.destroy')->middleware('permission:brands,delete');
    });

// ============================================================
// Inventario
// ============================================================
Route::controller(InventoryController::class)
    ->middleware('permission:inventory')
    ->prefix('inventario')
    ->name('inventory.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/movimientos', 'store')->name('store')->middleware('permission:inventory,create');
        Route::get('/almacenes', 'warehousesIndex')->name('warehouses.index');
        Route::post('/almacenes', 'warehousesStore')->name('warehouses.store')->middleware('permission:inventory,create');
        Route::put('/almacenes/{id}', 'warehousesUpdate')->name('warehouses.update')->middleware('permission:inventory,edit');
        Route::delete('/almacenes/{id}', 'warehousesDestroy')->name('warehouses.destroy')->middleware('permission:inventory,delete');
        Route::get('/edicion-masiva', 'bulkEditIndex')->name('bulk-edit');
        Route::post('/edicion-masiva/guardar', 'bulkEditSave')->name('bulk.save')->middleware('permission:inventory,edit');
        Route::post('/edicion-masiva/vistas', 'storeBulkEditView')->name('bulk-edit.views.store')->middleware('permission:inventory,create');
        Route::put('/edicion-masiva/vistas/{id}', 'updateBulkEditView')->name('bulk-edit.views.update')->middleware('permission:inventory,edit');
        Route::delete('/edicion-masiva/vistas/{id}', 'destroyBulkEditView')->name('bulk-edit.views.destroy')->middleware('permission:inventory,delete');
    });

// ============================================================
// Cotizaciones
// ============================================================
Route::controller(QuoteController::class)
    ->middleware('permission:quotes')
    ->prefix('cotizaciones')
    ->name('quotes.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/crear', 'create')->name('create')->middleware('permission:quotes,create');
        Route::post('/', 'store')->name('store')->middleware('permission:quotes,create');
        Route::get('/buscar-productos', 'searchProducts')->name('search-products');
        Route::get('/buscar-servicios', 'searchServices')->name('search-services');
        Route::get('/{quote}', 'show')->name('show');
        Route::get('/{quote}/editar', 'edit')->name('edit')->middleware('permission:quotes,edit');
        Route::put('/{quote}', 'update')->name('update')->middleware('permission:quotes,edit');
        Route::delete('/{quote}', 'destroy')->name('destroy')->middleware('permission:quotes,delete');
        Route::get('/{quote}/pdf', 'downloadPdf')->name('pdf');
        Route::get('/{quote}/pdf-preview', 'previewPdf')->name('pdf-preview');
        Route::post('/{quote}/enviar-correo', 'sendEmail')->name('send-email')->middleware('permission:quotes,edit');
        Route::patch('/{quote}/estado', 'updateStatus')->name('update-status')->middleware('permission:quotes,edit');
        Route::patch('/{quote}/cliente', 'attachCustomer')->name('attach-customer')->middleware('permission:quotes,edit');
        Route::get('/{quote}/actividad/correo', 'previewManualEmail')->name('activity.preview-manual-email');
        Route::get('/{quote}/actividad/{log}/correo', 'previewAutomatedEmail')->name('activity.preview-automated-email');
        Route::get('/{quote}/actividad/{log}/whatsapp', 'previewAutomatedWhatsapp')->name('activity.preview-automated-whatsapp');
    });

// ============================================================
// Pipelines (embudos de ventas para Negocios/Deals)
// ============================================================
Route::controller(PipelineController::class)
    ->middleware('permission:pipeline')
    ->prefix('pipelines')
    ->name('pipelines.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store')->middleware('permission:pipeline,create');
        Route::put('/{pipeline}', 'update')->name('update')->middleware('permission:pipeline,edit');
        Route::delete('/{pipeline}', 'destroy')->name('destroy')->middleware('permission:pipeline,delete');
        Route::post('/{pipeline}/reorder-stages', 'reorderStages')->name('reorder-stages')->middleware('permission:pipeline,edit');
        Route::delete('/stages/{stage}', 'deleteStage')->name('delete-stage')->middleware('permission:pipeline,delete');
        Route::post('/{pipeline}/duplicar', 'duplicate')->name('duplicate')->middleware('permission:pipeline,create');
    });

// ============================================================
// Negocios (Deals) — Fase 14: acciones masivas y exportación. Se
// declaran en un bloque NUEVO e independiente, ANTES del grupo ya
// existente de "negocios" de abajo (que no se toca), para que
// /negocios/exportar y /negocios/accion-masiva no sean interpretadas
// por Laravel como el wildcard /{deal} de ese grupo (el primer match
// gana; un GET/POST sobre esos paths declarados después del wildcard
// nunca llegaría aquí).
// ============================================================
Route::controller(DealController::class)
    ->middleware('permission:deals')
    ->prefix('negocios')
    ->name('deals.')
    ->group(function () {
        Route::get('/exportar', 'exportCsv')->name('export-csv');
        Route::post('/accion-masiva', 'bulkAction')->name('bulk-action')->middleware('permission:deals,edit');
    });

// ============================================================
// Etiquetas de Negocios (Tags) — CRUD modal-based, Fase 14 del plan CRM.
// ============================================================
Route::controller(TagController::class)
    ->middleware('permission:deals')
    ->prefix('etiquetas-negocio')
    ->name('deal-tags.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store')->middleware('permission:deals,create');
        Route::get('/{tag}', 'edit')->name('edit')->middleware('permission:deals,edit');
        Route::put('/{tag}', 'update')->name('update')->middleware('permission:deals,edit');
        Route::delete('/{tag}', 'destroy')->name('destroy')->middleware('permission:deals,delete');
    });

// ============================================================
// Negocios (Deals) — kanban por pipeline/etapa, con vista de tabla
// alterna. Nota: /crear y /tabla se declaran ANTES de /{deal} para que
// Laravel no las interprete como el wildcard {deal}.
// ============================================================
Route::controller(DealController::class)
    ->middleware('permission:deals')
    ->prefix('negocios')
    ->name('deals.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/tabla', 'table')->name('table');
        Route::get('/crear', 'create')->name('create')->middleware('permission:deals,create');
        Route::post('/', 'store')->name('store')->middleware('permission:deals,create');
        Route::get('/{deal}/editar', 'edit')->name('edit')->middleware('permission:deals,edit');
        Route::put('/{deal}', 'update')->name('update')->middleware('permission:deals,edit');
        Route::get('/{deal}', 'show')->name('show');
        Route::post('/{deal}/mover-etapa', 'moveStage')->name('move-stage')->middleware('permission:deals,edit');
        Route::delete('/{deal}', 'destroy')->name('destroy')->middleware('permission:deals,delete');
    });

// ============================================================
// Tareas (generadas por automatizaciones, ej. "sin correo"/"sin WhatsApp")
// ============================================================
Route::get('/tareas', [TaskController::class, 'index'])->name('tasks.index');

// ============================================================
// Email Marketing (página única con pestañas: plantillas, listas,
// campañas y secuencias)
// ============================================================
Route::get('/marketing-por-correo', [EmailMarketingController::class, 'index'])
    ->name('email-marketing.index')
    ->middleware('permission:email-marketing');

Route::controller(EmailTemplateController::class)
    ->middleware('permission:email-marketing')
    ->prefix('marketing-por-correo/plantillas')
    ->name('email-templates.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/opciones', 'options')->name('options');
        Route::get('/logos', 'logos')->name('logos');
        Route::post('/', 'store')->name('store')->middleware('permission:email-marketing,create');
        Route::put('/{emailTemplate}', 'update')->name('update')->middleware('permission:email-marketing,edit');
        Route::delete('/{emailTemplate}', 'destroy')->name('destroy')->middleware('permission:email-marketing,delete');
        Route::get('/{emailTemplate}/preview', 'preview')->name('preview');
    });

Route::controller(EmailListController::class)
    ->middleware('permission:email-marketing')
    ->prefix('marketing-por-correo/listas')
    ->name('email-lists.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/clientes-disponibles', 'customersForPicker')->name('customers-picker');
        Route::post('/estimar-destinatarios', 'estimateRecipients')->name('estimate-recipients');
        Route::get('/opciones', 'options')->name('options');
        Route::post('/', 'store')->name('store')->middleware('permission:email-marketing,create');
        Route::get('/{emailList}', 'show')->name('show');
        Route::put('/{emailList}', 'update')->name('update')->middleware('permission:email-marketing,edit');
        Route::delete('/{emailList}', 'destroy')->name('destroy')->middleware('permission:email-marketing,delete');
        Route::post('/{emailList}/miembros', 'addMembers')->name('add-members')->middleware('permission:email-marketing,edit');
        Route::delete('/{emailList}/miembros/{customer}', 'removeMember')->name('remove-member')->middleware('permission:email-marketing,delete');
    });

Route::controller(EmailCampaignController::class)
    ->middleware('permission:email-marketing')
    ->prefix('marketing-por-correo/campanas')
    ->name('email-campaigns.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store')->middleware('permission:email-marketing,create');
        Route::get('/{emailCampaign}', 'show')->name('show');
        Route::post('/{emailCampaign}/enviar', 'send')->name('send')->middleware('permission:email-marketing,edit');
        Route::post('/{emailCampaign}/programar', 'schedule')->name('schedule')->middleware('permission:email-marketing,edit');
        Route::delete('/{emailCampaign}', 'destroy')->name('destroy')->middleware('permission:email-marketing,delete');
    });

Route::controller(EmailSequenceController::class)
    ->middleware('permission:email-marketing')
    ->prefix('marketing-por-correo/secuencias')
    ->name('email-sequences.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store')->middleware('permission:email-marketing,create');
        Route::get('/{emailSequence}', 'show')->name('show');
        Route::put('/{emailSequence}', 'update')->name('update')->middleware('permission:email-marketing,edit');
        Route::delete('/{emailSequence}', 'destroy')->name('destroy')->middleware('permission:email-marketing,delete');
        Route::post('/{emailSequence}/pasos', 'addStep')->name('add-step')->middleware('permission:email-marketing,edit');
        Route::delete('/pasos/{step}', 'removeStep')->name('remove-step')->middleware('permission:email-marketing,delete');
        Route::post('/{emailSequence}/inscribir', 'enrollCustomer')->name('enroll-customer')->middleware('permission:email-marketing,edit');
    });

// Metadatos de solo-lectura (nombres de credencial mysql, sus tablas, sus
// columnas) para poblar el formulario estructurado del nodo "Base de datos
// externa" en el inspector del canvas de Automatizaciones. Mismo permiso
// que el resto del editor de workflows: no exponen ningún secreto.
// IMPORTANTE: declarada ANTES del grupo de WorkflowController de abajo —
// su prefijo 'automatizaciones/credenciales-bd' cae bajo el mismo espacio
// que 'automatizaciones/{workflow}' (show), y Laravel resuelve rutas en
// orden de registro: si este grupo se registrara después, GET
// /automatizaciones/credenciales-bd sería capturado por el wildcard
// {workflow} de abajo (intentaría Workflow::findOrFail('credenciales-bd')
// y tiraría 404) en vez de llegar a ExternalDatabaseController::index().
Route::controller(ExternalDatabaseController::class)
    ->middleware('permission:automations')
    ->prefix('automatizaciones/credenciales-bd')
    ->name('workflow-db-credentials.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{credential}/tablas', 'tables')->name('tables');
        Route::get('/{credential}/tablas/{table}/columnas', 'columns')->name('columns');
    });

// Metadatos de solo-lectura (nombres de credencial tipo webhook) para poblar
// el selector de credencial del nodo "Llamar webhook" en el inspector del
// canvas de Automatizaciones. Mismo permiso que el resto del editor de
// workflows: nunca expone el payload/secreto guardado en la credencial.
// IMPORTANTE: declarada ANTES del grupo de WorkflowController de abajo, por
// la misma razón que 'automatizaciones/credenciales-bd' arriba — su prefijo
// 'automatizaciones/credenciales-webhook' cae bajo el mismo espacio que
// 'automatizaciones/{workflow}' (show), y Laravel resuelve rutas en orden de
// registro: si este grupo se registrara después, GET
// /automatizaciones/credenciales-webhook sería capturado por el wildcard
// {workflow} de abajo (intentaría Workflow::findOrFail('credenciales-webhook')
// y tiraría 404) en vez de llegar a WorkflowWebhookCredentialController::index().
Route::controller(WorkflowWebhookCredentialController::class)
    ->middleware('permission:automations')
    ->prefix('automatizaciones/credenciales-webhook')
    ->name('workflow-webhook-credentials.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
    });

// Metadatos de solo-lectura (id/name/url de los Webhooks registrados en el
// catálogo de Integraciones) para poblar el selector de "Webhook guardado"
// del nodo "Llamar webhook" en el inspector del canvas de Automatizaciones.
// IMPORTANTE: declarada ANTES del grupo de WorkflowController de abajo, por
// la misma razón que 'automatizaciones/credenciales-bd' y
// 'automatizaciones/credenciales-webhook' arriba — su prefijo
// 'automatizaciones/webhooks-registrados' cae bajo el mismo espacio que
// 'automatizaciones/{workflow}' (show), y Laravel resuelve rutas en orden de
// registro: si este grupo se registrara después, GET
// /automatizaciones/webhooks-registrados sería capturado por el wildcard
// {workflow} de abajo (intentaría Workflow::findOrFail('webhooks-registrados')
// y tiraría 404) en vez de llegar a WorkflowRegisteredWebhookController::index().
Route::controller(WorkflowRegisteredWebhookController::class)
    ->middleware('permission:automations')
    ->prefix('automatizaciones/webhooks-registrados')
    ->name('workflow-registered-webhooks.')
    ->group(fn () => Route::get('/', 'index')->name('index'));

// ============================================================
// Automatizaciones (Workflows) — motor de automatización tipo
// enrollment/steps. Nota: /crear se declara ANTES de /{workflow} para
// que Laravel no la interprete como el wildcard {workflow}.
// ============================================================
Route::controller(WorkflowController::class)
    ->middleware('permission:automations')
    ->prefix('automatizaciones')
    ->name('workflows.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/plantillas', 'templates')->name('templates');
        Route::post('/rapido', 'quickCreate')->name('quick-create');
        Route::get('/{workflow}/canvas', 'canvas')->name('canvas');
        Route::post('/{workflow}/test', 'test')->name('test');
        Route::get('/{workflow}/editar', 'edit')->name('edit');
        Route::put('/{workflow}', 'update')->name('update');
        Route::get('/{workflow}', 'show')->name('show');
        Route::post('/{workflow}/toggle', 'toggleActive')->name('toggle');
        Route::delete('/{workflow}', 'destroy')->name('destroy');
        Route::post('/{workflow}/duplicar', 'duplicate')->name('duplicate');
        Route::post('/{workflow}/deshacer', 'undo')->name('undo');
        Route::post('/{workflow}/rehacer', 'redo')->name('redo');
    });

Route::controller(WorkflowExecutionController::class)
    ->middleware('permission:automations')
    ->prefix('automatizaciones-ejecuciones')
    ->name('workflow-executions.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
    });

Route::controller(CredentialController::class)
    ->middleware('permission:credentials')
    ->prefix('credenciales')
    ->name('credentials.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::post('/probar', 'testConnection')->name('test-connection');
        Route::get('/{credential}/editar', 'edit')->name('edit');
        Route::put('/{credential}', 'update')->name('update');
        Route::delete('/{credential}', 'destroy')->name('destroy');
    });

// ============================================================
// Cuentas de WhatsApp (Meta Cloud API) — catálogo de cuentas usadas por
// el Embudo de Venta (Fase 13) y el webhook público. CRUD modal-based,
// mismo patrón que Credenciales.
// ============================================================
Route::controller(WhatsappAccountController::class)
    ->middleware('permission:whatsapp')
    ->prefix('cuentas-whatsapp')
    ->name('whatsapp-accounts.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store')->middleware('permission:whatsapp,create');
        Route::get('/{whatsappAccount}/editar', 'edit')->name('edit')->middleware('permission:whatsapp,edit');
        Route::put('/{whatsappAccount}', 'update')->name('update')->middleware('permission:whatsapp,edit');
        Route::delete('/{whatsappAccount}', 'destroy')->name('destroy')->middleware('permission:whatsapp,delete');
    });

// ============================================================
// Embudo de Venta (Fase 13) — kanban de conversaciones de WhatsApp por
// etapa de un Pipeline channel='whatsapp'. Grupo nuevo e independiente,
// insertado deliberadamente aquí (justo después de Cuentas de WhatsApp)
// para no colisionar con las rutas nuevas que el módulo de Negocios agrega
// en paralelo en otra zona de este archivo.
// ============================================================
Route::controller(WhatsappFunnelController::class)
    ->middleware('permission:whatsapp')
    ->prefix('embudo-de-venta')
    ->name('whatsapp-funnel.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        // Fase 14 (quick action "WhatsApp" de la tarjeta de Deal): busca o
        // crea la WhatsappConversation vinculada a ese deal_id (vía su
        // customer_id) y devuelve a dónde redirigir para abrir el modal de
        // chat ya existente de esta misma página — no se duplica el modal.
        // Declarada antes de '/nuevo-chat' por si algún día 'desde-negocio'
        // colisionara con un segmento futuro, aunque hoy no hay wildcard
        // aquí que lo capture.
        Route::post('/desde-negocio/{deal}', 'fromDeal')->name('from-deal');
        Route::post('/nuevo-chat', 'newChat')->name('new-chat');
        Route::post('/{conversation}/mover-etapa', 'moveStage')->name('move-stage');
        Route::get('/{conversation}/mensajes', 'messages')->name('messages');
        Route::post('/{conversation}/mensajes', 'sendMessage')->name('send-message');
        Route::post('/{conversation}/crear-negocio', 'createDeal')->name('create-deal');
        Route::post('/{conversation}/reasignar-agente', 'reassignAgent')->name('reassign-agent');
    });

Route::controller(WorkflowStepController::class)
    ->middleware('permission:automations')
    ->prefix('automatizaciones/{workflow}/pasos')
    ->name('workflow-steps.')
    ->group(function () {
        Route::post('/layout', 'saveLayout')->name('layout');
        Route::post('/', 'store')->name('store');
        Route::put('/{step}', 'update')->name('update');
        Route::delete('/{step}', 'destroy')->name('destroy');
        Route::post('/reorder', 'reorder')->name('reorder');
    });

Route::controller(WorkflowVariableController::class)
    ->middleware('permission:automations')
    ->prefix('automatizaciones/{workflow}/variables')
    ->name('workflow-variables.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{variable}', 'update')->name('update');
        Route::delete('/{variable}', 'destroy')->name('destroy');
    });

Route::controller(WorkflowCanvasNoteController::class)
    ->middleware('permission:automations')
    ->prefix('automatizaciones/{workflow}/notas')
    ->name('workflow-notes.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{note}', 'update')->name('update');
        Route::delete('/{note}', 'destroy')->name('destroy');
    });

// ============================================================
// Órdenes de Compra
// ============================================================
Route::controller(PurchaseOrderController::class)
    ->middleware('permission:purchase-orders')
    ->group(function () {
        Route::get('/ordenes-compra',                  'index')->name('purchase-orders.index');
        Route::get('/ordenes-compra/nueva',            'create')->name('purchase-orders.create')->middleware('permission:purchase-orders,create');
        Route::post('/ordenes-compra/nueva',           'store')->name('purchase-orders.store')->middleware('permission:purchase-orders,create');
        // Va antes de /{id} para no colisionar con el wildcard.
        Route::get('/ordenes-compra/productos-por-proveedor', 'productsBySupplier')->name('purchase-orders.products-by-supplier');
        Route::get('/ordenes-compra/{id}',             'show')->name('purchase-orders.show');
        Route::get('/ordenes-compra/editar/{id}',      'edit')->name('purchase-orders.edit')->middleware('permission:purchase-orders,edit');
        Route::put('/ordenes-compra/editar/{id}',      'update')->name('purchase-orders.update')->middleware('permission:purchase-orders,edit');
        Route::delete('/ordenes-compra/eliminar/{id}', 'destroy')->name('purchase-orders.destroy')->middleware('permission:purchase-orders,delete');
        Route::patch('/ordenes-compra/estado/{id}',    'updateStatus')->name('purchase-orders.status')->middleware('permission:purchase-orders,edit');
        Route::get('/ordenes-compra/{id}/pdf',         'downloadPdf')->name('purchase-orders.pdf');
        Route::get('/ordenes-compra/{id}/pdf-preview', 'previewPdf')->name('purchase-orders.pdf-preview');
    });

// ============================================================
// Pedidos (generados automáticamente desde Cotizaciones aceptadas)
// ============================================================
Route::controller(SalesOrderController::class)
    ->middleware('permission:sales-orders')
    ->prefix('pedidos')
    ->name('sales-orders.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{salesOrder}', 'show')->name('show');
        Route::patch('/{salesOrder}/estado', 'updateStatus')->name('update-status')->middleware('permission:sales-orders,edit');
        Route::patch('/{salesOrder}/items/{item}/entrega', 'registerDelivery')->name('register-delivery')->middleware('permission:sales-orders,edit');
    });

// ============================================================
// Órdenes (checkout público de la tienda) — distinto de "Pedidos"
// (sales-orders, generados desde Cotizaciones B2B aceptadas).
// ============================================================
Route::controller(StoreOrderController::class)
    ->middleware('permission:orders')
    ->prefix('ordenes')
    ->name('orders.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/exportar', 'export')->name('export');
        Route::get('/{order}', 'show')->name('show');
        Route::patch('/{order}/estatus', 'updateStatus')->name('update-status')->middleware('permission:orders,edit');
        Route::get('/{order}/constancia-fiscal', 'downloadTaxCertificate')->name('tax-certificate');
    });

// ============================================================
// Envíos — 1:1 con StoreOrder (checkout público). Los avances de estatus
// pueden empujar automáticamente el estatus de la orden (ver
// App\Actions\AdvanceStoreOrderStatus).
// ============================================================
Route::controller(ShipmentController::class)
    ->middleware('permission:shipments')
    ->prefix('envios')
    ->name('shipments.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/exportar', 'export')->name('export');
        Route::post('/ordenes/{order}', 'store')->name('store')->middleware('permission:shipments,create');
        Route::patch('/{shipment}/estatus', 'updateStatus')->name('update-status')->middleware('permission:shipments,edit');
        Route::delete('/{shipment}', 'destroy')->name('destroy')->middleware('permission:shipments,delete');
    });

// ============================================================
// Entrega de Material (Reportes de Entrega) — módulo independiente,
// NO comparte endpoint con SalesOrderService::registerDelivery()
// ni con SalesOrderController::registerDelivery(). Ver decisión en
// MaterialDeliveryReportService: no toca quantity_delivered.
// ============================================================
Route::prefix('entrega-material')
    ->name('material-delivery-reports.')
    ->middleware('permission:material-delivery-reports')
    ->controller(MaterialDeliveryReportController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create')->middleware('permission:material-delivery-reports,create');
        Route::post('/', 'store')->name('store')->middleware('permission:material-delivery-reports,create');
        Route::get('/{report}', 'show')->name('show');
        Route::get('/{report}/edit', 'edit')->name('edit')->middleware('permission:material-delivery-reports,edit');
        Route::delete('/{report}', 'destroy')->name('destroy')->middleware('permission:material-delivery-reports,delete');
        Route::get('/{report}/pdf', 'downloadPdf')->name('download-pdf');
        Route::get('/{report}/pdf-preview', 'previewPdf')->name('pdf-preview');
        Route::post('/{report}/sign', 'sign')->name('sign')->middleware('permission:material-delivery-reports,edit');
        Route::get('/{report}/step/{step}', 'step')->name('step')->middleware('permission:material-delivery-reports,edit');
        Route::post('/{report}/step/{step}', 'saveStep')->name('save-step')->middleware('permission:material-delivery-reports,edit');
        Route::delete('/{report}/images/{image}', 'destroyImage')->name('images.destroy')->middleware('permission:material-delivery-reports,delete');
    });

// ============================================================
// Planeación de Químicos
// ============================================================
Route::controller(ChemicalPlanningController::class)
    ->middleware('permission:chemical-planning')
    ->prefix('planeacion-quimicos')
    ->name('chemical-planning.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/proyeccion', 'updateProjection')->name('projection.update')->middleware('permission:chemical-planning,edit');
        Route::get('/exportar', 'export')->name('export');
    });

// ============================================================
// Configuración ERP — módulo aislado de ajustes globales del ERP
// (tipos de servicio, parámetros generales), conceptualmente parte
// del mismo grupo que Planeación de Químicos pero con su propio
// permiso ('erp-settings') y controller dedicado.
// ============================================================
Route::controller(\App\Http\Controllers\Backend\ErpSettingController::class)
    ->middleware('permission:erp-settings')
    ->prefix('erp/configuracion')
    ->name('erp-settings.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::put('/', 'update')->name('update')->middleware('permission:erp-settings,edit');
        Route::post('/tipos-servicio', 'storeServiceType')->name('service-types.store')->middleware('permission:erp-settings,create');
        Route::put('/tipos-servicio/{serviceType}', 'updateServiceType')->name('service-types.update')->middleware('permission:erp-settings,edit');
        Route::delete('/tipos-servicio/{serviceType}', 'destroyServiceType')->name('service-types.destroy')->middleware('permission:erp-settings,delete');
        Route::post('/tipos-reporte', 'storeServiceReportType')->name('report-types.store')->middleware('permission:erp-settings,create');
        Route::put('/tipos-reporte/{serviceReportType}', 'updateServiceReportType')->name('report-types.update')->middleware('permission:erp-settings,edit');
        Route::delete('/tipos-reporte/{serviceReportType}', 'destroyServiceReportType')->name('report-types.destroy')->middleware('permission:erp-settings,delete');
    });

// ============================================================
// Estadísticas — módulo de BI cruzando Ventas, Compras, Servicios
// Técnicos, Reportes de Servicio, Inventario, Tienda pública y
// Embudo de ventas (Pipeline/Deals). Módulo independiente, permiso
// propio 'statistics', sin acciones de escritura (solo lectura de
// datos ya existentes en otros módulos) — nunca necesita
// create/edit/delete, solo 'view'.
// ============================================================
Route::controller(StatisticsController::class)
    ->middleware('permission:statistics')
    ->prefix('estadisticas')
    ->name('statistics.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{section}', 'show')->name('show')->where('section', 'ventas|embudo|compras|servicios|reportes|inventario|tienda');
    });

// ============================================================
// Reportes de Servicio
// ============================================================
Route::prefix('service-reports')
    ->name('service-reports.')
    ->middleware('permission:service-reports')
    ->controller(ServiceReportController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create')->middleware('permission:service-reports,create');
        Route::post('/', 'store')->name('store')->middleware('permission:service-reports,create');
        Route::get('/customers/search', 'searchCustomers')->name('customers.search');
        Route::get('/{report}', 'show')->name('show');
        Route::get('/{report}/edit', 'edit')->name('edit')->middleware('permission:service-reports,edit');
        Route::delete('/{report}', 'destroy')->name('destroy')->middleware('permission:service-reports,delete');
        Route::get('/{report}/pdf', 'downloadPdf')->name('download-pdf');
        Route::get('/{report}/pdf-preview', 'previewPdf')->name('pdf-preview');
        Route::post('/{report}/sign', 'sign')->name('sign')->middleware('permission:service-reports,edit');
        Route::get('/{report}/step/{step}', 'step')->name('step')->middleware('permission:service-reports,edit');
        Route::post('/{report}/step/{step}', 'saveStep')->name('save-step')->middleware('permission:service-reports,edit');
        Route::delete('/{report}/images/{image}', 'destroyImage')->name('images.destroy')->middleware('permission:service-reports,delete');
        Route::post('/{report}/vincular-servicio', 'attachService')->name('attach-service')->middleware('permission:service-reports,edit');
    });

// ============================================================
// Servicios Técnicos
// ============================================================
Route::controller(TechnicalServiceController::class)
    ->middleware('permission:technical-services')
    ->prefix('technical-services')
    ->name('technical-services.')
    ->group(function () {
        // Búsquedas AJAX — van primero para no colisionar con {service}
        Route::get('/search-technicians', 'searchTechnicians')->name('search-technicians');
        Route::get('/search-materials', 'searchMaterials')->name('search-materials');

        // CRUD principal
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create')->middleware('permission:technical-services,create');
        Route::post('/', 'store')->name('store')->middleware('permission:technical-services,create');
        Route::get('/{service}/draft-context', 'draftContext')->name('draft-context');
        Route::get('/{service}', 'show')->name('show');
        Route::get('/{service}/edit', 'edit')->name('edit')->middleware('permission:technical-services,edit');
        Route::delete('/{service}', 'destroy')->name('destroy')->middleware('permission:technical-services,delete');

        // Formulario multi-etapa
        Route::get('/{service}/step/{step}', 'step')->name('step')->middleware('permission:technical-services,edit');
        Route::post('/{service}/step/{step}', 'saveStep')->name('save-step')->middleware('permission:technical-services,edit');

        // Acciones especiales
        Route::patch('/{service}/update-date', 'updateDate')->name('update-date')->middleware('permission:technical-services,edit');
        Route::patch('/{service}/update-status', 'updateStatus')->name('update-status')->middleware('permission:technical-services,edit');
        Route::get('/{service}/generate-report', 'generateReport')->name('generate-report');
        Route::patch('/{service}/cotizacion', 'attachQuote')->name('attach-quote')->middleware('permission:technical-services,edit');
    });

// ============================================================
// Google Ads
// ============================================================
Route::controller(GoogleAdsController::class)
    ->middleware('permission:google-ads')
    ->prefix('google-ads')
    ->name('google-ads.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        // Antes de /{id} para que Laravel no lo interprete como el wildcard.
        Route::get('/exportar', 'export')->name('export');
        Route::get('/{id}', 'show')->name('show');
    });

// ============================================================
// Preferencia de columnas visibles — genérico, sin permiso propio
// (utilidad transversal, igual que Media, disponible a cualquier
// usuario autenticado para su propia preferencia)
// ============================================================
Route::put('/preferencias-columnas', [ColumnPreferenceController::class, 'update'])->name('column-preferences.update');

// ============================================================
// Paqueterías
// ============================================================
Route::controller(DeliveryController::class)
    -> middleware('permission:carriers')
    ->prefix('deliveries')
    ->name('deliveries.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/crear', 'store')->name('store')->middleware('permission:carriers,create');
        Route::put('/editar/{id}', 'update')->name('update')->middleware('permission:carriers,edit');
        Route::delete('/eliminar/{id}', 'destroy')->name('destroy')->middleware('permission:carriers,delete');
    });

// ============================================================
// Métodos de Pago
// ============================================================
Route::controller(PaymentMethodController::class)
    ->middleware('permission:payment-methods')
    ->prefix('metodos-de-pago')
    ->name('payment-methods.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store')->middleware('permission:payment-methods,create');
        Route::get('/{id}/editar', 'edit')->name('edit')->middleware('permission:payment-methods,edit');
        Route::put('/{id}', 'update')->name('update')->middleware('permission:payment-methods,edit');
        Route::delete('/{id}', 'destroy')->name('destroy')->middleware('permission:payment-methods,delete');
    });

// ============================================================
// Reglas de Envío
// ============================================================
Route::controller(ShippingRuleController::class)
    ->middleware('permission:shipping-rules')
    ->prefix('reglas-de-envio')
    ->name('shipping-rules.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store')->middleware('permission:shipping-rules,create');
        Route::get('/{id}/editar', 'edit')->name('edit')->middleware('permission:shipping-rules,edit');
        Route::put('/{id}', 'update')->name('update')->middleware('permission:shipping-rules,edit');
        Route::delete('/{id}', 'destroy')->name('destroy')->middleware('permission:shipping-rules,delete');
    });

// ============================================================
// Configuración del sitio
// ============================================================
Route::controller(SettingController::class)
    ->middleware('permission:settings')
    ->group(function () {
        Route::get('/configuracion-sitio', 'index')->name('settings.index');
        Route::put('/configuracion-sitio', 'update')->name('settings.update')->middleware('permission:settings,edit');
    });

// ============================================================
// Integraciones (SMTP y credenciales de servicios externos)
// ============================================================
Route::controller(IntegrationController::class)
    ->middleware('permission:settings')
    ->group(function () {
        Route::get('/integraciones', 'index')->name('integrations.index');
        Route::put('/integraciones', 'update')->name('integrations.update')->middleware('permission:settings,edit');
        Route::post('/integraciones/probar-correo', 'sendTestMail')->name('integrations.test-mail');
    });

// CRUD del catálogo "Webhooks" (entidad reutilizable para el nodo "Llamar
// webhook" del canvas de Automatizaciones), anidado bajo Integraciones.
// Mismo patrón modal-based que Credenciales.
Route::controller(WebhookController::class)
    ->prefix('integraciones/webhooks')
    ->name('integrations.webhooks.')
    ->group(function () {
        Route::post('/', 'store')->name('store')->middleware('permission:settings,edit');
        Route::get('/{webhook}/editar', 'edit')->name('edit')->middleware('permission:settings');
        Route::put('/{webhook}', 'update')->name('update')->middleware('permission:settings,edit');
        Route::delete('/{webhook}', 'destroy')->name('destroy')->middleware('permission:settings,edit');
        Route::post('/probar', 'test')->name('test')->middleware('permission:settings,edit');
        Route::get('/credenciales', 'credentials')->name('credentials')->middleware('permission:settings');
    });

// ============================================================
// Inicio - Secciones (page builder del home público)
// ============================================================
Route::controller(HomeSectionController::class)
    ->middleware('permission:home-sections')
    ->group(function () {
        Route::get('/inicio-secciones', 'index')->name('home-sections.index');
        Route::get('/inicio-secciones/productos/buscar', 'searchProducts')->name('home-sections.products.search');
        Route::post('/inicio-secciones/crear', 'store')->name('home-sections.store')->middleware('permission:home-sections,create');
        Route::get('/inicio-secciones/editar/{id}', 'edit')->name('home-sections.edit')->middleware('permission:home-sections,edit');
        Route::put('/inicio-secciones/editar/{id}', 'update')->name('home-sections.update')->middleware('permission:home-sections,edit');
        Route::delete('/inicio-secciones/eliminar/{id}', 'destroy')->name('home-sections.destroy')->middleware('permission:home-sections,delete');
        Route::post('/inicio-secciones/reordenar', 'reorder')->name('home-sections.reorder')->middleware('permission:home-sections,edit');
        Route::get('/inicio-secciones/{homeSection}/slides', 'slidesPage')->name('home-sections.slides.view');
        Route::get('/inicio-secciones/{homeSection}/slides/listado', 'slides')->name('home-sections.slides.index');
        Route::post('/inicio-secciones/{homeSection}/slides', 'storeSlide')->name('home-sections.slides.store')->middleware('permission:home-sections,create');
        Route::put('/inicio-secciones/{homeSection}/slides/{slide}', 'updateSlide')->name('home-sections.slides.update')->middleware('permission:home-sections,edit');
        Route::delete('/inicio-secciones/{homeSection}/slides/{slide}', 'destroySlide')->name('home-sections.slides.destroy')->middleware('permission:home-sections,delete');
        Route::post('/inicio-secciones/{homeSection}/slides/reordenar', 'reorderSlides')->name('home-sections.slides.reorder')->middleware('permission:home-sections,edit');
    });

// ============================================================
// Menús de navegación
// ============================================================
Route::controller(MenuController::class)
    ->middleware('permission:menu')
    ->group(function () {
        Route::get('/menus', 'index')->name('menus.index');
        Route::get('/menus/nuevo', 'create')->name('menus.create')->middleware('permission:menu,create');
        Route::post('/menus/nuevo', 'store')->name('menus.store')->middleware('permission:menu,create');
        Route::get('/menus/{menu}/editar', 'edit')->name('menus.edit')->middleware('permission:menu,edit');
        Route::put('/menus/{menu}/editar', 'update')->name('menus.update')->middleware('permission:menu,edit');
        Route::delete('/menus/{menu}/eliminar', 'destroy')->name('menus.destroy')->middleware('permission:menu,delete');
        Route::post('/menus/{menu}/items', 'storeItem')->name('menus.items.store')->middleware('permission:menu,create');
        Route::put('/menus/{menu}/items/{item}', 'updateItem')->name('menus.items.update')->middleware('permission:menu,edit');
        Route::delete('/menus/{menu}/items/{item}', 'destroyItem')->name('menus.items.destroy')->middleware('permission:menu,delete');
        Route::post('/menus/{menu}/items/reordenar', 'reorderItems')->name('menus.items.reorder')->middleware('permission:menu,edit');
    });

// ============================================================
// Colecciones (manuales y automáticas, estilo Shopify)
// ============================================================
Route::controller(CollectionController::class)
    ->middleware('permission:collections')
    ->prefix('colecciones')
    ->name('collections.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/crear', 'store')->name('store')->middleware('permission:collections,create');
        Route::get('/editar/{id}', 'edit')->name('edit')->middleware('permission:collections,edit');
        Route::put('/editar/{id}', 'update')->name('update')->middleware('permission:collections,edit');
        Route::delete('/eliminar/{id}', 'destroy')->name('destroy')->middleware('permission:collections,delete');
        Route::get('/buscar-productos', 'searchProducts')->name('products.search');
        Route::get('/{collection}/productos', 'show')->name('show');
        Route::post('/{collection}/productos', 'addProduct')->name('products.add')->middleware('permission:collections,edit');
        Route::delete('/{collection}/productos/{product}', 'removeProduct')->name('products.remove')->middleware('permission:collections,delete');
        Route::post('/{collection}/productos/reordenar', 'reorderProducts')->name('products.reorder')->middleware('permission:collections,edit');
    });

// ============================================================
// Páginas de Servicio (catálogo público con secciones, SEO)
// ============================================================
Route::controller(ServicePageController::class)
    ->middleware('permission:service-pages')
    ->prefix('servicios-web')
    ->name('service-pages.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/crear', 'create')->name('create')->middleware('permission:service-pages,create');
        Route::post('/', 'store')->name('store')->middleware('permission:service-pages,create');
        Route::get('/productos/buscar', 'searchProducts')->name('products.search');
        Route::get('/{servicePage}/editar', 'edit')->name('edit')->middleware('permission:service-pages,edit');
        Route::put('/{servicePage}', 'update')->name('update')->middleware('permission:service-pages,edit');
        Route::delete('/{servicePage}', 'destroy')->name('destroy')->middleware('permission:service-pages,delete');

        Route::post('/{servicePage}/secciones', 'storeSection')->name('sections.store')->middleware('permission:service-pages,create');
        Route::get('/{servicePage}/secciones/{section}', 'editSection')->name('sections.edit')->middleware('permission:service-pages,edit');
        Route::put('/{servicePage}/secciones/{section}', 'updateSection')->name('sections.update')->middleware('permission:service-pages,edit');
        Route::delete('/{servicePage}/secciones/{section}', 'destroySection')->name('sections.destroy')->middleware('permission:service-pages,delete');
        Route::post('/{servicePage}/secciones/reordenar', 'reorderSections')->name('sections.reorder')->middleware('permission:service-pages,edit');
    });

// ============================================================
// Galería de imágenes
// ============================================================
Route::controller(GalleryController::class)
    ->middleware('permission:gallery')
    ->prefix('galeria')
    ->name('gallery.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/subir', 'store')->name('store')->middleware('permission:gallery,create');
        Route::delete('/eliminar/{id}', 'destroy')->name('destroy')->middleware('permission:gallery,delete');
    });

Route::controller(DuplicateImageController::class)
    ->middleware('permission:gallery')
    ->prefix('galeria/duplicados')
    ->name('gallery.duplicates.')
    ->group(function () {
        Route::post('/escanear', 'scan')->name('scan')->middleware('permission:gallery,create');
        Route::get('/ultimo-escaneo', 'lastScan')->name('last-scan');
        Route::get('/buscar', 'searchLibrary')->name('search');
        Route::post('/{group}/aplicar', 'apply')->name('apply')->middleware('permission:gallery,edit');
        Route::post('/{group}/descartar', 'dismiss')->name('dismiss')->middleware('permission:gallery,edit');
    });

// ============================================================
// DevOps — consola SQL (solo Admin; 'devops' se omite a propósito
// de config/modules.php para que nunca sea asignable a otros roles,
// ver DevOpsController)
// ============================================================
Route::controller(DevOpsController::class)
    ->middleware('permission:devops')
    ->prefix('devops')
    ->name('devops.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/ejecutar', 'execute')->name('execute');
    });

// ============================================================
// Auditoría Sistema — lectura de system_logs (bitácora genérica escrita por
// el trait LogsActivity). A diferencia de DevOps, es de solo lectura sobre
// datos ya filtrados/redactados, así que usa el gate normal asignable por
// rol en vez del abort_unless(isAdmin()) hardcodeado de DevOps.
// ============================================================
Route::controller(AuditController::class)
    ->middleware('permission:audit')
    ->prefix('auditoria')
    ->name('audit.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
    });

Route::controller(AbandonedCartController::class)
    ->middleware('permission:abandoned-carts')
    ->prefix('carritos-abandonados')
    ->name('abandoned-carts.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/{cart}/descartar', 'dismiss')->name('dismiss')->middleware('permission:abandoned-carts,edit');
    });

// ============================================================
// Rastreo de Anuncios — módulo admin mínimo de solo lectura sobre
// ad_visits/ad_events (identidad de visitantes de anuncios + eventos de
// interés), para diagnóstico/soporte del pipeline de conversiones offline
// de Google Ads. Va antes de /exportar sin wildcards que colisionen.
// ============================================================
Route::controller(\App\Http\Controllers\Backend\AdTrackingAdminController::class)
    ->middleware('permission:ad-tracking')
    ->prefix('rastreo-anuncios')
    ->name('ad-tracking.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/exportar', 'export')->name('export');
    });

// ============================================================
// Reportes (CRM) — builder de reportes custom + prebuilt (pipeline
// performance, actividad por vendedor, análisis de embudo). Nota:
// /crear y /buscar-* van antes de /{report} para no colisionar con
// el wildcard.
// ============================================================
Route::controller(ReportController::class)
    ->middleware('permission:crm-reports')
    ->prefix('reportes')
    ->name('reports.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/crear', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::post('/preview', 'preview')->name('preview');
        Route::get('/prebuilt/pipeline-performance', 'pipelinePerformance')->name('prebuilt.pipeline-performance');
        Route::get('/prebuilt/rep-activity', 'repActivity')->name('prebuilt.rep-activity');
        Route::get('/prebuilt/funnel-analysis', 'funnelAnalysis')->name('prebuilt.funnel-analysis');
        Route::get('/{report}', 'show')->name('show');
        Route::get('/{report}/exportar', 'export')->name('export');
        Route::delete('/{report}', 'destroy')->name('destroy');
    });

// ============================================================
// Dashboards (CRM) — paneles personalizados armados a partir de
// Reports. Nota: /crear va antes de /{dashboard} para no colisionar
// con el wildcard.
// ============================================================
Route::controller(DashboardController::class)
    ->middleware('permission:crm-reports')
    ->prefix('dashboards')
    ->name('dashboards.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/crear', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{dashboard}/editar', 'edit')->name('edit');
        Route::put('/{dashboard}', 'update')->name('update');
        Route::post('/{dashboard}/reportes', 'addReport')->name('addReport');
        Route::delete('/{dashboard}/reportes/{report}', 'removeReport')->name('removeReport');
        Route::post('/{dashboard}/reportes/reordenar', 'reorderReports')->name('reorderReports');
        Route::post('/{dashboard}/compartir', 'share')->name('share');
        Route::get('/{dashboard}', 'show')->name('show');
        Route::delete('/{dashboard}', 'destroy')->name('destroy');
    });

// ============================================================
// Metas (CRM) — objetivos de ventas/actividad por owner y periodo
// ============================================================
Route::controller(GoalController::class)
    ->middleware('permission:crm-reports')
    ->prefix('metas')
    ->name('goals.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/crear', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{goal}/editar', 'edit')->name('edit');
        Route::put('/{goal}', 'update')->name('update');
        Route::delete('/{goal}', 'destroy')->name('destroy');
    });

    // sig module
