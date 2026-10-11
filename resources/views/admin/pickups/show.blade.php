@extends('admin.layouts.master')

@section('title', $pickup->number . ' - Recolecciones - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-logistics.css'])
@endpush

@section('content')
@php
    $statusMeta = \App\Support\PickupStatus::META;
    $open = \App\Support\PickupStatus::isOpen($pickup->status);
    $steps = ['Programada', 'En ruta', 'Recolectada', 'Entregada'];
    $stepIdx = match ($pickup->status) { 'programada' => 0, 'en_ruta' => 1, 'recolectada' => 2, 'entregada' => 3, default => null };
    $qty = fn ($n) => rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    $driverName = $pickup->driver ? trim($pickup->driver->first_name . ' ' . $pickup->driver->last_name) : null;
    $generalFacts = [
        ['label' => 'Contacto', 'value' => trim(($pickup->contact_name ?: '') . ' ' . ($pickup->contact_phone ?: '')) ?: '—'],
        ['label' => 'Dirección de recolección', 'value' => $pickup->address ?: '—'],
        ['label' => 'Notas', 'value' => $pickup->notes ?: '—'],
    ];
    if ($pickup->incident_note) {
        $generalFacts[] = ['label' => 'Incidencia', 'value' => $pickup->incident_note];
    }
