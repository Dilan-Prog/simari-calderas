@extends('admin.layouts.master')
@section('title')
    Opiniones de servicio - Admin
@endsection
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css'])
@endpush
@section('content')
    @php
        $tones = ['submitted' => 'ok', 'pending' => 'neu', 'skipped' => 'warn'];
        $stars = fn ($n) => str_repeat('★', (int) $n) . str_repeat('☆', 5 - (int) $n);
    @endphp
    <div class="erp-page">
        <x-erp.page-header title="Opiniones de servicio"
            desc="Calificaciones que los clientes dejan al firmar sus reportes de servicio."
            :crumbs="[['Servicios', null], ['Opiniones de servicio', null]]">
            <a class="erp-btn" href="{{ route('admin.service-ratings.export', request()->query()) }}">Exportar CSV</a>
        </x-erp.page-header>

        @if (session('success'))<div class="erp-card" role="status" style="padding:10px 14px;margin-bottom:12px;">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="erp-card" role="alert" style="padding:10px 14px;margin-bottom:12px;color:#b91c1c;">{{ session('error') }}</div>@endif

        <div class="erp-tiles">
            <x-erp.kpi-tile title="Calificaciones enviadas" :value="$kpi['total']" tone="info" caption="Total recibido" />
            <x-erp.kpi-tile title="Promedio" :value="$kpi['total'] ? number_format($kpi['average'], 2) . ' ★' : '—'" tone="ok" caption="De 5 estrellas" />
            <x-erp.kpi-tile title="Sin servicio web" :value="$kpi['noPage']" :tone="$kpi['noPage'] ? 'warn' : 'ok'" caption="Asígnales un servicio" :href="route('admin.service-ratings.index', ['service_page_id' => 'none', 'status' => 'submitted'])" />
        </div>

        <x-erp.worklist :chips="$chips">
            <x-slot:toolbar>
                <form method="GET" class="crm-toolbar-form" role="search" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
                    <label class="erp-field erp-field--grow">Buscar
                        <input class="erp-input" type="text" name="q" value="{{ request('q') }}" placeholder="Cliente o folio de reporte">
                    </label>
                    <label class="erp-field">Servicio web
                        <select class="erp-select" name="service_page_id">
                            <option value="">Todos</option>
                            <option value="none" @selected(request('service_page_id') === 'none')>Sin servicio</option>
                            @foreach ($servicePages as $sp)
                                <option value="{{ $sp->id }}" @selected((string) request('service_page_id') === (string) $sp->id)>{{ $sp->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="erp-field">Estrellas
                        <select class="erp-select" name="stars">
                            <option value="">Todas</option>
                            @foreach (range(5, 1) as $n)<option value="{{ $n }}" @selected((string) request('stars') === (string) $n)>{{ $n }} ★</option>@endforeach
                        </select>
                    </label>
                    <label class="erp-field">Estado
                        <select class="erp-select" name="status">
                            <option value="">Todos</option>
                            @foreach ($statusLabels as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach
                        </select>
                    </label>
                    <label class="erp-field">Desde<input class="erp-input" type="date" name="date_from" value="{{ request('date_from') }}"></label>
                    <label class="erp-field">Hasta<input class="erp-input" type="date" name="date_to" value="{{ request('date_to') }}"></label>
                    <button class="erp-btn erp-btn--primary" type="submit">Filtrar</button>
                </form>
            </x-slot:toolbar>

            @if ($ratings->isEmpty())
                <x-erp.empty title="Sin opiniones" text="Aún no hay calificaciones con estos filtros." />
            @else
                <div class="erp-table-wrap">
                    <table class="erp-table">
                        <thead>
                            <tr><th>Cliente</th><th>Reporte</th><th>Servicio web</th><th>Calificación</th><th>Tarjeta</th><th>Prom. variables</th><th>Fecha</th><th>Estado</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($ratings as $r)
                                <tr>
                                    <td><a href="{{ route('admin.service-ratings.show', $r) }}">{{ $r->customer_name }}</a></td>
                                    <td>@if ($r->report)<a href="{{ route('admin.service-reports.show', $r->report) }}">{{ $r->report->report_number }}</a>@else — @endif</td>
                                    <td>{{ $r->servicePage?->name ?? '—' }}</td>
                                    <td>@if ($r->rating)<span style="color:#f59e0b;" title="{{ $r->rating }} de 5">{{ $stars($r->rating) }}</span>@else — @endif</td>
                                    <td>{{ $r->card_label ?? '—' }}</td>
                                    <td>{{ $r->answers_avg !== null ? number_format($r->answers_avg, 1) : '—' }}</td>
                                    <td>{{ ($r->submitted_at ?? $r->created_at)?->format('d/m/Y H:i') }}</td>
                                    <td><x-erp.chip :tone="$tones[$r->status] ?? 'neu'" :label="$statusLabels[$r->status] ?? $r->status" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            <x-slot:footer>{{ $ratings->links() }}</x-slot:footer>
        </x-erp.worklist>
    </div>
@endsection
