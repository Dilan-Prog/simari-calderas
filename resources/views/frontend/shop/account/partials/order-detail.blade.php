{{--
    Contenido del detalle de un pedido del cliente. Compartido por la tienda
    (/cuenta#pedidos) y el portal (/customer/pedidos/{folio}); cada vista pone
    su propio encabezado alrededor. Requiere @include('...order-styles') en la
    página y la variable $o armada por App\Support\CustomerOrderPresenter::build().
--}}
@php
    $money = fn ($v) => '$' . number_format((float) $v, 2);
    $cur = $o['currency'];
@endphp

<div class="eqo-card">
    <div class="eqo-timeline">
        @foreach ($o['timeline'] as $i => $step)
            @php $mod = $step['terminal'] ? 'is-terminal' : ($step['done'] ? 'is-done' : ''); @endphp
            <div class="eqo-step">
                @if ($i > 0)
                    <div class="eqo-step__line {{ $mod }}"></div>
                @endif
                <div class="eqo-step__dot {{ $mod }}">{{ $step['terminal'] ? '✕' : ($step['done'] ? '✓' : $i + 1) }}</div>
                <div class="eqo-step__label {{ $step['done'] ? 'is-done' : '' }}">{{ $step['label'] }}</div>
                @if ($step['date'])
                    <div class="eqo-step__date">{{ $step['date'] }}</div>
                @endif
            </div>
        @endforeach
    </div>
    @if (! empty($o['timeline'][1]['hint']))
        <p class="eqo-timeline-hint">{{ $o['timeline'][1]['hint'] }}</p>
    @endif
</div>

<div class="eqo-grid">
    <div class="eqo-stack">
        <div class="eqo-card eqo-card--flush">
            <div class="eqo-card__title">Productos</div>
            @foreach ($o['items'] as $item)
                <div class="eqo-item">
                    <div class="eqo-item__img">
                        @if ($item['image'])
                            <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" loading="lazy">
                        @else
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#c4c8cd" stroke-width="1.6"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                        @endif
                    </div>
                    <div class="eqo-item__info">
                        <div class="eqo-item__name">{{ $item['name'] }}</div>
                        <div class="eqo-item__meta">
                            @if ($item['sku']) SKU: {{ $item['sku'] }} · @endif Cant: {{ $item['qty'] }}
                        </div>
                    </div>
                    <div class="eqo-item__price">
                        {{ $money($item['line_total']) }}
                        @if ($item['qty'] > 1)
                            <small>{{ $money($item['unit_price']) }} c/u</small>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="eqo-info-grid">
            <div class="eqo-card">
                <div class="eqo-card__title">Dirección de envío</div>
                <div class="eqo-text">
                    @if ($o['address']['name']) <strong>{{ $o['address']['name'] }}</strong><br> @endif
                    @if ($o['address']['line1']) {{ $o['address']['line1'] }}<br> @endif
                    @if ($o['address']['line2']) {{ $o['address']['line2'] }}<br> @endif
                    @if ($o['address']['city_line']) {{ $o['address']['city_line'] }}<br> @endif
                    @if ($o['address']['reference']) <span style="color:#9ca3af;">Ref.: {{ $o['address']['reference'] }}</span><br> @endif
                    @if ($o['address']['phone']) Tel. {{ $o['address']['phone'] }} @endif
                </div>
            </div>

            <div class="eqo-card">
                <div class="eqo-card__title">Envío</div>
                @if ($o['shipment'])
                    @php $s = $o['shipment']; @endphp
                    @if ($s['carrier'])
                        <div class="eqo-kv"><span>Paquetería</span><span>{{ $s['carrier'] }}</span></div>
                    @endif
                    @if ($s['guia'])
                        <div class="eqo-kv"><span>Guía</span><span style="font-family:monospace;">{{ $s['guia'] }}</span></div>
                    @endif
                    @if ($s['status_label'])
                        <div class="eqo-kv"><span>Estado</span><span>{{ $s['status_label'] }}</span></div>
                    @endif
                    @if ($s['shipped_at'])
                        <div class="eqo-kv"><span>Enviado</span><span>{{ $s['shipped_at'] }}</span></div>
                    @endif
                    @if ($s['eta'])
                        <div class="eqo-kv"><span>Entrega estimada</span><span>{{ $s['eta'] }}</span></div>
                    @endif
                    @if ($s['tracking_url'])
                        <a class="eqo-btn" href="{{ $s['tracking_url'] }}" target="_blank" rel="noopener noreferrer">Rastrear mi paquete →</a>
                    @endif
                @else
                    <div class="eqo-text" style="color:#9ca3af;">Aún no se genera la guía de envío. Te la mostraremos aquí en cuanto salga tu pedido.</div>
                @endif
            </div>
        </div>
    </div>

    <div class="eqo-stack">
        <div class="eqo-card">
            <div class="eqo-card__title">Resumen del pedido</div>
            <div class="eqo-sum"><span>Subtotal</span><span>{{ $money($o['subtotal']) }}</span></div>
            <div class="eqo-sum"><span>Envío</span><span>{{ $o['shipping'] > 0 ? $money($o['shipping']) : 'Gratis' }}</span></div>
            @if ($o['discount'] > 0)
                <div class="eqo-sum eqo-sum--discount"><span>Descuento</span><span>-{{ $money($o['discount']) }}</span></div>
            @endif
            <div class="eqo-sum"><span>IVA incluido</span><span>{{ $money($o['tax']) }}</span></div>
            <div class="eqo-sum eqo-sum--total"><span>Total</span><span>{{ $money($o['total']) }} {{ $cur }}</span></div>
        </div>

        <div class="eqo-card">
            <div class="eqo-card__title">Pago</div>
            <div class="eqo-kv"><span>Método</span><span>{{ $o['payment']['method'] ?? '—' }}</span></div>
            @if ($o['payment']['detail'])
                <div class="eqo-kv"><span>Detalle</span><span>{{ $o['payment']['detail'] }}</span></div>
            @endif
            <div class="eqo-kv"><span>Estado</span><span><span class="eqo-tag eqo-tag--{{ $o['payment']['tone'] }}">{{ $o['payment']['status_label'] }}</span></span></div>
            @if ($o['payment']['paid_at'])
                <div class="eqo-kv"><span>Pagado el</span><span>{{ $o['payment']['paid_at'] }}</span></div>
            @endif
        </div>

        <div class="eqo-card">
            <div class="eqo-card__title">Factura (CFDI)</div>
            @if ($o['invoice'])
                @php $inv = $o['invoice']; @endphp
                <div class="eqo-text" style="margin-bottom:6px;color:#0f7a4f;font-weight:700;">Solicitaste factura para este pedido.</div>
                @if ($inv['razon_social']) <div class="eqo-kv"><span>Razón social</span><span>{{ $inv['razon_social'] }}</span></div> @endif
                @if ($inv['rfc']) <div class="eqo-kv"><span>RFC</span><span>{{ $inv['rfc'] }}</span></div> @endif
                @if ($inv['regimen']) <div class="eqo-kv"><span>Régimen fiscal</span><span>{{ $inv['regimen'] }}</span></div> @endif
                @if ($inv['uso_cfdi']) <div class="eqo-kv"><span>Uso de CFDI</span><span>{{ $inv['uso_cfdi'] }}</span></div> @endif
                @if ($inv['cp_fiscal']) <div class="eqo-kv"><span>CP fiscal</span><span>{{ $inv['cp_fiscal'] }}</span></div> @endif
            @else
                <div class="eqo-text" style="color:#9ca3af;">No solicitaste factura para este pedido.</div>
            @endif
        </div>
    </div>
</div>
