@extends('admin.layouts.master')
@push('styles')
    @vite('resources/css/admin/pages/purchase-orders.css')
@endpush
@push('styles')
<style>
/* ── Teléfono (≤768px): todo acotado a .so-show; escritorio sin cambios ── */
@media (max-width: 768px) {
    .so-show { max-width: 100%; overflow-x: clip; }
    .so-show * { box-sizing: border-box; }
    .so-show .clients-manager-section { padding: 12px; }
    .so-show a, .so-show p, .so-show span, .so-show label { overflow-wrap: anywhere; }

    /* Encabezado: folio + estado */
    .so-show .so-head { flex-direction: column; align-items: stretch !important; gap: 10px !important; margin-bottom: 16px !important; }
    .so-show .so-head > div:first-child { min-width: 0; }
    .so-show .so-head h1 { font-size: 22px; line-height: 1.2; }
    .so-show .so-head .breadcrumb-clients-manager { line-height: 1.5; }
    .so-show .so-head .breadcrumb-clients-manager a { display: inline-block; padding: 2px 0; }
    .so-show .so-status { align-self: flex-start; white-space: normal; }

    /* Acciones: rejilla de 2 columnas, botones ≥44px */
    .so-show .so-actions { display: grid !important; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px !important; margin-bottom: 8px !important; }
    .so-show .so-actions > * { min-width: 0; }
    .so-show .so-actions > form { display: block !important; margin: 0; }
    .so-show .so-actions .button-secondary,
    .so-show .so-actions .button-primary {
        display: flex; align-items: center; justify-content: center; width: 100%; min-height: 44px;
        padding: 10px 12px; font-size: 14px; line-height: 1.25; text-align: center; white-space: normal;
    }
    .so-show .so-actions--danger { grid-template-columns: minmax(0, 1fr); margin-bottom: 16px !important; }
    .so-show .so-actions--danger #btnCancelOrder { color: #dc2626; border-color: #fecaca; }

    /* Cuenta por cobrar: campo + botón a lo ancho */
    .so-show .so-cxc { display: block !important; margin-bottom: 16px !important; }
    .so-show .so-cxc > a { display: flex; align-items: center; justify-content: center; width: 100%; min-height: 44px; text-align: center; white-space: normal; }
    .so-show .so-cxc-form { display: grid !important; grid-template-columns: minmax(0, 1fr); gap: 8px !important; padding: 12px; border: 1px solid #e5e7eb; border-radius: 12px; background: #fff; }
    .so-show .so-cxc-form label { display: flex; flex-direction: column; gap: 6px; font-weight: 600; }
    .so-show .so-cxc-form input[type="number"] { width: 100% !important; display: block !important; font-size: 16px; min-height: 44px; }
    .so-show .so-cxc-form button { width: 100%; min-height: 44px; font-size: 14px; }

    /* Caja de producción (parcial compartido) */
    .so-show .prd-box { padding: 12px; }
    .so-show .prd-box-link { display: inline-block; padding: 8px 0; }
    .so-show .prd-box-row { gap: 6px 10px; }
    .so-show .prd-box-row .prd-link { display: inline-block; padding: 4px 0; }

    /* Partidas → tarjetas */
    .so-show .so-items-title { display: block !important; margin: 4px 0 10px; font-size: 15px; font-weight: 700; color: #111827; }
    .so-show .table-container-clients-manager { border: 0; box-shadow: none; border-radius: 0; overflow: visible; }
    .so-show .clients-manager-table.brand-table { display: none; }
    .so-show .table-scroll { max-height: none; overflow: visible; }
    .so-show .so-items, .so-show .so-items tbody { display: block; width: 100%; table-layout: auto; }
    .so-show .so-items tbody tr.so-item {
        display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px 10px;
        grid-template-areas: "name name name" "sku sku sku" "ord del pen" "act act act";
        padding: 12px 14px; margin-bottom: 10px; border: 1px solid #e5e7eb !important; border-radius: 12px;
        background: #fff; box-shadow: 0 1px 2px rgba(16, 24, 40, .05);
    }
    .so-show .so-items tbody tr.so-item:last-child { margin-bottom: 0; }
    .so-show .so-items tbody tr.so-item > td { display: block; padding: 0 !important; border: 0; min-width: 0; font-size: 13px; overflow-wrap: anywhere; }
    .so-show .so-item td[data-label]::before {
        content: attr(data-label); display: block; margin-bottom: 3px;
        font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #9ca3af;
    }
    .so-show .so-i-name { grid-area: name; font-size: 15px !important; line-height: 1.3; }
    .so-show .so-i-sku { grid-area: sku; font-size: 12.5px !important; }
    .so-show .so-i-sku::before { display: inline !important; margin: 0 4px 0 0 !important; }
    .so-show .so-i-ord, .so-show .so-i-del, .so-show .so-i-pen { padding: 8px 10px !important; border-radius: 8px; background: #f9fafb; font-size: 14px !important; }
    .so-show .so-i-ord { grid-area: ord; }
    .so-show .so-i-del { grid-area: del; }
    .so-show .so-i-pen { grid-area: pen; }
    .so-show .so-i-act { grid-area: act; }
    .so-show .so-i-act .btn-register-delivery { width: 100%; min-height: 44px; font-size: 14px; display: flex; align-items: center; justify-content: center; }
    .so-show .so-i-act > span { display: block; text-align: center; padding: 6px 0; color: #16a34a !important; font-weight: 600; }
}

/* Modal "Registrar entrega": hoja inferior en teléfono */
@media (max-width: 640px) {
    .so-modal.del-confirm-overlay.active { align-items: flex-end !important; justify-content: stretch !important; }
    .so-modal .del-confirm-box {
        width: 100% !important; max-width: none !important; max-height: 90dvh; overflow-y: auto; overscroll-behavior: contain;
        border-radius: 16px 16px 0 0 !important; padding: 22px 16px 16px !important; padding-bottom: calc(16px + env(safe-area-inset-bottom)) !important;
        text-align: left; animation: soSheetIn .2s ease;
    }
    .so-modal .del-confirm-title { text-align: center; }
    .so-modal .del-confirm-desc { text-align: center; overflow-wrap: anywhere; }
    .so-modal .user-manager-form { display: block; }
    .so-modal label { display: block; margin-bottom: 6px; line-height: 1.35; }
    .so-modal #deliveryQuantity { width: 100%; font-size: 16px; min-height: 48px; }
    .so-modal .del-confirm-actions { flex-direction: column-reverse !important; gap: 10px !important; margin-top: 18px; }
    .so-modal .del-confirm-actions > button { width: 100% !important; min-height: 46px; font-size: 15px !important; }
    @keyframes soSheetIn { from { transform: translateY(24px); opacity: 0; } to { transform: none; opacity: 1; } }
}
/* Bloqueo de scroll del fondo con el modal abierto (solo teléfono) */
@media (max-width: 768px) {
    html:has(body.so-modal-open), body.so-modal-open { overflow: hidden; }
    body.so-modal-open .admin-main, body.so-modal-open .container.so-show { overflow: hidden !important; }
}
</style>
@endpush
@section('title')
    {{ $order->order_number }} - Pedidos - Admin
@endsection
@section('content')
<div class="container user-manager so-show">
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

    <div class="so-head" style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
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
        <span class="so-status" style="display:inline-block;padding:6px 16px;border-radius:20px;font-size:13px;font-weight:600;
            background:{{ $statusConfig['bg'] }};color:{{ $statusConfig['color'] }};">
            {{ $statusConfig['label'] }}
        </span>
    </div>

    @if ($order->status !== 'cancelado')
        <div class="so-actions" style="margin-bottom:12px;display:flex;gap:8px;flex-wrap:wrap;">
            @if (Route::has('admin.shipments.create'))
                <a href="{{ route('admin.shipments.create', ['sales_order' => $order->id]) }}" class="button-secondary size-adjustment">Crear envío</a>
            @endif
            @if (Route::has('admin.pickups.create'))
                <a href="{{ route('admin.pickups.create', ['sales_order' => $order->id]) }}" class="button-secondary size-adjustment">Crear recolección</a>
            @endif
            @if (Route::has('admin.mrp.from-sales-order') && (auth()->user()->isAdmin() || auth()->user()->hasPermission('mrp', 'create')))
                <form method="POST" action="{{ route('admin.mrp.from-sales-order', $order) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="button-secondary size-adjustment">Calcular materiales (MRP)</button>
                </form>
            @endif
        </div>
    @endif

    @if ($order->status !== 'cancelado' && $order->status !== 'entregado')
        <div class="so-actions so-actions--danger" style="margin-bottom:20px;">
            <button type="button" class="button-secondary size-adjustment" id="btnCancelOrder">Cancelar pedido</button>
        </div>
    @endif

    {{-- Finanzas: generar cuenta por cobrar desde el pedido --}}
    @if ($order->status !== 'cancelado' && Route::has('admin.receivables.from-sales-order') && auth()->user()->hasPermission('receivables', 'view'))
        @php
            $existingCxc = \App\Models\Receivable::where('source_type', 'sales_order')->where('source_id', $order->id)->where('status', '!=', 'cancelada')->first();
        @endphp
        <div class="so-cxc" style="margin-bottom:16px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
            @if ($existingCxc)
                <a href="{{ route('admin.receivables.show', $existingCxc->id) }}" class="button-secondary size-adjustment">Cuenta por cobrar {{ $existingCxc->number }}</a>
            @elseif (auth()->user()->hasPermission('receivables', 'create'))
                <form method="POST" action="{{ route('admin.receivables.from-sales-order', $order->id) }}" class="so-cxc-form" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    @csrf
                    <label style="font-size:13px;color:#374151;">Días de crédito
                        <input type="number" name="credit_days" min="0" max="3650" value="0" class="users-manager-input" style="width:90px;display:inline-block;">
                    </label>
                    <button type="submit" class="button-secondary size-adjustment">Generar cuenta por cobrar</button>
                </form>
            @endif
        </div>
    @endif

    @include('admin.production-orders._sales_order_box')

    <p class="so-items-title" style="display:none;">Partidas del pedido</p>
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
            <table class="clients-manager-table so-items">
                <tbody>
                @foreach ($order->items as $item)
                    <tr class="so-item" style="border-bottom:1px solid #f1f5f9;">
                        <td class="so-i-name" style="padding:14px 16px;font-weight:600;color:#111827;">{{ $item->product_name }}</td>
                        <td class="so-i-sku" data-label="SKU" style="padding:14px 16px;color:#6b7280;">{{ $item->product_sku ?? '—' }}</td>
                        <td class="so-i-ord" data-label="Pedido" style="padding:14px 16px;">{{ $item->quantity_ordered }} {{ $item->unit }}</td>
                        <td class="so-i-del" data-label="Entregado" style="padding:14px 16px;">{{ $item->quantity_delivered }} {{ $item->unit }}</td>
                        <td class="so-i-pen" data-label="Pendiente" style="padding:14px 16px;font-weight:600;{{ $item->pending > 0 ? 'color:#d97706;' : 'color:#16a34a;' }}">
                            {{ $item->pending }} {{ $item->unit }}
                        </td>
                        <td class="so-i-act" style="padding:14px 16px;">
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
<div id="deliveryModal" class="del-confirm-overlay so-modal" role="dialog" aria-modal="true" aria-labelledby="deliveryModalTitle">
    <div class="del-confirm-box">
        <h2 class="del-confirm-title" id="deliveryModalTitle">Registrar entrega</h2>
        <p class="del-confirm-desc" id="deliveryItemName">Producto</p>
        <div class="user-manager-form">
            <div>
                <label class="supliers-manager-slider-label">Cantidad a entregar (pendiente: <span id="deliveryPendingLabel">0</span>)</label>
                <input type="number" class="users-manager-input" id="deliveryQuantity" min="1" step="1" value="1" inputmode="numeric">
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

    // Bloquea el scroll del fondo mientras el modal está abierto (teléfono y escritorio).
    if (window.MutationObserver) {
        new MutationObserver(function () {
            document.body.classList.toggle('so-modal-open', deliveryModal.classList.contains('active'));
        }).observe(deliveryModal, { attributes: true, attributeFilter: ['class'] });
    }

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
