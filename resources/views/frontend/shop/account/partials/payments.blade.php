@php
    // DATOS DEMO (mockup) — el módulo real de métodos de pago llegará con
    // el checkout (tokenización vía pasarela; nunca se almacenan PAN/CVV).
    $demoCards = [
        ['brand' => 'Visa', 'last4' => '4242', 'expiry' => '09/28', 'gradient' => 'linear-gradient(135deg,#1a1f71,#2b3a9e)'],
        ['brand' => 'Mastercard', 'last4' => '8871', 'expiry' => '02/27', 'gradient' => 'linear-gradient(135deg,#3a2a1e,#5c3a1e)'],
    ];
@endphp

<section x-show="section === 'pagos'" x-cloak>
    <div class="portal-title-row">
        <h1 class="portal-title">Métodos de pago</h1>
        <button type="button" class="portal-btn" @click="openModal('card-form', { title: 'Agregar tarjeta' })">+ Agregar tarjeta</button>
    </div>

    <div class="card-grid">
        @foreach ($demoCards as $card)
            <div class="bank-card" style="background:{{ $card['gradient'] }};">
                <div class="bank-card__top">
                    <div class="bank-card__chip">
                        <svg width="20" height="15" viewBox="0 0 24 18" fill="none"><rect x="0.5" y="0.5" width="23" height="17" rx="3" stroke="rgba(255,255,255,0.6)"/><path d="M0 6h24" stroke="rgba(255,255,255,0.6)"/></svg>
                    </div>
                    <span class="bank-card__brand">{{ $card['brand'] }}</span>
                </div>
                <div class="bank-card__number">•••• •••• •••• {{ $card['last4'] }}</div>
                <div class="bank-card__bottom">
                    <span class="bank-card__expiry">Vence {{ $card['expiry'] }}</span>
                    <div class="bank-card__actions">
                        <a href="#" @click.prevent="openModal('card-form', { title: 'Editar tarjeta' })">Editar</a>
                        <a href="#" @click.prevent="openModal('card-delete', @js(['brand' => $card['brand'], 'last4' => $card['last4']]))">Eliminar</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>
