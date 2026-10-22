<?php

use App\Http\Controllers\Backend\Customers\CustomerController;
use App\Http\Controllers\Customers\AddressController;
use App\Http\Controllers\Customers\CalendarController;
use App\Http\Controllers\Customers\EquipmentController;
use App\Http\Controllers\Customers\MonitoringController;
use App\Http\Controllers\Customers\MonitoringEntryController;
use App\Http\Controllers\Customers\MonitoringItemController;
use App\Http\Controllers\Customers\OrderController;
use App\Http\Controllers\Customers\ProfileController;
use App\Http\Controllers\Customers\ServiceHistoryController;
use App\Http\Controllers\Customers\ServiceReportController;
use App\Http\Controllers\Customers\ServiceRequestController;
use App\Http\Controllers\Customers\TechnicalServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CustomerController::class, 'index'])->name('dashboard');

Route::controller(ServiceReportController::class)
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
    ->prefix('technical-services')
    ->name('technical-services.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{service}', 'show')->name('show');
    });

// Portal del cliente: calendario, historial, solicitud de servicios y perfil (solo datos del propio cliente).
Route::get('/calendario', [CalendarController::class, 'index'])->name('calendar');
Route::get('/calendario/eventos', [CalendarController::class, 'events'])->name('calendar.events');
Route::get('/historial', [ServiceHistoryController::class, 'index'])->name('history');
Route::get('/solicitar-servicio', [ServiceRequestController::class, 'create'])->name('service-requests.create');
Route::post('/solicitar-servicio', [ServiceRequestController::class, 'store'])->name('service-requests.store');
Route::get('/perfil', [ProfileController::class, 'show'])->name('profile');
Route::put('/perfil', [ProfileController::class, 'update'])->name('profile.update');
Route::put('/perfil/contrasena', [ProfileController::class, 'updatePassword'])->name('profile.password');

// Pedidos de la tienda del propio cliente.
Route::get('/pedidos', [OrderController::class, 'index'])->name('orders.index');
Route::get('/pedidos/{order}', [OrderController::class, 'show'])->name('orders.show');

// Monitoreo: programas del cliente, registro diario (lecturas y dosificación), gráficas y Gantt.
Route::get('/monitoreo', [MonitoringController::class, 'index'])->name('monitoring.index');
Route::get('/monitoreo/{program}', [MonitoringController::class, 'show'])->name('monitoring.show');
Route::get('/monitoreo/{program}/serie', [MonitoringController::class, 'series'])->name('monitoring.series');
Route::put('/monitoreo/{program}/fechas', [MonitoringController::class, 'updateDates'])->name('monitoring.dates');
Route::post('/monitoreo/{program}/registros', [MonitoringEntryController::class, 'store'])->name('monitoring.entries.store');
Route::put('/monitoreo/registros/{entry}', [MonitoringEntryController::class, 'update'])->name('monitoring.entries.update');
Route::delete('/monitoreo/registros/{entry}', [MonitoringEntryController::class, 'destroy'])->name('monitoring.entries.destroy');
Route::post('/monitoreo/{program}/elementos', [MonitoringItemController::class, 'store'])->name('monitoring.items.store');
Route::delete('/monitoreo/elementos/{item}', [MonitoringItemController::class, 'destroy'])->name('monitoring.items.destroy');

// Equipos del cliente (calderas, torres, chillers...).
Route::get('/equipos', [EquipmentController::class, 'index'])->name('equipment.index');
Route::post('/equipos', [EquipmentController::class, 'store'])->name('equipment.store');
Route::put('/equipos/{equipment}', [EquipmentController::class, 'update'])->name('equipment.update');
Route::delete('/equipos/{equipment}', [EquipmentController::class, 'destroy'])->name('equipment.destroy');

// Libreta de direcciones.
Route::get('/direcciones', [AddressController::class, 'index'])->name('addresses.index');
Route::post('/direcciones', [AddressController::class, 'store'])->name('addresses.store');
Route::put('/direcciones/{address}', [AddressController::class, 'update'])->name('addresses.update');
Route::patch('/direcciones/{address}/predeterminada', [AddressController::class, 'makeDefault'])->name('addresses.default');
Route::delete('/direcciones/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');
