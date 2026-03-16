<?php

use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::controller(HomeController::class)->group(function () {
    Route::get('/', 'index')->name('home');
    Route::get('/nuestra-empresa', 'business')->name('bussiness');
    Route::get('/contacto', 'contact')->name('contact');
    Route::get('/aviso-privacidad', 'privacyNotice')->name('privacy-notice');
    Route::get('/terminos-condiciones', 'termsOfService')->name('terms-of-service');
    // Services
    Route::get('servicios/mantenimiento-industrial', 'industrialMaintenance')->name('industrial-maintenance');
    Route::get('servicios/ingenieria-hidraulica', 'hydraulicEngineering')->name('hydraulic-engineering');
    Route::get('servicios/calibracion-equipos', 'equipementCalibration')->name('equipement-calibration');
    Route::get('servicios/tratamiento-agua', 'waterTreatment')->name('water-treatment');
    Route::get('servicios/automatizacion', 'automation')->name('automation');
    Route::get('servicios/mantenimiento-chillers', 'chillerMaintenance')->name('chiller-maintenance');
    Route::get('servicios/proyectos-industriales', 'industrialProject')->name('industrial-project');
    Route::get('servicios/desincrustacion-calderas', 'descaleBoilers')->name('descale-boilers');
    Route::get('servicios/reparacion-secadoras', 'hairRepair')->name('hair-repair');
    // Products
    Route::get('productos/calderas-simari', 'simariBoilers')->name('simari-boilers');
    Route::get('productos/calentadores-solares', 'solarHeaters')->name('solar-heaters');
    Route::get('productos/instrumentacion-industrial', 'industrialInstrumentation')->name('industrial-instrumentation');
    Route::get('productos/tratamiento-agua', 'waterTreatment')->name('water-treatment');
    Route::get('productos/refacciones-mantenimiento', 'spareParts')->name('spare-parts');
    // Masstercal Rinnai
    Route::get('masstercal-rinnai/bombas-de-calor', 'rinnaiHeatPumps')->name('heat-pumps');
    Route::get('masstercal-rinnai/calentadores-agua', 'waterHeaters')->name('water-heaters');
    Route::get('masstercal-rinnai/calentadores-electricos', 'electricHeaters')->name('electric-heaters');
    Route::get('masstercal-rinnai/calentadores-paso-gas', 'tanklessHeaters')->name('tankless-heaters');
    Route::get('masstercal-rinnai/suavizadores-filtros', 'softenersFilters')->name('softeners-filters');
    Route::get('masstercal-rinnai/tanques-almacenamiento', 'storageTanks')->name('storage-tanks');

});

Route::get('/',[HomeController::class,'index'])->name('home');


// les hago el comentario de que toda la documentacion la quiero en Ingles
// otra parte si es que van a tocar las rutas requiero que  la parte del /ruta que sea en español
// si no no la toquen y diganme /nombrerutaespanol y va a llevar un difernete nombre




Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
