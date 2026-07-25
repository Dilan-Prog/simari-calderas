<section x-show="section === 'pedidos' && selectedOrder === '{{ $order['number'] }}'" x-cloak>
    <a href="#" class="order-back" @click.prevent="selectedOrder = null">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Volver a mis pedidos
    </a>

    <div class="order-detail__header">
        <div>
            <h1 class="portal-title" style="margin-bottom:6px;">Pedido {{ $order['number'] }}</h1>
            <div class="order-row__date">Realizado el {{ $order['date'] }}</div>
        </div>
        <span class="order-pill {{ $order['status_class'] }}">{{ $order['status'] }}</span>
    </div>

    {{-- Tracking --}}
    <div class="portal-card">
        <div class="order-tracking">
            @foreach ($order['steps'] as $i => $step)
                @php $done = ($i + 1) <= $order['current_step']; @endphp
                <div class="order-tracking__step">
                    @if ($i > 0)
                        <div class="order-tracking__line {{ $done ? 'is-done' : '' }}"></div>
                    @endif
                    <div class="order-tracking__dot {{ $done ? 'is-done' : '' }}">{{ $done ? '✓' : $i + 1 }}</div>
                    <div class="order-tracking__label {{ $done ? 'is-done' : '' }}">{{ $step }}</div>
                    <div class="order-tracking__date">{{ $order['step_dates'][$i] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="order-detail__grid">
        <div>
            <div class="portal-card portal-card--flush">
                <div class="portal-card__title portal-card__title--padded">Productos</div>
                @foreach ($order['items'] as $item)
                    <div class="order-item">
                        <div class="order-item__img">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#c4c8cd" stroke-width="1.6"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                        </div>
                        <div class="order-item__info">
                            <div class="order-item__name">{{ $item['name'] }}</div>
                            <div class="order-item__meta">SKU: {{ $item['sku'] }} · Cant: {{ $item['qty'] }}</div>
                        </div>
                        <div class="order-item__price">{{ $item['price'] }}</div>
                    </div>
                @endforeach
            </div>

            <div class="order-detail__info-grid">
                <div class="portal-card">
                    <div class="portal-card__title">Dirección de envío</div>
                    <div class="order-info-text">
                        {{ $order['ship_label'] }}<br>
                        {{ $order['ship_street'] }}<br>
                        {{ $order['ship_colonia'] }}<br>
                        {{ $order['ship_city'] }}, CP {{ $order['ship_cp'] }}
                    </div>
                </div>
                <div class="portal-card">
                    <div class="portal-card__title">Método de pago</div>
                    <div class="order-info-text">
                        {{ $order['payment'] }}<br>
                        Guía de rastreo: {{ $order['tracking'] }}
                    </div>
                </div>
            </div>
        </div>

        <div class="portal-card order-summary">
            <div class="portal-card__title">Resumen del pedido</div>
            <div class="order-summary__row"><span>Subtotal</span><span>{{ $order['subtotal'] }}</span></div>
            <div class="order-summary__row"><span>Envío</span><span>{{ $order['shipping'] }}</span></div>
            <div class="order-summary__row"><span>IVA</span><span>{{ $order['tax'] }}</span></div>
            <div class="order-summary__total"><span>Total</span><span>{{ $order['total'] }}</span></div>
            <button type="button" class="portal-btn portal-btn--full">Descargar factura</button>
            <button type="button" class="portal-btn portal-btn--outline portal-btn--full">Volver a comprar</button>
        </div>
    </div>
</section>
