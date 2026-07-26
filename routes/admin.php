<?php

use App\Http\Controllers\Admin\GoogleAdsController;
use App\Http\Controllers\Backend\AdminController;
use App\Http\Controllers\Backend\CategoryController;
use App\Http\Controllers\Backend\ClientManageController;
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
use App\Http\Controllers\Backend\GalleryController;
use App\Http\Controllers\Backend\MediaController;

// ============================================================
// Dashboard — sin permiso, todos los usuarios autenticados
// ============================================================
Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');


// ============================================================
// Roles
// ============================================================
Route::controller(RoleController::class)
    ->middleware('permission:role')
    ->group(function () {
        Route::get('/roles', 'index')->name('roles.index');
        Route::get('/roles/mostrar-rol/{id}', 'show')->name('roles.show');
        Route::post('/roles/crear-rol', 'store')->name('roles.store');
        Route::put('/roles/editar-rol/{id}', 'update')->name('roles.update');
        Route::delete('/roles/eliminar-rol/{id}', 'destroy')->name('roles.destroy');
    });

// ============================================================
// Usuarios
// ============================================================
Route::controller(UserManageController::class)
    ->middleware('permission:users')
    ->group(function () {
        Route::get('/usuarios', 'index')->name('users.index');
        Route::get('/usuarios/mostrar-usuario/{id}', 'show')->name('users.show');
        Route::post('/usuarios/crear-usuario', 'store')->name('users.store');
        Route::put('/usuarios/editar-usuario/{id}', 'update')->name('users.update');
        Route::delete('/usuarios/eliminar-usuario/{id}', 'destroy')->name('users.destroy');
    });

// ============================================================
// Clientes
// ============================================================
Route::controller(ClientManageController::class)
    ->middleware('permission:clients')
    ->group(function () {
        Route::get('/clientes', 'index')->name('clients.index');
        Route::post('/clientes/crear-usuario/', 'store')->name('clients.store');
        Route::post('/clientes/parse-cfdi', 'parseCfdi')->name('clients.parse-cfdi');
        Route::get('/clientes/editar-cliente/{id}', 'edit')->name('clients.edit');
        Route::put('/clientes/editar-cliente/{id}', 'update')->name('clients.update');
        Route::delete('/clientes/eliminar-cliente/{id}', 'destroy')->name('clients.destroy');
        Route::get('/clientes/informacion/{id}', 'information')->name('clients.information');
        Route::post('/clientes/{id}/acceso', 'grantAccess')->name('clients.grant-access');
        Route::patch('/clientes/{id}/estado', 'updateStatus')->name('clients.update-status');
        Route::patch('/clientes/{id}/portal', 'updatePortalAccess')->name('clients.update-portal-access');
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
        Route::post('/proveedores/crear-proveedor', 'store')->name('suppliers.store');
        Route::get('/proveedores/editar-proveedor/{id}', 'edit')->name('suppliers.edit');
        Route::put('/proveedores/editar-proveedor/{id}', 'update')->name('suppliers.update');
        Route::delete('/proveedores/eliminar-proveedor/{id}', 'destroy')->name('suppliers.destroy');
        Route::get('/proveedores/informacion/{id}', 'information')->name('suppliers.information');
    });

// ============================================================
// Productos
// ============================================================
Route::controller(ProductController::class)
    ->middleware('permission:products')
    ->group(function () {
        Route::get('/productos', 'index')->name('products.index');
        Route::get('/productos/nuevo', 'create')->name('products.create');
        Route::post('/productos/nuevo', 'store')->name('products.store');
        Route::get('/productos/editar/{id}', 'edit')->name('products.edit');
        Route::put('/productos/editar/{id}', 'update')->name('products.update');
        Route::delete('/productos/eliminar/{id}', 'destroy')->name('products.destroy');
        Route::post('/productos/{id}/imagenes/reordenar', 'reorderImages')->name('products.images.reorder');
        Route::get('/productos/imagenes/biblioteca', 'mediaLibrary')->name('products.images.library');
        Route::get('/productos/etiquetas/buscar', 'tagSuggestions')->name('products.tags.suggestions');
        Route::post('/productos/bulk', 'bulkUpdate')->name('products.bulk');
        Route::get('/productos/edicion-masiva', 'bulkEditIndex')->name('products.bulk-edit');
        Route::post('/productos/edicion-masiva/guardar', 'bulkEditSave')->name('products.bulk.save');
        Route::post('/productos/edicion-masiva/vistas', 'storeBulkEditView')->name('products.bulk-edit.views.store');
        Route::put('/productos/edicion-masiva/vistas/{id}', 'updateBulkEditView')->name('products.bulk-edit.views.update');
        Route::delete('/productos/edicion-masiva/vistas/{id}', 'destroyBulkEditView')->name('products.bulk-edit.views.destroy');
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
        Route::post('/productos/importar', 'import')->name('products.import');
        Route::get('/productos/importar/plantilla-actualizacion', 'downloadUpdateTemplate')->name('products.import.template.update');
        Route::post('/productos/importar/actualizar', 'importUpdate')->name('products.import.update');
        Route::get('/productos/exportar', 'export')->name('products.export');
    });

