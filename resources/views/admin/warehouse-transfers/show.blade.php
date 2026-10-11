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
    $steps = ['borrador' => 'Borrador', 'en_transito' => 'En tránsito', 'recibido' => 'Recibido'];
    $order = array_keys($steps);
    $currentIdx = array_search($transfer->status, $order, true);
@endphp
<div class="container user-manager">
<section class="clients-manager-section wh-page">

    @if (session('error')) <div class="wh-alert wh-alert-error">{{ session('error') }}</div> @endif

    <x-erp.object-header :title="$transfer->number" :subtitle="$transfer->fromWarehouse->name . ' → ' . $transfer->toWarehouse->name"
        :status="\App\Models\WarehouseTransfer::statusLabel($transfer->status)" :tone="\App\Models\WarehouseTransfer::statusTone($transfer->status)"
        :back="route('admin.warehouse-transfers.index')"
        :facts="[
            ['label' => 'Origen', 'value' => $transfer->fromWarehouse->name],
            ['label' => 'Destino', 'value' => $transfer->toWarehouse->name],
            ['label' => 'Solicitó', 'value' => trim(($transfer->requestedBy->first_name ?? '') . ' ' . ($transfer->requestedBy->last_name ?? '')) ?: '—'],
            ['label' => 'Enviado', 'value' => $transfer->shipped_at?->format('d/m/Y H:i')],
            ['label' => 'Recibido', 'value' => $transfer->received_at?->format('d/m/Y H:i')],
        ]">
        @if ($canEdit && $transfer->status === 'borrador')
            <a href="{{ route('admin.warehouse-transfers.edit', $transfer) }}" class="wh-btn">Editar</a>
            <form method="POST" action="{{ route('admin.warehouse-transfers.ship', $transfer) }}" data-confirm="¿Enviar el traspaso? Se descontará el inventario del almacén origen.">
                @csrf <button class="wh-btn wh-btn-primary" type="submit">Enviar</button>
            </form>
        @endif
        @if ($canEdit && in_array($transfer->status, ['borrador', 'en_transito'], true))
            <form method="POST" action="{{ route('admin.warehouse-transfers.cancel', $transfer) }}"
                  data-confirm="{{ $enTransito ? '¿Cancelar el traspaso? La mercancía regresará al almacén origen.' : '¿Cancelar el traspaso?' }}">
                @csrf <button class="wh-btn wh-btn-danger" type="submit">Cancelar traspaso</button>
            </form>
        @endif
        @if ($canDelete && $transfer->status === 'borrador')
            <form method="POST" action="{{ route('admin.warehouse-transfers.destroy', $transfer) }}" data-confirm="¿Eliminar este borrador?">
                @csrf @method('DELETE') <button class="wh-btn wh-btn-danger" type="submit">Eliminar</button>
            </form>
        @endif
    </x-erp.object-header>

    @if ($transfer->status !== 'cancelado')
        <x-erp.process-chevrons :steps="$steps" :current="$transfer->status" />
    @endif

    @if ($transfer->notes)
        <div class="wh-card"><h2>Notas</h2><p style="margin:0; white-space:pre-line;">{{ $transfer->notes }}</p></div>
    @endif

    <form method="POST" action="{{ route('admin.warehouse-transfers.receive', $transfer) }}">
        @csrf
        <div class="wh-card">
            <h2>Productos</h2>
            <div class="wh-table-wrap">
                <table class="wh-table wh-stack">
                    <thead><tr>
                        <th>Producto</th><th>SKU</th><th class="num">Cantidad</th>
                        @if ($enTransito || $transfer->status === 'recibido') <th class="num">Recibida</th> @endif
                        @if ($transfer->status === 'borrador') <th class="num">Disponible en origen</th> @endif
                    </tr></thead>
                    <tbody>
                        @foreach ($transfer->lines as $line)
                            <tr>
                                <td data-label="Producto">{{ $line->product->name }}</td>
                                <td data-label="SKU">{{ $line->product->sku }}</td>
                                <td data-label="Cantidad" class="num">{{ $line->quantity }}</td>
                                @if ($enTransito)
                                    <td data-label="Recibida" class="num">
                                        @if ($canEdit)
                                            <input type="number" min="0" max="{{ $line->quantity }}" class="wh-input-qty" name="received[{{ $line->id }}]" value="{{ old('received.' . $line->id, $line->quantity) }}">
                                        @else — @endif
                                    </td>
                                @elseif ($transfer->status === 'recibido')
                                    <td data-label="Recibida" class="num {{ $line->received_quantity !== $line->quantity ? 'wh-neg' : '' }}">{{ $line->received_quantity }}</td>
                                @endif
                                @if ($transfer->status === 'borrador')
                                    @php $avail = (int) ($balances[$line->product_id] ?? 0); @endphp
                                    <td data-label="Disponible en origen" class="num {{ $avail < $line->quantity ? 'wh-neg' : '' }}">{{ $avail }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if ($enTransito && $canEdit)
            <div class="wh-card">
                <div class="wh-field">
                    <label for="receipt_notes">Nota de recepción (obligatoria si hay diferencias)</label>
                    <textarea name="receipt_notes" id="receipt_notes" rows="2" class="users-manager-input" maxlength="1000">{{ old('receipt_notes') }}</textarea>
                </div>
                <p class="wh-alert wh-alert-info" style="margin-top:12px;">La cantidad no recibida no regresa al inventario: queda como faltante registrado en la nota.</p>
                <button type="submit" class="wh-btn wh-btn-primary">Confirmar recepción</button>
            </div>
        @endif
    </form>

    @if ($transfer->status === 'recibido' && $transfer->receipt_notes)
        <div class="wh-card"><h2>Nota de recepción</h2><p style="margin:0; white-space:pre-line;">{{ $transfer->receipt_notes }}</p></div>
    @endif
</section>
</div>
@endsection
