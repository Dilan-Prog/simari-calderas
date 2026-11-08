<?php

/*
|--------------------------------------------------------------------------
| Correos de pedidos de la tienda (Ecommerce > Correos de pedidos)
|--------------------------------------------------------------------------
| Textos por defecto de cada correo. El admin puede cambiar asunto, título y
| mensaje desde el panel (se guardan en settings, grupo "integraciones", claves
| order_emails.*); lo que no cambie sigue saliendo de aquí.
|
| Se pueden usar estos marcadores en asunto, título y mensaje:
*/

return [

    /*
     | Aviso interno de pedido nuevo (le llega al equipo, no al cliente). Se puede cambiar el destino y apagarlo
     | desde Ecommerce > Correos de pedidos; sin cambios sale a este correo. Varios destinos separados por coma.
     */
    'staff' => [
        'enabled' => true,
        'email'   => env('ORDER_ALERT_EMAIL', 'administracion@equitermindustries.com.mx'),
    ],

    /*
     | Plantillas (diseños) que se pueden elegir para cada correo desde Ecommerce > Correos de pedidos.
     | Cambian colores y la disposición del encabezado, no el contenido. La elegida se guarda por correo
     | en settings (order_emails.{evento}.template); sin elegir, sale 'clasica' (el diseño de siempre).
     |
     | layout: standard = franja con el nombre de la empresa y el título debajo;
     |         banner   = el título va dentro de una franja grande de color;
     |         flat     = sin tarjeta ni franja: texto limpio con una línea de color arriba.
     */
    'default_template' => 'clasica',

    'templates' => [

        'clasica' => [
            'name' => 'Clásica', 'description' => 'Encabezado negro y detalles naranja. El diseño de siempre.',
            'layout' => 'standard', 'radius' => '10px', 'border' => 'none',
            'page' => '#f4f5f7', 'card' => '#ffffff',
            'header_bg' => '#141516', 'header_color' => '#ffffff',
            'heading' => '#141516', 'text' => '#1f2933', 'muted' => '#52606d', 'line' => '#eef0f2',
            'accent' => '#ff6213', 'accent_dark' => '#c4480a', 'accent_soft' => '#fff6f0', 'accent_line' => '#ffd2b8',
            'button_bg' => '#ff6213', 'button_color' => '#ffffff', 'footer_bg' => '#f4f5f7',
        ],

        'destacada' => [
            'name' => 'Destacada', 'description' => 'El título va dentro de una franja grande naranja. Llama la atención.',
            'layout' => 'banner', 'radius' => '10px', 'border' => 'none',
            'page' => '#f4f5f7', 'card' => '#ffffff',
            'header_bg' => '#ff6213', 'header_color' => '#ffffff',
            'heading' => '#141516', 'text' => '#1f2933', 'muted' => '#52606d', 'line' => '#eef0f2',
            'accent' => '#ff6213', 'accent_dark' => '#c4480a', 'accent_soft' => '#fff6f0', 'accent_line' => '#ffd2b8',
            'button_bg' => '#141516', 'button_color' => '#ffffff', 'footer_bg' => '#f4f5f7',
        ],

        'minimalista' => [
            'name' => 'Minimalista', 'description' => 'Fondo blanco, sin tarjetas ni franjas. Se lee como una carta.',
            'layout' => 'flat', 'radius' => '0', 'border' => 'none',
            'page' => '#ffffff', 'card' => '#ffffff',
            'header_bg' => '#ffffff', 'header_color' => '#141516',
            'heading' => '#141516', 'text' => '#1f2933', 'muted' => '#52606d', 'line' => '#e4e7eb',
            'accent' => '#141516', 'accent_dark' => '#141516', 'accent_soft' => '#fafafa', 'accent_line' => '#d9dde2',
            'button_bg' => '#141516', 'button_color' => '#ffffff', 'footer_bg' => '#ffffff',
        ],

        'corporativa' => [
            'name' => 'Corporativa azul', 'description' => 'Encabezado azul marino y detalles azules. Sobria y formal.',
            'layout' => 'standard', 'radius' => '6px', 'border' => '1px solid #d8e1ea',
            'page' => '#eef2f6', 'card' => '#ffffff',
            'header_bg' => '#12324f', 'header_color' => '#ffffff',
            'heading' => '#12324f', 'text' => '#1f2933', 'muted' => '#52606d', 'line' => '#e6ecf2',
            'accent' => '#1e6fb8', 'accent_dark' => '#12324f', 'accent_soft' => '#eef5fc', 'accent_line' => '#c6dcf2',
            'button_bg' => '#1e6fb8', 'button_color' => '#ffffff', 'footer_bg' => '#eef2f6',
        ],

        'verde' => [
            'name' => 'Verde', 'description' => 'Franja verde con el título dentro. Buena para pago confirmado o entrega.',
            'layout' => 'banner', 'radius' => '10px', 'border' => 'none',
            'page' => '#f1f6f3', 'card' => '#ffffff',
            'header_bg' => '#0f7a4f', 'header_color' => '#ffffff',
            'heading' => '#0b4f34', 'text' => '#1f2933', 'muted' => '#52606d', 'line' => '#e5efe9',
            'accent' => '#0f7a4f', 'accent_dark' => '#0b4f34', 'accent_soft' => '#eaf7f0', 'accent_line' => '#bfe5d1',
            'button_bg' => '#0f7a4f', 'button_color' => '#ffffff', 'footer_bg' => '#f1f6f3',
        ],

    ],

    'tokens' => [
        '{{order_number}}'    => 'Folio del pedido (PW-2026-0001)',
        '{{customer_name}}'   => 'Nombre del cliente',
        '{{total}}'           => 'Total del pedido con moneda',
        '{{payment_method}}'  => 'Método de pago elegido',
        '{{carrier}}'         => 'Paquetería (solo cuando ya hay envío)',
        '{{tracking_number}}' => 'Número de guía (solo cuando ya hay envío)',
    ],

    'events' => [

        'created_offline' => [
            'label'       => 'Pedido recibido, pago pendiente',
            'when'        => 'Al crear el pedido con transferencia, referencia, crédito u otro método que no se cobra en línea. Incluye los datos para pagar.',
            'subject'     => 'Recibimos tu pedido {{order_number}}',
            'heading'     => '¡Gracias por tu pedido!',
            'intro'       => "Hola {{customer_name}},\n\nRecibimos tu pedido {{order_number}} por {{total}}. Para apartarlo, realiza tu pago con los datos de abajo y responde a este correo con tu comprobante.",
        ],

        'paid' => [
            'label'       => 'Pago confirmado',
            'when'        => 'Cuando el pedido pasa a Pagado: pago con tarjeta o Mercado Pago aprobado, o cuando el equipo confirma una transferencia.',
            'subject'     => 'Pago confirmado de tu pedido {{order_number}}',
            'heading'     => 'Pago confirmado',
            'intro'       => "Hola {{customer_name}},\n\nConfirmamos el pago de tu pedido {{order_number}}. Ya lo estamos preparando y te avisaremos cuando salga a tu domicilio.",
        ],

        'shipped' => [
            'label'       => 'Pedido enviado',
            'when'        => 'Cuando el pedido pasa a Enviado. Incluye paquetería y guía si ya se registraron en Envíos.',
            'subject'     => 'Tu pedido {{order_number}} va en camino',
            'heading'     => 'Tu pedido va en camino',
            'intro'       => "Hola {{customer_name}},\n\nTu pedido {{order_number}} ya salió. Aquí tienes los datos para dar seguimiento a tu envío.",
        ],

        'delivered' => [
            'label'       => 'Pedido entregado',
            'when'        => 'Cuando el pedido pasa a Entregado.',
            'subject'     => 'Entregamos tu pedido {{order_number}}',
            'heading'     => 'Pedido entregado',
            'intro'       => "Hola {{customer_name}},\n\nTu pedido {{order_number}} fue entregado. Gracias por comprar con nosotros; si algo no llegó como esperabas, responde a este correo y lo revisamos.",
        ],

        'cancelled' => [
            'label'       => 'Pedido cancelado',
            'when'        => 'Cuando el pedido pasa a Cancelado.',
            'subject'     => 'Cancelamos tu pedido {{order_number}}',
            'heading'     => 'Pedido cancelado',
            'intro'       => "Hola {{customer_name}},\n\nTu pedido {{order_number}} fue cancelado. Si no lo solicitaste o tienes dudas, responde a este correo y con gusto te ayudamos.",
        ],

        // Solo da nombre a la bitácora: el aviso interno no se redacta en el panel ni está en StoreOrderMailService::EVENTS.
        'staff_new_order' => [
            'label' => 'Aviso interno: pedido nuevo',
        ],

    ],

];
