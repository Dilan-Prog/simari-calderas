@extends('admin.layouts.master')
@push('styles')
    @vite('resources/css/admin/pages/purchase-orders.css')
@endpush
@section('title')
    {{ $order->order_number }} - Pedidos - Admin
@endsection
@section('content')
<div class="container user-manager">
<section class="clients-manager-section">

    @php
        $statusConfig = match($order->status) {
            'pendiente'              => ['label' => 'Pendiente',              'color' => '#d97706', 'bg' => '#fffbeb'],
            'en_preparacion'         => ['label' => 'En preparación',          'color' => '#2563eb', 'bg' => '#eff6ff'],
            'parcialmente_entregado' => ['label' => 'Parcialmente entregado',  'color' => '#2563eb', 'bg' => '#eff6ff'],
            'entregado'              => ['label' => 'Entregado',               'color' => '#16a34a', 'bg' => '#f0fdf4'],
            'cancelado'              => ['label' => 'Cancelado',               'color' => '#dc2626', 'bg' => '#fef2f2'],
            default                  => ['label' => $order->status,            'color' => '#6b7280', 'bg' => '#f3f4f6'],
        };
        $customerName = $order->customer
            ? trim($order->customer->first_name . ' ' . $order->customer->last_name)
            : '—';
    @endphp

    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
        <div>
            <p class="breadcrumb-clients-manager" style="margin-bottom:4px;">
                Panel de Control &gt; <a href="{{ route('admin.sales-orders.index') }}">Pedidos</a> &gt; <strong>{{ $order->order_number }}</strong>
            </p>
            <h1 style="margin:0 0 4px;">{{ $order->order_number }}</h1>
            <p class="breadcrumb-clients-manager">
                Cliente: <strong>{{ $customerName }}</strong>
                @if ($order->quote)
                    &nbsp;·&nbsp; Cotización origen: <a href="{{ route('admin.quotes.show', $order->quote) }}">{{ $order->quote->quote_number }}</a>
                @endif
            </p>
        </div>
        <span style="display:inline-block;padding:6px 16px;border-radius:20px;font-size:13px;font-weight:600;
            background:{{ $statusConfig['bg'] }};color:{{ $statusConfig['color'] }};">
            {{ $statusConfig['label'] }}
        </span>
    </div>

    @if ($order->status !== 'cancelado' && $order->status !== 'entregado')
        <div style="margin-bottom:20px;">
            <button type="button" class="button-secondary size-adjustment" id="btnCancelOrder">Cancelar pedido</button>
        </div>
    @endif

    <main class="table-container-clients-manager">
        <table class="clients-manager-table brand-table">
            <thead>
                <tr>
                    <th>PRODUCTO</th>
                    <th>SKU</th>
                    <th>PEDIDO</th>
                    <th>ENTREGADO</th>
                    <th>PENDIENTE</th>
                    <th>ACCIONES</th>
                </tr>
            </thead>
        </table>
        <div class="table-scroll">
            <table class="clients-manager-table">
                <tbody>
                @foreach ($order->items as $item)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:14px 16px;font-weight:600;color:#111827;">{{ $item->product_name }}</td>
                        <td style="padding:14px 16px;color:#6b7280;">{{ $item->product_sku ?? '—' }}</td>
                        <td style="padding:14px 16px;">{{ $item->quantity_ordered }} {{ $item->unit }}</td>
                        <td style="padding:14px 16px;">{{ $item->quantity_delivered }} {{ $item->unit }}</td>
                        <td style="padding:14px 16px;font-weight:600;{{ $item->pending > 0 ? 'color:#d97706;' : 'color:#16a34a;' }}">
                            {{ $item->pending }} {{ $item->unit }}
                        </td>
                        <td style="padding:14px 16px;">
                            @if ($item->pending > 0 && $order->status !== 'cancelado')
                                <button type="button" class="button-secondary size-adjustment btn-register-delivery"
                                    data-item-id="{{ $item->id }}" data-pending="{{ $item->pending }}" data-name="{{ $item->product_name }}">
                                    Registrar entrega
                                </button>
                            @else
                                <span style="color:#9ca3af;font-size:13px;">Completo</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </main>

</section>

{{-- Registrar entrega modal --}}
<div id="deliveryModal" class="del-confirm-overlay">
    <div class="del-confirm-box">
        <h2 class="del-confirm-title">Registrar entrega</h2>
        <p class="del-confirm-desc" id="deliveryItemName">Producto</p>
        <div class="user-manager-form">
            <div>
                <label class="supliers-manager-slider-label">Cantidad a entregar (pendiente: <span id="deliveryPendingLabel">0</span>)</label>
                <input type="number" class="users-manager-input" id="deliveryQuantity" min="1" step="1" value="1">
            </div>
        </div>
        <div class="del-confirm-actions">
            <button type="button" class="button-secondary size-adjustment" id="deliveryCancel">Cancelar</button>
            <button type="button" class="button-primary size-adjustment" id="deliveryConfirm" style="background:#ff6213;border-color:#ff6213;">Confirmar entrega</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const deliveryModal = document.getElementById('deliveryModal');
    let deliveryItemId = null;
    let deliveryPending = 0;

    document.querySelectorAll('.btn-register-delivery').forEach(function (btn) {
        btn.addEventListener('click', function () {
            deliveryItemId = btn.dataset.itemId;
            deliveryPending = parseInt(btn.dataset.pending, 10);
            document.getElementById('deliveryItemName').textContent = btn.dataset.name;
            document.getElementById('deliveryPendingLabel').textContent = deliveryPending;
            const qtyInput = document.getElementById('deliveryQuantity');
            qtyInput.max = deliveryPending;
            qtyInput.value = deliveryPending;
            deliveryModal.classList.add('active');
        });
    });

    document.getElementById('deliveryCancel').addEventListener('click', () => deliveryModal.classList.remove('active'));
    deliveryModal.addEventListener('click', (e) => {
        if (e.target === deliveryModal) deliveryModal.classList.remove('active');
    });

    document.getElementById('deliveryConfirm').addEventListener('click', async function () {
        const qty = parseInt(document.getElementById('deliveryQuantity').value, 10);
        if (!qty || qty < 1 || qty > deliveryPending) {
            showCenterToast('Cantidad inválida.', 'error');
            return;
        }

        try {
            const response = await fetch(`{{ url('/admin/pedidos/' . $order->id . '/items') }}/${deliveryItemId}/entrega`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ _method: 'PATCH', quantity: qty }),
            });
            const data = await response.json();

            if (response.ok && data.success) {
                queueCenterToast(data.message ?? 'Entrega registrada.');
                setTimeout(() => window.location.reload(), 200);
            } else {
                showCenterToast(data.message ?? (data.errors ? Object.values(data.errors).flat()[0] : 'No se pudo registrar la entrega.'), 'error');
            }
        } catch (err) {
            showCenterToast('Error de conexión al registrar la entrega.', 'error');
        }
    });

    const btnCancelOrder = document.getElementById('btnCancelOrder');
    if (btnCancelOrder) {
        btnCancelOrder.addEventListener('click', async function () {
            if (!window.confirm('¿Cancelar este pedido?')) return;
            try {
                const response = await fetch(`{{ url('/admin/pedidos/' . $order->id . '/estado') }}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ _method: 'PATCH', status: 'cancelado' }),
                });
                if (response.ok) {
                    window.location.reload();
                } else {
                    showCenterToast('No se pudo cancelar el pedido.', 'error');
                }
            } catch (err) {
                showCenterToast('Error de conexión.', 'error');
            }
        });
    }
</script>
@endpush
@include('admin.components.center-toast')
</div>
@endsection
