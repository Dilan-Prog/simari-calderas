@extends('admin.layouts.master')

@section('title', $quote->quote_number . ' - Cotizaciones')

@push('styles')
@vite(['resources/css/admin-quotes.css'])
@endpush

@section('content')
<div class="quotes-page">

    {{-- ── Header ─────────────────────────────────────── --}}
    <div class="quotes-header--flex">
        <div>
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
        </div>

        <div class="quick-actions">
            @if($quote->isEditable())
            <a href="{{ route('admin.quotes.edit', $quote) }}" class="btn-action btn-action--secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                Editar
            </a>
            @endif

            <a href="{{ route('admin.quotes.pdf', $quote) }}" class="btn-action btn-action--secondary" target="_blank">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M12 18v-6"/><path d="m9 15 3 3 3-3"/></svg>
                Descargar PDF
            </a>

            @if($quote->guest_email)
            <form method="POST" action="{{ route('admin.quotes.send-email', $quote) }}"
                  onsubmit="return confirm('¿Enviar cotización por correo a {{ $quote->guest_email }}?')">
                @csrf
                <button type="submit" class="btn-action btn-action--primary">
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
                            <p>administracion@equitermindustries.com.mx</p>
                            <p>México, Aguascalientes</p>
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
                        </div>
                    </div>
                </div>
                <div class="quote-preview__header-divider"></div>

                {{-- Facturar a + Condiciones --}}
                <div class="quote-preview__bill-to">
                    <div>
                        <div class="quote-preview__bill-label">Facturar a</div>
                        <div style="display:flex;flex-direction:column;gap:4px;">
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
                <table class="quote-preview__table">
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
                            <td class="td-num">{{ $i + 1 }}</td>
                            <td class="td-name">{{ $item->product_name }}</td>
                            <td class="td-sku">{{ $item->product_sku ?? '—' }}</td>
                            <td class="td-right">{{ $item->quantity }}</td>
                            <td class="td-right">{{ $quote->currency }} ${{ number_format($item->unit_price, 2) }}</td>
                            <td class="td-right">{{ $item->discount_percent > 0 ? $item->discount_percent . '%' : '—' }}</td>
                            <td class="td-total">{{ $quote->currency }} ${{ number_format($item->line_total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

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
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
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
                <div class="quote-show-footer__right">
                    administracion@equitermindustries.com.mx<br>
                    <a href="{{ route('home') }}">equitermindustries.com.mx</a>
                </div>
            </footer>

        </div>

        {{-- ════ SIDEBAR ════ --}}
        <aside class="summary-panel">

            {{-- Detalles de la cotización --}}
            <div class="quotes-card">
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

            {{-- Cambiar estado --}}
            @if(!in_array($quote->status, ['accepted', 'rejected', 'expired']))
            <div class="quotes-card">
                <h2 class="quotes-card__header">Cambiar Estado</h2>
                <form method="POST" action="{{ route('admin.quotes.update-status', $quote) }}"
                      class="status-change-form">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="status-select">
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

@push('scripts')
@vite(['resources/js/admin-quotes.js'])
@endpush
