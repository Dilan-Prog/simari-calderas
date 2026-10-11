@extends('admin.layouts.master')

@section('title', $pickup->number . ' - Recolecciones - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-logistics.css'])
@endpush

@section('content')
@php
    $statusMeta = \App\Support\PickupStatus::META;
    $open = \App\Support\PickupStatus::isOpen($pickup->status);
@endphp
<div class="lg-page">
    <x-erp.object-header
        :title="$pickup->number"
        :subtitle="$pickup->counterpartLabel()"
        :status="$pickup->statusLabel()"
        :tone="\App\Support\PickupStatus::tone($pickup->status)"
        :back="route('admin.pickups.index')"
        :facts="[
            ['label' => 'Tipo', 'value' => \App\Support\PickupStatus::TYPES[$pickup->type] ?? $pickup->type],
            ['label' => 'Programada', 'value' => trim(($pickup->scheduled_date?->format('d/m/Y') ?? '') . ' ' . ($pickup->time_window ?? ''))],
            ['label' => 'Chofer', 'value' => $pickup->driver ? trim($pickup->driver->first_name . ' ' . $pickup->driver->last_name) : null],
            ['label' => 'Paquetería', 'value' => $pickup->carrier?->name],
        ]">
        <a href="{{ route('admin.pickups.mobile', $pickup) }}" class="lg-btn">Captura móvil</a>
        @if ($open)
            <a href="{{ route('admin.pickups.edit', $pickup) }}" class="lg-btn">Editar</a>
        @endif
        @if (in_array($pickup->status, ['programada', 'cancelada'], true))
            <form method="POST" action="{{ route('admin.pickups.destroy', $pickup) }}" onsubmit="return confirm('¿Eliminar esta recolección?')">
                @csrf @method('DELETE')
                <button class="lg-btn lg-btn-danger">Eliminar</button>
            </form>
        @endif
    </x-erp.object-header>

    <x-erp.document-flow :document="$pickup" />

    <div class="lg-card">
        <h2>Recolección</h2>
        <div class="lg-form-grid">
            <div><div class="lg-muted">Contacto</div>{{ $pickup->contact_name ?: '—' }} {{ $pickup->contact_phone }}</div>
            <div><div class="lg-muted">Dirección</div>{{ $pickup->address ?: '—' }}</div>
            <div><div class="lg-muted">Notas</div>{{ $pickup->notes ?: '—' }}</div>
            @if ($pickup->incident_note)
                <div><div class="lg-muted">Incidencia</div><span class="erp-tone-bad">{{ $pickup->incident_note }}</span></div>
            @endif
        </div>
    </div>

    <div class="lg-card">
        <h2>Partidas</h2>
        <table class="lg-table">
            <thead><tr><th>Descripción</th><th>Cantidad</th><th>Unidad</th><th>Pedido</th></tr></thead>
            <tbody>
            @forelse ($pickup->items as $it)
                <tr>
                    <td>{{ $it->description }}</td>
                    <td>{{ rtrim(rtrim(number_format($it->quantity, 2, '.', ''), '0'), '.') }}</td>
                    <td>{{ $it->unit ?: '—' }}</td>
                    <td>@if ($it->salesOrder)<a href="{{ route('admin.sales-orders.show', $it->salesOrder) }}">{{ $it->salesOrder->order_number }}</a>@else — @endif</td>
                </tr>
            @empty
                <tr><td colspan="4" class="lg-muted">Sin partidas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($allowed)
        <div class="lg-card">
            <h2>Cambiar estatus</h2>
            <form method="POST" action="{{ route('admin.pickups.update-status', $pickup) }}" class="lg-form">
                @csrf @method('PATCH')
                <div class="lg-form-grid">
                    <div>
                        <label>Nuevo estatus</label>
                        <select name="status" required>
                            @foreach ($allowed as $st)
                                <option value="{{ $st }}">{{ $statusMeta[$st]['label'] }}</option>
                            @endforeach
                        </select>
                        @error('status') <div class="lg-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="span-2">
                        <label>Nota (obligatoria para incidencia)</label>
                        <input type="text" name="note" maxlength="2000">
                    </div>
                </div>
                <div class="lg-actions" style="margin-top:12px;"><button type="submit" class="lg-btn lg-btn-primary">Actualizar</button></div>
            </form>
        </div>
    @endif

    <div class="lg-card">
        <h2>Evidencia</h2>
        @if ($pickup->evidences->isNotEmpty())
            <div class="lg-evid">
                @foreach ($pickup->evidences as $ev)
                    <figure>
                        <a href="{{ $ev->url() }}" target="_blank" rel="noopener"><img src="{{ $ev->url() }}" alt="{{ $ev->caption }}" loading="lazy"></a>
                        <figcaption>{{ $ev->caption ?: 'Sin descripción' }}
                            <form method="POST" action="{{ route('admin.pickups.evidences.destroy', [$pickup, $ev]) }}" style="display:inline;" onsubmit="return confirm('¿Eliminar foto?')">
                                @csrf @method('DELETE') <button class="lg-muted" style="background:none;border:0;cursor:pointer;color:#b91c1c;">Quitar</button>
                            </form>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        @else
            <p class="lg-muted">Aún no hay fotos.</p>
        @endif

        <form method="POST" action="{{ route('admin.pickups.evidences.store', $pickup) }}" enctype="multipart/form-data" class="lg-form" style="margin-top:14px;">
            @csrf
            <div class="lg-form-grid">
                <div><label>Foto</label><input type="file" name="photo" accept="image/*" required></div>
                <div><label>Descripción</label><input type="text" name="caption" maxlength="255"></div>
            </div>
            @error('photo') <div class="lg-err">{{ $message }}</div> @enderror
            <div class="lg-actions" style="margin-top:10px;"><button class="lg-btn">Subir foto</button></div>
        </form>

        @if ($pickup->signature_data)
            <div style="margin-top:18px;">
                <div class="lg-muted">Firma de {{ $pickup->signed_by_name ?: 'quien recibe' }} · {{ $pickup->signed_at?->format('d/m/Y H:i') }}</div>
                <img class="lg-sign-img" src="{{ $pickup->signature_data }}" alt="Firma">
            </div>
        @endif
    </div>

    <div class="lg-card">
        <h2>Bitácora</h2>
        <ul class="lg-timeline">
            @foreach ($pickup->statusLogs as $log)
                <li>
                    <div class="lg-t-title">{{ \App\Support\PickupStatus::label($log->to_status) }}</div>
                    @if ($log->note) <div>{{ $log->note }}</div> @endif
                    <div class="lg-muted">{{ $log->created_at?->format('d/m/Y H:i') }} · {{ $log->changedBy?->first_name ?? 'Sistema' }}</div>
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endsection
