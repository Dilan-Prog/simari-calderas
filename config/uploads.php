<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Base path para archivos subidos
    |--------------------------------------------------------------------------
    |
    | Fuera de public_html en producción (una carpeta hermana), para que el
    | auto-deploy de Hostinger no los borre en cada push — resetea todo lo
    | que no esté trackeado en git dentro del árbol deployado. Si no se
    | define, App\Support\UploadPath cae en public_path() para dev local.
    |
    | IMPORTANTE: se lee vía config() y no env() directo en el código de la
    | app (ver App\Support\UploadPath::base()) porque env() fuera de un
    | archivo de config deja de funcionar en cuanto el servidor corre
    | `php artisan config:cache` — devuelve null aunque el .env tenga el
    | valor correcto, sin ningún aviso.
    |
    */
    'base_path' => env('UPLOADS_BASE_PATH'),

];
