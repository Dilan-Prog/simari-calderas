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
    Route::get('/aviso-privacidad', 'privacyNotice')->name('privacy-notice');
    Route::get('/terminos-condiciones', 'termsOfService')->name('terms-of-service');
    // Services
    Route::get('/proyectos-industriales', 'industrialProject')->name('industrial-project');
    Route::get('/calibracion-equipos', 'equipementCalibration')->name('equipement-calibration');
    Route::get('/reparacion-secadoras', 'hairRepair')->name('hair-repair');
    // Masstercal Rinnai
    Route::get('/tanques-almacenamiento', 'storageTanks')->name('storage-tanks');
    Route::get('/masstercal-rinnai/bombas-de-calor', 'rinnaiHeatPumps')->name('heat-pumps');

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
