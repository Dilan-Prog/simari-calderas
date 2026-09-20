<?php

use App\Http\Controllers\Backend\AdminController;
use App\Http\Controllers\Frontend\Shop\CartController;
use App\Http\Controllers\Frontend\Shop\CatalogController;
use App\Http\Controllers\Frontend\Shop\CheckoutController;
use App\Http\Controllers\Frontend\Shop\CollectionController as ShopCollectionController;
use App\Http\Controllers\Frontend\Shop\LegalController;
use App\Http\Controllers\Frontend\Shop\MercadoPagoCheckoutController;
use App\Http\Controllers\Frontend\Shop\ProductController as ShopProductController;
use App\Http\Controllers\Frontend\Shop\ServicePageController as ShopServicePageController;
use App\Http\Controllers\Frontend\EmailTrackingController;
use App\Http\Controllers\Frontend\GoogleMerchantFeedController;
use App\Http\Controllers\Frontend\SitemapController;
use App\Http\Controllers\MediaServeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\EmailBounceWebhookController;
use App\Http\Controllers\Public\MercadoPagoWebhookController;
use App\Http\Controllers\Public\WhatsappWebhookController;
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
Route::get('/feed/google-merchant.xml', [GoogleMerchantFeedController::class, 'index'])->name('feed.google-merchant');

// Tracking público de correos (sin auth): pixel de apertura, click-through y baja.
Route::get('/email/track/open/{token}', [EmailTrackingController::class, 'open'])->name('email.track.open');
Route::get('/email/track/click/{token}', [EmailTrackingController::class, 'click'])->name('email.track.click');
Route::get('/e/open/{token}.png', [EmailTrackingController::class, 'open'])->name('email.open');
Route::get('/e/click/{token}', [EmailTrackingController::class, 'click'])->name('email.click');
Route::get('/e/unsubscribe/{token}', [EmailTrackingController::class, 'unsubscribe'])->name('email.unsubscribe');

// Webhook público de Meta Cloud API (WhatsApp), sin auth: GET de
// verificación (hub.challenge) + POST de recepción de mensajes/estados.
Route::get('/whatsapp/webhook', [WhatsappWebhookController::class, 'verify'])->name('whatsapp.webhook.verify');
Route::post('/whatsapp/webhook', [WhatsappWebhookController::class, 'receive'])->name('whatsapp.webhook.receive');

// Webhook del buzón de correo (Hostinger "Agentic Mail" / hMail, evento
// message.received) -- por ahora solo registra el payload real en log
// mientras se documenta su formato (ver EmailBounceWebhookController).
Route::post('/webhooks/email-bounce', [EmailBounceWebhookController::class, 'receive'])->name('webhooks.email-bounce');

// Webhook de notificaciones de Mercado Pago (IPN): ?data.id=X&type=payment
// en la URL + header x-request-id. Nunca confía en el status del payload,
// solo dispara ProcessMercadoPagoWebhookJob (que sí hace la llamada
// server-to-server real).
Route::post('/webhooks/mercadopago', [MercadoPagoWebhookController::class, 'receive'])->name('webhooks.mercadopago');

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
Route::controller(CartController::class)->prefix('carrito')->name('cart.')->group(function () {
    Route::post('/agregar', 'add')->name('add');
    Route::patch('/actualizar', 'update')->name('update');
    Route::delete('/eliminar', 'remove')->name('remove');
    Route::get('/mini', 'mini')->name('mini');
    Route::get('/recuperar/{token}', 'recover')->name('recover');
});
// Checkout de una sola página (acordeón: Carrito / Envío / Pago apilados en
// checkout.index) -- shipping.store y confirm siguen siendo endpoints POST
// normales, ahora consumidos por fetch() desde esa misma página en vez de
// un GET por cada paso (ver CheckoutController::index()).
Route::controller(CheckoutController::class)->prefix('finalizar-pedido')->name('checkout.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/envio', 'storeShipping')->name('shipping.store');
    Route::post('/contacto', 'captureContact')->name('capture-contact')->middleware('throttle:20,1');
    Route::post('/confirmar', 'confirm')->name('confirm');
});
Route::get('/finalizar-pedido/pago/mercadopago/{order:order_number}', [MercadoPagoCheckoutController::class, 'show'])->name('checkout.payment.mercadopago');
// throttle bajo (10/min por IP) a propósito -- charge()/checkoutPro() son
// las únicas rutas de este checkout que de verdad procesan un cobro contra
// Mercado Pago; sin límite, alguien podría usarlas para probar en volumen
// si tarjetas robadas son válidas (carding). Un checkout real nunca hace
// más de 1-2 intentos por minuto.
Route::post('/finalizar-pedido/pago/mercadopago/{order:order_number}/cobrar/{payment}', [MercadoPagoCheckoutController::class, 'charge'])->name('checkout.payment.mercadopago.charge')->middleware('throttle:10,1');
Route::post('/finalizar-pedido/pago/mercadopago/{order:order_number}/checkout-pro/{payment}', [MercadoPagoCheckoutController::class, 'checkoutPro'])->name('checkout.payment.mercadopago.checkout-pro')->middleware('throttle:10,1');
// thanks() la llama nuestro propio JS en segundo plano (fetch) además de la
// navegación real -- límite más generoso, no procesa cobros, solo
// reconcilia contra la API.
Route::get('/finalizar-pedido/pago/mercadopago/{order:order_number}/gracias', [MercadoPagoCheckoutController::class, 'thanks'])->name('checkout.payment.mercadopago.thanks')->middleware('throttle:30,1');
Route::get('/finalizar-pedido/pago/mercadopago/{order:order_number}/reintentar/{payment}', [MercadoPagoCheckoutController::class, 'retry'])->name('checkout.payment.mercadopago.retry')->middleware('signed');
Route::get('/producto/{slug}', [ShopProductController::class, 'show'])->name('product.show');
Route::get('/coleccion/{slug}', [ShopCollectionController::class, 'show'])->name('collection.show');
// Arquitectura de 3 niveles (hub → categoría → servicio), todas resueltas
// por ServicePage vía page_type/parent_id — ver ShopServicePageController.
// /servicio/{slug} (singular) se conserva para páginas "planas" legacy sin
// padre; showLegacy() redirige 301 a la ruta anidada si el servicio ya
// tiene categoría asignada.
Route::get('/servicios', [ShopServicePageController::class, 'hub'])->name('service-pages.hub');
Route::get('/servicios/{level2}/{level3}', [ShopServicePageController::class, 'showNested'])->name('service-pages.level3');
Route::get('/servicios/{level2}', [ShopServicePageController::class, 'category'])->name('service-pages.level2');
Route::get('/servicio/{slug}', [ShopServicePageController::class, 'showLegacy'])->name('service-page.show');

// Aviso de Privacidad / Términos y Condiciones — únicas 2 páginas del sitio
// viejo que sobreviven, migradas a shop porque el footer y el registro de
// clientes (frontend/shop/layouts/footer.blade.php, frontend/shop/account/auth.blade.php)
// enlazan activamente a estos mismos nombres de ruta.
Route::controller(LegalController::class)->group(function () {
    Route::get('/aviso-privacidad', 'privacyNotice')->name('privacy-notice');
    Route::get('/terminos-condiciones', 'termsOfService')->name('terms-of-service');
    Route::get('/politica-de-devoluciones', 'returnPolicy')->name('return-policy');
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
