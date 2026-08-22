{{--
    Rastreo de Anuncios — Admin (rediseño sobre mockup "Rastreo de
    Anuncios.dc.html", sección isList). La pantalla de DETALLE (show.blade.php)
    se construye/mantiene aparte, en paralelo -- no se toca desde aquí.

    Contrato de datos real (AdTrackingAdminController::index()):
      - $kpis            [{label,value,delta (string|null),deltaIsPositive,hint,accent}] x4
      - $funnel          [{label,count,pct,width (0-100),note,tone (gray|orange|green)}] x3
      - $rangeLabel      "Últimos 30 días" o "Del 12 ago al 14 ago, 2026"
      - $visits          LengthAwarePaginator<AdVisit> con ->events_count y ->converted
                         anotados por fila (ver controller)
      - $sourceOptions / $campaignOptions   Collection<string> para los <select>
      - $filters         ['q','range','from','to','source','campaign','onlyConverted']
      - $tableTotals     ['total','converted','interest'] -- ya sobre el conjunto filtrado
--}}
@extends('admin.layouts.master')

@section('title', 'Rastreo de Anuncios - Admin')

@push('styles')
<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" media="print" onload="this.media='all'">
@vite('resources/css/admin/pages/ad-tracking.css')
@endpush

@section('content')
<div class="adt-page">

    {{-- Header --}}
    <div class="adt-header">
        <div>
            <div class="adt-breadcrumb">
                <span>Panel de Control</span>
                <span class="adt-breadcrumb-sep">&rsaquo;</span>
                <span class="adt-breadcrumb-current">Rastreo de Anuncios</span>
            </div>
            <h1 class="adt-title">Rastreo de Anuncios</h1>
            <p class="adt-subtitle">Visitantes identificados por clic de anuncio (GCLID / WBRAID) y el recorrido que siguieron hasta cotizar o comprar.</p>
        </div>
        <div>
            <a href="{{ route('admin.ad-tracking.export', request()->only(['from', 'to'])) }}" class="adt-btn" target="_blank" rel="noopener">Exportar</a>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="adt-kpi-grid">
        @foreach ($kpis as $k)
            <div class="adt-kpi-card">
                <div class="adt-kpi-label">{{ $k['label'] }}</div>
                <div class="adt-kpi-value-row">
                    <span class="adt-kpi-value @if($k['accent']) is-accent @endif">{{ $k['value'] }}</span>
                    @if ($k['delta'] !== null)
                        <span class="adt-chip @if($k['deltaIsPositive']) is-positive @endif">{{ $k['delta'] }}</span>
                    @endif
                </div>
                <div class="adt-kpi-hint">{{ $k['hint'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Embudo del recorrido --}}
    <div class="adt-funnel-card">
        <div class="adt-funnel-head">
            <div class="adt-funnel-title">Embudo del recorrido</div>
            <div class="adt-funnel-sub">{{ $rangeLabel }} &middot; {{ $funnel[0]['count'] }} visitantes con clic de anuncio</div>
        </div>
        <div class="adt-funnel-grid">
            @foreach ($funnel as $f)
                <div class="adt-funnel-item adt-tone-{{ $f['tone'] }}">
                    <div class="adt-funnel-row">
                        <span class="adt-funnel-label">{{ $f['label'] }}</span>
                        <span class="adt-chip adt-funnel-pct adt-tone-{{ $f['tone'] }}">{{ $f['pct'] }}</span>
                    </div>
                    <div class="adt-funnel-count">{{ $f['count'] }}</div>
                    <div class="adt-funnel-bar-track">
                        <div class="adt-funnel-bar-fill adt-tone-{{ $f['tone'] }}" style="width:{{ $f['width'] }}%;"></div>
                    </div>
                    <div class="adt-funnel-note">{{ $f['note'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Filtros + tabla + paginación --}}
    <div class="adt-table-card">

        <form method="GET" action="{{ route('admin.ad-tracking.index') }}" class="adt-filters">
            <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Buscar GCLID o WBRAID&hellip;" class="adt-filter-search">

            <select name="range" onchange="this.form.submit()">
                <option value="7" @selected($filters['range'] === '7')>Últimos 7 días</option>
                <option value="30" @selected($filters['range'] === '30')>Últimos 30 días</option>
                <option value="90" @selected($filters['range'] === '90')>Últimos 90 días</option>
            </select>

            <div class="adt-filter-dates">
                Desde <input type="date" name="from" value="{{ $filters['from'] }}">
                Hasta <input type="date" name="to" value="{{ $filters['to'] }}">
            </div>

            <select name="source" onchange="this.form.submit()">
                <option value="" @selected($filters['source'] === '')>Todas las fuentes</option>
                @foreach ($sourceOptions as $o)
                    <option value="{{ $o }}" @selected($filters['source'] === $o)>{{ $o }}</option>
                @endforeach
            </select>

            <select name="campaign" class="adt-filter-campaign" onchange="this.form.submit()">
                <option value="" @selected($filters['campaign'] === '')>Todas las campañas</option>
                @foreach ($campaignOptions as $o)
                    <option value="{{ $o }}" @selected($filters['campaign'] === $o)>{{ $o }}</option>
                @endforeach
            </select>

            <label class="adt-toggle-label">
                <input type="checkbox" name="only_converted" value="1"
                    style="position:absolute;opacity:0;width:1px;height:1px;"
                    @checked($filters['onlyConverted']) onchange="this.form.submit()">
                <span class="adt-toggle-track {{ $filters['onlyConverted'] ? 'is-on' : '' }}"><span class="adt-toggle-knob"></span></span>
                Solo convertidos
            </label>

            <button type="submit" class="adt-filter-submit">Filtrar</button>
            <a href="{{ route('admin.ad-tracking.index') }}" class="adt-filter-clear">Limpiar</a>
        </form>

        <div class="adt-scroll">
            <table class="adt-table">
                <thead>
                    <tr>
                        <th>Visitor UUID</th>
                        <th>GCLID / WBRAID</th>
                        <th>UTM Source / Campaña</th>
                        <th>Primera visita</th>
                        <th>Última visita</th>
                        <th># Eventos</th>
                        <th class="adt-th-right"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($visits as $visit)
                        <tr class="adt-row" onclick="window.location='{{ route('admin.ad-tracking.show', $visit) }}'">
                            <td>
                                <div class="adt-uuid adt-mono" title="{{ $visit->visitor_uuid }}">{{ Str::limit($visit->visitor_uuid, 18, '…') }}</div>
                            </td>
                            <td>
                                <div class="adt-clickid-cell">
                                    <span class="adt-clickid-chip adt-mono" title="{{ $visit->primary_click_id }}">
                                        {{ $visit->primary_click_id ? Str::limit($visit->primary_click_id, 26, '…') : '—' }}
                                    </span>
                                    <span class="adt-chip adt-status-chip {{ $visit->converted ? 'is-positive' : 'is-neutral' }}">
                                        {{ $visit->converted ? 'Convertido' : 'Sin conversión' }}
                                    </span>
                                </div>
                                <div class="adt-clickkind">{{ $visit->identifier_kind }}</div>
                            </td>
                            <td>
                                <div class="adt-source">{{ $visit->utm_source ?: '—' }}</div>
                                <div class="adt-campaign" title="{{ $visit->utm_campaign }}">{{ $visit->utm_campaign ?: '—' }}</div>
                            </td>
                            <td class="adt-date-cell">{{ $visit->first_seen_at?->locale('es')->translatedFormat('d M, H:i') ?? '—' }}</td>
                            <td class="adt-date-cell">{{ $visit->last_seen_at?->locale('es')->translatedFormat('d M, H:i') ?? '—' }}</td>
                            <td>
                                <span class="adt-events-chip {{ $visit->events_count >= 10 ? 'is-hot' : '' }}">{{ $visit->events_count }} eventos</span>
                            </td>
                            <td class="adt-chevron-cell">&rsaquo;</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="adt-empty">Ningún visitante coincide con estos filtros.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="adt-footer">
            <div class="adt-footer-info">
                Mostrando {{ $visits->total() > 0 ? $visits->firstItem() : 0 }}&ndash;{{ $visits->total() > 0 ? $visits->lastItem() : 0 }}
                de {{ $tableTotals['total'] }} visitantes &middot; {{ $tableTotals['converted'] }} convertidos &middot; {{ $tableTotals['interest'] }} con interés
            </div>
            <div class="adt-pagination">
                @if ($visits->onFirstPage())
                    <span class="adt-page-btn is-disabled">Anterior</span>
                @else
                    <a href="{{ $visits->previousPageUrl() }}" class="adt-page-btn">Anterior</a>
                @endif
                @if ($visits->hasMorePages())
                    <a href="{{ $visits->nextPageUrl() }}" class="adt-page-btn">Siguiente</a>
                @else
                    <span class="adt-page-btn is-disabled">Siguiente</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
