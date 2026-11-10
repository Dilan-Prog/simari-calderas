@extends('admin.layouts.master')

@section('title', $quote->quote_number . ' - Cotizaciones')

@push('styles')
@vite(['resources/css/admin-quotes.css'])
@endpush

@push('styles')
<style>
/* ── Detalle de cotización: adaptación a teléfono (solo ≤768px / ≤640px; escritorio sin cambios) ── */
.qshow-modal__mfoot { display: none; }
@media (max-width: 768px) {
    .qshow-m { padding: 16px; max-width: 100%; overflow-x: clip; }
    .qshow-m .qshow-wrap,
    .qshow-m .text-block,
    .qshow-m .info-item__value { overflow-wrap: anywhere; word-break: normal; }

    /* Header: título + acciones (acciones antes de la tarjeta de vínculos) */
    .qshow-m .quotes-header--flex { flex-direction: column; align-items: stretch; gap: 0; margin-bottom: 20px; }
    .qshow-m .qshow-head-main { display: contents; }
    .qshow-m .breadcrumb { flex-wrap: wrap; margin-bottom: 8px; }
    .qshow-m .quotes-header__title { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; font-size: 22px; line-height: 1.2; overflow-wrap: anywhere; margin: 0; }
    .qshow-m .quotes-header__subtitle { margin: 6px 0 0; }
    .qshow-m .quick-actions { order: 1; margin-top: 16px; width: 100%; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
    .qshow-m .quick-actions > * { min-width: 0; }
    .qshow-m .quick-actions .btn-action { width: 100%; min-height: 44px; justify-content: center; text-align: center; white-space: normal; line-height: 1.25; padding: 10px 12px; font-size: 14px; }
    .qshow-m #quotes-send-email { order: -2; grid-column: 1 / -1; }
    .qshow-m .quick-actions > a.btn-action--primary { order: -1; grid-column: 1 / -1; }
    .qshow-m .ql-card { order: 2; margin-top: 16px; }

    /* Columnas: una sola; documento (cliente, partidas, totales) primero, luego acciones del panel */
    .qshow-m .quotes-layout { grid-template-columns: minmax(0, 1fr); gap: 16px; }
    .qshow-m .summary-panel { position: static; gap: 16px; min-width: 0; }
    .qshow-m .qshow-card-status   { order: 1; }
    .qshow-m .qshow-card-link     { order: 2; }
    .qshow-m .qshow-card-activity { order: 3; }
    .qshow-m .qshow-card-details  { order: 4; }

    /* Documento */
    .qshow-m .quote-preview { padding: 16px; }
    .qshow-m .quote-preview__header--dark { margin: -16px -16px 0; padding: 18px 16px; gap: 14px; }
    .qshow-m .quote-preview__header-divider { margin: 0 -16px 20px; }
    .qshow-m .quote-preview__header-divider::before { left: 16px; }
    .qshow-m .quote-preview__cot-number { font-size: 24px; overflow-wrap: anywhere; }
    .qshow-m .quote-preview__bill-to { grid-template-columns: minmax(0, 1fr); gap: 18px; margin-bottom: 20px; }
    .qshow-m .info-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }

    /* Partidas: tabla → tarjetas apiladas */
    .qshow-m .qshow-items,
    .qshow-m .qshow-items tbody,
    .qshow-m .qshow-items tr { display: block; width: 100%; }
    .qshow-m .qshow-items thead { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
    .qshow-m .qshow-items tbody tr { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px 12px; padding: 12px; margin-bottom: 10px; border: 1px solid var(--aq-border, #e5e7eb); border-radius: 10px; }
    .qshow-m .qshow-items td { display: block; width: auto; min-width: 0; padding: 0; border: 0; text-align: left; font-size: 13px; overflow-wrap: anywhere; }
    .qshow-m .qshow-items td::before { content: attr(data-label); display: block; margin-bottom: 2px; font-size: 10.5px; font-weight: 600; letter-spacing: .4px; text-transform: uppercase; color: var(--aq-text-muted, #9ca3af); }
    .qshow-m .qshow-items td.td-num { grid-column: 1 / -1; font-size: 11px; font-weight: 600; }
    .qshow-m .qshow-items td.td-num::before { content: 'Partida '; display: inline; font-size: 11px; }
    .qshow-m .qshow-items td.td-name { grid-column: 1 / -1; font-size: 14px; }
    .qshow-m .qshow-items td.td-name::before { display: none; }
    .qshow-m .qshow-items td.td-total { grid-column: 1 / -1; display: flex; justify-content: space-between; align-items: baseline; gap: 12px; padding-top: 8px; border-top: 1px dashed var(--aq-border, #e5e7eb); font-size: 15px; color: var(--aq-text, #141516); }
    .qshow-m .qshow-items td.td-total::before { margin: 0; }

    /* Totales */
    .qshow-m .quote-preview__totals { display: block; }
    .qshow-m .totals-box { min-width: 0; width: 100%; }
    .qshow-m .totals-row { gap: 12px; font-size: 14px; }
    .qshow-m .totals-row span:last-child { text-align: right; white-space: nowrap; }
    .qshow-m .totals-row--final { flex-wrap: wrap; padding: 12px 14px; font-size: 16px; }
    .qshow-m .totals-row--final span:last-child { font-size: 19px; }

    .qshow-m .qshow-notes-grid { grid-template-columns: minmax(0, 1fr) !important; gap: 18px !important; }

    .qshow-m .quote-show-footer { flex-direction: column; text-align: center; padding: 18px 16px; margin-top: 8px; }
    .qshow-m .quote-show-footer__right { text-align: center; }

    /* Panel lateral: controles táctiles */
    .qshow-m .status-change-form { display: grid; grid-template-columns: minmax(0, 1fr); gap: 8px; }
    .qshow-m .status-select { width: 100%; min-height: 44px; font-size: 16px; }
    .qshow-m .status-submit-btn { width: 100%; min-height: 44px; font-size: 15px; }
    .qshow-m .qshow-activity-more { min-height: 44px; font-size: 14px !important; }
    .qshow-act-preview { min-height: 36px; padding: 6px 0 !important; font-size: 13px !important; }
}

/* Modales: pantalla completa / bottom sheet en teléfono */
@media (max-width: 640px) {
    .qshow-modal--full { align-items: stretch !important; justify-content: stretch !important; }
    .qshow-modal--full .qshow-modal__panel { width: 100% !important; max-width: none !important; height: 100% !important; height: 100dvh !important; border-radius: 0 !important; }
    .qshow-modal__bar { gap: 8px; padding: 6px 6px 6px 16px !important; padding-top: max(6px, env(safe-area-inset-top)) !important; }
    .qshow-modal__title { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .qshow-modal__x { flex: none; min-width: 44px; min-height: 44px; display: inline-flex !important; align-items: center; justify-content: center; font-size: 28px !important; }
    #pdf-download-btn { display: none !important; }
    #pdf-frame, #activity-preview-frame { width: 100%; min-height: 0; }
    #activity-preview-text { padding: 16px !important; }
    #activity-preview-text > div { max-width: 100% !important; }
    .qshow-modal__body { padding: 4px 16px 16px !important; overscroll-behavior: contain; }
    .qshow-modal__mfoot { display: flex; flex-shrink: 0; gap: 10px; padding: 10px 12px; padding-bottom: calc(10px + env(safe-area-inset-bottom)); background: #fff; border-top: 1px solid #e5e7eb; }
    .qshow-mbtn { flex: 1; min-height: 46px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font: inherit; font-size: 15px; font-weight: 700; text-decoration: none; cursor: pointer; }
    .qshow-mbtn--primary { background: #ff6213; color: #fff; border: none; }
    .qshow-mbtn--ghost { background: #fff; color: #374151; border: 1px solid rgba(0,0,0,.14); }

    .qshow-modal--sheet { align-items: flex-end !important; }
    .qshow-modal--sheet .qshow-modal__panel { width: 100% !important; max-width: none !important; max-height: 90dvh; overflow-y: auto; border-radius: 16px 16px 0 0 !important; padding: 24px 16px 16px !important; padding-bottom: calc(16px + env(safe-area-inset-bottom)) !important; }
    .qshow-modal__actions > button { flex: 1; min-height: 46px; font-size: 15px !important; }
}
</style>
@endpush

@section('content')
<div class="quotes-page qshow-m">

    {{-- ── Header ─────────────────────────────────────── --}}
    <div class="quotes-header--flex">
        <div class="qshow-head-main">
            <nav class="breadcrumb" aria-label="breadcrumb">
                <a href="{{ route('admin.quotes.index') }}">Cotizaciones</a>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                <span>{{ $quote->quote_number }}</span>
            </nav>
            <h1 class="quotes-header__title">
                {{ $quote->quote_number }}
                <span class="status-badge status-badge--{{ $quote->status }}">
                    {{ \App\Models\Quote::statusLabel($quote->status) }}
                </span>
            </h1>
            <p class="quotes-header__subtitle">Creada el {{ $quote->created_at->format('d/m/Y H:i') }}</p>
            @include('admin.quotes.partials._links_card', ['quote' => $quote])
        </div>

        <div class="quick-actions">
            @if(Route::has('admin.shipments.create'))
            <a href="{{ route('admin.shipments.create', ['quote' => $quote->id]) }}" class="btn-action btn-action--secondary">Crear envío</a>
            @endif
            @if($quote->isEditable())
            <a href="{{ route('admin.quotes.edit', $quote) }}" class="btn-action btn-action--secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                Editar
            </a>
            @endif

            <button type="button" class="btn-action btn-action--secondary" onclick="openPdfModal()">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M12 18v-6"/><path d="m9 15 3 3 3-3"/></svg>
                Vista previa PDF
            </button>

            @if($quote->status === 'accepted' && $quote->customer_id)
            <a href="{{ route('admin.technical-services.create', ['from_quote' => $quote->id]) }}"
               class="btn-action btn-action--primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                Generar Servicio
            </a>
            @endif

            @foreach($quote->salesOrders as $salesOrder)
            <a href="{{ route('admin.sales-orders.show', $salesOrder) }}" class="btn-action btn-action--secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                Ver Pedido {{ $salesOrder->order_number }}
            </a>
            @endforeach

            @php $sendRecipient = $quote->customer?->email ?: $quote->guest_email; @endphp
            @if($sendRecipient)
            <form method="POST" id="quotes-send-email" action="{{ route('admin.quotes.send-email', $quote) }}">
                @csrf
                <button type="button" class="btn-action btn-action--primary" onclick="openSendEmailModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    Enviar por correo
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- ── Layout ─────────────────────────────────────── --}}
    <div class="quotes-layout">
        {{-- ════ MAIN: Quote preview (PDF-style) ════ --}}
        <div class="quotes-main">
            <div class="quote-preview">
                {{-- Encabezado oscuro del documento --}}
                <div class="quote-preview__header--dark">
                    <div>
                        <img src="{{ asset('images/logo/equiterm-logo-blanco-color-3x.png') }}"
                             alt="Equiterm Industries" style="height:28px;width:auto;display:block;">
                        <div class="quote-preview__company-meta">
                            <p class="qshow-wrap">administracion@equitermindustries.com.mx</p>
                            <p>{{ \App\Models\Setting::companyAddressLine() }}</p>
                        </div>
                    </div>
                    <div class="quote-preview__header-right">
                        <div class="quote-preview__cot-label">Cotización</div>
                        <div class="quote-preview__cot-number">{{ $quote->quote_number }}</div>
                        <div class="quote-preview__cot-dates">
                            <p>Emisión: <span>{{ $quote->created_at->format('d/m/Y') }}</span></p>
                            @if($quote->valid_until)
                            <p>Válida hasta: <span>{{ $quote->valid_until->format('d/m/Y') }}</span></p>
                            @endif
                            <p>Moneda: <span>{{ $quote->currency }}</span></p>
                            @if($quote->exchange_rate)
                            <p>Tipo de cambio: <span>{{ number_format($quote->exchange_rate, 4) }}</span></p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="quote-preview__header-divider"></div>

                {{-- Facturar a + Condiciones --}}
                <div class="quote-preview__bill-to">
                    <div>
                        <div class="quote-preview__bill-label">Facturar a</div>
                        <div class="qshow-wrap" style="display:flex;flex-direction:column;gap:4px;">
                            <div class="info-item__value info-item__value--highlight">{{ $quote->guest_name }}</div>
                            @if($quote->guest_company)
                            <div class="info-item__value">{{ $quote->guest_company }}</div>
                            @endif
                            @if($quote->guest_email)
                            <div class="info-item__value">{{ $quote->guest_email }}</div>
                            @endif
                            @if($quote->guest_phone)
                            <div class="info-item__value">{{ $quote->guest_phone }}</div>
                            @endif
                            @if($quote->guest_rfc)
                            <div class="info-item__value">RFC: {{ $quote->guest_rfc }}</div>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="quote-preview__bill-label">Condiciones</div>
                        <div class="info-grid">
                            <div>
                                <div class="info-item__label">IVA</div>
                                <div class="info-item__value">{{ $quote->tax_rate }}%</div>
                            </div>
                            <div>
                                <div class="info-item__label">Moneda</div>
                                <div class="info-item__value">{{ $quote->currency }}</div>
                            </div>
                            @if($quote->exchange_rate)
                            <div>
                                <div class="info-item__label">Tipo de cambio</div>
                                <div class="info-item__value">{{ number_format($quote->exchange_rate, 4) }}</div>
                            </div>
                            @endif
                            @if($quote->valid_until)
                            <div>
                                <div class="info-item__label">Vence</div>
                                <div class="info-item__value">{{ $quote->valid_until->format('d/m/Y') }}</div>
                            </div>
                            @endif
                            <div>
                                <div class="info-item__label">Elaboró</div>
                                <div class="info-item__value">{{ $quote->createdBy ? $quote->createdBy->first_name . ' ' . $quote->createdBy->last_name : '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tabla de productos --}}
                <div class="qshow-table-wrap">
                <table class="quote-preview__table qshow-items">
                    <thead>
                        <tr>
                            <th class="td-num">#</th>
                            <th>Descripción</th>
                            <th>SKU</th>
                            <th class="td-right">Cant.</th>
                            <th class="td-right">Precio Unit.</th>
                            <th class="td-right">Desc. %</th>
                            <th class="td-right">Total línea</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($quote->items as $i => $item)
                        <tr>
                            <td class="td-num" data-label="#">{{ $i + 1 }}</td>
                            <td class="td-name" data-label="Descripción">
                                {{ $item->product_name }}
                                @if($item->notes)
                                <div style="font-size:11px;font-weight:400;color:var(--aq-text-sec);margin-top:5px;white-space:pre-wrap;line-height:1.55;">{{ $item->notes }}</div>
                                @endif
                            </td>
                            <td class="td-sku" data-label="SKU">{{ $item->product_sku ?? '—' }}</td>
                            <td class="td-right" data-label="Cantidad">{{ $item->quantity }}</td>
                            <td class="td-right" data-label="Precio unit.">{{ $quote->currency }} ${{ number_format($item->unit_price, 2) }}</td>
                            <td class="td-right" data-label="Desc. %">{{ $item->discount_percent > 0 ? $item->discount_percent . '%' : '—' }}</td>
                            <td class="td-total" data-label="Total línea">{{ $quote->currency }} ${{ number_format($item->line_total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>

                {{-- Totales --}}
                <div class="quote-preview__totals">
                    <div class="totals-box">
                        <div class="totals-row">
                            <span>Subtotal</span>
                            <span>{{ $quote->currency }} ${{ number_format($quote->subtotal, 2) }}</span>
                        </div>
                        @if($quote->discount_total > 0)
                        <div class="totals-row">
                            <span>Descuento</span>
                            <span>− {{ $quote->currency }} ${{ number_format($quote->discount_total, 2) }}</span>
                        </div>
                        @endif
                        <div class="totals-row">
                            <span>IVA ({{ $quote->tax_rate }}%)</span>
                            <span>{{ $quote->currency }} ${{ number_format($quote->tax_total, 2) }}</span>
                        </div>
                        @if($quote->isr_retention_total > 0)
                        <div class="totals-row">
                            <span>Retención ISR ({{ $quote->isr_retention_rate }}%)</span>
                            <span>− {{ $quote->currency }} ${{ number_format($quote->isr_retention_total, 2) }}</span>
                        </div>
                        @endif
                        <div class="totals-row totals-row--final">
                            <span>Total</span>
                            <span>{{ $quote->currency }} ${{ number_format($quote->total, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Notas y términos --}}
            @if($quote->notes || $quote->terms_conditions)
            <div class="quotes-card">
                <div class="qshow-notes-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
                    @if($quote->notes)
                    <div>
                        <h2 class="quotes-card__header">Notas</h2>
                        <div class="text-block">{{ $quote->notes }}</div>
                    </div>
                    @endif
                    @if($quote->terms_conditions)
                    <div>
                        <h2 class="quotes-card__header">Términos y Condiciones</h2>
                        <div class="text-block">{{ $quote->terms_conditions }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif
            {{-- ── Footer ─────────────────────────────────────── --}}
            <footer class="quote-show-footer">
                <div class="quote-show-footer__brand">
                    <img src="{{ asset('images/logo/equiterm-logo-blanco-color-3x.png') }}"
                        alt="Equiterm Industries" width="140" height="44">
                </div>
                <div class="quote-show-footer__center">
                    Cotización generada el {{ $quote->created_at->format('d/m/Y H:i') }}<br>
                    Este documento es una cotización y no constituye una factura.
                </div>
                <div class="quote-show-footer__right qshow-wrap">
                    administracion@equitermindustries.com.mx<br>
                    <a href="{{ route('home') }}">equitermindustries.com.mx</a>
                </div>
            </footer>

        </div>

        {{-- ════ SIDEBAR ════ --}}
        <aside class="summary-panel">

            {{-- Detalles de la cotización --}}
            <div class="quotes-card qshow-card-details">
                <h2 class="quotes-card__header">Detalles</h2>
                <div style="display:flex;flex-direction:column;gap:14px;">
                    <div>
                        <div class="info-item__label">Folio</div>
                        <div class="info-item__value info-item__value--orange">{{ $quote->quote_number }}</div>
                    </div>
                    <div>
                        <div class="info-item__label">Estado</div>
                        <div style="margin-top:3px;">
                            <span class="status-badge status-badge--{{ $quote->status }}">
                                {{ \App\Models\Quote::statusLabel($quote->status) }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <div class="info-item__label">Moneda</div>
                        <div class="info-item__value">{{ $quote->currency }}</div>
                    </div>
                    @if($quote->exchange_rate)
                    <div>
                        <div class="info-item__label">Tipo de cambio</div>
                        <div class="info-item__value">{{ number_format($quote->exchange_rate, 4) }}</div>
                    </div>
                    @endif
                    <div>
                        <div class="info-item__label">IVA</div>
                        <div class="info-item__value">{{ $quote->tax_rate }}%</div>
                    </div>
                    <div>
                        <div class="info-item__label">Válido hasta</div>
                        <div class="info-item__value">{{ $quote->valid_until ? $quote->valid_until->format('d/m/Y') : '—' }}</div>
                    </div>
                    <div>
                        <div class="info-item__label">Elaboró</div>
                        <div class="info-item__value">{{ $quote->createdBy ? $quote->createdBy->first_name . ' ' . $quote->createdBy->last_name : '—' }}</div>
                    </div>
                    <div>
                        <div class="info-item__label">Creada</div>
                        <div class="info-item__value">{{ $quote->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                </div>

            </div>

            {{-- Actividad: historial de envíos manuales + recordatorios automáticos --}}
            <div class="quotes-card qshow-card-activity">
                <h2 class="quotes-card__header">Actividad</h2>
                @if($activities->isEmpty())
                    <div style="font-size:12.5px;color:#9CA3AF;padding:4px 0;">Todavía no se ha enviado esta cotización.</div>
                @else
                    <div class="qshow-activity-list" style="display:flex;flex-direction:column;gap:0;max-height:260px;overflow-y:auto;">
                        @include('admin.quotes.partials._activity_items', ['activities' => $activities])
                    </div>
                    <button type="button" class="qshow-activity-more" onclick="openActivityModal()"
                            style="margin-top:12px;width:100%;border:1px solid rgba(0,0,0,.14);background:#fff;border-radius:7px;padding:9px;font-size:12.5px;font-weight:700;color:#4b5563;cursor:pointer;">
                        Ver detalles
                    </button>
                @endif
            </div>

            {{-- Vincular cliente (solo admin, solo si falta el vínculo) --}}
            @if(!$quote->customer_id && auth()->user()->isAdmin())
            <div class="quotes-card qshow-card-link">
                <h2 class="quotes-card__header">Vincular Cliente</h2>
                <p style="font-size:12px;color:#6B7280;margin:0 0 10px;">
                    Esta cotización no tiene un cliente registrado vinculado. Asígnalo sin reabrir el resto de la cotización.
                </p>
                <form method="POST" action="{{ route('admin.quotes.attach-customer', $quote) }}">
                    @csrf
                    @method('PATCH')
                    <select name="customer_id" class="status-select" required style="width:100%;margin-bottom:8px;">
                        <option value="">Seleccionar cliente...</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">
                                {{ $customer->company ?: trim("{$customer->first_name} {$customer->last_name}") }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="status-submit-btn" style="width:100%;">Vincular</button>
                </form>
            </div>
            @endif

            {{-- Cambiar estado --}}
            @if(!in_array($quote->status, ['accepted', 'rejected', 'expired']))
            <div class="quotes-card qshow-card-status">
                <h2 class="quotes-card__header">Cambiar Estado</h2>
                <form method="POST" action="{{ route('admin.quotes.update-status', $quote) }}"
                      class="status-change-form">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="status-select">
                        <option value="sent">Marcar como Enviada</option>
                        <option value="accepted">Marcar como Aceptada</option>
                        <option value="rejected">Marcar como Rechazada</option>
                        <option value="expired">Marcar como Vencida</option>
                    </select>
                    <button type="submit" class="status-submit-btn">Aplicar</button>
                </form>
            </div>
            @endif

        </aside>
    </div>



</div>
@endsection

{{-- ── Modal visualizador de PDF ──────────────────────── --}}
<div id="pdf-modal" class="qshow-modal qshow-modal--full" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.65);align-items:center;justify-content:center;">
    <div class="qshow-modal__panel" style="background:#fff;border-radius:8px;width:90vw;max-width:960px;height:90vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.4);">

        {{-- Barra superior del modal --}}
        <div class="qshow-modal__bar" style="display:flex;align-items:center;justify-content:space-between;padding:12px 18px;background:#1a1a1a;border-bottom:2px solid #ff6213;flex-shrink:0;">
            <span class="qshow-modal__title" style="color:#fff;font-size:14px;font-weight:600;">
                Vista previa — {{ $quote->quote_number }}
            </span>
            <div style="display:flex;gap:10px;align-items:center;">
                <a id="pdf-download-btn"
                   href="{{ route('admin.quotes.pdf', $quote) }}"
                   style="display:inline-flex;align-items:center;gap:6px;background:#ff6213;color:#fff;border:none;padding:7px 14px;border-radius:5px;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 17V3"/><path d="m6 11 6 6 6-6"/><path d="M19 21H5"/></svg>
                    Descargar PDF
                </a>
                <button type="button" class="qshow-modal__x" onclick="closePdfModal()" aria-label="Cerrar" style="background:transparent;border:none;cursor:pointer;color:#9CA3AF;font-size:20px;line-height:1;padding:4px 8px;" title="Cerrar">&times;</button>
            </div>
        </div>

        {{-- iframe con el PDF en stream --}}
        <iframe id="pdf-frame"
                src=""
                style="flex:1;border:none;background:#525659;"
                title="Vista previa PDF"></iframe>

        {{-- Barra inferior (solo móvil ≤640px) --}}
        <div class="qshow-modal__mfoot">
            <a href="{{ route('admin.quotes.pdf-preview', $quote) }}" target="_blank" rel="noopener" class="qshow-mbtn qshow-mbtn--ghost">Abrir en pestaña</a>
            <a href="{{ route('admin.quotes.pdf', $quote) }}" class="qshow-mbtn qshow-mbtn--primary">Descargar PDF</a>
        </div>
    </div>
</div>

{{-- ── Modal "Vista previa" de un envío (correo o WhatsApp) ── --}}
<div id="activity-preview-modal" class="qshow-modal qshow-modal--full" style="display:none;position:fixed;inset:0;z-index:10000;background:rgba(0,0,0,.65);align-items:center;justify-content:center;">
    <div class="qshow-modal__panel" style="background:#fff;border-radius:8px;width:90vw;max-width:640px;height:80vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.4);">
        <div class="qshow-modal__bar" style="display:flex;align-items:center;justify-content:space-between;padding:12px 18px;background:#1a1a1a;border-bottom:2px solid #ff6213;flex-shrink:0;">
            <span class="qshow-modal__title" style="color:#fff;font-size:14px;font-weight:600;">Vista previa del envío</span>
            <button type="button" class="qshow-modal__x" onclick="closeActivityPreview()" aria-label="Cerrar" style="background:transparent;border:none;cursor:pointer;color:#9CA3AF;font-size:20px;line-height:1;padding:4px 8px;" title="Cerrar">&times;</button>
        </div>
        <iframe id="activity-preview-frame" src="" style="flex:1;border:none;background:#fff;display:none;" title="Vista previa"></iframe>
        <div id="activity-preview-text" style="flex:1;overflow-y:auto;padding:24px;display:none;">
            <div class="qshow-wrap" style="background:#dcf8c6;border-radius:10px;padding:12px 16px;max-width:80%;font-size:13.5px;color:#141516;line-height:1.5;white-space:pre-wrap;"></div>
        </div>
        <div id="activity-preview-loading" style="flex:1;display:flex;align-items:center;justify-content:center;font-size:13px;color:#9CA3AF;">Cargando…</div>
        <div class="qshow-modal__mfoot">
            <button type="button" class="qshow-mbtn qshow-mbtn--ghost" onclick="closeActivityPreview()">Cerrar</button>
        </div>
    </div>
</div>

{{-- ── Modal "Actividad" completa (80% de pantalla) ───────── --}}
<div id="activity-modal" class="qshow-modal qshow-modal--full" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.65);align-items:center;justify-content:center;">
    <div class="qshow-modal__panel" style="background:#fff;border-radius:12px;width:80vw;height:80vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.4);">

        <div class="qshow-modal__bar qshow-modal__bar--light" style="display:flex;align-items:center;justify-content:space-between;padding:16px 22px;border-bottom:1px solid #F3F4F6;flex-shrink:0;">
            <span class="qshow-modal__title" style="font-size:15px;font-weight:700;color:#141516;">
                Actividad — {{ $quote->quote_number }}
            </span>
            <button type="button" class="qshow-modal__x" onclick="closeActivityModal()" aria-label="Cerrar" style="background:transparent;border:none;cursor:pointer;color:#9CA3AF;font-size:22px;line-height:1;padding:4px 8px;" title="Cerrar">&times;</button>
        </div>

        <div class="qshow-modal__body" style="flex:1;overflow-y:auto;padding:8px 22px 22px;">
            @if($activities->isEmpty())
                <div style="font-size:13px;color:#9CA3AF;padding:20px 0;">Todavía no se ha enviado esta cotización.</div>
            @else
                @include('admin.quotes.partials._activity_items', ['activities' => $activities])
            @endif
        </div>
        <div class="qshow-modal__mfoot">
            <button type="button" class="qshow-mbtn qshow-mbtn--ghost" onclick="closeActivityModal()">Cerrar</button>
        </div>
    </div>
</div>

{{-- ── Modal de confirmación "Enviar por correo" ─────────── --}}
<div id="send-email-modal" class="qshow-modal qshow-modal--sheet" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.65);align-items:center;justify-content:center;">
    <div class="qshow-modal__panel" style="background:#fff;border-radius:12px;width:90vw;max-width:420px;padding:28px 26px 22px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.4);">
        <div style="width:56px;height:56px;border-radius:50%;background:#fff2eb;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#ff6213" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
        </div>
        <div style="font-size:16px;font-weight:700;color:#141516;margin-bottom:6px;">¿Enviar cotización por correo?</div>
        <div class="qshow-wrap" style="font-size:13.5px;color:#6b7280;margin-bottom:22px;">
            Se enviará <strong>{{ $quote->quote_number }}</strong> a
            <strong>{{ $sendRecipient ?? '' }}</strong>.
        </div>
        <div class="qshow-modal__actions" style="display:flex;gap:10px;justify-content:center;">
            <button type="button" onclick="closeSendEmailModal()"
                    style="border:1px solid rgba(0,0,0,.14);background:#fff;border-radius:7px;padding:10px 18px;font-size:13px;font-weight:700;color:#4b5563;cursor:pointer;">
                Cancelar
            </button>
            <button type="button" onclick="document.getElementById('quotes-send-email').submit()"
                    style="border:none;background:#ff6213;border-radius:7px;padding:10px 18px;font-size:13px;font-weight:700;color:#fff;cursor:pointer;">
                Sí, enviar
            </button>
        </div>
    </div>
</div>

@push('scripts')
@vite(['resources/js/admin-quotes.js'])
<script>
const quoteActivityUrls = {
    manualEmail: '{{ route('admin.quotes.activity.preview-manual-email', $quote) }}',
    automatedEmail: (id) => '{{ route('admin.quotes.activity.preview-automated-email', [$quote, '__ID__']) }}'.replace('__ID__', id),
    automatedWhatsapp: (id) => '{{ route('admin.quotes.activity.preview-automated-whatsapp', [$quote, '__ID__']) }}'.replace('__ID__', id),
};

function openActivityPreview(type, id) {
    const modal = document.getElementById('activity-preview-modal');
    const frame = document.getElementById('activity-preview-frame');
    const textBox = document.getElementById('activity-preview-text');
    const loading = document.getElementById('activity-preview-loading');

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    frame.style.display = 'none';
    textBox.style.display = 'none';
    loading.style.display = 'flex';
    frame.src = '';

    if (type === 'whatsapp') {
        fetch(quoteActivityUrls.automatedWhatsapp(id))
            .then(r => r.json())
            .then(data => {
                loading.style.display = 'none';
                textBox.style.display = 'block';
                textBox.querySelector('div').textContent = data.text || '(sin texto)';
            })
            .catch(() => {
                loading.textContent = 'No se pudo cargar la vista previa.';
            });
        return;
    }

    const url = id ? quoteActivityUrls.automatedEmail(id) : quoteActivityUrls.manualEmail;
    frame.onload = () => {
        loading.style.display = 'none';
        frame.style.display = 'block';
    };
    frame.src = url;
}
function closeActivityPreview() {
    document.getElementById('activity-preview-modal').style.display = 'none';
    document.getElementById('activity-preview-frame').src = '';
    document.body.style.overflow = '';
}
document.getElementById('activity-preview-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closeActivityPreview();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeActivityPreview();
});
function openActivityModal() {
    document.getElementById('activity-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeActivityModal() {
    document.getElementById('activity-modal').style.display = 'none';
    document.body.style.overflow = '';
}
document.getElementById('activity-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closeActivityModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeActivityModal();
});
function openSendEmailModal() {
    document.getElementById('send-email-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeSendEmailModal() {
    document.getElementById('send-email-modal').style.display = 'none';
    document.body.style.overflow = '';
}
document.getElementById('send-email-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closeSendEmailModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeSendEmailModal();
});
function openPdfModal() {
    const modal = document.getElementById('pdf-modal');
    const frame = document.getElementById('pdf-frame');
    modal.style.display = 'flex';
    if (!frame.src || frame.src === window.location.href) {
        frame.src = '{{ route('admin.quotes.pdf-preview', $quote) }}';
    }
    document.body.style.overflow = 'hidden';
}
function closePdfModal() {
    document.getElementById('pdf-modal').style.display = 'none';
    document.body.style.overflow = '';
}
document.getElementById('pdf-modal').addEventListener('click', function(e) {
    if (e.target === this) closePdfModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closePdfModal();
});
// Bloqueo de scroll de fondo en teléfono (iOS ignora overflow:hidden en body).
// Observa el display de los modales; no modifica la lógica de abrir/cerrar.
(function () {
    var mq = window.matchMedia('(max-width: 640px)');
    var modals = Array.prototype.slice.call(document.querySelectorAll('.qshow-modal'));
    if (!modals.length || !window.MutationObserver) return;
    var locked = false, savedY = 0;
    function anyOpen() {
        return modals.some(function (m) { return m.style.display && m.style.display !== 'none'; });
    }
    function sync() {
        var want = mq.matches && anyOpen();
        var b = document.body.style;
        if (want && !locked) {
            savedY = window.scrollY || window.pageYOffset || 0;
            b.position = 'fixed'; b.top = (-savedY) + 'px'; b.left = '0'; b.right = '0'; b.width = '100%';
            locked = true;
        } else if (!want && locked) {
            b.position = ''; b.top = ''; b.left = ''; b.right = ''; b.width = '';
            locked = false;
            window.scrollTo(0, savedY);
        }
    }
    var mo = new MutationObserver(sync);
    modals.forEach(function (m) { mo.observe(m, { attributes: true, attributeFilter: ['style'] }); });
    if (mq.addEventListener) mq.addEventListener('change', sync); else if (mq.addListener) mq.addListener(sync);
})();
</script>
@endpush
