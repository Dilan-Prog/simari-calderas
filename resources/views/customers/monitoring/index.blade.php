@extends('customers.layouts.master')

@section('title', 'Monitoreo')

@push('styles')
    @vite('resources/css/admin/technical-services.css')
    @include('customers.monitoring.partials.styles')
@endpush

@section('content')
@php
    $num = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 3, '.', ','), '0'), '.');
    $dm = fn ($d) => $d->locale('es')->isoFormat('D MMM YYYY');
@endphp
<div class="ts-page" id="ts-page">

    <div class="ts-breadcrumb">
        <span>Portal de servicios</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m9 18 6-6-6-6"/>
        </svg>
        <span class="ts-breadcrumb__current">Monitoreo</span>
    </div>

    <div class="ts-header-top">
        <div>
            <h1 class="ts-title">Monitoreo de agua y químicos</h1>
            <p class="ts-subtitle">Revisa la evolución de tu análisis químico del agua: tendencia, estado de las lecturas y cumplimiento diario.</p>
        </div>
    </div>

    @if ($analysis)
        @include('customers.monitoring.partials.analysis', ['analysis' => $analysis])
    @elseif ($cards->isEmpty())
        <div class="ts-card mn-empty">
            <p>No tienes permiso para ver los datos de análisis químico.</p>
        </div>
    @endif

    @if ($cards->isNotEmpty())
        <h2 class="mn-section-title" style="margin-top:1.5rem">Programas de monitoreo</h2>
        <div class="mn-grid mn-block">
            @foreach ($cards as $c)
                @php
                    $p = $c['program'];
                    $s = $c['stats'];
                    $pct = $c['days_elapsed'] > 0 ? min(100, (int) round($c['days_logged'] / $c['days_elapsed'] * 100)) : 0;
                @endphp
                <a class="mn-card mn-card--link" href="{{ route('customer.monitoring.show', $p) }}"
                   aria-label="Abrir programa {{ $p->name }}">
                    <div class="mn-card__head">
                        <h2 class="mn-card__title">{{ $p->name }}</h2>
                        <span class="mn-badge mn-badge--{{ $p->status }}">{{ $p->status_label }}</span>
                    </div>

                    <div class="mn-muted">
                        {{ $dm($p->start_date) }} &ndash; {{ $dm($c['end']) }} &middot; {{ $p->weeks }} {{ $p->weeks === 1 ? 'semana' : 'semanas' }}
                    </div>

                    <div>
                        <strong style="font-size:.9375rem;color:var(--ts-text-title,#111827)">
                            @if ($c['current_week'])
                                Semana {{ $c['current_week'] }} de {{ $p->weeks }}
                            @elseif ($c['not_started'])
                                Inicia el {{ $dm($p->start_date) }}
                            @else
                                Programa concluido ({{ $p->weeks }} {{ $p->weeks === 1 ? 'semana' : 'semanas' }})
                            @endif
                        </strong>
                    </div>

                    <div>
                        <div class="mn-progress-label">
                            <span>Días con registro</span>
                            <span>{{ $c['days_logged'] }} de {{ $c['days_elapsed'] }} transcurridos</span>
                        </div>
                        <div class="mn-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100"
                             aria-valuenow="{{ $pct }}" aria-label="Avance de registro diario">
                            <span style="width: {{ $pct }}%"></span>
                        </div>
                    </div>

                    <dl class="mn-meta">
                        <div>
                            <dt>Registros</dt>
                            <dd>{{ $s['entries'] }}</dd>
                        </div>
                        <div>
                            <dt>Fuera de rango</dt>
                            <dd class="{{ $s['out_of_range'] > 0 ? 'mn-bad-text' : '' }}">{{ $s['out_of_range'] }}</dd>
                        </div>
                        <div>
                            <dt>Cumplimiento</dt>
                            <dd>{{ $s['compliance'] !== null ? $s['compliance'] . '%' : 'Sin datos' }}</dd>
                        </div>
                        <div>
                            <dt>Último registro</dt>
                            <dd>{{ $s['last_entry_date'] ? \Carbon\Carbon::parse($s['last_entry_date'])->format('d/m/Y') : 'Aún sin registros' }}</dd>
                        </div>
                    </dl>
                </a>
            @endforeach
        </div>

        <section class="ts-card" aria-labelledby="mn-alerts-title">
            <h2 class="mn-section-title" id="mn-alerts-title">Lecturas fuera de rango recientes</h2>
            <p class="mn-muted" style="margin:-.5rem 0 .75rem">Últimos 30 días. Si algo se repite, avisa a tu equipo de Equiterm.</p>

            @if ($alerts->isEmpty())
                <p class="mn-muted" style="margin:0">No hay lecturas fuera de rango en los últimos 30 días.
                    <span aria-hidden="true">&#10004;</span></p>
            @else
                <div class="mn-table-wrap">
                    <table class="mn-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Programa</th>
                                <th>Equipo</th>
                                <th>Parámetro</th>
                                <th class="mn-num">Valor</th>
                                <th>Rango esperado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($alerts as $a)
                                @php
                                    $it = $a->item;
                                    $lo = $it?->limit_min; $hi = $it?->limit_max;
                                    $range = $lo !== null && $hi !== null ? $num($lo) . ' – ' . $num($hi)
                                        : ($lo !== null ? '≥ ' . $num($lo) : ($hi !== null ? '≤ ' . $num($hi) : '—'));
                                @endphp
                                <tr>
                                    <td>{{ $a->entry_date->format('d/m/Y') }}</td>
                                    <td>
                                        <a href="{{ route('customer.monitoring.show', $a->program_id) }}">{{ $a->program?->name }}</a>
                                    </td>
                                    <td>{{ $a->equipment?->name ?? '—' }}</td>
                                    <td>{{ $it?->name }}</td>
                                    <td class="mn-num"><strong class="mn-bad-text">{{ $num($a->value) }}</strong> {{ $it?->unit }}</td>
                                    <td>{{ $range }} {{ $it?->unit }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif
</div>
@endsection
