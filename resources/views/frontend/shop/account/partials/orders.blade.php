@php
    // DATOS DEMO (fieles al mockup) — se reemplazan cuando exista el módulo
    // real de pedidos. Estados: entregado | transito | cancelado.
    $demoOrders = [
        [
            'number' => 'EQ-482913', 'date' => '14 jul 2026', 'items_label' => '3 productos',
            'status' => 'Entregado', 'status_class' => 'is-delivered', 'total' => '$178,500 MXN',
            'ship_label' => 'Oficina Corporativa', 'ship_street' => 'Av. Industria 1200', 'ship_colonia' => 'Parque Industrial', 'ship_city' => 'Ciudad de México, CDMX', 'ship_cp' => '06000',
            'payment' => 'Visa •••• 4242', 'tracking' => 'MX-8827301',
            'subtotal' => '$165,000 MXN', 'shipping' => 'Gratis', 'tax' => '$13,500 MXN',
            'items' => [
                ['name' => 'Caldera Pirotubular SIMARI 30 BHP', 'sku' => 'SIM-30BHP', 'qty' => 1, 'price' => '$178,500 MXN'],
            ],
            'steps' => ['Confirmado', 'Preparando', 'Enviado', 'Entregado'],
            'step_dates' => ['14 jul', '15 jul', '16 jul', '18 jul'],
            'current_step' => 4,
        ],
        [
            'number' => 'EQ-481207', 'date' => '2 jul 2026', 'items_label' => '1 producto',
            'status' => 'En tránsito', 'status_class' => 'is-transit', 'total' => '$64,500 MXN',
            'ship_label' => 'Planta Norte', 'ship_street' => 'Blvd. Manufactura 450', 'ship_colonia' => 'Zona Industrial', 'ship_city' => 'Monterrey, N.L.', 'ship_cp' => '64000',
            'payment' => 'Mastercard •••• 8871', 'tracking' => 'MX-8819044',
            'subtotal' => '$59,900 MXN', 'shipping' => 'Gratis', 'tax' => '$4,600 MXN',
            'items' => [
                ['name' => 'Bomba de Calor Rinnai BCP 50', 'sku' => 'RIN-BCP50', 'qty' => 1, 'price' => '$59,900 MXN'],
            ],
            'steps' => ['Confirmado', 'Preparando', 'Enviado', 'Entregado'],
            'step_dates' => ['2 jul', '3 jul', '4 jul', 'Estimado 8 jul'],
            'current_step' => 3,
        ],
        [
            'number' => 'EQ-479812', 'date' => '19 jun 2026', 'items_label' => '5 productos',
            'status' => 'Entregado', 'status_class' => 'is-delivered', 'total' => '$92,300 MXN',
            'ship_label' => 'Oficina Corporativa', 'ship_street' => 'Av. Industria 1200', 'ship_colonia' => 'Parque Industrial', 'ship_city' => 'Ciudad de México, CDMX', 'ship_cp' => '06000',
            'payment' => 'Visa •••• 4242', 'tracking' => 'MX-8792215',
            'subtotal' => '$84,500 MXN', 'shipping' => 'Gratis', 'tax' => '$7,800 MXN',
            'items' => [
                ['name' => 'Suavizador Automático Aquaplus Serie F', 'sku' => 'AQP-SF', 'qty' => 2, 'price' => '$38,900 MXN'],
                ['name' => 'Tablero de Control PLC', 'sku' => 'TC-PLC01', 'qty' => 1, 'price' => '$33,500 MXN'],
            ],
            'steps' => ['Confirmado', 'Preparando', 'Enviado', 'Entregado'],
            'step_dates' => ['19 jun', '20 jun', '21 jun', '24 jun'],
            'current_step' => 4,
        ],
        [
            'number' => 'EQ-477630', 'date' => '3 jun 2026', 'items_label' => '2 productos',
            'status' => 'Cancelado', 'status_class' => 'is-cancelled', 'total' => '$38,900 MXN',
            'ship_label' => 'Planta Norte', 'ship_street' => 'Blvd. Manufactura 450', 'ship_colonia' => 'Zona Industrial', 'ship_city' => 'Monterrey, N.L.', 'ship_cp' => '64000',
            'payment' => 'Mastercard •••• 8871', 'tracking' => '—',
            'subtotal' => '$36,000 MXN', 'shipping' => 'Gratis', 'tax' => '$2,900 MXN',
            'items' => [
                ['name' => 'Refacción válvula de control', 'sku' => 'REF-VC22', 'qty' => 2, 'price' => '$19,450 MXN'],
            ],
            'steps' => ['Confirmado', 'Preparando', 'Enviado', 'Entregado'],
            'step_dates' => ['3 jun', '—', '—', '—'],
            'current_step' => 0,
        ],
    ];
@endphp

<section x-show="section === 'pedidos' && !selectedOrder" x-cloak>
    <h1 class="portal-title">Mis pedidos</h1>
    <div class="portal-card portal-card--flush">
        @foreach ($demoOrders as $order)
            <div class="order-row">
                <div class="order-row__id">
                    <div class="order-row__number">{{ $order['number'] }}</div>
                    <div class="order-row__date">{{ $order['date'] }}</div>
                </div>
                <div class="order-row__items">{{ $order['items_label'] }}</div>
                <span class="order-pill {{ $order['status_class'] }}">{{ $order['status'] }}</span>
                <div class="order-row__total">{{ $order['total'] }}</div>
                <a href="#" class="auth-link" @click.prevent="selectedOrder = '{{ $order['number'] }}'">Ver detalle</a>
            </div>
        @endforeach
    </div>
</section>

@foreach ($demoOrders as $order)
    @include('frontend.shop.account.partials.order-detail', ['order' => $order])
@endforeach
