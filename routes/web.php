<?php

use App\Http\Controllers\Backend\AdminController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\Shop\CatalogController;
use App\Http\Controllers\Frontend\Shop\CollectionController as ShopCollectionController;
use App\Http\Controllers\Frontend\Shop\ProductController as ShopProductController;
use App\Http\Controllers\Frontend\SitemapController;
use App\Http\Controllers\MediaServeController;
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

// Serves uploaded product/service-report/document files from UploadPath::base(),
// which may live outside public_html in production (see App\Support\UploadPath).
Route::get('/media/{path}', [MediaServeController::class, 'show'])
    ->where('path', '.*')
    ->name('media.show');
Route::controller(CatalogController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/catalogo', 'index')->name('catalog.index');
    // FIX (SEO slugs): widened to match multi-segment hierarchical slugs
    // (e.g. "bombas-de-calor/masstercal") — see Redirect model + Category::slug.
    Route::get('/catalogo/{categorySlug}', 'category')
        ->where('categorySlug', '.*')
        ->name('catalog.category');
    Route::get('/buscar-en-vivo', 'liveSearch')->name('catalog.live-search');
});
Route::get('/producto/{slug}', [ShopProductController::class, 'show'])->name('product.show');
Route::get('/coleccion/{slug}', [ShopCollectionController::class, 'show'])->name('collection.show');

Route::controller(HomeController::class)->group(function () {
    Route::get('/nuestra-empresa', 'company')->name('company');
    Route::get('/contacto', 'contact')->name('contact');
    Route::get('/aviso-privacidad', 'privacyNotice')->name('privacy-notice');
    Route::get('/terminos-condiciones', 'termsOfService')->name('terms-of-service');
    // Services
    // new services SEO
    Route::get('/servicios-calderas', 'boilerServices')->name('boiler-services');
    Route::get('/servicios/calderas/reparacion-calderas', 'boilerRepair')->name('boiler-repair');
    Route::get('/servicios/calderas/mantenimiento-calderas', 'boilerMaintenance')->name('boiler-maintenance');
    Route::get('/desincrustacion-calderas', 'boilerDescaling')->name('boiler-descaling');
    
    //old services
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
    // Admin (rutas legacy del HomeController — no modificar)
    Route::get('/admin/users', [HomeController::class, 'users'])->name('admin');
    Route::get('admin/clients',[HomeController::class,'clients'])->name('clients');
    Route::get('admin/supliers',[HomeController::class,'supliers'])->name('supliers');
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
require __DIR__.'/shop-auth.php';

// FIX (SEO redirects): catches any URL that doesn't match any route pattern
// at all (e.g. a deprecated URL structure). Registered last so every real
// route gets first chance to match. Old category/collection URLs whose
// pattern still matches today but whose slug no longer exists are instead
// handled inside CatalogController::category() / CollectionController::show()
// — this fallback alone can't see those, since the route itself matches.
Route::fallback(function (\Illuminate\Http\Request $request) {
    if ($redirect = \App\Models\Redirect::resolve($request->path())) {
        return redirect($redirect->new_path, $redirect->status_code);
    }
    abort(404);
});
