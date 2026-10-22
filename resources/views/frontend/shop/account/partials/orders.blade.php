{{-- "Mis pedidos" de /cuenta. Listado + un detalle por pedido en la misma
     página (selectedOrder del componente accountPortal elige cuál se ve).
     $orders viene de AccountController (App\Support\CustomerOrderPresenter). --}}
@include('frontend.shop.account.partials.order-styles')

<section class="eqo" x-show="section === 'pedidos' && !selectedOrder" x-cloak>
    <h1 class="portal-title">Mis pedidos</h1>

    @if ($orders->isEmpty())
        <div class="eqo-empty">
            <p>Aún no tienes pedidos en la tienda.</p>
            <a href="{{ route('catalog.index') }}" class="eqo-btn">Ver productos</a>
        </div>
    @else
        <div class="eqo-card eqo-list">
            @foreach ($orders as $o)
                <a href="#pedidos" class="eqo-row" @click.prevent="selectedOrder = '{{ $o['number'] }}'; window.scrollTo({ top: 0 })">
                    <div class="eqo-row__id">
                        <div class="eqo-row__num">{{ $o['number'] }}</div>
                        <div class="eqo-row__date">{{ $o['date_short'] }}</div>
                    </div>
                    <span class="eqo-pill" style="background:{{ $o['status_bg'] }};color:{{ $o['status_color'] }};">{{ $o['status_label'] }}</span>
                    <div class="eqo-row__items">{{ $o['items_count'] }} {{ $o['items_count'] === 1 ? 'artículo' : 'artículos' }}</div>
                    <div class="eqo-row__total">${{ number_format($o['total'], 2) }}</div>
                    <svg class="eqo-row__chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            @endforeach
        </div>
        @if ($ordersHasMore ?? false)
            <p class="eqo-note">Mostrando tus {{ $orders->count() }} pedidos más recientes.</p>
        @endif
    @endif
</section>

@foreach ($orders as $o)
    <section class="eqo" x-show="section === 'pedidos' && selectedOrder === '{{ $o['number'] }}'" x-cloak>
        <a href="#pedidos" class="eqo-back" @click.prevent="selectedOrder = null">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Volver a mis pedidos
        </a>

        <div class="eqo-head">
            <div>
                <h1 class="eqo-head__title">Pedido {{ $o['number'] }}</h1>
                <div class="eqo-head__date">Realizado el {{ $o['date'] }}</div>
            </div>
            <span class="eqo-pill" style="background:{{ $o['status_bg'] }};color:{{ $o['status_color'] }};">{{ $o['status_label'] }}</span>
        </div>

        @include('frontend.shop.account.partials.order-detail', ['o' => $o])
    </section>
@endforeach
