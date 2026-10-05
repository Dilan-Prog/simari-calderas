<?php

return [
    // Sin cálculo real de lead time en el proyecto -- valor estático
    // mostrado en el paso de Envío del checkout y en la confirmación del
    // pedido (ver checkout/index.blade.php y checkout/confirmation.blade.php).
    'delivery_estimate_label' => env('SHOP_DELIVERY_ESTIMATE_LABEL', '3–5 días hábiles'),

    // Secciones GLOBALES de la página de producto (HomeSection page='product':
    // "También te puede interesar", "Más vendidos en {categoria}"). Apagadas:
    // la página de producto ahora muestra solo los bloques por producto
    // (plantillas/propios, ver App\Services\ProductBlocks). Poner en true
    // para reactivarlas.
    'product_global_sections' => env('SHOP_PRODUCT_GLOBAL_SECTIONS', false),
];
