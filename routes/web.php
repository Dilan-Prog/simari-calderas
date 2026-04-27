<?php

use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\SitemapController;
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
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/crear-admin', function () {
    \App\Models\User::create([
        'first_name' => 'Dev Dilan Yovani',
        'last_name'  => 'Garcia Gonzalez',
        'email'      => 'dilangarcia145@gmail.com',
        'password'   => bcrypt('GAGD050127HMSRNLA3'),
        'status'     => 'active',
        'role_id'    => 1,
        'position'   => 'Administrador',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return 'Usuario creado!';
});
Route::controller(HomeController::class)->group(function () {
    Route::get('/', 'index')->name('home');
    Route::get('/nuestra-empresa', 'company')->name('company');
    Route::get('/contacto', 'contact')->name('contact');
    Route::get('/aviso-privacidad', 'privacyNotice')->name('privacy-notice');
    Route::get('/terminos-condiciones', 'termsOfService')->name('terms-of-service');
    // Services
    Route::get('servicios/mantenimiento-industrial', 'industrialMaintenance')->name('industrial-maintenance');
    Route::get('servicios/ingenieria-hidraulica', 'hydraulicEngineering')->name('hydraulic-engineering');
    Route::get('servicios/calibracion-equipos', 'equipementCalibration')->name('equipement-calibration');
    Route::get('servicios/tratamiento-agua', 'waterTreatment')->name('water-treatment');
    Route::get('servicios/automatizacion-industrial', 'automation')->name('automation');
    Route::get('servicios/mantenimiento-chillers', 'chillerMaintenance')->name('chiller-maintenance');
    Route::get('servicios/desincrustacion-calderas', 'descaleBoilers')->name('descale-boilers');
    Route::get('servicios/proyectos-industriales', 'industrialProject')->name('industrial-project');
    Route::get('servicios/reparacion-secadoras', 'hairRepair')->name('hair-repair');
    // Products
    Route::get('productos/calderas-industriales-simari', 'simariBoilers')->name('simari-boilers');
    Route::get('productos/calentadores-solares', 'solarHeaters')->name('solar-heaters');
    Route::get('productos/instrumentacion-industrial', 'industrialInstrumentation')->name('industrial-instrumentation');
    Route::get('productos/tratamiento-agua-anti-incrustante', 'waterTreatmentAnti')->name('water-treatment-Anti');
    Route::get('productos/refacciones-mantenimiento-industrial', 'spareParts')->name('spare-parts');
    // Masstercal Rinnai
    Route::get('masstercal-rinnai/bombas-de-calor-rinnai', 'rinnaiHeatPumps')->name('heat-pumps');
    Route::get('masstercal-rinnai/calentadores-agua-rinnai', 'waterHeaters')->name('water-heaters');
    Route::get('masstercal-rinnai/calentadores-electricos-rinnai', 'electricHeaters')->name('electric-heaters');
    Route::get('masstercal-rinnai/calentadores-paso-gas-rinnai', 'tanklessHeaters')->name('tankless-heaters');
    Route::get('masstercal-rinnai/suavizadores-filtros-rinnai', 'softenersFilters')->name('softeners-filters');
    Route::get('masstercal-rinnai/tanques-almacenamiento-rinnai', 'storageTanks')->name('storage-tanks');
});
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
