@extends('admin.layouts.master')

@section('title', 'Captura ' . $pickup->number . ' - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-logistics.css'])
@endpush

@section('content')
@php
    $qty = fn ($n) => rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    $hasPhoto = $pickup->evidences->count() > 0;
    $hasSign = ! empty($pickup->signature_data);
    $addr = $pickup->address;
@endphp
<div class="lg-mobile">
    <header class="lg-m-top">
        <a href="{{ route('admin.pickups.show', $pickup) }}" class="lg-m-back">‹ Detalle</a>
        <small>App de campo · {{ \App\Support\PickupStatus::TYPES[$pickup->type] ?? $pickup->type }}</small>
        <h1>{{ $pickup->number }} <x-erp.chip :tone="\App\Support\PickupStatus::chipTone($pickup->status)" :label="$pickup->statusLabel()" /></h1>
    </header>

    @if (session('success')) <div class="lg-alert lg-alert--ok" role="status">{{ session('success') }}</div> @endif
    @if (session('error')) <div class="lg-alert" role="alert">{{ session('error') }}</div> @endif

    <div class="lg-m-steps" aria-label="Avance de la captura">
        <span class="lg-m-step {{ $hasPhoto ? 'is-done' : '' }}">{{ $hasPhoto ? '✓ ' : '' }}Foto</span>
        <span class="lg-m-step {{ $hasSign ? 'is-done' : '' }}">{{ $hasSign ? '✓ ' : '' }}Firma</span>
        <span class="lg-m-step {{ $pickup->status === 'entregada' ? 'is-done' : '' }}">{{ $pickup->status === 'entregada' ? '✓ ' : '' }}Estatus</span>
    </div>

    <section class="lg-m-card">
        <div class="lg-m-name">{{ $pickup->counterpartLabel() }}</div>
        <div class="lg-m-addr">{{ $addr }}</div>
        @if ($pickup->time_window)<div class="lg-muted" style="margin-top:3px;">Ventana {{ $pickup->time_window }}</div>@endif
        <div class="lg-m-grid">
            <a class="lg-m-btn lg-m-btn--sm" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query={{ urlencode((string) $addr) }}">Abrir en mapas</a>
            @if ($pickup->contact_phone)
                <a class="lg-m-btn lg-m-btn--sm" href="tel:{{ preg_replace('/[^0-9+]/', '', $pickup->contact_phone) }}">Llamar · {{ $pickup->contact_phone }}</a>
            @else
                <span class="lg-m-btn lg-m-btn--sm" aria-disabled="true" style="opacity:.5;">Sin teléfono</span>
            @endif
        </div>
    </section>

    <section class="lg-m-card">
        <h2>Contenido</h2>
        <ul class="lg-m-list">
            @foreach ($pickup->items as $it)
                <li>{{ $it->description }} ({{ $qty($it->quantity) }} {{ $it->unit }})</li>
            @endforeach
        </ul>
    </section>

    <section class="lg-m-card">
        <h2>1. Foto de evidencia</h2>
        <form method="POST" action="{{ route('admin.pickups.evidences.store', $pickup) }}" enctype="multipart/form-data">
            @csrf
            <input type="file" name="photo" accept="image/*" capture="environment" required aria-label="Tomar foto del material">
            <input type="text" name="caption" class="erp-input" placeholder="Descripción (opcional)" maxlength="255">
            <button class="lg-m-btn lg-m-btn--primary">Subir foto</button>
        </form>
        @error('photo') <div class="lg-err">{{ $message }}</div> @enderror
        <div class="lg-muted">{{ $pickup->evidences->count() }} foto(s) cargada(s)</div>
    </section>

    <section class="lg-m-card">
        <h2>2. Firma de quien recibe</h2>
        <form method="POST" action="{{ route('admin.pickups.sign', $pickup) }}" id="lg-sign-form">
            @csrf
            <input type="text" name="signed_by_name" class="erp-input" placeholder="Nombre de quien recibe" maxlength="160" value="{{ $pickup->signed_by_name }}">
            <div class="lg-m-sign-head"><strong style="font-size:12.5px;">Firma</strong><button type="button" id="lg-sign-clear" class="erp-btn erp-btn--link">Limpiar</button></div>
            <canvas id="lg-sign-canvas" class="lg-sign-canvas" aria-label="Área de firma"></canvas>
            <input type="hidden" name="signature_data" id="lg-sign-data">
            @error('signature_data') <div class="lg-err">{{ $message }}</div> @enderror
            <button class="lg-m-btn lg-m-btn--primary" style="margin-top:10px;">Guardar firma</button>
        </form>
        @if ($pickup->signature_data)
            <div class="lg-muted">Firma guardada el {{ $pickup->signed_at?->format('d/m/Y H:i') }}.</div>
        @endif
    </section>

    @if ($allowed)
        <section class="lg-m-card">
            <h2>3. Estatus</h2>
            @foreach (array_intersect(['en_ruta', 'recolectada', 'entregada'], $allowed) as $st)
                <form method="POST" action="{{ route('admin.pickups.update-status', $pickup) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="{{ $st }}">
                    <button class="lg-m-btn {{ $st === 'entregada' ? 'lg-m-btn--primary' : '' }}">
                        {{ $st === 'entregada' ? 'Marcar entregada' : ($st === 'recolectada' ? 'Marcar recolectada' : 'Salir en ruta') }}
                    </button>
                </form>
            @endforeach
            @if (in_array('incidencia', $allowed, true))
                <form method="POST" action="{{ route('admin.pickups.update-status', $pickup) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="incidencia">
                    <input type="text" name="note" class="erp-input" placeholder="Describe la incidencia" required>
                    <button class="lg-m-btn lg-m-btn--danger">Reportar incidencia</button>
                </form>
            @endif
            @error('status') <div class="lg-err">{{ $message }}</div> @enderror
        </section>
    @endif
</div>
@endsection

@push('scripts')
    @vite(['resources/js/admin/erp-logistics.js'])
@endpush
