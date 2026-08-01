@extends('admin.layouts.master')
@section('title')
    Planeación de Químicos - Admin
@endsection
@section('content')
<div class="container user-manager">
<section class="clients-manager-section">

    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
        <div>
            <p class="breadcrumb-clients-manager" style="margin-bottom:4px;">
                Panel de Control &gt; <strong>Planeación de Químicos</strong>
            </p>
            <h1 style="margin:0 0 4px;">Planeación de Químicos</h1>
            <p class="breadcrumb-clients-manager">Entregado, pendiente, proyección e inventario por cliente y producto</p>
        </div>
        <a href="{{ route('admin.chemical-planning.export') }}" class="button-primary size-adjustment"
            style="background:#ff6213;border-color:#ff6213;white-space:nowrap;">
            Exportar a Excel
        </a>
    </div>

    <main class="table-container-clients-manager">
        <table class="clients-manager-table brand-table">
            <thead>
                <tr>
                    <th>CLIENTE</th>
                    <th>PRODUCTO</th>
                    <th>ENTREGADO</th>
                    <th>POR ENTREGAR</th>
                    <th>PROYECCIÓN</th>
                    <th>INVENTARIO</th>
                </tr>
            </thead>
        </table>
        <div class="table-scroll">
            <table class="clients-manager-table">
                <tbody>
                @forelse ($rows as $row)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:14px 16px;font-weight:600;color:#111827;">{{ $row['customer_name'] }}</td>
                        <td style="padding:14px 16px;">{{ $row['product_name'] }}</td>
                        <td style="padding:14px 16px;">{{ $row['delivered'] }} {{ $row['stock_unit'] }}</td>
                        <td style="padding:14px 16px;{{ $row['pending'] > 0 ? 'color:#d97706;font-weight:600;' : '' }}">{{ $row['pending'] }} {{ $row['stock_unit'] }}</td>
                        <td style="padding:14px 16px;">
                            <input type="number" class="users-manager-input projection-input" style="max-width:110px;"
                                min="0" step="0.01"
                                value="{{ $row['projected_quantity'] }}"
                                data-customer-id="{{ $row['customer_id'] }}"
                                data-product-id="{{ $row['product_id'] }}">
                        </td>
                        <td style="padding:14px 16px;font-weight:600;">{{ $row['stock'] }} {{ $row['stock_unit'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:40px;color:#9ca3af;">
                            No hay datos de pedidos todavía para planear. La tabla se llena a partir de los Pedidos generados desde cotizaciones aceptadas.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </main>

</section>

@push('scripts')
<script>
    let projectionDebounce = null;

    document.querySelectorAll('.projection-input').forEach(function (input) {
        input.addEventListener('input', function () {
            clearTimeout(projectionDebounce);
            const el = this;
            projectionDebounce = setTimeout(async function () {
                try {
                    const response = await fetch('{{ route('admin.chemical-planning.projection.update') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            customer_id: el.dataset.customerId,
                            product_id: el.dataset.productId,
                            projected_quantity: el.value || 0,
                        }),
                    });
                    if (response.ok) {
                        showCenterToast('Proyección guardada.');
                    } else {
                        showCenterToast('No se pudo guardar la proyección.', 'error');
                    }
                } catch (err) {
                    showCenterToast('Error de conexión al guardar la proyección.', 'error');
                }
            }, 500);
        });
    });
</script>
@endpush
@include('admin.components.center-toast')
</div>
@endsection
