<?php

use App\Http\Controllers\Backend\Customers\CustomerController;
use App\Http\Controllers\Customers\AddressController;
use App\Http\Controllers\Customers\CalendarController;
use App\Http\Controllers\Customers\EquipmentController;
use App\Http\Controllers\Customers\MaterialDeliveryController;
use App\Http\Controllers\Customers\MonitoringController;
use App\Http\Controllers\Customers\MonitoringEntryController;
use App\Http\Controllers\Customers\MonitoringItemController;
use App\Http\Controllers\Customers\OrderController;
use App\Http\Controllers\Customers\ProfileController;
use App\Http\Controllers\Customers\QuoteController;
use App\Http\Controllers\Customers\TimelineController;
use App\Http\Controllers\Customers\WaterAnalysisController;
use App\Http\Controllers\Customers\PurchaseOrderController;
use App\Http\Controllers\Customers\TeamController;
use App\Http\Controllers\Customers\TeamRoleController;
use App\Http\Controllers\Customers\ServiceHistoryController;
use App\Http\Controllers\Customers\ServiceReportController;
use App\Http\Controllers\Customers\ServiceRequestController;
use App\Http\Controllers\Customers\TechnicalServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CustomerController::class, 'index'])->name('dashboard');

Route::controller(ServiceReportController::class)
    ->middleware('portal.permission:service-reports,view')
    ->prefix('service-reports')
    ->name('service-reports.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{report}', 'show')->name('show');
        Route::get('/{report}/pdf', 'downloadPdf')->name('download-pdf');
        Route::get('/{report}/pdf-preview', 'previewPdf')->name('pdf-preview');
        Route::match(['get', 'post'], '/{report}/calificar', 'rate')->name('rate');
        Route::post('/{report}/calificar/omitir', 'skipRating')->name('rate.skip');
    });

Route::controller(TechnicalServiceController::class)
    ->middleware('portal.permission:technical-services,view')
    ->prefix('technical-services')
    ->name('technical-services.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{service}', 'show')->name('show');
    });

// Portal del cliente: calendario, historial, solicitud de servicios y perfil (solo datos del propio cliente).
Route::get('/calendario', [CalendarController::class, 'index'])->name('calendar')->middleware('portal.permission:calendar,view');
Route::get('/calendario/eventos', [CalendarController::class, 'events'])->name('calendar.events')->middleware('portal.permission:calendar,view');
Route::get('/historial', [ServiceHistoryController::class, 'index'])->name('history')->middleware('portal.permission:history,view');
Route::get('/solicitar-servicio', [ServiceRequestController::class, 'create'])->name('service-requests.create')->middleware('portal.permission:service-requests,view');
Route::post('/solicitar-servicio', [ServiceRequestController::class, 'store'])->name('service-requests.store')->middleware('portal.permission:service-requests,create');
Route::get('/perfil', [ProfileController::class, 'show'])->name('profile');
Route::put('/perfil', [ProfileController::class, 'update'])->name('profile.update');
Route::put('/perfil/contrasena', [ProfileController::class, 'updatePassword'])->name('profile.password');

// Pedidos de la tienda del propio cliente.
Route::get('/pedidos', [OrderController::class, 'index'])->name('orders.index')->middleware('portal.permission:orders,view');
Route::get('/pedidos/{order}', [OrderController::class, 'show'])->name('orders.show')->middleware('portal.permission:orders,view');

// Cotizaciones del cliente (solo las que ya se le enviaron; nunca borradores).
Route::get('/cotizaciones', [QuoteController::class, 'index'])->name('quotes.index')->middleware('portal.permission:quotes,view');
Route::get('/cotizaciones/{quote}', [QuoteController::class, 'show'])->name('quotes.show')->middleware('portal.permission:quotes,view');
Route::get('/cotizaciones/{quote}/pdf', [QuoteController::class, 'pdf'])->name('quotes.pdf')->middleware('portal.permission:quotes,view');

// Entregas de material: pedidos de entrega y reportes de entrega firmados.
Route::get('/entregas', [MaterialDeliveryController::class, 'index'])->name('deliveries.index')->middleware('portal.permission:deliveries,view');
Route::get('/entregas/pedidos/{order}', [MaterialDeliveryController::class, 'showOrder'])->name('deliveries.orders.show')->middleware('portal.permission:deliveries,view');
Route::get('/entregas/reportes/{report}', [MaterialDeliveryController::class, 'showReport'])->name('deliveries.reports.show')->middleware('portal.permission:deliveries,view');
Route::get('/entregas/reportes/{report}/pdf', [MaterialDeliveryController::class, 'reportPdf'])->name('deliveries.reports.pdf')->middleware('portal.permission:deliveries,view');

// Expediente: línea de tiempo con todo lo ligado al cliente.
Route::get('/expediente', [TimelineController::class, 'index'])->name('timeline')->middleware('portal.permission:timeline,view');

