@extends('admin.layouts.master')

@section('title', 'Cotizaciones - Admin')

@push('styles')
<style>
:root {
    --background--white:          #ffffff;
    --header-footer-color:        #1A2535;
    --text-subwhite-color:        #D1D5DC;
    --text-description-color:     #6B7280;
    --secondary-color:            #ff6213;
    --button-primary-color:       #ff6213;
    --button-primary-color-hover: #de4a00;
    --font-family:                'Inter', sans-serif;
    --shadow-sm:                  0 1px 2px rgba(0,0,0,.06);
    --shadow-md:                  0 10px 20px rgba(0,0,0,.1);
}

/* ── Layout ─────────────────────────────── */
.quotes-page { padding: 32px; font-family: var(--font-family); display: flex; flex-direction: column; gap: 24px; }

/* ── Header ─────────────────────────────── */
.quotes-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.quotes-breadcrumb { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--text-description-color); margin-bottom: 8px; }
.quotes-breadcrumb svg { flex-shrink: 0; color: #9CA3AF; }
.quotes-breadcrumb-current { color: #374151; }
.quotes-title { font-size: 24px; font-weight: 700; color: #111827; line-height: 1.2; margin: 0 0 6px; }
.quotes-subtitle { font-size: 14px; color: var(--text-description-color); margin: 0; }
.quotes-btn-new { display: inline-flex; align-items: center; gap: 8px; height: 40px; padding: 0 16px; background: var(--button-primary-color); color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 500; font-family: var(--font-family); text-decoration: none; cursor: pointer; box-shadow: var(--shadow-md); transition: background .2s; white-space: nowrap; flex-shrink: 0; }
.quotes-btn-new:hover { background: var(--button-primary-color-hover); color: #fff; }

/* ── Filter card ─────────────────────────── */
.quotes-filters-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); padding: 16px; }
.quotes-filters-grid { display: grid; grid-template-columns: 4fr 3fr 4fr 1fr; gap: 12px; align-items: center; }
.quotes-filter-field { position: relative; }
.quotes-search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #9CA3AF; pointer-events: none; display: flex; align-items: center; }
.quotes-filter-input { width: 100%; height: 40px; padding: 0 12px 0 34px; border: 1px solid #D1D5DB; border-radius: 6px; font-size: 13px; font-family: var(--font-family); color: #111827; background: #fff; transition: border-color .2s, box-shadow .2s; box-sizing: border-box; }
.quotes-filter-input::placeholder { color: #9CA3AF; }
.quotes-filter-input:focus { outline: none; border-color: var(--secondary-color); box-shadow: 0 0 0 3px rgba(255,98,19,.12); }
.quotes-select-wrap { position: relative; }
.quotes-filter-select { width: 100%; height: 40px; padding: 0 32px 0 12px; border: 1px solid #D1D5DB; border-radius: 6px; font-size: 13px; font-family: var(--font-family); color: #111827; background: #fff; appearance: none; cursor: pointer; transition: border-color .2s; box-sizing: border-box; }
.quotes-filter-select:focus { outline: none; border-color: var(--secondary-color); }
.quotes-select-chevron { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: var(--text-description-color); pointer-events: none; display: flex; align-items: center; }
.quotes-btn-filter { height: 40px; padding: 0 12px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: 1px solid var(--secondary-color); color: var(--secondary-color); background: transparent; border-radius: 6px; font-size: 13px; font-weight: 500; font-family: var(--font-family); cursor: pointer; transition: background .2s; white-space: nowrap; width: 100%; }
.quotes-btn-filter:hover { background: #FFF7ED; }

/* ── Table card ──────────────────────────── */
.quotes-table-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); overflow: hidden; }
.quotes-table-scroll { overflow-x: auto; }
.quotes-table { width: 100%; border-collapse: collapse; min-width: 700px; }

/* Scroll solo en tbody — thead queda fijo */
.quotes-table thead,
.quotes-table tbody { display: block; width: 100%; }

.quotes-table thead tr,
.quotes-table tbody tr { display: table; width: 100%; table-layout: fixed; }

.quotes-table tbody {
    max-height: 420px;
    overflow-y: auto;
    scrollbar-gutter: stable;   /* reserva espacio para la scrollbar sin desplazar thead */
}

.quotes-table thead tr { background: var(--header-footer-color); height: 44px; }
.quotes-table thead th { padding: 0 20px; text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: var(--text-subwhite-color); white-space: nowrap; }
.quotes-table tbody tr { height: 52px; border-bottom: 1px solid #F3F4F6; transition: background .15s; }
.quotes-table tbody tr:last-child { border-bottom: none; }
.quotes-table tbody tr:hover { background: rgba(255,247,237,.4); }
.quotes-table td { padding: 0 20px; vertical-align: middle; }
.quotes-td-folio { font-size: 13px; font-weight: 600; color: var(--secondary-color); font-variant-numeric: tabular-nums; text-decoration: none; }
.quotes-td-folio:hover { text-decoration: underline; color: var(--button-primary-color-hover); }
.quotes-td-receptor { font-size: 14px; color: #111827; }
.quotes-td-empresa { font-size: 14px; color: #374151; }
.quotes-td-total { font-size: 14px; font-weight: 600; color: #111827; font-variant-numeric: tabular-nums; }
.quotes-td-fecha { font-size: 13px; color: var(--text-description-color); }
.quotes-badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; border: 1px solid; white-space: nowrap; }
.quotes-badge-draft    { background: #F3F4F6; color: #6B7280;  border-color: #D1D5DB; }
.quotes-badge-sent     { background: #EFF6FF; color: #3B82F6;  border-color: #BFDBFE; }
.quotes-badge-accepted { background: #F0FDF4; color: #16A34A;  border-color: #BBF7D0; }
.quotes-badge-rejected { background: #FEF2F2; color: #DC2626;  border-color: #FECACA; }
.quotes-badge-expired  { background: #FFF7ED; color: #EA580C;  border-color: #FED7AA; }
.quotes-actions { display: flex; align-items: center; gap: 4px; }
.quotes-action-btn { width: 32px; height: 32px; border-radius: 6px; border: none; background: transparent; display: inline-flex; align-items: center; justify-content: center; color: var(--text-description-color); text-decoration: none; cursor: pointer; transition: background .15s, color .15s; padding: 0; }
.quotes-action-btn:hover { background: #F3F4F6; color: var(--secondary-color); }
.quotes-action-btn-delete:hover { background: #FEF2F2 !important; color: #DC2626 !important; }
.quotes-empty-row td { padding: 48px 20px; text-align: center; }
.quotes-empty-inner { display: flex; flex-direction: column; align-items: center; gap: 10px; color: #9CA3AF; }
.quotes-empty-inner svg { opacity: .4; }
.quotes-empty-inner p { font-size: 14px; margin: 0; }

/* ── Pagination ──────────────────────────── */
.quotes-pagination-bar { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-top: 1px solid #F3F4F6; gap: 16px; flex-wrap: wrap; }
.quotes-pagination-info { font-size: 13px; color: var(--text-description-color); }
.quotes-pagination-info strong { font-weight: 600; color: #111827; }
.quotes-pagination-bar nav { display: flex; align-items: center; }
.quotes-pagination-bar .pagination { display: flex; align-items: center; gap: 4px; list-style: none; margin: 0; padding: 0; }
.quotes-pagination-bar .page-item .page-link,
.quotes-pagination-bar .page-item span { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 6px; border: 1px solid #D1D5DB; font-size: 13px; font-family: var(--font-family); color: #374151; background: #fff; text-decoration: none; cursor: pointer; transition: background .15s; line-height: 1; }
.quotes-pagination-bar .page-item .page-link:hover { background: #F9FAFB; }
.quotes-pagination-bar .page-item.active .page-link,
.quotes-pagination-bar .page-item.active span { background: var(--secondary-color); border-color: var(--secondary-color); color: #fff; font-weight: 500; }
.quotes-pagination-bar .page-item.disabled .page-link,
.quotes-pagination-bar .page-item.disabled span { opacity: .4; pointer-events: none; }

/* ═══════════════════════════════════════════
   DATE PICKER  (prefix: quotes-dp-)
═══════════════════════════════════════════ */
.quotes-datepicker-wrapper { position: relative; }

.quotes-dp-trigger {
    display: flex; align-items: center; gap: 8px; width: 100%; height: 40px; padding: 0 10px;
    border: 1px solid #D1D5DB; border-radius: 6px; background: #fff;
    font-family: var(--font-family); font-size: 13px; color: #9CA3AF;
    cursor: pointer; transition: border-color .2s; text-align: left; box-sizing: border-box;
}
.quotes-dp-trigger:hover { border-color: var(--secondary-color); }
.quotes-dp-trigger.has-value { color: #111827; }
.quotes-dp-trigger-label { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.quotes-dp-trigger svg { flex-shrink: 0; }

/* Overlay */
.quotes-dp-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,.4);
    backdrop-filter: blur(2px); -webkit-backdrop-filter: blur(2px);
    z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 16px;
    box-sizing: border-box; opacity: 0; visibility: hidden;
    transition: opacity 200ms ease, visibility 200ms ease;
}
.quotes-dp-overlay.quotes-dp-open { opacity: 1; visibility: visible; }
.quotes-dp-overlay.quotes-dp-open .quotes-dp-modal { transform: translateY(0); }

.quotes-dp-modal {
    background: #fff; border-radius: 12px; box-shadow: 0 20px 50px rgba(0,0,0,.25);
    width: 100%; max-width: 760px; overflow: hidden;
    transform: translateY(12px); transition: transform 200ms ease;
}

/* Header */
.quotes-dp-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #F3F4F6; }
.quotes-dp-header-text h3 { font-size: 15px; font-weight: 600; color: #111827; margin: 0 0 2px; }
.quotes-dp-header-text p  { font-size: 12px; color: #6B7280; margin: 0; }
.quotes-dp-close { width: 32px; height: 32px; border-radius: 6px; border: none; background: transparent; color: #6B7280; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background .15s; flex-shrink: 0; }
.quotes-dp-close:hover { background: #F3F4F6; }

/* Body */
.quotes-dp-body { display: flex; }

/* Quick ranges panel */
.quotes-dp-quick { width: 180px; flex-shrink: 0; border-right: 1px solid #F3F4F6; padding: 12px 8px; background: #FAFBFC; }
.quotes-dp-quick-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: #9CA3AF; padding: 4px 8px 8px; display: block; }
.quotes-dp-quick-btn { display: block; width: 100%; text-align: left; padding: 8px 10px; border: none; background: transparent; border-radius: 6px; font-size: 12.5px; font-family: var(--font-family); color: #374151; cursor: pointer; transition: background .15s, color .15s; margin-bottom: 1px; }
.quotes-dp-quick-btn:hover { background: #fff; color: var(--secondary-color); }

/* Calendars panel */
.quotes-dp-calendars { flex: 1; padding: 20px; min-width: 0; }

.quotes-dp-nav { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
.quotes-dp-nav-btn { width: 32px; height: 32px; border-radius: 6px; border: none; background: transparent; color: #6B7280; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background .15s; flex-shrink: 0; }
.quotes-dp-nav-btn:hover { background: #F3F4F6; }
.quotes-dp-months { flex: 1; display: grid; grid-template-columns: 1fr 1fr; gap: 24px; padding: 0 8px; }
.quotes-dp-month-title { text-align: center; font-size: 13px; font-weight: 600; color: #111827; text-transform: capitalize; }

.quotes-dp-cals { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.quotes-dp-weekdays { display: grid; grid-template-columns: repeat(7, 1fr); margin-bottom: 6px; }
.quotes-dp-weekday { text-align: center; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: #9CA3AF; padding: 4px 0; }
.quotes-dp-days { display: grid; grid-template-columns: repeat(7, 1fr); row-gap: 2px; }

/* Day cell */
.quotes-dp-day-cell { position: relative; height: 36px; display: flex; align-items: center; justify-content: center; }

/* Range bg via pseudo-elements (z-index 0, below button at z-index 1) */
.quotes-dp-cell-in-range::after { content: ''; position: absolute; inset: 0; background: #FFF7ED; pointer-events: none; z-index: 0; }
.quotes-dp-cell-start:not(.quotes-dp-cell-single)::after { content: ''; position: absolute; top: 0; bottom: 0; right: 0; width: 50%; background: #FFF7ED; pointer-events: none; z-index: 0; }
.quotes-dp-cell-end:not(.quotes-dp-cell-single)::before { content: ''; position: absolute; top: 0; bottom: 0; left: 0; width: 50%; background: #FFF7ED; pointer-events: none; z-index: 0; }

/* Day button */
.quotes-dp-day-btn {
    position: relative; z-index: 1;
    width: 36px; height: 36px; border: none; background: transparent; border-radius: 6px;
    font-size: 12px; font-family: var(--font-family); font-variant-numeric: tabular-nums;
    color: #374151; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s, color .15s; line-height: 1;
}
.quotes-dp-day-btn:not(:disabled):not(.quotes-dp-day-selected):not(.quotes-dp-day-in-range):hover { background: #FFF7ED; }
.quotes-dp-day-other { color: #D1D5DB; cursor: default; }
.quotes-dp-day-other:hover { background: transparent !important; }
.quotes-dp-day-today { outline: 1px solid rgba(255,98,19,.4); color: var(--secondary-color); font-weight: 600; }
.quotes-dp-day-in-range { background: transparent; border-radius: 0; }
.quotes-dp-day-selected { background: var(--secondary-color) !important; color: #fff !important; border-radius: 6px !important; font-weight: 500; outline: none; }
.quotes-dp-day-today.quotes-dp-day-selected { outline: none; }

/* Footer */
.quotes-dp-footer { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 20px; border-top: 1px solid #F3F4F6; background: #FAFBFC; flex-wrap: wrap; }
.quotes-dp-footer-dates { display: flex; align-items: center; gap: 8px; }
.quotes-dp-date-box { padding: 6px 12px; border-radius: 6px; background: #fff; border: 1px solid #E5E7EB; min-width: 120px; text-align: center; font-variant-numeric: tabular-nums; font-family: var(--font-family); font-size: 12.5px; color: #9CA3AF; }
.quotes-dp-footer-arrow { color: #9CA3AF; font-size: 14px; }
.quotes-dp-footer-actions { display: flex; align-items: center; gap: 8px; }
.quotes-dp-btn-clear  { height: 36px; padding: 0 12px; border: none; background: transparent; font-size: 13px; font-family: var(--font-family); color: #6B7280; cursor: pointer; border-radius: 6px; transition: background .15s, color .15s; }
.quotes-dp-btn-clear:hover  { background: #fff; color: #111827; }
.quotes-dp-btn-cancel { height: 36px; padding: 0 12px; border: 1px solid #D1D5DB; background: #fff; font-size: 13px; font-family: var(--font-family); color: #374151; cursor: pointer; border-radius: 6px; transition: background .15s; }
.quotes-dp-btn-cancel:hover { background: #F9FAFB; }
.quotes-dp-btn-apply  { height: 36px; padding: 0 16px; border: none; background: var(--secondary-color); color: #fff; font-size: 13px; font-weight: 500; font-family: var(--font-family); cursor: pointer; border-radius: 6px; box-shadow: var(--shadow-md); transition: background .15s; }
.quotes-dp-btn-apply:hover:not(:disabled) { background: var(--button-primary-color-hover); }
.quotes-dp-btn-apply:disabled { opacity: .4; cursor: not-allowed; box-shadow: none; }

/* ── Responsive ──────────────────────────── */
@media (max-width: 1024px) {
    .quotes-filters-grid { grid-template-columns: 1fr 1fr; }
    .quotes-filter-field:first-child { grid-column: 1 / -1; }
    .quotes-dp-cals { grid-template-columns: 1fr; }
    .quotes-dp-months { grid-template-columns: 1fr; }
    .quotes-dp-months .quotes-dp-month-title:last-child { display: none; }
    .quotes-dp-cals .quotes-dp-calendar:last-child { display: none; }
}
@media (max-width: 640px) {
    .quotes-page { padding: 16px; gap: 16px; }
    .quotes-filters-grid { grid-template-columns: 1fr; }
    .quotes-filter-field:first-child { grid-column: 1; }
    .quotes-header { flex-direction: column; align-items: stretch; }
    .quotes-btn-new { justify-content: center; }
    .quotes-dp-quick { display: none; }
}
</style>
@endpush

@section('content')
<div class="quotes-page">

    {{-- Header --}}
    <div class="quotes-header">
        <div>
            <div class="quotes-breadcrumb">
                <span>Panel de Control</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                <span class="quotes-breadcrumb-current">Cotizaciones</span>
            </div>
            <h1 class="quotes-title">Gestión de Cotizaciones</h1>
            <p class="quotes-subtitle">Crea y administra cotizaciones para tus clientes</p>
        </div>
        <a href="{{ route('admin.quotes.create') }}" class="quotes-btn-new">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            Nueva Cotización
        </a>
    </div>

    {{-- Filters --}}
    <form id="quotes-filter-form" method="GET" action="{{ route('admin.quotes.index') }}" class="quotes-filters-card">
        <div class="quotes-filters-grid">

            <div class="quotes-filter-field">
                <span class="quotes-search-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </span>
                <input type="text" name="search" class="quotes-filter-input" placeholder="Buscar por folio, cliente o empresa..." value="{{ request('search') }}" autocomplete="off">
            </div>

            <div class="quotes-select-wrap">
                <select name="status" class="quotes-filter-select">
                    <option value="">Todos los estados</option>
                    <option value="draft"    {{ request('status') === 'draft'    ? 'selected' : '' }}>Borrador</option>
                    <option value="sent"     {{ request('status') === 'sent'     ? 'selected' : '' }}>Enviada</option>
                    <option value="accepted" {{ request('status') === 'accepted' ? 'selected' : '' }}>Aceptada</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rechazada</option>
                    <option value="expired"  {{ request('status') === 'expired'  ? 'selected' : '' }}>Expirada</option>
                </select>
                <span class="quotes-select-chevron">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </span>
            </div>

            {{-- Date picker trigger (hidden inputs live here inside the form) --}}
            <div class="quotes-datepicker-wrapper">
                <button type="button" id="datePickerTrigger"
                        class="quotes-dp-trigger{{ (request('date_from') || request('date_to')) ? ' has-value' : '' }}"
                        data-initial-from="{{ request('date_from') }}"
                        data-initial-to="{{ request('date_to') }}"
                        aria-label="Seleccionar rango de fechas">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>
                    <span id="datePickerLabel" class="quotes-dp-trigger-label">Seleccionar rango de fechas</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <input type="hidden" name="date_from" id="dateFrom" value="{{ request('date_from') }}">
                <input type="hidden" name="date_to"   id="dateTo"   value="{{ request('date_to') }}">
            </div>

            <button type="submit" class="quotes-btn-filter">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 20a1 1 0 0 0 .553.895l2 1A1 1 0 0 0 14 21v-7a2 2 0 0 1 .517-1.341L21.74 4.67A1 1 0 0 0 21 3H3a1 1 0 0 0-.742 1.67l7.225 7.989A2 2 0 0 1 10 14z"/></svg>
                Filtrar
            </button>
        </div>
    </form>

    {{-- Table card --}}
    <div class="quotes-table-card">
        <div class="quotes-table-scroll">
            <table class="quotes-table">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Receptor</th>
                        <th>Empresa</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Válido hasta</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($quotes as $quote)
                    <tr>
                        <td><a href="{{ route('admin.quotes.show', $quote) }}" class="quotes-td-folio">{{ $quote->quote_number }}</a></td>
                        <td class="quotes-td-receptor">{{ $quote->guest_name }}</td>
                        <td class="quotes-td-empresa">{{ $quote->guest_company ?? '—' }}</td>
                        <td class="quotes-td-total">{{ $quote->currency }} ${{ number_format($quote->total, 2) }}</td>
                        <td>
                            @php
                                $badgeMap = [
                                    'draft'    => ['class' => 'quotes-badge-draft',    'label' => 'Borrador'],
                                    'sent'     => ['class' => 'quotes-badge-sent',     'label' => 'Enviada'],
                                    'accepted' => ['class' => 'quotes-badge-accepted', 'label' => 'Aceptada'],
                                    'rejected' => ['class' => 'quotes-badge-rejected', 'label' => 'Rechazada'],
                                    'expired'  => ['class' => 'quotes-badge-expired',  'label' => 'Expirada'],
                                ];
                                $badge = $badgeMap[$quote->status] ?? ['class' => 'quotes-badge-draft', 'label' => $quote->status];
                            @endphp
                            <span class="quotes-badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                        </td>
                        <td class="quotes-td-fecha">{{ $quote->valid_until ? $quote->valid_until->format('d M Y') : '—' }}</td>
                        <td>
                            <div class="quotes-actions">
                                <a href="{{ route('admin.quotes.show', $quote) }}" class="quotes-action-btn" title="Ver">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                                @if($quote->isEditable())
                                <a href="{{ route('admin.quotes.edit', $quote) }}" class="quotes-action-btn" title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                </a>
                                @endif
                                <form method="POST" action="{{ route('admin.quotes.destroy', $quote) }}" onsubmit="return confirm('¿Cancelar esta cotización?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="quotes-action-btn quotes-action-btn-delete" title="Cancelar">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr class="quotes-empty-row">
                        <td colspan="7">
                            <div class="quotes-empty-inner">
                                <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h5"/></svg>
                                <p>No se encontraron cotizaciones con los filtros actuales.</p>
                                @if(request()->hasAny(['search','status','date_from','date_to']))
                                <a href="{{ route('admin.quotes.index') }}" style="font-size:13px;color:#ff6213;text-decoration:none;">Limpiar filtros</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($quotes->total() > 0)
        <div class="quotes-pagination-bar">
            <span class="quotes-pagination-info">
                Mostrando <strong>{{ $quotes->firstItem() }}-{{ $quotes->lastItem() }}</strong>
                de <strong>{{ $quotes->total() }}</strong> cotizaciones
            </span>
            @if($quotes->hasPages())
            {{ $quotes->appends(request()->query())->links() }}
            @endif
        </div>
        @endif
    </div>

    {{-- ═══ Date picker modal (position:fixed, fuera del flow) ═══ --}}
    <div id="datePickerOverlay" class="quotes-dp-overlay" role="dialog" aria-modal="true" aria-label="Selector de rango de fechas">
        <div class="quotes-dp-modal">

            <div class="quotes-dp-header">
                <div class="quotes-dp-header-text">
                    <h3>Seleccionar rango de fechas</h3>
                    <p>Elige una fecha de inicio y una de fin</p>
                </div>
                <button type="button" id="dpCloseBtn" class="quotes-dp-close" aria-label="Cerrar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            <div class="quotes-dp-body">
                <div class="quotes-dp-quick">
                    <span class="quotes-dp-quick-label">Rangos rápidos</span>
                    <button type="button" class="quotes-dp-quick-btn" data-quick-range="today">Hoy</button>
                    <button type="button" class="quotes-dp-quick-btn" data-quick-range="7days">Últimos 7 días</button>
                    <button type="button" class="quotes-dp-quick-btn" data-quick-range="30days">Últimos 30 días</button>
                    <button type="button" class="quotes-dp-quick-btn" data-quick-range="thismonth">Este mes</button>
                    <button type="button" class="quotes-dp-quick-btn" data-quick-range="lastmonth">Mes anterior</button>
                    <button type="button" class="quotes-dp-quick-btn" data-quick-range="90days">Últimos 90 días</button>
                </div>

                <div class="quotes-dp-calendars">
                    <div class="quotes-dp-nav">
                        <button type="button" id="dpPrevMonth" class="quotes-dp-nav-btn" aria-label="Mes anterior">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                        </button>
                        <div class="quotes-dp-months">
                            <div id="dpLeftTitle"  class="quotes-dp-month-title"></div>
                            <div id="dpRightTitle" class="quotes-dp-month-title"></div>
                        </div>
                        <button type="button" id="dpNextMonth" class="quotes-dp-nav-btn" aria-label="Mes siguiente">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                        </button>
                    </div>

                    <div class="quotes-dp-cals">
                        <div class="quotes-dp-calendar">
                            <div class="quotes-dp-weekdays">
                                <div class="quotes-dp-weekday">L</div><div class="quotes-dp-weekday">M</div>
                                <div class="quotes-dp-weekday">M</div><div class="quotes-dp-weekday">J</div>
                                <div class="quotes-dp-weekday">V</div><div class="quotes-dp-weekday">S</div>
                                <div class="quotes-dp-weekday">D</div>
                            </div>
                            <div id="dpCalLeft" class="quotes-dp-days"></div>
                        </div>
                        <div class="quotes-dp-calendar">
                            <div class="quotes-dp-weekdays">
                                <div class="quotes-dp-weekday">L</div><div class="quotes-dp-weekday">M</div>
                                <div class="quotes-dp-weekday">M</div><div class="quotes-dp-weekday">J</div>
                                <div class="quotes-dp-weekday">V</div><div class="quotes-dp-weekday">S</div>
                                <div class="quotes-dp-weekday">D</div>
                            </div>
                            <div id="dpCalRight" class="quotes-dp-days"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="quotes-dp-footer">
                <div class="quotes-dp-footer-dates">
                    <div class="quotes-dp-date-box" id="dpDisplayStart"><span style="color:#9CA3AF">Inicio</span></div>
                    <span class="quotes-dp-footer-arrow">→</span>
                    <div class="quotes-dp-date-box" id="dpDisplayEnd"><span style="color:#9CA3AF">Fin</span></div>
                </div>
                <div class="quotes-dp-footer-actions">
                    <button type="button" id="dpClearBtn"  class="quotes-dp-btn-clear">Limpiar</button>
                    <button type="button" id="dpCancelBtn" class="quotes-dp-btn-cancel">Cancelar</button>
                    <button type="button" id="dpApplyBtn"  class="quotes-dp-btn-apply" disabled>Aplicar rango</button>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    var MONTHS  = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    var MSHORT  = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

    var state = { startDate: null, endDate: null, hoverDate: null, leftMonth: null, rightMonth: null };

    /* ── helpers ───────────────────────────── */
    function norm(d) { return new Date(d.getFullYear(), d.getMonth(), d.getDate()); }

    function parseISO(s) {
        if (!s) return null;
        var p = s.split('-');
        return new Date(+p[0], +p[1] - 1, +p[2]);
    }

    function fmtISO(d) {
        if (!d) return '';
        return d.getFullYear() + '-' +
               String(d.getMonth() + 1).padStart(2, '0') + '-' +
               String(d.getDate()).padStart(2, '0');
    }

    function fmtDate(d) {
        if (!d) return '';
        return String(d.getDate()).padStart(2, '0') + ' ' + MSHORT[d.getMonth()] + ' ' + d.getFullYear();
    }

    function isToday(d) {
        var t = new Date();
        return d.getFullYear() === t.getFullYear() && d.getMonth() === t.getMonth() && d.getDate() === t.getDate();
    }

    function sameDay(a, b) {
        return a && b && a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
    }

    function effectiveRange() {
        var s = state.startDate, e = state.endDate || state.hoverDate;
        if (!s) return { s: null, e: null };
        if (!e) return { s: s, e: null };
        var ns = norm(s), ne = norm(e);
        return ns <= ne ? { s: ns, e: ne } : { s: ne, e: ns };
    }

    /* ── open / close ──────────────────────── */
    function openModal() {
        document.getElementById('datePickerOverlay').classList.add('quotes-dp-open');
        renderCalendars();
        updateFooter();
        updateApplyBtn();
    }

    function closeModal() {
        document.getElementById('datePickerOverlay').classList.remove('quotes-dp-open');
        state.hoverDate = null;
    }

    /* ── render ────────────────────────────── */
    function renderCalendars() {
        renderCal('dpCalLeft',  state.leftMonth);
        renderCal('dpCalRight', state.rightMonth);
        document.getElementById('dpLeftTitle').textContent  = MONTHS[state.leftMonth.getMonth()]  + ' ' + state.leftMonth.getFullYear();
        document.getElementById('dpRightTitle').textContent = MONTHS[state.rightMonth.getMonth()] + ' ' + state.rightMonth.getFullYear();
    }

    function renderCal(id, monthDate) {
        var el    = document.getElementById(id);
        var yr    = monthDate.getFullYear(), mo = monthDate.getMonth();
        var first = new Date(yr, mo, 1), last = new Date(yr, mo + 1, 0);
        var total = last.getDate();
        var dow   = first.getDay(); // 0=Sun
        var off   = dow === 0 ? 6 : dow - 1; // Mon-based offset
        var prevLast = new Date(yr, mo, 0).getDate();
        var cells = Math.ceil((off + total) / 7) * 7;
        var r     = effectiveRange();
        var html  = '', cur = 1, nxt = 1;

        for (var i = 0; i < cells; i++) {
            var d, other;
            if (i < off) {
                d = new Date(yr, mo - 1, prevLast - off + i + 1); other = true;
            } else if (cur <= total) {
                d = new Date(yr, mo, cur++); other = false;
            } else {
                d = new Date(yr, mo + 1, nxt++); other = true;
            }
            html += buildCell(d, other, r);
        }

        el.innerHTML = html;

        el.querySelectorAll('.quotes-dp-day-btn[data-date]').forEach(function (btn) {
            btn.addEventListener('click',      function () { handleClick(parseISO(this.dataset.date)); });
            btn.addEventListener('mouseenter', function () { handleHover(parseISO(this.dataset.date)); });
            btn.addEventListener('mouseleave', function () { state.hoverDate = null; renderCalendars(); updateFooter(); });
        });
    }

    function buildCell(d, other, r) {
        var num = d.getDate(), iso = fmtISO(d);

        if (other) {
            return '<div class="quotes-dp-day-cell"><button class="quotes-dp-day-btn quotes-dp-day-other" disabled tabindex="-1" aria-hidden="true">' + num + '</button></div>';
        }

        var nd = norm(d);
        var isStart  = r.s && sameDay(nd, r.s);
        var isEnd    = r.e && sameDay(nd, r.e);
        var inRange  = r.s && r.e && nd > r.s && nd < r.e;
        var isSingle = isStart && isEnd;

        var cellCls = 'quotes-dp-day-cell';
        if (isSingle)     cellCls += ' quotes-dp-cell-single';
        else if (isStart) cellCls += ' quotes-dp-cell-start';
        else if (isEnd)   cellCls += ' quotes-dp-cell-end';
        else if (inRange) cellCls += ' quotes-dp-cell-in-range';

        var btnCls = 'quotes-dp-day-btn';
        if (isStart || isEnd) btnCls += ' quotes-dp-day-selected';
        else if (inRange)     btnCls += ' quotes-dp-day-in-range';
        if (isToday(d))       btnCls += ' quotes-dp-day-today';

        return '<div class="' + cellCls + '"><button class="' + btnCls + '" data-date="' + iso + '" aria-label="' + iso + '">' + num + '</button></div>';
    }

    /* ── interactions ──────────────────────── */
    function handleClick(d) {
        var nd = norm(d);
        if (!state.startDate || (state.startDate && state.endDate)) {
            state.startDate = nd; state.endDate = null;
        } else {
            var ns = norm(state.startDate);
            if (nd < ns) { state.startDate = nd; state.endDate = null; }
            else          { state.endDate = nd; }
        }
        renderCalendars(); updateFooter(); updateApplyBtn();
    }

    function handleHover(d) {
        if (state.startDate && !state.endDate) {
            state.hoverDate = norm(d);
            renderCalendars(); updateFooter();
        }
    }

    /* ── UI updates ────────────────────────── */
    function updateFooter() {
        var r = effectiveRange();
        document.getElementById('dpDisplayStart').innerHTML = r.s
            ? '<span style="color:#111827;font-variant-numeric:tabular-nums">' + fmtDate(r.s) + '</span>'
            : '<span style="color:#9CA3AF">Inicio</span>';
        document.getElementById('dpDisplayEnd').innerHTML = r.e
            ? '<span style="color:#111827;font-variant-numeric:tabular-nums">' + fmtDate(r.e) + '</span>'
            : '<span style="color:#9CA3AF">Fin</span>';
    }

    function updateApplyBtn() {
        document.getElementById('dpApplyBtn').disabled = !(state.startDate && state.endDate);
    }

    function updateTrigger(s, e) {
        var lbl = document.getElementById('datePickerLabel');
        var trg = document.getElementById('datePickerTrigger');
        if (s && e) { lbl.textContent = fmtDate(s) + ' → ' + fmtDate(e); trg.classList.add('has-value'); }
        else if (s) { lbl.textContent = fmtDate(s); trg.classList.add('has-value'); }
        else        { lbl.textContent = 'Seleccionar rango de fechas'; trg.classList.remove('has-value'); }
    }

    /* ── apply / clear ─────────────────────── */
    function applyRange() {
        if (!state.startDate || !state.endDate) return;
        var r = effectiveRange();
        document.getElementById('dateFrom').value = fmtISO(r.s);
        document.getElementById('dateTo').value   = fmtISO(r.e);
        updateTrigger(r.s, r.e);
        closeModal();
        document.getElementById('quotes-filter-form').submit();
    }

    function clearRange() {
        state.startDate = state.endDate = state.hoverDate = null;
        document.getElementById('dateFrom').value = '';
        document.getElementById('dateTo').value   = '';
        renderCalendars(); updateFooter(); updateApplyBtn(); updateTrigger(null, null);
    }

    function applyQuick(s, e) {
        state.startDate = s; state.endDate = e; state.hoverDate = null;
        document.getElementById('dateFrom').value = fmtISO(s);
        document.getElementById('dateTo').value   = fmtISO(e);
        updateTrigger(s, e);
        closeModal();
        document.getElementById('quotes-filter-form').submit();
    }

    /* ── navigation ────────────────────────── */
    function prevMonth() {
        state.leftMonth  = new Date(state.leftMonth.getFullYear(),  state.leftMonth.getMonth()  - 1, 1);
        state.rightMonth = new Date(state.rightMonth.getFullYear(), state.rightMonth.getMonth() - 1, 1);
        renderCalendars();
    }
    function nextMonth() {
        state.leftMonth  = new Date(state.leftMonth.getFullYear(),  state.leftMonth.getMonth()  + 1, 1);
        state.rightMonth = new Date(state.rightMonth.getFullYear(), state.rightMonth.getMonth() + 1, 1);
        renderCalendars();
    }

    /* ── quick ranges ──────────────────────── */
    function quickRange(key) {
        var t = new Date(); t = new Date(t.getFullYear(), t.getMonth(), t.getDate());
        var s, e;
        if      (key === 'today')     { s = t; e = t; }
        else if (key === '7days')     { s = new Date(t); s.setDate(s.getDate() - 6); e = t; }
        else if (key === '30days')    { s = new Date(t); s.setDate(s.getDate() - 29); e = t; }
        else if (key === 'thismonth') { s = new Date(t.getFullYear(), t.getMonth(), 1); e = t; }
        else if (key === 'lastmonth') { s = new Date(t.getFullYear(), t.getMonth() - 1, 1); e = new Date(t.getFullYear(), t.getMonth(), 0); }
        else if (key === '90days')    { s = new Date(t); s.setDate(s.getDate() - 89); e = t; }
        if (s && e) applyQuick(s, e);
    }

    /* ── init ──────────────────────────────── */
    function init() {
        var now = new Date();
        state.leftMonth  = new Date(now.getFullYear(), now.getMonth(), 1);
        state.rightMonth = new Date(now.getFullYear(), now.getMonth() + 1, 1);

        var trg  = document.getElementById('datePickerTrigger');
        var from = trg && trg.dataset.initialFrom;
        var to   = trg && trg.dataset.initialTo;
        if (from) state.startDate = parseISO(from);
        if (to)   state.endDate   = parseISO(to);
        if (state.startDate) {
            state.leftMonth  = new Date(state.startDate.getFullYear(), state.startDate.getMonth(), 1);
            state.rightMonth = new Date(state.startDate.getFullYear(), state.startDate.getMonth() + 1, 1);
            var r = effectiveRange();
            updateTrigger(r.s, r.e);
        }

        trg && trg.addEventListener('click', openModal);
        document.getElementById('dpCloseBtn').addEventListener('click', closeModal);
        document.getElementById('dpPrevMonth').addEventListener('click', prevMonth);
        document.getElementById('dpNextMonth').addEventListener('click', nextMonth);
        document.getElementById('dpApplyBtn').addEventListener('click', applyRange);
        document.getElementById('dpClearBtn').addEventListener('click', clearRange);
        document.getElementById('dpCancelBtn').addEventListener('click', closeModal);

        document.getElementById('datePickerOverlay').addEventListener('click', function (e) {
            if (e.target === this) closeModal();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeModal();
        });

        document.querySelectorAll('[data-quick-range]').forEach(function (btn) {
            btn.addEventListener('click', function () { quickRange(this.dataset.quickRange); });
        });
    }

    document.readyState === 'loading'
        ? document.addEventListener('DOMContentLoaded', init)
        : init();

})();
</script>
@endpush
