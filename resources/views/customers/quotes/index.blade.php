@extends('customers.layouts.master')

@section('title', 'Cotizaciones')

@push('styles')
    @vite('resources/css/admin/technical-services.css')
    <style>
        .qt-stats { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:.75rem; margin-bottom:1rem; }
        .qt-stat { background:var(--ts-bg-white); border-radius:8px; box-shadow:var(--ts-shadow-sm); padding:1rem 1.125rem; }
        .qt-stat__label { font-size:.6875rem; font-weight:600; text-transform:uppercase; letter-spacing:.06em; color:var(--ts-text-muted); }
        .qt-stat__value { font-size:1.625rem; font-weight:700; color:var(--ts-text-title); line-height:1.2; margin-top:.25rem; }
        .qt-stat--ok .qt-stat__value { color:#16A34A; }
        .qt-stat--wait .qt-stat__value { color:#2563EB; }
        .qt-stat--closed .qt-stat__value { color:#6B7280; }

        .qt-filters { display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; margin-bottom:.75rem; }
        .qt-input, .qt-select {
            height:36px; padding:0 .75rem; border:1px solid var(--ts-border); border-radius:6px;
            font-size:.8125rem; color:var(--ts-text-body); background:var(--ts-bg-white); outline:none;
            font-family:var(--ts-font);
        }
        .qt-input { min-width:220px; flex:1 1 220px; max-width:360px; }
        .qt-input:focus, .qt-select:focus { border-color:var(--ts-primary); }
        .qt-btn {
            height:36px; padding:0 1rem; border:none; border-radius:6px; cursor:pointer;
            background:var(--ts-primary); color:#fff; font-size:.8125rem; font-weight:500;
            display:inline-flex; align-items:center; text-decoration:none;
        }
        .qt-btn:hover { background:var(--ts-primary-hover); color:#fff; }
        .qt-btn--ghost { background:var(--ts-bg-white); color:var(--ts-text-body); border:1px solid var(--ts-border); }
        .qt-btn--ghost:hover { background:#F9FAFB; color:var(--ts-text-body); }
        .qt-count { font-size:.8125rem; color:var(--ts-text-muted); margin:0 0 .75rem; }

        .qt-badge { display:inline-flex; align-items:center; padding:.25rem .625rem; border-radius:9999px; font-size:.6875rem; font-weight:600; border:1px solid transparent; white-space:nowrap; }
        .qt-badge--sent { background:#EFF6FF; color:#1D4ED8; border-color:#BFDBFE; }
        .qt-badge--accepted { background:#F0FDF4; color:#15803D; border-color:#BBF7D0; }
        .qt-badge--rejected { background:#FEF2F2; color:#B91C1C; border-color:#FECACA; }
        .qt-badge--expired { background:#F3F4F6; color:#4B5563; border-color:#D1D5DB; }

        .qt-folio { font-weight:600; color:var(--ts-primary); text-decoration:none; }
        .qt-folio:hover { text-decoration:underline; }
        .qt-valid { font-size:.8125rem; color:var(--ts-text-body); }
        .qt-valid small { display:block; font-size:.75rem; color:var(--ts-text-muted); }
        .qt-valid small.qt-warn { color:#B45309; font-weight:600; }
        .qt-valid small.qt-late { color:#B91C1C; font-weight:600; }
        .qt-money { font-weight:600; color:var(--ts-text-title); white-space:nowrap; }
        .qt-money small { font-weight:500; color:var(--ts-text-muted); }

        @media (max-width: 900px) { .qt-stats { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 768px) { .qt-input { max-width:none; flex-basis:100%; } }
    </style>
@endpush

@section('content')
@php
    $today = now()->startOfDay();
    $hasFilters = $search !== '' || $year || $status !== '';

    // Estado efectivo: una "Enviada" cuya vigencia ya paso se muestra como Vencida.
    $viewOf = function ($q) use ($today) {
        $lapsed = $q->status === 'sent' && $q->valid_until && $q->valid_until->lt($today);
        $key = $lapsed ? 'expired' : $q->status;
        $note = null; $noteClass = '';
        if ($q->status === 'sent' && $q->valid_until) {
            if ($lapsed) {
                $note = 'Vencida'; $noteClass = 'qt-late';
            } else {
                $days = (int) $today->diffInDays($q->valid_until);
                $note = $days === 0 ? 'Vence hoy' : 'Vence en ' . $days . ' ' . ($days === 1 ? 'día' : 'días');
                $noteClass = $days <= 7 ? 'qt-warn' : '';
            }
        } elseif ($q->status === 'expired') {
            $note = 'Vencida'; $noteClass = 'qt-late';
        }
        return [
            'key'       => $key,
            'label'     => \App\Models\Quote::statusLabel($key),
            'note'      => $note,
            'noteClass' => $noteClass,
            'date'      => ($q->sent_at ?? $q->created_at)?->format('d/m/Y'),
            'valid'     => $q->valid_until?->format('d/m/Y'),
            'money'     => '$' . number_format((float) $q->total, 2),
            'currency'  => $q->currency ?: 'MXN',
        ];
    };
@endphp
<div class="ts-page">

    <div class="ts-breadcrumb">
        <span>Panel de Control</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m9 18 6-6-6-6"/>
        </svg>
        <span class="ts-breadcrumb__current">Cotizaciones</span>
    </div>

    <div class="ts-header-top">
        <div>
            <h1 class="ts-title">Cotizaciones</h1>
            <p class="ts-subtitle">Historial de las cotizaciones que te hemos enviado</p>
        </div>
    </div>

    <div class="qt-stats">
        <div class="qt-stat">
            <div class="qt-stat__label">Total</div>
            <div class="qt-stat__value">{{ $stats['total'] }}</div>
        </div>
        <div class="qt-stat qt-stat--ok">
            <div class="qt-stat__label">Aceptadas</div>
            <div class="qt-stat__value">{{ $stats['accepted'] }}</div>
        </div>
        <div class="qt-stat qt-stat--wait">
            <div class="qt-stat__label">En espera</div>
            <div class="qt-stat__value">{{ $stats['waiting'] }}</div>
        </div>
        <div class="qt-stat qt-stat--closed">
            <div class="qt-stat__label">Vencidas / rechazadas</div>
            <div class="qt-stat__value">{{ $stats['closed'] }}</div>
        </div>
    </div>

    <form method="GET" action="{{ route('customer.quotes.index') }}" class="qt-filters">
        <input type="search" name="q" class="qt-input" value="{{ $search }}"
               placeholder="Buscar por folio o producto" maxlength="100" aria-label="Buscar">
        <select name="status" class="qt-select" aria-label="Estado" onchange="this.form.submit()">
            <option value="">Todos los estados</option>
            <option value="sent" @selected($status === 'sent')>En espera</option>
            <option value="accepted" @selected($status === 'accepted')>Aceptada</option>
            <option value="rejected" @selected($status === 'rejected')>Rechazada</option>
            <option value="expired" @selected($status === 'expired')>Vencida</option>
        </select>
        <select name="year" class="qt-select" aria-label="Año" onchange="this.form.submit()">
            <option value="">Todos los años</option>
            @foreach($years as $y)
                <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
            @endforeach
        </select>
        <button type="submit" class="qt-btn">Filtrar</button>
        @if($hasFilters)
            <a href="{{ route('customer.quotes.index') }}" class="qt-btn qt-btn--ghost">Limpiar filtros</a>
        @endif
    </form>

    @if($hasFilters)
        <p class="qt-count">{{ $quotes->total() }} {{ $quotes->total() === 1 ? 'resultado' : 'resultados' }}</p>
    @endif

    {{-- Tabla (escritorio) --}}
    <div class="ts-table-wrap">
        <table class="ts-table">
            <thead>
                <tr>
                    <th>FOLIO</th>
                    <th>FECHA</th>
                    <th>VIGENCIA</th>
                    <th>PARTIDAS</th>
                    <th>ESTADO</th>
                    <th style="text-align:right">TOTAL</th>
                    <th>ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                @forelse($quotes as $quote)
                    @php $v = $viewOf($quote); @endphp
                    <tr>
                        <td><a class="qt-folio" href="{{ route('customer.quotes.show', $quote->id) }}">{{ $quote->quote_number }}</a></td>
                        <td class="ts-table-date">{{ $v['date'] ?? '—' }}</td>
                        <td class="qt-valid">
                            {{ $v['valid'] ?? '—' }}
                            @if($v['note'])<small class="{{ $v['noteClass'] }}">{{ $v['note'] }}</small>@endif
                        </td>
                        <td>{{ $quote->items_count }} {{ $quote->items_count === 1 ? 'partida' : 'partidas' }}</td>
                        <td><span class="qt-badge qt-badge--{{ $v['key'] }}">{{ $v['label'] }}</span></td>
                        <td style="text-align:right"><span class="qt-money">{{ $v['money'] }} <small>{{ $v['currency'] }}</small></span></td>
                        <td>
                            <div class="ts-table-actions">
                                <a href="{{ route('customer.quotes.show', $quote->id) }}"
                                   class="ts-action-btn ts-action-btn--primary" title="Ver detalle" aria-label="Ver detalle de {{ $quote->quote_number }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="ts-table-empty">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>
                                </svg>
                                <p>{{ $hasFilters ? 'No hay cotizaciones con esos filtros' : 'Aún no tienes cotizaciones' }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($quotes->hasPages())
            <div class="ts-pagination">
                <span class="ts-pagination__info">
                    Mostrando {{ $quotes->firstItem() }}–{{ $quotes->lastItem() }}
                    de {{ $quotes->total() }} registros
                </span>
                <div class="ts-pagination__links">
                    {{ $quotes->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- Tarjetas (móvil) --}}
    <div class="ts-mobile-cards">
        @forelse($quotes as $quote)
            @php $v = $viewOf($quote); @endphp
            <div class="ts-mobile-card">
                <div class="ts-mobile-card__top">
                    <a class="qt-folio ts-mobile-card__num" href="{{ route('customer.quotes.show', $quote->id) }}">{{ $quote->quote_number }}</a>
                    <span class="qt-badge qt-badge--{{ $v['key'] }}">{{ $v['label'] }}</span>
                </div>
                <div class="ts-mobile-card__type qt-money">{{ $v['money'] }} <small>{{ $v['currency'] }}</small></div>
                <div class="ts-mobile-card__meta">
                    <span>Fecha: {{ $v['date'] ?? '—' }}</span>
                    <span>{{ $quote->items_count }} {{ $quote->items_count === 1 ? 'partida' : 'partidas' }}</span>
                </div>
                <div class="qt-valid" style="margin-top:.375rem">
                    Vigencia: {{ $v['valid'] ?? '—' }}
                    @if($v['note'])<small class="{{ $v['noteClass'] }}">{{ $v['note'] }}</small>@endif
                </div>
                <div class="ts-mobile-card__divider"></div>
                <div class="ts-mobile-card__footer">
                    <span></span>
                    <a href="{{ route('customer.quotes.show', $quote->id) }}" class="ts-mobile-card__btn">Ver →</a>
                </div>
            </div>
        @empty
            <div class="ts-mobile-empty">
                <p>{{ $hasFilters ? 'No hay cotizaciones con esos filtros' : 'Aún no tienes cotizaciones' }}</p>
            </div>
        @endforelse

        @if($quotes->hasPages())
            <div class="ts-pagination" style="justify-content:center">
                <div class="ts-pagination__links">{{ $quotes->links() }}</div>
            </div>
        @endif
    </div>

</div>
@endsection