@endphp
<div class="erp-page">
    <x-erp.object-header
        :title="$pickup->number"
        :eyebrow="'RECOLECCIÓN · ' . (\App\Support\PickupStatus::TYPES[$pickup->type] ?? $pickup->type)"
        :subtitle="$pickup->counterpartLabel()"
        :status="$pickup->statusLabel()"
        :tone="\App\Support\PickupStatus::chipTone($pickup->status)"
        :back="route('admin.pickups.index')"
        process="Recolección"
        :steps="$stepIdx === null ? [] : $steps"
        :current="$stepIdx"
        :facts="[
            ['label' => 'Tipo', 'value' => \App\Support\PickupStatus::TYPES[$pickup->type] ?? $pickup->type],
            ['label' => 'Programada', 'value' => trim(($pickup->scheduled_date?->format('d/m/Y') ?? '') . ' ' . ($pickup->time_window ?? ''))],
            ['label' => 'Chofer', 'value' => $driverName],
            ['label' => 'Paquetería', 'value' => $pickup->carrier?->name],
        ]">
        <a href="{{ route('admin.pickups.mobile', $pickup) }}" class="erp-btn">Captura móvil</a>
        @if ($open)
            <a href="{{ route('admin.pickups.edit', $pickup) }}" class="erp-btn">Editar</a>
        @endif
        @if (in_array($pickup->status, ['programada', 'cancelada'], true))
            <form method="POST" action="{{ route('admin.pickups.destroy', $pickup) }}" class="lg-inline-form" onsubmit="return confirm('¿Eliminar esta recolección?')">
                @csrf @method('DELETE')
                <button class="erp-btn erp-btn--danger">Eliminar</button>
            </form>
        @endif
    </x-erp.object-header>

    @if (session('success')) <div class="lg-alert lg-alert--ok" role="status">{{ session('success') }}</div> @endif
    @if (session('error')) <div class="lg-alert" role="alert">{{ session('error') }}</div> @endif

    <div class="erp-split">
        <div>
            @if ($allowed)
                <x-erp.card title="Cambiar estatus" sub="Registra el avance de la recolección.">
                    <form method="POST" action="{{ route('admin.pickups.update-status', $pickup) }}">
                        @csrf @method('PATCH')
                        <div class="erp-form-grid">
                            <label class="erp-field">Nuevo estatus
                                <select name="status" class="erp-select" required>
                                    @foreach ($allowed as $st)
                                        <option value="{{ $st }}">{{ $statusMeta[$st]['label'] }}</option>
                                    @endforeach
                                </select>
                                @error('status') <span class="lg-err">{{ $message }}</span> @enderror
                            </label>
                            <label class="erp-field lg-span-2">Nota (obligatoria para incidencia)
                                <input type="text" name="note" class="erp-input" maxlength="2000">
                            </label>
                        </div>
                        <div class="erp-form-actions"><button type="submit" class="erp-btn erp-btn--primary">Actualizar</button></div>
                    </form>
                </x-erp.card>
            @endif

            <section class="erp-card" data-erp-tabs>
                <x-erp.tab-nav :tabs="['general' => 'General', 'partidas' => 'Partidas (' . $pickup->items->count() . ')', 'evidencia' => 'Evidencia (' . $pickup->evidences->count() . ')', 'bitacora' => 'Bitácora']" active="general" />

                <div class="erp-tabpanel" data-panel="general">
                    <x-erp.facts :items="$generalFacts" />
                </div>

                <div class="erp-tabpanel" data-panel="partidas">
                    <div class="erp-table-wrap">
                        <table class="erp-table">
                            <thead><tr><th>Descripción</th><th class="is-right">Cantidad</th><th>Unidad</th><th>Pedido</th></tr></thead>
                            <tbody>
                            @forelse ($pickup->items as $it)
                                <tr>
                                    <td class="erp-cell-main">{{ $it->description }}</td>
                                    <td class="is-num">{{ $qty($it->quantity) }}</td>
                                    <td>{{ $it->unit ?: '—' }}</td>
                                    <td>@if ($it->salesOrder)<a href="{{ route('admin.sales-orders.show', $it->salesOrder) }}">{{ $it->salesOrder->order_number }}</a>@else — @endif</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="lg-muted">Sin partidas.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="erp-tabpanel" data-panel="evidencia">
                    <div class="lg-evid-status">
                        <div class="lg-evid-slot"><b>Foto</b>@if ($pickup->evidences->count())<x-erp.chip tone="ok" :label="$pickup->evidences->count() . ' cargada(s)'" />@else<x-erp.chip tone="neu" label="Pendiente" />@endif</div>
                        <div class="lg-evid-slot"><b>Firma</b>@if ($pickup->signature_data)<x-erp.chip tone="ok" label="Firmada" />@else<x-erp.chip tone="neu" label="Pendiente" />@endif</div>
                    </div>

                    @if ($pickup->evidences->isNotEmpty())
                        <div class="lg-evid">
                            @foreach ($pickup->evidences as $ev)
                                <figure>
                                    <a href="{{ $ev->url() }}" target="_blank" rel="noopener"><img src="{{ $ev->url() }}" alt="{{ $ev->caption ?: 'Evidencia de recolección' }}" loading="lazy"></a>
                                    <figcaption><span>{{ $ev->caption ?: 'Sin descripción' }}</span>
                                        <form method="POST" action="{{ route('admin.pickups.evidences.destroy', [$pickup, $ev]) }}" class="lg-inline-form" onsubmit="return confirm('¿Eliminar foto?')">
                                            @csrf @method('DELETE') <button class="lg-link-danger">Quitar</button>
                                        </form>
                                    </figcaption>
                                </figure>
                            @endforeach
                        </div>
                    @else
                        <p class="lg-muted">Aún no hay fotos.</p>
                    @endif

                    <form method="POST" action="{{ route('admin.pickups.evidences.store', $pickup) }}" enctype="multipart/form-data" style="margin-top:16px;">
                        @csrf
                        <div class="erp-form-grid">
                            <label class="erp-field">Foto
                                <input type="file" name="photo" accept="image/*" class="erp-input" required>
                            </label>
                            <label class="erp-field">Descripción
                                <input type="text" name="caption" class="erp-input" maxlength="255">
                            </label>
                        </div>
                        @error('photo') <div class="lg-err">{{ $message }}</div> @enderror
                        <div style="margin-top:10px;"><button class="erp-btn">Subir foto</button></div>
                    </form>

                    @if ($pickup->signature_data)
                        <div style="margin-top:18px;">
                            <div class="erp-fact-label">Firma de {{ $pickup->signed_by_name ?: 'quien recibe' }} · {{ $pickup->signed_at?->format('d/m/Y H:i') }}</div>
                            <img class="lg-sign-img" src="{{ $pickup->signature_data }}" alt="Firma de quien recibe">
                        </div>
                    @endif
                </div>

                <div class="erp-tabpanel" data-panel="bitacora">
                    <ul class="lg-timeline">
                        @forelse ($pickup->statusLogs as $log)
                            <li>
                                <div class="lg-t-title">{{ \App\Support\PickupStatus::label($log->to_status) }}</div>
                                @if ($log->note) <div>{{ $log->note }}</div> @endif
                                <div class="lg-muted">{{ $log->created_at?->format('d/m/Y H:i') }} · {{ $log->changedBy?->first_name ?? 'Sistema' }}</div>
                            </li>
                        @empty
                            <li class="lg-muted">Sin movimientos.</li>
                        @endforelse
                    </ul>
                </div>
            </section>
        </div>

        <aside>
            <x-erp.document-flow :document="$pickup" />
        </aside>
    </div>
</div>
@endsection