// Monitoreo: programas del cliente, registro diario (lecturas y dosificación), gráficas y Gantt.
Route::get('/monitoreo', [MonitoringController::class, 'index'])->name('monitoring.index')->middleware('portal.permission:monitoring,view');
Route::get('/monitoreo/{program}', [MonitoringController::class, 'show'])->name('monitoring.show')->middleware('portal.permission:monitoring,view');
Route::get('/monitoreo/{program}/serie', [MonitoringController::class, 'series'])->name('monitoring.series')->middleware('portal.permission:monitoring,view');
Route::put('/monitoreo/{program}/fechas', [MonitoringController::class, 'updateDates'])->name('monitoring.dates')->middleware('portal.permission:monitoring,edit');
Route::post('/monitoreo/{program}/registros', [MonitoringEntryController::class, 'store'])->name('monitoring.entries.store')->middleware('portal.permission:monitoring,create');
Route::put('/monitoreo/registros/{entry}', [MonitoringEntryController::class, 'update'])->name('monitoring.entries.update')->middleware('portal.permission:monitoring,edit');
Route::delete('/monitoreo/registros/{entry}', [MonitoringEntryController::class, 'destroy'])->name('monitoring.entries.destroy')->middleware('portal.permission:monitoring,delete');
Route::post('/monitoreo/{program}/elementos', [MonitoringItemController::class, 'store'])->name('monitoring.items.store')->middleware('portal.permission:monitoring,create');
Route::delete('/monitoreo/elementos/{item}', [MonitoringItemController::class, 'destroy'])->name('monitoring.items.destroy')->middleware('portal.permission:monitoring,delete');

// Equipos del cliente (calderas, torres, chillers...).
Route::get('/equipos', [EquipmentController::class, 'index'])->name('equipment.index')->middleware('portal.permission:equipment,view');
Route::post('/equipos', [EquipmentController::class, 'store'])->name('equipment.store')->middleware('portal.permission:equipment,create');
Route::put('/equipos/{equipment}', [EquipmentController::class, 'update'])->name('equipment.update')->middleware('portal.permission:equipment,edit');
Route::delete('/equipos/{equipment}', [EquipmentController::class, 'destroy'])->name('equipment.destroy')->middleware('portal.permission:equipment,delete');

// Libreta de direcciones.
Route::get('/direcciones', [AddressController::class, 'index'])->name('addresses.index')->middleware('portal.permission:addresses,view');
Route::post('/direcciones', [AddressController::class, 'store'])->name('addresses.store')->middleware('portal.permission:addresses,create');
Route::put('/direcciones/{address}', [AddressController::class, 'update'])->name('addresses.update')->middleware('portal.permission:addresses,edit');
Route::patch('/direcciones/{address}/predeterminada', [AddressController::class, 'makeDefault'])->name('addresses.default')->middleware('portal.permission:addresses,edit');
Route::delete('/direcciones/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy')->middleware('portal.permission:addresses,delete');

// >>> Reporte de análisis químico (registro diario: dureza, pH, alcalinidad, conductividad, STD).
Route::get('/analisis-quimico', [WaterAnalysisController::class, 'index'])->name('water-analysis.index')->middleware('portal.permission:water-analysis,view');
Route::get('/analisis-quimico/dia/{date}', [WaterAnalysisController::class, 'day'])->name('water-analysis.day')->middleware('portal.permission:water-analysis,view');
Route::post('/analisis-quimico', [WaterAnalysisController::class, 'store'])->name('water-analysis.store')->middleware('portal.permission:water-analysis,create');
Route::put('/analisis-quimico/{report}', [WaterAnalysisController::class, 'update'])->name('water-analysis.update')->middleware('portal.permission:water-analysis,edit');
Route::delete('/analisis-quimico/{report}', [WaterAnalysisController::class, 'destroy'])->name('water-analysis.destroy')->middleware('portal.permission:water-analysis,delete');
// <<< Reporte de análisis químico

// >>> Órdenes de compra del cliente (PDF subido al portal).
Route::get('/ordenes-de-compra', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index')->middleware('portal.permission:purchase-orders,view');
Route::post('/ordenes-de-compra', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store')->middleware('portal.permission:purchase-orders,create');
Route::get('/ordenes-de-compra/{purchaseOrder}/archivo', [PurchaseOrderController::class, 'download'])->name('purchase-orders.download')->middleware('portal.permission:purchase-orders,view');
Route::delete('/ordenes-de-compra/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->name('purchase-orders.destroy')->middleware('portal.permission:purchase-orders,delete');
// <<< Órdenes de compra del cliente

// >>> Usuarios y roles del portal (solo el titular de la cuenta).
Route::get('/equipo', [TeamController::class, 'index'])->name('team.index')->middleware('portal.permission:team,view');
Route::post('/equipo/usuarios', [TeamController::class, 'storeUser'])->name('team.users.store')->middleware('portal.permission:team,create');
Route::put('/equipo/usuarios/{id}', [TeamController::class, 'updateUser'])->whereNumber('id')->name('team.users.update')->middleware('portal.permission:team,edit');
Route::patch('/equipo/usuarios/{id}/estado', [TeamController::class, 'toggleUser'])->whereNumber('id')->name('team.users.toggle')->middleware('portal.permission:team,edit');
Route::put('/equipo/usuarios/{id}/contrasena', [TeamController::class, 'resetPassword'])->whereNumber('id')->name('team.users.password')->middleware('portal.permission:team,edit');
Route::delete('/equipo/usuarios/{id}', [TeamController::class, 'destroyUser'])->whereNumber('id')->name('team.users.destroy')->middleware('portal.permission:team,delete');
Route::post('/equipo/roles', [TeamRoleController::class, 'store'])->name('team.roles.store')->middleware('portal.permission:team,create');
Route::put('/equipo/roles/{id}', [TeamRoleController::class, 'update'])->whereNumber('id')->name('team.roles.update')->middleware('portal.permission:team,edit');
Route::delete('/equipo/roles/{id}', [TeamRoleController::class, 'destroy'])->whereNumber('id')->name('team.roles.destroy')->middleware('portal.permission:team,delete');
// <<< Usuarios y roles del portal
