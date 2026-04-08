<?php

use App\Http\Controllers\Backend\AdminController;
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
    Route::get('servicios/proyectos-industriales', 'industrialProject')->name('industrial-project');
    Route::get('servicios/desincrustacion-calderas', 'descaleBoilers')->name('descale-boilers');
    Route::get('servicios/reparacion-secadoras', 'hairRepair')->name('hair-repair');
    // Products
    Route::get('productos/calderas-simari', 'simariBoilers')->name('simari-boilers');
    Route::get('productos/calentadores-solares', 'solarHeaters')->name('solar-heaters');
    Route::get('productos/instrumentacion-industrial', 'industrialInstrumentation')->name('industrial-instrumentation');
    Route::get('productos/tratamiento-agua-antiincrustante', 'waterTreatmentAnti')->name('water-treatment-Anti');
    Route::get('productos/refacciones-mantenimiento', 'spareParts')->name('spare-parts');
    // Masstercal Rinnai
    Route::get('masstercal-rinnai/bombas-de-calor-rinnai', 'rinnaiHeatPumps')->name('heat-pumps');
    Route::get('masstercal-rinnai/calentadores-agua-rinnai', 'waterHeaters')->name('water-heaters');
    Route::get('masstercal-rinnai/calentadores-electricos-rinnai', 'electricHeaters')->name('electric-heaters');
    Route::get('masstercal-rinnai/calentadores-paso-gas-rinnai', 'tanklessHeaters')->name('tankless-heaters');
    Route::get('masstercal-rinnai/suavizadores-filtros-rinnai', 'softenersFilters')->name('softeners-filters');
    Route::get('masstercal-rinnai/tanques-almacenamiento-rinnai', 'storageTanks')->name('storage-tanks');
    // Admin (rutas legacy del HomeController — no modificar)
    Route::get('/admin/users', [HomeController::class, 'users'])->name('admin');
    Route::get('admin/clients',[HomeController::class,'clients'])->name('clients');
    Route::get('admin/supliers',[HomeController::class,'supliers'])->name('supliers');
});

// ─── Panel administrativo ────────────────────────────────────────────────────

// Dashboard principal (sidebar + contenido dinámico)
Route::get('/admin', function () {
    return view('admin.dashboard');
})->name('admin.panel');

// Partial de Usuarios — devuelve solo el HTML del contenido (para fetch desde el dashboard)
Route::get('/admin/usuarios-partial', function () {
    $roles = \App\Models\Role::all();
    return view('admin.users.partial', compact('roles'));
})->name('admin.users.partial');

// Vista completa de Usuarios (con layout propio)
Route::get('/admin/usuarios', [AdminController::class, 'index'])->name('admin.users.index');

// Crear usuario
Route::post('/admin/usuarios', [AdminController::class, 'store'])->name('admin.users.store');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
