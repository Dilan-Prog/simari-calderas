<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Correo de avisos internos del portal de clientes
    |--------------------------------------------------------------------------
    |
    | Recibe un correo cuando un cliente sube una orden de compra o registra una
    | lectura de analisis quimico fuera de rango (ademas de la tarea en el panel).
    | Acepta varios correos separados por coma.
    |
    */
    'alert_email' => env('PORTAL_ALERT_EMAIL', 'administracion@equitermindustries.com.mx'),

];
