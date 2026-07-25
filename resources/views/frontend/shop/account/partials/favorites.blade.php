@php
    // DATOS DEMO (mockup) — la lógica real de favoritos llega en la fase de
    // backend correspondiente.
    $demoFavorites = [
        ['name' => 'Caldera Pirotubular SIMARI 30 BHP', 'sku' => 'SKU-R1', 'price' => '$178,500 MXN', 'original' => '$210,000 MXN', 'discount' => '15% OFF', 'monthly' => '$29,750 MXN', 'badge' => 'Más vendido'],
        ['name' => 'Calentador Comercial Rinnai', 'sku' => 'SKU-R2', 'price' => '$64,500 MXN', 'original' => '$72,000 MXN', 'discount' => '10% OFF', 'monthly' => '$10,750 MXN', 'badge' => null],
        ['name' => 'Suavizador Automático Aquaplus Serie F', 'sku' => 'SKU-R3', 'price' => '$38,900 MXN', 'original' => '$45,000 MXN', 'discount' => '14% OFF', 'monthly' => '$6,483 MXN', 'badge' => null],
        ['name' => 'Tablero de Control PLC', 'sku' => 'SKU-R4', 'price' => '$33,500 MXN', 'original' => '$39,500 MXN', 'discount' => '15% OFF', 'monthly' => '$5,583 MXN', 'badge' => null],
        ['name' => 'Calentador de Paso Rinnai REU-A 2000', 'sku' => 'SKU-R5', 'price' => '$24,900 MXN', 'original' => '$28,900 MXN', 'discount' => '14% OFF', 'monthly' => '$4,150 MXN', 'badge' => null],
    ];
@endphp

<section x-show="section === 'favoritos'" x-cloak>
    <h1 class="portal-title">Favoritos</h1>

    <div class="fav-grid">
        @foreach ($demoFavorites as $fav)
            <div class="fav-card">
                @if ($fav['badge'])
                    <span class="fav-card__badge">{{ $fav['badge'] }}</span>
                @endif
                <div class="fav-card__img">
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#c4c8cd" stroke-width="1.6"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                </div>
                <div class="fav-card__name">{{ $fav['name'] }}</div>
                <div class="fav-card__sku">SKU: {{ $fav['sku'] }}</div>
                <div class="fav-card__original">{{ $fav['original'] }}</div>
                <div class="fav-card__price">{{ $fav['price'] }}</div>
                <span class="fav-card__discount">{{ $fav['discount'] }}</span>
                <div class="fav-card__financing">6 meses sin intereses de {{ $fav['monthly'] }}</div>
                <div class="fav-card__shipping">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="8" width="15" height="9" rx="1"/><path d="M16 11h3l3 3v3h-6z"/><circle cx="6" cy="19" r="1.5"/><circle cx="17.5" cy="19" r="1.5"/></svg>
                    Envío gratis
                </div>
                <button type="button" class="portal-btn portal-btn--full">Agregar al carrito</button>
            </div>
        @endforeach
    </div>
</section>
