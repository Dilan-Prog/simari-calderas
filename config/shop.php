<?php

return [
    // Sin cálculo real de lead time en el proyecto -- valor estático
    // mostrado en el paso de Envío del checkout y en la confirmación del
    // pedido (ver checkout/index.blade.php y checkout/confirmation.blade.php).
    'delivery_estimate_label' => env('SHOP_DELIVERY_ESTIMATE_LABEL', '3–5 días hábiles'),
];
