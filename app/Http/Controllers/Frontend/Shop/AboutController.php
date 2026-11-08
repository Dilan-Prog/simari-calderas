<?php

namespace App\Http\Controllers\Frontend\Shop;

use App\Http\Controllers\Controller;

/**
 * Página pública "Nosotros". Contenido fijo en la vista; solo las fotos se
 * editan desde Configuración del Sitio (grupo "Página Nosotros", claves
 * about.image_*), porque todavía no hay fotografía propia que publicar.
 */
class AboutController extends Controller
{
    public function show()
    {
        return view('frontend.shop.about');
    }
}
