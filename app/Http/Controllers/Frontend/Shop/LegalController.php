<?php

namespace App\Http\Controllers\Frontend\Shop;

use App\Http\Controllers\Controller;

/**
 * Aviso de Privacidad y Términos y Condiciones — contenido estático,
 * portado del sitio viejo (App\Http\Controllers\Frontend\HomeController,
 * ya retirado) porque el footer y el registro de clientes de shop
 * enlazan activamente a estas 2 páginas.
 */
class LegalController extends Controller
{
    public function privacyNotice()
    {
        return view('frontend.shop.legal.privacy-notice');
    }

    public function termsOfService()
    {
        return view('frontend.shop.legal.terms-of-service');
    }
}