// ============================================================
// Categorías
// ============================================================
Route::controller(CategoryController::class)
    ->middleware('permission:categories')
    ->group(function () {
        Route::get('/categorias', 'index')->name('categories.index');
        Route::post('/categorias/crear', 'store')->name('categories.store');
        Route::get('/categorias/editar/{id}', 'edit')->name('categories.edit');
        Route::put('/categorias/editar/{id}', 'update')->name('categories.update');
        Route::delete('/categorias/eliminar/{id}', 'destroy')->name('categories.destroy');
    });

// ============================================================
// Marcas
// ============================================================
Route::controller(BrandController::class)
    ->middleware('permission:brands')
    ->group(function () {
        Route::get('/marcas', 'index')->name('brands.index');
        Route::post('/marcas/crear', 'store')->name('brands.store');
        Route::get('/marcas/editar/{id}', 'edit')->name('brands.edit');
        Route::put('/marcas/editar/{id}', 'update')->name('brands.update');
        Route::delete('/marcas/eliminar/{id}', 'destroy')->name('brands.destroy');
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
        Route::get('/crear', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/buscar-productos', 'searchProducts')->name('search-products');
        Route::get('/{quote}', 'show')->name('show');
        Route::get('/{quote}/editar', 'edit')->name('edit');
        Route::put('/{quote}', 'update')->name('update');
        Route::delete('/{quote}', 'destroy')->name('destroy');
        Route::get('/{quote}/pdf', 'downloadPdf')->name('pdf');
        Route::get('/{quote}/pdf-preview', 'previewPdf')->name('pdf-preview');
        Route::post('/{quote}/enviar-correo', 'sendEmail')->name('send-email');
        Route::patch('/{quote}/estado', 'updateStatus')->name('update-status');
    });

// ============================================================
// Órdenes de Compra
// ============================================================
Route::controller(PurchaseOrderController::class)
    ->middleware('permission:purchase-orders')
    ->group(function () {
        Route::get('/ordenes-compra',                  'index')->name('purchase-orders.index');
        Route::get('/ordenes-compra/nueva',            'create')->name('purchase-orders.create');
        Route::post('/ordenes-compra/nueva',           'store')->name('purchase-orders.store');
        Route::get('/ordenes-compra/{id}',             'show')->name('purchase-orders.show');
        Route::get('/ordenes-compra/editar/{id}',      'edit')->name('purchase-orders.edit');
        Route::put('/ordenes-compra/editar/{id}',      'update')->name('purchase-orders.update');
        Route::delete('/ordenes-compra/eliminar/{id}', 'destroy')->name('purchase-orders.destroy');
        Route::patch('/ordenes-compra/estado/{id}',    'updateStatus')->name('purchase-orders.status');
        Route::get('/ordenes-compra/{id}/pdf',         'downloadPdf')->name('purchase-orders.pdf');
        Route::get('/ordenes-compra/{id}/pdf-preview', 'previewPdf')->name('purchase-orders.pdf-preview');
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
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/customers/search', 'searchCustomers')->name('customers.search');
        Route::get('/{report}', 'show')->name('show');
        Route::get('/{report}/edit', 'edit')->name('edit');
        Route::delete('/{report}', 'destroy')->name('destroy');
        Route::get('/{report}/pdf', 'downloadPdf')->name('download-pdf');
        Route::get('/{report}/pdf-preview', 'previewPdf')->name('pdf-preview');
        Route::post('/{report}/sign', 'sign')->name('sign');
        Route::get('/{report}/step/{step}', 'step')->name('step');
        Route::post('/{report}/step/{step}', 'saveStep')->name('save-step');
        Route::delete('/{report}/images/{image}', 'destroyImage')->name('images.destroy');
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
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{service}/draft-context', 'draftContext')->name('draft-context');
        Route::get('/{service}', 'show')->name('show');
        Route::get('/{service}/edit', 'edit')->name('edit');
        Route::delete('/{service}', 'destroy')->name('destroy');

        // Formulario multi-etapa
        Route::get('/{service}/step/{step}', 'step')->name('step');
        Route::post('/{service}/step/{step}', 'saveStep')->name('save-step');

        // Acciones especiales
        Route::patch('/{service}/update-date', 'updateDate')->name('update-date');
        Route::patch('/{service}/update-status', 'updateStatus')->name('update-status');
        Route::get('/{service}/generate-report', 'generateReport')->name('generate-report');
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
        Route::get('/{id}', 'show')->name('show');
    });
// ============================================================
// Paqueterías
// ============================================================
Route::controller(DeliveryController::class)
    -> middleware('permission:carriers')
    ->prefix('deliveries')
    ->name('deliveries.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/crear', 'store')->name('store');
        Route::put('/editar/{id}', 'update')->name('update');
        Route::delete('/eliminar/{id}', 'destroy')->name('destroy');
    });

