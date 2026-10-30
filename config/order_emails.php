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

    ],

];
