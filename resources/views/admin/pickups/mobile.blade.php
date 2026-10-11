@extends('admin.layouts.master')

@section('title', 'Captura ' . $pickup->number . ' - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-logistics.css'])
@endpush

@section('content')
<div class="lg-mobile">
    <a href="{{ route('admin.pickups.show', $pickup) }}" class="erp-objhead-back">← Detalle</a>
    <h1 style="font-size:22px;margin:6px 0;">{{ $pickup->number }}
        <span class="erp-chip erp-tone-{{ \App\Support\PickupStatus::tone($pickup->status) }}">{{ $pickup->statusLabel() }}</span>
    </h1>

    <div class="lg-card">
        <strong>{{ $pickup->counterpartLabel() }}</strong>
        <div>{{ $pickup->address }}</div>
        @if ($pickup->contact_phone)
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $pickup->contact_phone) }}">{{ $pickup->contact_phone }}</a>
        @endif
        <ul style="margin:10px 0 0;padding-left:18px;">
            @foreach ($pickup->items as $it)
                <li>{{ $it->description }} ({{ rtrim(rtrim(number_format($it->quantity, 2, '.', ''), '0'), '.') }} {{ $it->unit }})</li>
            @endforeach
        </ul>
    </div>

    <div class="lg-card lg-form">
        <h2>1. Foto de evidencia</h2>
        <form method="POST" action="{{ route('admin.pickups.evidences.store', $pickup) }}" enctype="multipart/form-data">
            @csrf
            <input type="file" name="photo" accept="image/*" capture="environment" required style="margin-bottom:8px;">
            <input type="text" name="caption" placeholder="Descripción (opcional)" maxlength="255" style="margin-bottom:8px;">
            <button class="lg-btn lg-btn-primary">Subir foto</button>
        </form>
        @error('photo') <div class="lg-err">{{ $message }}</div> @enderror
        <div class="lg-muted">{{ $pickup->evidences->count() }} foto(s) cargada(s)</div>
    </div>

    <div class="lg-card lg-form">
        <h2>2. Firma de quien recibe</h2>
        <form method="POST" action="{{ route('admin.pickups.sign', $pickup) }}" id="lg-sign-form">
            @csrf
            <input type="text" name="signed_by_name" placeholder="Nombre de quien firma" maxlength="160" style="margin-bottom:8px;" value="{{ $pickup->signed_by_name }}">
            <canvas id="lg-sign-canvas" class="lg-sign-canvas"></canvas>
            <input type="hidden" name="signature_data" id="lg-sign-data">
            @error('signature_data') <div class="lg-err">{{ $message }}</div> @enderror
            <div style="display:flex;gap:8px;margin-top:8px;">
                <button type="button" id="lg-sign-clear" class="lg-btn">Borrar</button>
                <button class="lg-btn lg-btn-primary">Guardar firma</button>
            </div>
        </form>
        @if ($pickup->signature_data)
            <div class="lg-muted">Firma guardada el {{ $pickup->signed_at?->format('d/m/Y H:i') }}.</div>
        @endif
    </div>

    @if ($allowed)
        <div class="lg-card lg-form">
            <h2>3. Estatus</h2>
            @foreach (array_intersect(['en_ruta', 'recolectada', 'entregada'], $allowed) as $st)
                <form method="POST" action="{{ route('admin.pickups.update-status', $pickup) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="{{ $st }}">
                    <button class="lg-btn {{ $st === 'entregada' ? 'lg-btn-primary' : '' }}">
                        {{ $st === 'entregada' ? 'Marcar entregada' : ($st === 'recolectada' ? 'Marcar recolectada' : 'Salir en ruta') }}
                    </button>
                </form>
            @endforeach
            @if (in_array('incidencia', $allowed, true))
                <form method="POST" action="{{ route('admin.pickups.update-status', $pickup) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="incidencia">
                    <input type="text" name="note" placeholder="Describe la incidencia" required style="margin-bottom:8px;">
                    <button class="lg-btn lg-btn-danger">Reportar incidencia</button>
                </form>
            @endif
            @error('status') <div class="lg-err">{{ $message }}</div> @enderror
        </div>
    @endif
</div>
@endsection

@push('scripts')
    @vite(['resources/js/admin/erp-logistics.js'])
@endpush
