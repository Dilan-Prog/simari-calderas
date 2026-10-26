@extends('customers.layouts.master')

@section('title', 'Mi expediente')

@push('styles')
    @vite('resources/css/admin/technical-services.css')
    <style>
        .tl-next { display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; padding:.75rem 1rem; margin-bottom:1rem;
            background:#EFF6FF; border:1px solid #BFDBFE; border-radius:10px; color:#1E3A8A; text-decoration:none; font-size:.8125rem; }
        a.tl-next:hover { background:#E0EEFF; }
        .tl-next:focus-visible { outline:2px solid #2563EB; outline-offset:2px; }
        .tl-next__label { font-weight:700; text-transform:uppercase; letter-spacing:.04em; font-size:.6875rem; }
        .tl-next__main { font-weight:600; }
        .tl-next__go { margin-left:auto; font-weight:600; }

        .tl-summary { display:grid; grid-template-columns:repeat(auto-fill, minmax(150px, 1fr)); gap:.75rem; margin-bottom:1.25rem; }
        .tl-sum { display:block; padding:.75rem 1rem; background:var(--ts-bg-white,#fff); border:1px solid var(--ts-border,#E5E7EB);
            border-radius:10px; text-decoration:none; color:inherit; transition:box-shadow .15s ease, transform .15s ease; }
        a.tl-sum:hover { box-shadow:0 6px 16px rgba(0,0,0,.08); transform:translateY(-1px); }
        .tl-sum:focus-visible { outline:2px solid var(--ts-primary,#ff6213); outline-offset:2px; }
        .tl-sum__count { display:block; font-size:1.5rem; font-weight:700; color:#1F2937; line-height:1.2; }
        .tl-sum__label { display:block; font-size:.75rem; color:#6B7280; margin-top:.125rem; }
        .tl-sum__go { display:block; font-size:.6875rem; font-weight:600; color:var(--ts-primary,#ff6213); margin-top:.375rem; }

        .tl-filters { margin-bottom:1.25rem; display:flex; flex-direction:column; gap:.75rem; }
        .tl-chips { display:flex; flex-wrap:wrap; gap:.5rem; }
        .tl-chip { display:inline-flex; align-items:center; gap:.375rem; min-height:34px; padding:0 .75rem; border-radius:999px;
            border:1px solid var(--ts-border,#E5E7EB); background:#fff; color:#374151; font-size:.8125rem; font-weight:500; text-decoration:none; }
        .tl-chip:hover { background:#F9FAFB; }
        .tl-chip:focus-visible { outline:2px solid var(--ts-primary,#ff6213); outline-offset:2px; }
        .tl-chip[aria-current="true"] { background:#FFF7F4; border-color:var(--ts-primary,#ff6213); color:#9A3412; font-weight:700; }
        .tl-chip[aria-current="true"]::before { content:"\2713"; font-size:.75rem; }
        .tl-row { display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; }
        .tl-input, .tl-select { height:36px; padding:0 .75rem; border:1px solid var(--ts-border,#E5E7EB); border-radius:6px; font-size:.8125rem;
            color:var(--ts-text-body,#374151); background:#fff; font-family:var(--ts-font,inherit); }
        .tl-input { flex:1 1 220px; max-width:360px; min-width:0; }
        .tl-input:focus-visible, .tl-select:focus-visible { outline:2px solid var(--ts-primary,#ff6213); outline-offset:1px; }
        .tl-btn { height:36px; padding:0 1rem; border:1px solid transparent; border-radius:6px; cursor:pointer; background:var(--ts-primary,#ff6213);
            color:#fff; font-size:.8125rem; font-weight:500; display:inline-flex; align-items:center; text-decoration:none; }
        .tl-btn:hover { background:var(--ts-primary-hover,#de4a00); color:#fff; }
        .tl-btn:focus-visible { outline:2px solid #1F2937; outline-offset:2px; }
        .tl-btn--ghost { background:#fff; color:#374151; border-color:var(--ts-border,#E5E7EB); }
        .tl-btn--ghost:hover { background:#F9FAFB; color:#374151; }
        .tl-count { font-size:.75rem; color:#6B7280; }

        .tl-month { margin:1.5rem 0 .75rem; font-size:.9375rem; font-weight:700; color:#1F2937; }
        .tl-month:first-child { margin-top:0; }
        .tl-list { list-style:none; margin:0; padding:0 0 0 1.5rem; position:relative; }
        .tl-list::before { content:""; position:absolute; left:.4375rem; top:.5rem; bottom:.5rem; width:2px; background:#E5E7EB; }
        .tl-item { position:relative; margin:0 0 .75rem; }
        .tl-item::before { content:""; position:absolute; left:-1.5rem; top:1.125rem; width:12px; height:12px; border-radius:50%;
            background:#fff; border:2px solid var(--tl-dot,#9CA3AF); box-sizing:border-box; }
        .tl-card { display:flex; align-items:center; gap:.875rem; padding:.75rem 1rem; background:var(--ts-bg-white,#fff);
            border:1px solid var(--ts-border,#E5E7EB); border-radius:10px; text-decoration:none; color:inherit; }
        a.tl-card:hover { background:#F9FAFB; border-color:#D1D5DB; }
        .tl-card:focus-visible { outline:2px solid var(--ts-primary,#ff6213); outline-offset:2px; }
        .tl-icon { flex-shrink:0; width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center;
            background:var(--tl-bg,#F3F4F6); color:var(--tl-dot,#6B7280); }
        .tl-main { flex:1; min-width:0; }
        .tl-kind { display:block; font-size:.6875rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:#6B7280; }
        .tl-title { display:block; font-size:.9375rem; font-weight:600; color:#1F2937; overflow-wrap:anywhere; }
        .tl-sub { display:block; font-size:.75rem; color:#6B7280; margin-top:.125rem; overflow-wrap:anywhere; }
        .tl-side { flex-shrink:0; text-align:right; display:flex; flex-direction:column; align-items:flex-end; gap:.25rem; }
        .tl-date { font-size:.75rem; color:#6B7280; white-space:nowrap; }
        .tl-amount { font-size:.8125rem; font-weight:700; color:#1F2937; white-space:nowrap; }
        .tl-badge { display:inline-flex; align-items:center; padding:2px 10px; border-radius:999px; font-size:.6875rem; font-weight:600;
            border:1px solid transparent; white-space:nowrap; }
        .tl-badge--ok { background:#F0FDF4; color:#166534; border-color:#BBF7D0; }
        .tl-badge--info { background:#EFF6FF; color:#1D4ED8; border-color:#BFDBFE; }
        .tl-badge--warn { background:#FFFBEB; color:#92400E; border-color:#FDE68A; }
        .tl-badge--bad { background:#FEF2F2; color:#B91C1C; border-color:#FECACA; }
        .tl-badge--neutral { background:#F3F4F6; color:#4B5563; border-color:#D1D5DB; }

        .tl-t-quote { --tl-dot:#2563EB; --tl-bg:#EFF6FF; }
        .tl-t-sales_order { --tl-dot:#7C3AED; --tl-bg:#F5F3FF; }
        .tl-t-delivery_report { --tl-dot:#0F766E; --tl-bg:#F0FDFA; }
        .tl-t-service { --tl-dot:#EA580C; --tl-bg:#FFF7ED; }
        .tl-t-service_report { --tl-dot:#16A34A; --tl-bg:#F0FDF4; }
        .tl-t-store_order { --tl-dot:#BE185D; --tl-bg:#FDF2F8; }
        .tl-t-monitoring { --tl-dot:#0E7490; --tl-bg:#ECFEFF; }

        .tl-empty { text-align:center; padding:2.5rem 1rem; color:#6B7280; background:#fff; border:1px dashed #D1D5DB; border-radius:10px; }
        .tl-empty svg { color:#9CA3AF; margin-bottom:.5rem; }
        .tl-empty p { margin:0 0 .25rem; font-size:.875rem; }
        .tl-pager { margin-top:1rem; }

        @media (max-width: 640px) {
            .tl-summary { grid-template-columns:repeat(2, minmax(0, 1fr)); }
            .tl-input { max-width:none; flex-basis:100%; }
            .tl-card { flex-wrap:wrap; align-items:flex-start; }
            .tl-main { flex-basis:calc(100% - 60px); }
            .tl-side { flex-direction:row; flex-wrap:wrap; align-items:center; justify-content:flex-start; text-align:left; width:100%; gap:.5rem; }
        }
        @media (prefers-reduced-motion: reduce) { a.tl-sum, .tl-card { transition:none; } a.tl-sum:hover { transform:none; } }
    </style>
@endpush

@section('content')
@php
    $icons = [
        'quote'           => '<path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5z"/><path d="M14 2v4a2 2 0 0 0 2 2h4M8 13h8M8 17h5"/>',
        'sales_order'     => '<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="m3 8 9 5 9-5M12 13v8"/>',
        'delivery_report' => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
        'service'         => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
        'service_report'  => '<rect width="8" height="4" x="8" y="2" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2M9 14l2 2 4-4"/>',
        'store_order'     => '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>',
        'monitoring'      => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
    ];
    $filtered = count($selected) > 0 || $year || $search !== '';
    $hasNext = $next !== null;
@endphp
<div class="ts-page">

    <div class="ts-breadcrumb">
        <span>Panel de Control</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m9 18 6-6-6-6"/>
        </svg>
        <span class="ts-breadcrumb__current">Mi expediente</span>
    </div>

    <div class="ts-header-top">
        <div>
            <h1 class="ts-title">Mi expediente</h1>
            <p class="ts-subtitle">Todo lo que tienes con Equiterm Industries, en orden cronológico</p>
        </div>
    </div>

    {{-- Próximo servicio --}}
    @if ($hasNext)
        @php
            $nextTag = $next['url'] ? 'a' : 'div';
        @endphp
        <{{ $nextTag }} class="tl-next" @if ($next['url']) href="{{ $next['url'] }}" @endif>
            <span class="tl-next__label">Próximo servicio</span>
            <span class="tl-next__main">
                {{ $next['date']->locale('es')->isoFormat('ddd D [de] MMMM [de] YYYY') }}{{ $next['time'] ? ' · ' . $next['time'] . ' h' : '' }}
            </span>
            <span>{{ $next['number'] }}{{ $next['type'] !== '' ? ' · ' . $next['type'] : '' }} · {{ $next['status'] }}</span>
            @if ($next['url'])
                <span class="tl-next__go">Ver detalle →</span>
            @endif
        </{{ $nextTag }}>
    @endif

    {{-- Resumen por tipo --}}
    <section class="tl-summary" aria-label="Resumen de tu expediente">
        @foreach ($summary as $s)
            @if ($s['url'])
                <a class="tl-sum" href="{{ $s['url'] }}">
                    <span class="tl-sum__count">{{ number_format($s['count']) }}</span>
                    <span class="tl-sum__label">{{ $s['label'] }}</span>
                    <span class="tl-sum__go">Ver sección →</span>
                </a>
            @else
                <div class="tl-sum">
                    <span class="tl-sum__count">{{ number_format($s['count']) }}</span>
                    <span class="tl-sum__label">{{ $s['label'] }}</span>
                </div>
            @endif
        @endforeach
    </section>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('customer.timeline') }}" class="tl-filters" role="search" aria-label="Filtrar expediente">
        <div class="tl-chips" role="group" aria-label="Filtrar por tipo">
            <a class="tl-chip" href="{{ route('customer.timeline', array_filter(['year' => $year, 'q' => $search !== '' ? $search : null])) }}"
               @if (count($selected) === 0) aria-current="true" @endif>Todo</a>
            @foreach ($types as $key => $meta)
                @php
                    $isOn = in_array($key, $selected, true);
                    $next_types = $isOn ? array_values(array_diff($selected, [$key])) : array_merge($selected, [$key]);
                    $chipUrl = route('customer.timeline', array_filter([
                        'types' => $next_types ?: null,
                        'year'  => $year,
                        'q'     => $search !== '' ? $search : null,
                    ]));
                @endphp
                <a class="tl-chip" href="{{ $chipUrl }}" @if ($isOn) aria-current="true" @endif>{{ $meta['label'] }}</a>
            @endforeach
        </div>

        @foreach ($selected as $t)
            <input type="hidden" name="types[]" value="{{ $t }}">
        @endforeach

        <div class="tl-row">
            <input type="search" name="q" class="tl-input" value="{{ $search }}" maxlength="100"
                   placeholder="Buscar por folio, estado o detalle" aria-label="Buscar en el expediente">
            <select name="year" class="tl-select" aria-label="Año">
                <option value="">Todos los años</option>
                @foreach ($years as $y)
                    <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                @endforeach
            </select>
            <button type="submit" class="tl-btn">Filtrar</button>
            @if ($filtered)
                <a href="{{ route('customer.timeline') }}" class="tl-btn tl-btn--ghost">Limpiar filtros</a>
            @endif
            <span class="tl-count" role="status">{{ number_format($items->total()) }} {{ $items->total() === 1 ? 'movimiento' : 'movimientos' }}</span>
        </div>
    </form>

    {{-- Línea de tiempo --}}
    @if ($items->isEmpty())
        <div class="tl-empty">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
            </svg>
            @if ($filtered)
                <p>No hay movimientos con esos filtros.</p>
                <a href="{{ route('customer.timeline') }}" class="tl-btn tl-btn--ghost">Limpiar filtros</a>
            @else
                <p>Aún no hay movimientos en tu expediente.</p>
                <p>Cuando recibas cotizaciones, servicios, entregas o pedidos, aparecerán aquí.</p>
            @endif
        </div>
    @else
        @php
            $groups = $items->getCollection()->groupBy(fn ($e) => $e['date']->format('Y-m'));
        @endphp
        @foreach ($groups as $ym => $events)
            @php
                $first = $events->first()['date'];
                $monthLabel = \Illuminate\Support\Str::ucfirst($first->copy()->locale('es')->translatedFormat('F Y'));
            @endphp
            <h2 class="tl-month" id="tl-m-{{ $ym }}">{{ $monthLabel }}</h2>
            <ol class="tl-list" aria-labelledby="tl-m-{{ $ym }}">
                @foreach ($events as $e)
                    @php $tag = $e['url'] ? 'a' : 'div'; @endphp
                    <li class="tl-item tl-t-{{ $e['type'] }}">
                        <{{ $tag }} class="tl-card" @if ($e['url']) href="{{ $e['url'] }}" @endif>
                            <span class="tl-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $icons[$e['type']] !!}</svg>
                            </span>
                            <span class="tl-main">
                                <span class="tl-kind">{{ $e['type_label'] }}</span>
                                <span class="tl-title">{{ $e['title'] }}</span>
                                @if ($e['subtitle'] !== '')
                                    <span class="tl-sub">{{ $e['subtitle'] }}</span>
                                @endif
                            </span>
                            <span class="tl-side">
                                <time class="tl-date" datetime="{{ $e['date']->toDateString() }}">{{ $e['date']->format('d/m/Y') }}</time>
                                <span class="tl-badge tl-badge--{{ $e['tone'] }}">{{ $e['badge'] }}</span>
                                @if ($e['amount'])
                                    <span class="tl-amount">{{ $e['amount'] }}</span>
                                @endif
                            </span>
                        </{{ $tag }}>
                    </li>
                @endforeach
            </ol>
        @endforeach

        @if ($items->hasPages())
            <div class="ts-pagination tl-pager">
                <span class="ts-pagination__info">
                    Mostrando {{ $items->firstItem() }}–{{ $items->lastItem() }} de {{ $items->total() }} movimientos
                </span>
                <div class="ts-pagination__links">
                    {{ $items->links() }}
                </div>
            </div>
        @endif
    @endif

</div>
@endsection
