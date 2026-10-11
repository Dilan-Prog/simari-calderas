@extends('admin.layouts.master')

@section('title', 'Ruta del día - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-logistics.css'])
@endpush

@section('content')
@php
    $shipmentTone = fn ($st) => match ($st) { 'entregado' => 'ok', 'incidencia' => 'bad', 'preparando' => 'neu', default => 'info' };
@endphp
<div class="erp-page">
    <x-erp.page-header title="Ruta del día" code="LOG3"
        :desc="$date->translatedFormat('l d \d\e F \d\e Y') . ' · ' . $totals['pickups'] . ' recolecciones · ' . $totals['shipments'] . ' envíos'"
        :crumbs="[['Logística', null], ['Recolecciones', route('admin.pickups.index')], ['Ruta del día', null]]">
        <form method="GET" class="lg-datenav">
            <a href="{{ route('admin.pickups.route', ['date' => $date->copy()->subDay()->toDateString()]) }}" class="erp-btn" aria-label="Día anterior">←</a>
            <input type="date" name="date" class="erp-select" aria-label="Fecha de la ruta" value="{{ $date->toDateString() }}" onchange="this.form.submit()">
            <a href="{{ route('admin.pickups.route', ['date' => $date->copy()->addDay()->toDateString()]) }}" class="erp-btn" aria-label="Día siguiente">→</a>
            <a href="{{ route('admin.pickups.route') }}" class="erp-btn">Hoy</a>
        </form>
    </x-erp.page-header>

    @if (count($groups) === 0)
        <x-erp.empty title="Sin paradas" text="No hay recolecciones ni envíos programados para este día.">
            <a href="{{ route('admin.pickups.create') }}" class="erp-btn erp-btn--primary">+ Nueva recolección</a>
        </x-erp.empty>
    @else
        <div class="lg-route">
            @foreach ($groups as $group)
                @php
                    $np = count($group['pickups'] ?? []);
                    $ns = count($group['shipments'] ?? []);
                    $n = 0;
                @endphp
                <section class="erp-card">
                    <div class="erp-card-body">
                        <div class="lg-route-head">
                            <div>
                                <div class="lg-route-title">{{ $group['label'] }}</div>
                                <div class="lg-route-sub">{{ $np + $ns }} {{ $np + $ns === 1 ? 'parada' : 'paradas' }}@if ($np) · {{ $np }} recolección(es)@endif @if ($ns) · {{ $ns }} envío(s)@endif</div>
                            </div>
                        </div>
                        <ol class="lg-stops">
                            @foreach ($group['pickups'] ?? [] as $p)
                                @php
                                    $n++;
                                    $tone = \App\Support\PickupStatus::chipTone($p->status);
                                @endphp
                                <li class="lg-stop">
                                    <div class="lg-stop-rail"><span class="lg-stop-dot is-{{ $tone }}">{{ $n }}</span><span class="lg-stop-line"></span></div>
                                    <div class="lg-stop-body">
                                        <a class="lg-stop-link" href="{{ route('admin.pickups.show', $p) }}">
                                            <div class="lg-stop-meta">
                                                @if ($p->time_window)<span class="lg-stop-time">{{ $p->time_window }}</span>@endif
                                                <span class="lg-stop-kind">Recolección</span>
                                                <x-erp.chip :tone="$tone" :label="$p->statusLabel()" />
                                            </div>
                                            <div class="lg-stop-name">{{ $p->counterpartLabel() }} <small>· {{ $p->number }}</small></div>
                                            <div class="lg-stop-addr">{{ \Illuminate\Support\Str::limit($p->address, 90) }}</div>
                                            @if ($p->contact_name || $p->contact_phone)
                                                <div class="lg-stop-contact">{{ $p->contact_name }}@if ($p->contact_name && $p->contact_phone) · @endif{{ $p->contact_phone }}</div>
                                            @endif
                                        </a>
                                    </div>
                                </li>
                            @endforeach
                            @foreach ($group['shipments'] ?? [] as $s)
                                @php
                                    $n++;
                                    $tone = $shipmentTone($s->status);
                                @endphp
                                <li class="lg-stop">
                                    <div class="lg-stop-rail"><span class="lg-stop-dot is-{{ $tone }}">{{ $n }}</span><span class="lg-stop-line"></span></div>
                                    <div class="lg-stop-body">
                                        <a class="lg-stop-link" href="{{ $s->detailUrl() }}">
                                            <div class="lg-stop-meta">
                                                <span class="lg-stop-kind ship">Envío</span>
                                                <x-erp.chip :tone="$tone" :label="\App\Support\ShipmentStatus::meta($s->status)['label'] ?? $s->status" />
                                            </div>
                                            <div class="lg-stop-name">{{ $s->recipientLabel() }} <small>· {{ $s->guia }}</small></div>
                                            <div class="lg-stop-addr">{{ $s->documentNumber() }} {{ $s->delivery_address ? '· ' . \Illuminate\Support\Str::limit($s->delivery_address, 80) : '' }}</div>
                                        </a>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </section>
            @endforeach
            <div class="lg-map-ph" role="note">
                <div><strong>Mapa de ruta</strong>Se conectará con el proveedor de mapas para trazar las paradas y la ubicación de la unidad.</div>
            </div>
        </div>
    @endif
</div>
@endsection
