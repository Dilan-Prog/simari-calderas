@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-warehouse.css'])
@endpush
@push('scripts')
    @vite(['resources/js/admin/erp-warehouse.js'])
@endpush
@section('title')
    {{ $transfer->number }} - Traspasos - Admin
@endsection
@section('content')
@php
    $user = auth()->user();
    $canEdit = $user->hasPermission('warehouse-transfers', 'edit');
    $canDelete = $user->hasPermission('warehouse-transfers', 'delete');
    $enTransito = $transfer->status === 'en_transito';
    $steps = ['Borrador', 'En tránsito', 'Recibido'];
    $stepIdx = array_search($transfer->status, ['borrador', 'en_transito', 'recibido'], true);
    $tones = ['borrador' => 'neu', 'en_transito' => 'info', 'recibido' => 'ok', 'cancelado' => 'bad'];
    $hasDiff = $transfer->status === 'recibido' && $transfer->lines->contains(fn ($l) => $l->received_quantity !== $l->quantity);
@endphp
<div class="erp-page">
    <x-erp.object-header :title="$transfer->number" eyebrow="TRASPASO ENTRE ALMACENES" :subtitle="$transfer->fromWarehouse->name . ' → ' . $transfer->toWarehouse->name"
        :status="\App\Models\WarehouseTransfer::statusLabel($transfer->status)" :tone="$hasDiff ? 'warn' : ($tones[$transfer->status] ?? 'neu')"
        :back="route('admin.warehouse-transfers.index')"
        process="Traspaso" :steps="$transfer->status === 'cancelado' ? [] : $steps" :current="$stepIdx === false ? null : $stepIdx"
        :facts="[
            ['label' => 'Origen', 'value' => $transfer->fromWarehouse->name],
            ['label' => 'Destino', 'value' => $transfer->toWarehouse->name],
            ['label' => 'Solicitó', 'value' => trim(($transfer->requestedBy->first_name ?? '') . ' ' . ($transfer->requestedBy->last_name ?? '')) ?: '—'],
            ['label' => 'Enviado', 'value' => $transfer->shipped_at?->format('d/m/Y H:i')],
            ['label' => 'Recibido', 'value' => $transfer->received_at?->format('d/m/Y H:i')],
        ]">
        @if ($canEdit && $transfer->status === 'borrador')
            <a href="{{ route('admin.warehouse-transfers.edit', $transfer) }}" class="erp-btn">Editar</a>
            <form method="POST" action="{{ route('admin.warehouse-transfers.ship', $transfer) }}" class="wh-inline" data-confirm="¿Enviar el traspaso? Se descontará el inventario del almacén origen.">
                @csrf <button class="erp-btn erp-btn--primary" type="submit">Enviar</button>
            </form>
        @endif
        @if ($canEdit && in_array($transfer->status, ['borrador', 'en_transito'], true))
            <form method="POST" action="{{ route('admin.warehouse-transfers.cancel', $transfer) }}" class="wh-inline"
                  data-confirm="{{ $enTransito ? '¿Cancelar el traspaso? La mercancía regresará al almacén origen.' : '¿Cancelar el traspaso?' }}">
                @csrf <button class="erp-btn erp-btn--danger" type="submit">Cancelar traspaso</button>
            </form>
        @endif
        @if ($canDelete && $transfer->status === 'borrador')
            <form method="POST" action="{{ route('admin.warehouse-transfers.destroy', $transfer) }}" class="wh-inline" data-confirm="¿Eliminar este borrador?">
                @csrf @method('DELETE') <button class="erp-btn erp-btn--danger" type="submit">Eliminar</button>
            </form>
        @endif
    </x-erp.object-header>

    @if (session('error')) <div class="wh-alert wh-alert-error" role="alert">{{ session('error') }}</div> @endif
    @if (session('success')) <div class="wh-alert wh-alert-ok" role="status">{{ session('success') }}</div> @endif
    @if ($errors->any()) <div class="wh-alert wh-alert-error" role="alert">@foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach</div> @endif

    @if ($transfer->notes)
        <x-erp.card title="Notas"><p style="margin:0; white-space:pre-line;">{{ $transfer->notes }}</p></x-erp.card>
    @endif

    <form method="POST" action="{{ route('admin.warehouse-transfers.receive', $transfer) }}">
        @csrf
        <x-erp.card title="Material">
            <div class="erp-table-wrap">
                <table class="erp-table wh-stack">
                    <thead><tr>
                        <th>Producto</th><th>SKU</th><th class="is-right">Enviado</th>
                        @if ($enTransito || $transfer->status === 'recibido') <th class="is-right">Recibido</th> @endif
                        @if ($transfer->status === 'borrador') <th class="is-right">Disponible en origen</th> @endif
                    </tr></thead>
                    <tbody>
                        @foreach ($transfer->lines as $line)
                            <tr>
                                <td data-label="Producto" class="erp-cell-main">{{ $line->product->name }}</td>
                                <td data-label="SKU">{{ $line->product->sku }}</td>
                                <td data-label="Cantidad" class="is-num">{{ $line->quantity }}</td>
                                @if ($enTransito)
                                    <td data-label="Recibida" class="is-num">
                                        @if ($canEdit)
                                            <input type="number" min="0" max="{{ $line->quantity }}" class="wh-input-qty" name="received[{{ $line->id }}]" value="{{ old('received.' . $line->id, $line->quantity) }}" aria-label="Cantidad recibida de {{ $line->product->name }}">
                                        @else — @endif
                                    </td>
                                @elseif ($transfer->status === 'recibido')
                                    <td data-label="Recibida" class="is-num {{ $line->received_quantity !== $line->quantity ? 'wh-neg' : '' }}">{{ $line->received_quantity }}</td>
                                @endif
                                @if ($transfer->status === 'borrador')
                                    @php $avail = (int) ($balances[$line->product_id] ?? 0); @endphp
                                    <td data-label="Disponible en origen" class="is-num {{ $avail < $line->quantity ? 'wh-neg' : '' }}">{{ $avail }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-erp.card>

        @if ($enTransito && $canEdit)
            <x-erp.card title="Confirmar recepción">
                <div class="erp-field">
                    <label for="receipt_notes">Nota de recepción (obligatoria si hay diferencias)</label>
                    <textarea name="receipt_notes" id="receipt_notes" rows="2" class="erp-input" maxlength="1000">{{ old('receipt_notes') }}</textarea>
                </div>
                <p class="wh-alert wh-alert-info" style="margin:12px 0 0;">La cantidad no recibida no regresa al inventario: queda como faltante registrado en la nota.</p>
                <div class="erp-form-actions"><button type="submit" class="erp-btn erp-btn--primary">Confirmar recepción</button></div>
            </x-erp.card>
        @endif
    </form>

    @if ($transfer->status === 'recibido' && $transfer->receipt_notes)
        <x-erp.card title="Nota de recepción"><p style="margin:0; white-space:pre-line;">{{ $transfer->receipt_notes }}</p></x-erp.card>
    @endif
</div>
@endsection
