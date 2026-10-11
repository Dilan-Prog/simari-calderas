@extends('admin.layouts.master')

@section('title', 'Envío ' . $shipment->guia . ' - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-logistics.css'])
@endpush

@section('content')
@php
    $meta = $statusMeta[$shipment->status] ?? [];
    $tone = match ($shipment->status) { 'entregado' => 'ok', 'incidencia' => 'bad', 'preparando' => 'neutral', default => 'warn' };
@endphp
<div class="lg-page">
    <x-erp.object-header
        :title="'Envío ' . $shipment->guia"
        :subtitle="$shipment->recipientLabel()"
        :status="$meta['label'] ?? $shipment->status"
        :tone="$tone"
        :back="route('admin.shipments.index')"
        :facts="[
            ['label' => 'Pedido / referencia', 'value' => $shipment->order_number_ref],
            ['label' => 'Paquetería', 'value' => $shipment->carrier?->name ?? 'Entrega propia'],
            ['label' => 'Fecha de envío', 'value' => $shipment->fecha_envio?->format('d/m/Y')],
            ['label' => 'Entrega estimada', 'value' => $shipment->eta?->format('d/m/Y')],
        ]">
        @if (auth()->user()->isAdmin() || auth()->user()->hasPermission('shipments', 'edit'))
            <a href="{{ route('admin.shipments.edit', $shipment) }}" class="lg-btn">Editar</a>
        @endif
        @if (in_array($shipment->status, ['preparando', 'enviado'], true))
            <form method="POST" action="{{ route('admin.shipments.destroy', $shipment) }}" onsubmit="return confirm('¿Eliminar este envío?')">
                @csrf @method('DELETE')
                <button type="submit" class="lg-btn lg-btn-danger">Eliminar</button>
            </form>
        @endif
    </x-erp.object-header>

    <x-erp.document-flow :document="$shipment" />

    <div class="lg-card">
        <h2>Destinatario y direcciones</h2>
        <div class="lg-form-grid">
            <div><div class="lg-muted">Persona que recibe</div>{{ $shipment->recipient_name ?: '—' }}</div>
            <div><div class="lg-muted">Empresa</div>{{ $shipment->recipient_company ?: '—' }}</div>
            <div><div class="lg-muted">Teléfono</div>{{ $shipment->recipient_phone ?: '—' }}</div>
            <div><div class="lg-muted">Correo</div>{{ $shipment->recipient_email ?: '—' }}</div>
            <div><div class="lg-muted">Dirección de salida</div>{{ $shipment->origin_address ?: '—' }}</div>
            <div><div class="lg-muted">Dirección de entrega</div>{{ $shipment->delivery_address ?: '—' }}</div>
            <div><div class="lg-muted">Cotización de referencia</div>
                @if ($shipment->quote)
                    <a href="{{ route('admin.quotes.show', $shipment->quote) }}">{{ $shipment->quote->quote_number }}</a>
                @else — @endif
            </div>
            <div><div class="lg-muted">Bultos / peso / costo</div>{{ $shipment->bultos }} · {{ $shipment->peso ?? '—' }} kg · {{ $shipment->costo !== null ? '$' . number_format($shipment->costo, 2) : '—' }}</div>
        </div>
        @if ($shipment->carrier?->tracking_url_template)
            <p style="margin:12px 0 0;"><a href="{{ str_replace('{guia}', urlencode($shipment->guia), $shipment->carrier->tracking_url_template) }}" target="_blank" rel="noopener">Rastrear en la paquetería ↗</a></p>
        @endif
    </div>

    @if ($allowed)
        <div class="lg-card">
            <h2>Cambiar estatus</h2>
            <form method="POST" action="{{ route('admin.shipments.update-status', $shipment) }}" class="lg-form">
                @csrf @method('PATCH')
                <div class="lg-form-grid">
                    <div>
                        <label>Nuevo estatus</label>
                        <select name="status" required>
                            @foreach ($allowed as $st)
                                <option value="{{ $st }}">{{ $statusMeta[$st]['label'] ?? $st }}</option>
                            @endforeach
                        </select>
                        @error('status') <div class="lg-err">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label>Motivo (solo incidencia)</label>
                        <select name="motivo">
                            <option value="">—</option>
                            @foreach ($motivos as $k => $label)
                                <option value="{{ $k }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('motivo') <div class="lg-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="span-2">
                        <label>Nota</label>
                        <input type="text" name="note" maxlength="2000">
                        @error('note') <div class="lg-err">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="lg-actions" style="margin-top:12px;"><button type="submit" class="lg-btn lg-btn-primary">Actualizar estatus</button></div>
            </form>
        </div>
    @endif

    <div class="lg-card">
        <h2>Línea de tiempo</h2>
        <ul class="lg-timeline">
            @foreach ($shipment->statusLogs as $log)
                <li>
                    <div class="lg-t-title">{{ $statusMeta[$log->to_status]['label'] ?? $log->to_status }}
                        @if ($log->motivo) <span class="lg-muted">· {{ $motivos[$log->motivo] ?? $log->motivo }}</span> @endif
                    </div>
                    @if ($log->note) <div>{{ $log->note }}</div> @endif
                    <div class="lg-muted">{{ $log->created_at?->format('d/m/Y H:i') }} · {{ $log->changedBy?->first_name ?? 'Sistema' }}</div>
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endsection
