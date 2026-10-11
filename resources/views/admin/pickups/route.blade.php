@extends('admin.layouts.master')

@section('title', 'Ruta del día - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-logistics.css'])
@endpush

@section('content')
<div class="lg-page">
    <div class="lg-head">
        <div>
            <a href="{{ route('admin.pickups.index') }}" class="erp-objhead-back">← Recolecciones</a>
            <h1>Ruta del día</h1>
            <p>{{ $date->translatedFormat('l d \d\e F \d\e Y') }} · {{ $totals['pickups'] }} recolecciones · {{ $totals['shipments'] }} envíos</p>
        </div>
        <form method="GET" class="lg-filters" style="margin:0;">
            <a href="{{ route('admin.pickups.route', ['date' => $date->copy()->subDay()->toDateString()]) }}" class="lg-btn">←</a>
            <input type="date" name="date" value="{{ $date->toDateString() }}" onchange="this.form.submit()">
            <a href="{{ route('admin.pickups.route', ['date' => $date->copy()->addDay()->toDateString()]) }}" class="lg-btn">→</a>
            <a href="{{ route('admin.pickups.route') }}" class="lg-btn">Hoy</a>
        </form>
    </div>

    @forelse ($groups as $group)
        <section class="lg-route-group">
            <h3>{{ $group['label'] }}</h3>
            @foreach ($group['pickups'] ?? [] as $p)
                <a class="lg-stop" href="{{ route('admin.pickups.show', $p) }}">
                    <span class="lg-stop-kind">Recolección</span>
                    <span style="flex:1;">
                        <strong>{{ $p->number }}</strong> · {{ $p->counterpartLabel() }}
                        <div class="lg-muted">{{ $p->time_window }} {{ $p->time_window ? '·' : '' }} {{ \Illuminate\Support\Str::limit($p->address, 90) }}</div>
                    </span>
                    <span class="erp-chip erp-tone-{{ \App\Support\PickupStatus::tone($p->status) }}">{{ $p->statusLabel() }}</span>
                </a>
            @endforeach
            @foreach ($group['shipments'] ?? [] as $s)
                <a class="lg-stop" href="{{ $s->detailUrl() }}">
                    <span class="lg-stop-kind ship">Envío</span>
                    <span style="flex:1;">
                        <strong>{{ $s->guia }}</strong> · {{ $s->recipientLabel() }}
                        <div class="lg-muted">{{ $s->documentNumber() }} {{ $s->delivery_address ? '· ' . \Illuminate\Support\Str::limit($s->delivery_address, 80) : '' }}</div>
                    </span>
                    <span class="erp-chip erp-tone-neutral">{{ \App\Support\ShipmentStatus::meta($s->status)['label'] ?? $s->status }}</span>
                </a>
            @endforeach
        </section>
    @empty
        <div class="erp-empty">No hay recolecciones ni envíos programados para este día.</div>
    @endforelse
</div>
@endsection
