@extends('admin.layouts.master')

@section('title', 'Rastreo de Anuncios - Admin')

@section('content')
<div class="ad-tracking-page">
    <div>
        <p class="breadcrumb-clients-manager">Panel de Control &gt; <strong>Rastreo de Anuncios</strong></p>
        <h1 style="margin:0 0 4px;">Rastreo de Anuncios</h1>
        <p class="breadcrumb-clients-manager main">Visitantes identificados por gclid/wbraid/UTM, con sus eventos de interés registrados</p>
    </div>

    <div class="ad-tracking-card">
        <form method="GET" action="{{ route('admin.ad-tracking.index') }}" class="ad-tracking-filters">
            <input type="text" name="q" value="{{ request('q') }}" class="ad-tracking-filter-input"
                placeholder="Buscar por GCLID o WBRAID…">

            <span class="ad-tracking-filter-label">Desde:</span>
            <input type="date" name="from" value="{{ request('from') }}" class="ad-tracking-filter-input">

            <span class="ad-tracking-filter-label">Hasta:</span>
            <input type="date" name="to" value="{{ request('to') }}" class="ad-tracking-filter-input">

            <button type="submit" class="dt-button">Filtrar</button>
            <a href="{{ route('admin.ad-tracking.index') }}" class="dt-button">Limpiar</a>

            <div class="ad-tracking-filters-spacer"></div>

            <a href="{{ route('admin.ad-tracking.export', request()->only(['from', 'to'])) }}" class="dt-button"
                target="_blank" rel="noopener">
                Exportar eventos (detallado interno)
            </a>
        </form>

        <div class="ad-tracking-table-wrap">
            <table class="ad-tracking-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>Visitor UUID</th>
                        <th>GCLID</th>
                        <th>WBRAID</th>
                        <th>UTM Source</th>
                        <th>Primera visita</th>
                        <th>Última visita</th>
                        <th>Eventos</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($visits as $visit)
                        <tr>
                            <td title="{{ $visit->visitor_uuid }}">{{ Str::limit($visit->visitor_uuid, 13, '…') }}</td>
                            <td>{{ $visit->gclid ?? '—' }}</td>
                            <td>{{ $visit->wbraid ?? '—' }}</td>
                            <td>{{ $visit->utm_source ?? '—' }}</td>
                            <td>{{ $visit->first_seen_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $visit->last_seen_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $visit->events_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center; color:#9ca3af; padding:24px 0;">
                                No hay visitantes registrados con estos filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($visits->hasPages())
            {{ $visits->links('admin.components.pagination') }}
        @endif
    </div>
</div>
@endsection

@push('styles')
    <style>
        .ad-tracking-page {
            width: 95%;
            max-width: 1300px;
            margin: 0 auto;
            padding: 24px 0 48px;
            height: 100%;
            overflow-y: auto;
            box-sizing: border-box;
        }

        .ad-tracking-card {
            background: #fff;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            padding: 20px;
            margin-top: 16px;
        }

        .ad-tracking-filters {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }

        .ad-tracking-filter-label {
            font-size: 12.5px;
            font-weight: 600;
            color: #6b7280;
        }

        .ad-tracking-filter-input {
            padding: 6px 10px;
            border: 1px solid rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            font-size: 12.5px;
        }

        .ad-tracking-filters-spacer {
            flex: 1 1 auto;
        }

        .ad-tracking-table-wrap {
            overflow-x: auto;
            overflow-y: auto;
            max-height: 60vh;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 8px;
        }

        .ad-tracking-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
        }

        .ad-tracking-table th, .ad-tracking-table td {
            padding: 10px 12px;
            text-align: left;
            white-space: nowrap;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }

        .ad-tracking-table th {
            background: #f9fafb;
            font-weight: 700;
            position: sticky;
            top: 0;
        }
    </style>
@endpush
