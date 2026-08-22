@extends('admin.layouts.master')

@section('title', 'Rastreo de Anuncios - Detalle - Admin')

@php
    // Metadatos de presentación por tipo de evento -- calca EVENT_TYPES del
    // mockup: page_view es el único sin intención de compra; purchase además
    // de "alta intención" es un "win" (verde propio), el resto es naranja.
    $eventMeta = [
        'page_view'      => ['title' => 'Vista de página',    'icon' => '◻', 'intent' => false],
        'whatsapp_click' => ['title' => 'Clic en WhatsApp',   'icon' => '◉', 'intent' => true],
        'add_to_cart'    => ['title' => 'Agregó al carrito',  'icon' => '▣', 'intent' => true],
        'cart_abandoned' => ['title' => 'Carrito abandonado', 'icon' => '◌', 'intent' => true],
        'quote_start'    => ['title' => 'Inició cotización',  'icon' => '▤', 'intent' => true],
        'checkout_start' => ['title' => 'Inició checkout',    'icon' => '▩', 'intent' => true],
        'purchase'       => ['title' => 'Compra completada',  'icon' => '★', 'intent' => true, 'win' => true],
    ];

    $conversionAmount = null;
    $conversionCurrency = null;
    $orderKind = null;
    $folio = null;

    if ($converted) {
        if ($storeOrder !== null) {
            $conversionAmount = $storeOrder->total;
            $conversionCurrency = $storeOrder->currency;
            $orderKind = 'Pedido pagado';
            $folio = $storeOrder->order_number;
        } elseif ($quote) {
            $conversionAmount = $quote->total;
            $conversionCurrency = $quote->currency;
            $orderKind = 'Cotización aceptada';
            $folio = $quote->quote_number;
        }
    }
@endphp

