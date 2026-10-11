@extends('admin.layouts.master')

@section('title', 'Envío ' . $shipment->guia . ' - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-logistics.css'])
@endpush

@section('content')
@php
    $meta = $statusMeta[$shipment->status] ?? [];
    $tone = match ($shipment->status) { 'entregado' => 'ok', 'incidencia' => 'bad', 'preparando' => 'neu', 'en_transito' => 'info', default => 'warn' };
    $flow = \App\Support\ShipmentStatus::FLOW;
    $steps = array_map(fn ($k) => $statusMeta[$k]['label'] ?? $k, $flow);
    $stepIdx = array_search($shipment->status, $flow, true);
    $canEdit = auth()->user()->isAdmin() || auth()->user()->hasPermission('shipments', 'edit');
@endphp
<div class="erp-page">
    <x-erp.object-header
        :title="'Envío ' . $shipment->guia"
        :eyebrow="'ENVÍO B2B' . ($shipment->documentNumber() ? ' · ' . $shipment->documentNumber() : '')"
        :subtitle="$shipment->recipientLabel()"
        :status="$meta['label'] ?? $shipment->status"
        :tone="$tone"
        :back="route('admin.shipments.index')"
        process="Envío"
        :steps="$stepIdx === false ? [] : $steps"
        :current="$stepIdx === false ? null : $stepIdx"
        :facts="[
            ['label' => 'Pedido / referencia', 'value' => $shipment->order_number_ref],
            ['label' => 'Paquetería', 'value' => $shipment->carrier?->name ?? 'Entrega propia'],
            ['label' => 'Fecha de envío', 'value' => $shipment->fecha_envio?->format('d/m/Y')],
            ['label' => 'Entrega estimada', 'value' => $shipment->eta?->format('d/m/Y')],
        ]">
        @if ($canEdit)
            <a href="{{ route('admin.shipments.edit', $shipment) }}" class="erp-btn">Editar</a>
        @endif
        @if (in_array($shipment->status, ['preparando', 'enviado'], true))
            <form method="POST" action="{{ route('admin.shipments.destroy', $shipment) }}" class="lg-inline-form" onsubmit="return confirm('¿Eliminar este envío?')">
                @csrf @method('DELETE')
                <button type="submit" class="erp-btn erp-btn--danger">Eliminar</button>
            </form>
        @endif
    </x-erp.object-header>

    @if (session('success')) <div class="lg-alert lg-alert--ok" role="status">{{ session('success') }}</div> @endif
    @if (session('error')) <div class="lg-alert" role="alert">{{ session('error') }}</div> @endif

    <div class="erp-split">
        <div>
            <x-erp.card title="Destinatario y direcciones">
                <x-erp.facts :items="[
                    ['label' => 'Persona que recibe', 'value' => $shipment->recipient_name ?: '—'],
                    ['label' => 'Empresa', 'value' => $shipment->recipient_company ?: '—'],
                    ['label' => 'Teléfono', 'value' => $shipment->recipient_phone ?: '—'],
                    ['label' => 'Correo', 'value' => $shipment->recipient_email ?: '—'],
                    ['label' => 'Dirección de salida', 'value' => $shipment->origin_address ?: '—'],
                    ['label' => 'Dirección de entrega', 'value' => $shipment->delivery_address ?: '—'],
                    ['label' => 'Bultos / peso / costo', 'value' => $shipment->bultos . ' · ' . ($shipment->peso ?? '—') . ' kg · ' . ($shipment->costo !== null ? '$' . number_format($shipment->costo, 2) : '—')],
                ]" />
                @if ($shipment->quote)
                    <p style="margin:14px 0 0;font-size:13px;"><span class="erp-fact-label">Cotización de referencia</span><br>
                        <a href="{{ route('admin.quotes.show', $shipment->quote) }}">{{ $shipment->quote->quote_number }}</a></p>
                @endif
                @if ($shipment->carrier?->tracking_url_template)
                    <p style="margin:12px 0 0;"><a href="{{ str_replace('{guia}', urlencode($shipment->guia), $shipment->carrier->tracking_url_template) }}" target="_blank" rel="noopener">Rastrear en la paquetería ↗</a></p>
                @endif
            </x-erp.card>

            @if ($allowed)
                <x-erp.card title="Cambiar estatus">
                    <form method="POST" action="{{ route('admin.shipments.update-status', $shipment) }}">
                        @csrf @method('PATCH')
                        <div class="erp-form-grid">
                            <label class="erp-field">Nuevo estatus
                                <select name="status" class="erp-select" required>
                                    @foreach ($allowed as $st)
                                        <option value="{{ $st }}">{{ $statusMeta[$st]['label'] ?? $st }}</option>
                                    @endforeach
                                </select>
                                @error('status') <span class="lg-err">{{ $message }}</span> @enderror
                            </label>
                            <label class="erp-field">Motivo (solo incidencia)
                                <select name="motivo" class="erp-select">
                                    <option value="">—</option>
                                    @foreach ($motivos as $k => $label)
                                        <option value="{{ $k }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('motivo') <span class="lg-err">{{ $message }}</span> @enderror
                            </label>
                            <label class="erp-field lg-span-2">Nota
                                <input type="text" name="note" class="erp-input" maxlength="2000">
                                @error('note') <span class="lg-err">{{ $message }}</span> @enderror
                            </label>
                        </div>
                        <div class="erp-form-actions"><button type="submit" class="erp-btn erp-btn--primary">Actualizar estatus</button></div>
                    </form>
                </x-erp.card>
            @endif

            <x-erp.card title="Línea de tiempo">
                <ul class="lg-timeline">
                    @forelse ($shipment->statusLogs as $log)
                        <li>
                            <div class="lg-t-title">{{ $statusMeta[$log->to_status]['label'] ?? $log->to_status }}
                                @if ($log->motivo) <span class="lg-muted">· {{ $motivos[$log->motivo] ?? $log->motivo }}</span> @endif
                            </div>
                            @if ($log->note) <div>{{ $log->note }}</div> @endif
                            <div class="lg-muted">{{ $log->created_at?->format('d/m/Y H:i') }} · {{ $log->changedBy?->first_name ?? 'Sistema' }}</div>
                        </li>
                    @empty
                        <li class="lg-muted">Sin movimientos.</li>
                    @endforelse
                </ul>
            </x-erp.card>
        </div>

        <aside>
            <x-erp.document-flow :document="$shipment" />
        </aside>
    </div>
</div>
@endsection