// ============================================================
// Configuración del sitio
// ============================================================
Route::controller(SettingController::class)
    ->middleware('permission:settings')
    ->group(function () {
        Route::get('/configuracion-sitio', 'index')->name('settings.index');
        Route::put('/configuracion-sitio', 'update')->name('settings.update');
    });

// ============================================================
// Integraciones (SMTP y credenciales de servicios externos)
// ============================================================
Route::controller(IntegrationController::class)
    ->middleware('permission:settings')
    ->group(function () {
        Route::get('/integraciones', 'index')->name('integrations.index');
        Route::put('/integraciones', 'update')->name('integrations.update');
        Route::post('/integraciones/probar-correo', 'sendTestMail')->name('integrations.test-mail');
    });

// ============================================================
// Inicio - Secciones (page builder del home público)
// ============================================================
Route::controller(HomeSectionController::class)
    ->middleware('permission:home-sections')
    ->group(function () {
        Route::get('/inicio-secciones', 'index')->name('home-sections.index');
        Route::get('/inicio-secciones/productos/buscar', 'searchProducts')->name('home-sections.products.search');
        Route::post('/inicio-secciones/crear', 'store')->name('home-sections.store');
        Route::get('/inicio-secciones/editar/{id}', 'edit')->name('home-sections.edit');
        Route::put('/inicio-secciones/editar/{id}', 'update')->name('home-sections.update');
        Route::delete('/inicio-secciones/eliminar/{id}', 'destroy')->name('home-sections.destroy');
        Route::post('/inicio-secciones/reordenar', 'reorder')->name('home-sections.reorder');
        Route::get('/inicio-secciones/{homeSection}/slides', 'slidesPage')->name('home-sections.slides.view');
        Route::get('/inicio-secciones/{homeSection}/slides/listado', 'slides')->name('home-sections.slides.index');
        Route::post('/inicio-secciones/{homeSection}/slides', 'storeSlide')->name('home-sections.slides.store');
        Route::put('/inicio-secciones/{homeSection}/slides/{slide}', 'updateSlide')->name('home-sections.slides.update');
        Route::delete('/inicio-secciones/{homeSection}/slides/{slide}', 'destroySlide')->name('home-sections.slides.destroy');
        Route::post('/inicio-secciones/{homeSection}/slides/reordenar', 'reorderSlides')->name('home-sections.slides.reorder');
    });

// ============================================================
// Menús de navegación
// ============================================================
Route::controller(MenuController::class)
    ->middleware('permission:menus')
    ->group(function () {
        Route::get('/menus', 'index')->name('menus.index');
        Route::get('/menus/nuevo', 'create')->name('menus.create');
        Route::post('/menus/nuevo', 'store')->name('menus.store');
        Route::get('/menus/{menu}/editar', 'edit')->name('menus.edit');
        Route::put('/menus/{menu}/editar', 'update')->name('menus.update');
        Route::delete('/menus/{menu}/eliminar', 'destroy')->name('menus.destroy');
        Route::post('/menus/{menu}/items', 'storeItem')->name('menus.items.store');
        Route::put('/menus/{menu}/items/{item}', 'updateItem')->name('menus.items.update');
        Route::delete('/menus/{menu}/items/{item}', 'destroyItem')->name('menus.items.destroy');
        Route::post('/menus/{menu}/items/reordenar', 'reorderItems')->name('menus.items.reorder');
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
        Route::post('/crear', 'store')->name('store');
        Route::get('/editar/{id}', 'edit')->name('edit');
        Route::put('/editar/{id}', 'update')->name('update');
        Route::delete('/eliminar/{id}', 'destroy')->name('destroy');
        Route::get('/buscar-productos', 'searchProducts')->name('products.search');
        Route::get('/{collection}/productos', 'show')->name('show');
        Route::post('/{collection}/productos', 'addProduct')->name('products.add');
        Route::delete('/{collection}/productos/{product}', 'removeProduct')->name('products.remove');
        Route::post('/{collection}/productos/reordenar', 'reorderProducts')->name('products.reorder');
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
        Route::post('/subir', 'store')->name('store');
        Route::delete('/eliminar/{id}', 'destroy')->name('destroy');
    });

    // sig module