@section('content')
<div class="ad-tracking-page">
    <a href="{{ route('admin.ad-tracking.index') }}" class="ad-tracking-back-btn">
        <span class="ad-tracking-back-arrow">‹</span> Volver al listado
    </a>

    <div class="ad-tracking-header-card">
        <div class="ad-tracking-header-top">
            <div class="ad-tracking-header-main">
                <div class="ad-tracking-kind">{{ $adVisit->identifier_kind }}</div>

                <div class="ad-tracking-clickid-row">
                    <span class="ad-tracking-clickid">{{ $adVisit->primary_click_id ?? '—' }}</span>
                    @if ($adVisit->primary_click_id)
                        <button
                            type="button"
                            id="copyClickIdBtn"
                            class="ad-tracking-copy-btn"
                            data-clickid="{{ $adVisit->primary_click_id }}"
                        >Copiar</button>
                    @endif
                </div>

                @if ($adVisit->utm_source || $adVisit->utm_medium || $adVisit->utm_campaign)
                    <div class="ad-tracking-utm-chips">
                        @if ($adVisit->utm_source)
                            <span class="ad-tracking-utm-chip"><span class="ad-tracking-utm-key">source</span>{{ $adVisit->utm_source }}</span>
                        @endif
                        @if ($adVisit->utm_medium)
                            <span class="ad-tracking-utm-chip"><span class="ad-tracking-utm-key">medium</span>{{ $adVisit->utm_medium }}</span>
                        @endif
                        @if ($adVisit->utm_campaign)
                            <span class="ad-tracking-utm-chip"><span class="ad-tracking-utm-key">campaign</span>{{ $adVisit->utm_campaign }}</span>
                        @endif
                    </div>
                @endif

                <div class="ad-tracking-stats">
                    <div class="ad-tracking-stat">
                        <div class="ad-tracking-stat-label">Primera visita</div>
                        <div class="ad-tracking-stat-value">
                            {{ $adVisit->first_seen_at?->locale('es')->translatedFormat('d \d\e F \d\e Y, H:i \h\r\s') ?? '—' }}
                        </div>
                    </div>
                    <div class="ad-tracking-stat">
                        <div class="ad-tracking-stat-label">Última visita</div>
                        <div class="ad-tracking-stat-value">
                            {{ $adVisit->last_seen_at?->locale('es')->translatedFormat('d \d\e F \d\e Y, H:i \h\r\s') ?? '—' }}
                        </div>
                    </div>
                    <div class="ad-tracking-stat">
                        <div class="ad-tracking-stat-label">Eventos</div>
                        <div class="ad-tracking-stat-value">{{ $events->count() }} registrados</div>
                    </div>
                </div>
            </div>

            <span class="ad-tracking-big-badge {{ $converted ? 'is-converted' : 'is-pending' }}">
                @if ($converted)
                    Convertido — {{ $conversionCurrency }} {{ number_format((float) $conversionAmount, 2) }}
                @else
                    Sin conversión
                @endif
            </span>
        </div>
    </div>

    @if ($converted)
        <div class="ad-tracking-attribution-card">
            <div>
                <div class="ad-tracking-attribution-label">Conversión atribuida</div>
                <div class="ad-tracking-attribution-title">{{ $orderKind }} · {{ $folio }}</div>
                <div class="ad-tracking-attribution-meta">
                    Monto {{ $conversionCurrency }} {{ number_format((float) $conversionAmount, 2) }}
                    · atribuido a esta visita por {{ ucfirst($adVisit->utm_source ?? 'tráfico directo') }}
                </div>
            </div>
            @if ($storeOrder !== null)
                <a href="{{ route('admin.orders.show', $storeOrder) }}" class="ad-tracking-attribution-btn">Abrir registro</a>
            @elseif ($quote)
                <a href="{{ route('admin.quotes.show', $quote) }}" class="ad-tracking-attribution-btn">Abrir registro</a>
            @endif
        </div>
    @elseif ($quote)
        <div class="ad-tracking-attribution-card ad-tracking-attribution-card--pending">
            <div>
                <div class="ad-tracking-attribution-label">Cotización sin aceptar</div>
                <div class="ad-tracking-attribution-title">{{ $quote->quote_number }}</div>
                <div class="ad-tracking-attribution-meta">
                    Monto {{ $quote->currency }} {{ number_format((float) $quote->total, 2) }}
                </div>
            </div>
            <a href="{{ route('admin.quotes.show', $quote) }}" class="ad-tracking-attribution-btn">Abrir registro</a>
        </div>
    @endif

    @if ($customer)
        <p class="ad-tracking-customer-note">
            Identificado como cliente:
            <a href="{{ route('admin.clients.information', $customer) }}">{{ $customer->first_name }} {{ $customer->last_name }}</a>
        </p>
    @endif

    <div class="ad-tracking-timeline-card">
        <div class="ad-tracking-timeline-header">
            <div>
                <div class="ad-tracking-timeline-title">Línea de tiempo</div>
                <div class="ad-tracking-timeline-subtitle">Orden cronológico. Los momentos de intención de compra están resaltados.</div>
            </div>
            <label class="ad-tracking-switch">
                <input type="checkbox" id="intentToggle">
                <span class="ad-tracking-switch-track"><span class="ad-tracking-switch-knob"></span></span>
                Solo alta intención
            </label>
        </div>

        <div class="ad-tracking-timeline-body">
            @forelse ($eventsByDay as $dayLabel => $dayEvents)
                <div class="ad-tracking-day-group">
                    <div class="ad-tracking-day-pill">{{ $dayLabel }}</div>
                    <div class="ad-tracking-day-events">
                        @foreach ($dayEvents as $event)
                            @php
                                $meta = $eventMeta[$event->event_type] ?? ['title' => $event->event_type, 'icon' => '◻', 'intent' => true];
                                $isIntent = $meta['intent'] ?? true;
                                $isWin = $meta['win'] ?? false;
                                $iconClass = $isWin ? 'is-win' : ($isIntent ? 'is-high' : 'is-normal');
                            @endphp
                            <div class="ad-tracking-event-row" data-intent="{{ $isIntent ? 1 : 0 }}">
                                <span class="ad-tracking-event-icon {{ $iconClass }}">{{ $meta['icon'] }}</span>
                                <div class="ad-tracking-event-card {{ $iconClass }}">
                                    <div class="ad-tracking-event-top">
                                        <span class="ad-tracking-event-title {{ $iconClass }}">{{ $meta['title'] }}</span>
                                        <span class="ad-tracking-event-time">{{ $event->occurred_at?->format('H:i') }} hrs</span>
                                    </div>
                                    @if ($event->url)
                                        <div class="ad-tracking-event-page" title="{{ $event->url }}">{{ Str::limit($event->url, 90) }}</div>
                                    @endif
                                    @if ($event->product)
                                        <div class="ad-tracking-event-product">
                                            <span class="ad-tracking-event-product-swatch"></span>
                                            <span class="ad-tracking-event-product-name">{{ $event->product->name }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="ad-tracking-timeline-empty">Sin eventos registrados.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        .ad-tracking-page {
            width: 95%;
            max-width: 1180px;
            margin: 0 auto;
            padding: 24px 0 48px;
            height: 100%;
            overflow-y: auto;
            box-sizing: border-box;
            font-family: 'Inter Tight', system-ui, sans-serif;
            color: #141516;
        }

        .ad-tracking-back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 13px;
            font-size: 13px;
            font-weight: 600;
            color: #141516;
            background: #ffffff;
            border: 1px solid #d7dade;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .06);
            text-decoration: none;
        }
        .ad-tracking-back-btn:hover {
            background: #f4f5f7;
            color: #141516;
        }
        .ad-tracking-back-arrow {
            font-size: 15px;
            line-height: 1;
        }

        .ad-tracking-header-card,
        .ad-tracking-attribution-card,
        .ad-tracking-timeline-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .06);
            margin-top: 16px;
        }

        .ad-tracking-header-card { padding: 22px 24px; }

        .ad-tracking-header-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 22px;
            flex-wrap: wrap;
        }

        .ad-tracking-header-main { min-width: 0; }

        .ad-tracking-kind {
            font-size: 11.5px;
            font-weight: 600;
            letter-spacing: .6px;
            text-transform: uppercase;
            color: #8a9099;
        }

        .ad-tracking-clickid-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 8px;
            flex-wrap: wrap;
        }

        .ad-tracking-clickid {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 14.5px;
            font-weight: 500;
            letter-spacing: -.2px;
            word-break: break-all;
        }

        .ad-tracking-copy-btn {
            padding: 5px 11px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 7px;
            border: 1px solid #d7dade;
            background: #ffffff;
            color: #4b5158;
            cursor: pointer;
            white-space: nowrap;
        }
        .ad-tracking-copy-btn.is-copied {
            border-color: #c8ecd5;
            background: #eaf7ee;
            color: #16794a;
        }

        .ad-tracking-utm-chips {
            display: flex;
            gap: 7px;
            margin-top: 12px;
            flex-wrap: wrap;
        }

        .ad-tracking-utm-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 9px;
            border-radius: 99px;
            background: #f4f5f7;
            font-size: 11.5px;
            color: #4b5158;
        }

        .ad-tracking-utm-key {
            font-weight: 600;
            color: #8a9099;
            text-transform: uppercase;
            letter-spacing: .4px;
            font-size: 10px;
        }

        .ad-tracking-stats {
            display: flex;
            gap: 26px;
            margin-top: 16px;
            flex-wrap: wrap;
        }

        .ad-tracking-stat-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .5px;
            text-transform: uppercase;
            color: #a0a6ad;
        }

        .ad-tracking-stat-value {
            font-size: 13.5px;
            color: #2a2c30;
            margin-top: 4px;
        }

        .ad-tracking-big-badge {
            display: inline-flex;
            align-items: center;
            padding: 9px 16px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: -.1px;
            white-space: nowrap;
            border: 1px solid transparent;
        }
        .ad-tracking-big-badge.is-converted {
            background: #eaf7ee;
            color: #12703a;
            border-color: #c8ecd5;
        }
        .ad-tracking-big-badge.is-pending {
            background: #f0f1f3;
            color: #6b7280;
            border-color: #e3e5e9;
        }

        .ad-tracking-attribution-card {
            border-left: 4px solid #16a34a;
            padding: 18px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }
        .ad-tracking-attribution-card--pending {
            border-left-color: #d7dade;
        }

        .ad-tracking-attribution-label {
            font-size: 11.5px;
            font-weight: 600;
            letter-spacing: .6px;
            text-transform: uppercase;
            color: #16a34a;
        }
        .ad-tracking-attribution-card--pending .ad-tracking-attribution-label {
            color: #8a9099;
        }

        .ad-tracking-attribution-title {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -.3px;
            margin-top: 6px;
        }

        .ad-tracking-attribution-meta {
            font-size: 13px;
            color: #6b7280;
            margin-top: 5px;
        }

        .ad-tracking-attribution-btn {
            padding: 9px 15px;
            font-size: 13px;
            font-weight: 600;
            color: #ffffff;
            background: #ff6213;
            border-radius: 8px;
            text-decoration: none;
            white-space: nowrap;
        }
        .ad-tracking-attribution-btn:hover {
            background: #e5570e;
            color: #ffffff;
        }

        .ad-tracking-customer-note {
            font-size: 12.5px;
            color: #6b7280;
            margin: 14px 2px 0;
        }

        .ad-tracking-timeline-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            padding: 18px 22px;
            border-bottom: 1px solid #eceef1;
        }

        .ad-tracking-timeline-title {
            font-size: 15px;
            font-weight: 700;
            letter-spacing: -.2px;
        }

        .ad-tracking-timeline-subtitle {
            font-size: 12.5px;
            color: #8a9099;
            margin-top: 3px;
        }

        .ad-tracking-switch {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #4b5158;
            cursor: pointer;
            white-space: nowrap;
            user-select: none;
        }
        .ad-tracking-switch input { display: none; }
        .ad-tracking-switch-track {
            width: 38px;
            height: 22px;
            flex: none;
            border-radius: 99px;
            background: #d7dade;
            display: inline-flex;
            align-items: center;
            padding: 2px;
            box-sizing: border-box;
            transition: background .16s;
        }
        .ad-tracking-switch input:checked + .ad-tracking-switch-track {
            background: #ff6213;
        }
        .ad-tracking-switch-knob {
            width: 18px;
            height: 18px;
            border-radius: 99px;
            background: #fff;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .25);
            transition: transform .16s;
        }
        .ad-tracking-switch input:checked + .ad-tracking-switch-track .ad-tracking-switch-knob {
            transform: translateX(16px);
        }

        .ad-tracking-timeline-body {
            max-height: 600px;
            overflow: auto;
            padding: 22px 24px 26px;
        }

        .ad-tracking-day-group { margin-bottom: 8px; }

        .ad-tracking-day-pill {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 99px;
            background: #f0f1f3;
            font-size: 11.5px;
            font-weight: 600;
            color: #4b5158;
            letter-spacing: .2px;
            margin-bottom: 12px;
        }

        .ad-tracking-day-events {
            position: relative;
            padding-left: 34px;
        }
        .ad-tracking-day-events::before {
            content: '';
            position: absolute;
            left: 13px;
            top: 4px;
            bottom: 4px;
            width: 2px;
            background: #eceef1;
        }

        .ad-tracking-event-row {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            margin-bottom: 12px;
            position: relative;
        }
        .ad-tracking-event-row:last-child { margin-bottom: 0; }

        .ad-tracking-event-icon {
            position: absolute;
            left: -34px;
            width: 28px;
            height: 28px;
            flex: none;
            border-radius: 99px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            background: #ffffff;
            color: #a0a6ad;
            border: 2px solid #e3e5e9;
            box-shadow: 0 0 0 4px #ffffff;
        }
        .ad-tracking-event-icon.is-high {
            font-size: 14px;
            background: #ff6213;
            color: #ffffff;
            border-color: #ff6213;
        }
        .ad-tracking-event-icon.is-win {
            font-size: 14px;
            background: #16a34a;
            color: #ffffff;
            border-color: #16a34a;
        }

        .ad-tracking-event-card {
            flex: 1;
            min-width: 0;
            border-radius: 10px;
            padding: 10px 14px;
            background: #fafafb;
            border: 1px solid #f0f1f3;
        }
        .ad-tracking-event-card.is-high {
            padding: 13px 16px;
            background: #fff7f2;
            border-color: #ffd9c2;
        }
        .ad-tracking-event-card.is-win {
            padding: 13px 16px;
            background: #f4fbf6;
            border-color: #c8ecd5;
        }

        .ad-tracking-event-top {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
        }

        .ad-tracking-event-title {
            font-size: 13px;
            font-weight: 500;
            letter-spacing: -.1px;
            color: #4b5158;
        }
        .ad-tracking-event-title.is-high {
            font-size: 14px;
            font-weight: 700;
            color: #2a2c30;
        }
        .ad-tracking-event-title.is-win {
            font-size: 14px;
            font-weight: 700;
            color: #12703a;
        }

        .ad-tracking-event-time {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 12px;
            color: #8a9099;
            white-space: nowrap;
        }

        .ad-tracking-event-page {
            font-size: 12.5px;
            color: #6b7280;
            margin-top: 5px;
        }

        .ad-tracking-event-product {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 9px;
            padding: 6px 10px;
            border-radius: 8px;
            background: #ffffff;
            border: 1px solid #e3e5e9;
        }
        .ad-tracking-event-product-swatch {
            width: 20px;
            height: 20px;
            border-radius: 5px;
            background: #f0f1f3;
            flex: none;
        }
        .ad-tracking-event-product-name {
            font-size: 12.5px;
            font-weight: 600;
            color: #2a2c30;
        }

        .ad-tracking-timeline-empty {
            color: #9ca3af;
            text-align: center;
            padding: 24px 0;
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            var copyBtn = document.getElementById('copyClickIdBtn');
            if (copyBtn) {
                copyBtn.addEventListener('click', function () {
                    var text = copyBtn.getAttribute('data-clickid') || '';
                    if (!text || !navigator.clipboard) {
                        return;
                    }
                    navigator.clipboard.writeText(text).then(function () {
                        var original = copyBtn.textContent;
                        copyBtn.textContent = 'Copiado';
                        copyBtn.classList.add('is-copied');
                        setTimeout(function () {
                            copyBtn.textContent = original;
                            copyBtn.classList.remove('is-copied');
                        }, 1400);
                    });
                });
            }

            var intentToggle = document.getElementById('intentToggle');
            if (intentToggle) {
                intentToggle.addEventListener('change', function () {
                    var onlyIntent = intentToggle.checked;

                    document.querySelectorAll('.ad-tracking-event-row').forEach(function (row) {
                        var isIntent = row.getAttribute('data-intent') === '1';
                        row.style.display = (!onlyIntent || isIntent) ? '' : 'none';
                    });

                    document.querySelectorAll('.ad-tracking-day-group').forEach(function (group) {
                        var rows = group.querySelectorAll('.ad-tracking-event-row');
                        var anyVisible = Array.prototype.some.call(rows, function (row) {
                            return row.style.display !== 'none';
                        });
                        group.style.display = anyVisible ? '' : 'none';
                    });
                });
            }
        })();
    </script>
@endpush
