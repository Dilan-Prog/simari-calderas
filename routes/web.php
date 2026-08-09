<?php

use App\Http\Controllers\Backend\AdminController;
use App\Http\Controllers\Frontend\Shop\CatalogController;
use App\Http\Controllers\Frontend\Shop\CollectionController as ShopCollectionController;
use App\Http\Controllers\Frontend\Shop\LegalController;
use App\Http\Controllers\Frontend\Shop\ProductController as ShopProductController;
use App\Http\Controllers\Frontend\Shop\ServicePageController as ShopServicePageController;
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
    Route::get('/buscar-en-vivo', 'liveSearch')->middleware('throttle:30,1')->name('catalog.live-search');
});
Route::get('/producto/{slug}', [ShopProductController::class, 'show'])->name('product.show');
Route::get('/coleccion/{slug}', [ShopCollectionController::class, 'show'])->name('collection.show');
// Singular a propósito: /servicios/... ya tiene 9+ rutas estáticas de un
// segmento (ver bloque "new/old services" más abajo); /servicio/{slug}
// evita colisionar con ellas sin depender del orden de registro.
Route::get('/servicio/{slug}', [ShopServicePageController::class, 'show'])->name('service-page.show');

// Aviso de Privacidad / Términos y Condiciones — únicas 2 páginas del sitio
// viejo que sobreviven, migradas a shop porque el footer y el registro de
// clientes (frontend/shop/layouts/footer.blade.php, frontend/shop/account/auth.blade.php)
// enlazan activamente a estos mismos nombres de ruta.
Route::controller(LegalController::class)->group(function () {
    Route::get('/aviso-privacidad', 'privacyNotice')->name('privacy-notice');
    Route::get('/terminos-condiciones', 'termsOfService')->name('terms-of-service');
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
