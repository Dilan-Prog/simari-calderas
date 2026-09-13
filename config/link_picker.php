<?php

/**
 * Páginas estáticas ofrecidas por el tipo "static_page" del picker de
 * enlaces del admin (ver App\Http\Controllers\Backend\LinkController).
 *
 * Cada entrada normal es {label, route} — `route` es un NOMBRE de ruta real
 * (verificado con `php artisan route:list`), resuelto en el controlador vía
 * route($entry['route']). "Contacto" es la excepción: el sitio no tiene una
 * página de contacto dedicada (routes/web.php solo registra un POST
 * checkout.capture-contact para captura progresiva de checkout, no una
 * página pública) — el footer (resources/views/frontend/shop/layouts/
 * footer.blade.php) resuelve "Contacto" como un link directo de WhatsApp,
 * así que esa entrada usa `url` (una URL cruda) en vez de `route`, para no
 * inventar un nombre de ruta que no existe.
 */
return [
    'static_pages' => [
        ['label' => 'Inicio', 'route' => 'home'],
        ['label' => 'Catálogo', 'route' => 'catalog.index'],
        ['label' => 'Hub de Servicios', 'route' => 'service-pages.hub'],
        ['label' => 'Contacto', 'url' => 'https://wa.me/5214494577320'],
        ['label' => 'Aviso de Privacidad', 'route' => 'privacy-notice'],
        ['label' => 'Términos y Condiciones', 'route' => 'terms-of-service'],
        ['label' => 'Política de Devoluciones', 'route' => 'return-policy'],
    ],
];
