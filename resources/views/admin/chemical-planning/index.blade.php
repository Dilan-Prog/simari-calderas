@extends('admin.layouts.master')
@section('title')
    Planeación de Químicos - Admin
@endsection
@push('styles')
<style>
/* Planeación de Químicos — adaptación a teléfono. Todo scoped bajo
   .chem-planning-page y dentro de @media (max-width:768px): escritorio intacto.
   En teléfono cada fila (cliente × producto) se muestra como tarjeta con sus
   cifras etiquetadas y el input de proyección a ancho completo. */
@media (max-width: 768px) {
    .chem-planning-page { padding-left: 0; padding-right: 0; max-width: 100%; overflow-x: hidden; }
    .chem-planning-page .clients-manager-section { padding: 16px; }
    .chem-planning-page .cp-header { flex-direction: column; align-items: stretch !important; margin-bottom: 16px !important; }
    .chem-planning-page .cp-header h1 { font-size: 22px; line-height: 1.25; }
    .chem-planning-page .cp-actions { flex-direction: column; align-items: stretch !important; width: 100%; }
    .chem-planning-page .cp-actions .col-vis-wrap { position: relative; width: 100%; }
    .chem-planning-page .cp-actions .col-vis-btn,
    .chem-planning-page .cp-actions .cp-export-btn {
        width: 100%; min-height: 44px; font-size: 16px; box-sizing: border-box; white-space: normal !important;
    }
    .chem-planning-page .cp-actions .col-vis-menu {
        left: 0; right: 0; top: calc(100% + 6px); min-width: 0 !important; max-width: 100%; box-sizing: border-box;
    }
    .chem-planning-page .cp-actions .col-vis-item { min-height: 44px; font-size: 16px; }
    .chem-planning-page .cp-actions .col-vis-checkbox { width: 20px; height: 20px; }

    /* Tabla → tarjetas */
    .chem-planning-page .cp-table-wrap { border: 0; box-shadow: none; border-radius: 0; overflow: visible; }
    .chem-planning-page .cp-head-table { display: none; }
    .chem-planning-page .cp-table-wrap .table-scroll { max-height: none; overflow: visible; }
    .chem-planning-page .cp-body-table,
    .chem-planning-page .cp-body-table tbody { display: block; width: 100%; table-layout: auto; }
    .chem-planning-page .cp-body-table tr.cp-row {
        display: grid; grid-template-columns: 1fr 1fr; gap: 10px 12px;
        background: #fff; border: 1px solid #e5e7eb !important; border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06); padding: 14px; margin-bottom: 12px;
    }
    .chem-planning-page .cp-body-table tr.cp-row > td {
        display: block; padding: 0 !important; border: 0; min-width: 0;
        font-size: 15px; overflow-wrap: anywhere; word-break: break-word; white-space: normal;
    }
    .chem-planning-page .cp-body-table tr.cp-row > td::before {
        content: attr(data-label); display: block; font-size: 11px; font-weight: 600;
        letter-spacing: .04em; text-transform: uppercase; color: #6b7280; margin-bottom: 2px;
    }
    .chem-planning-page .cp-body-table tr.cp-row > td.cp-customer { grid-column: 1 / -1; font-size: 16px; }
    .chem-planning-page .cp-body-table tr.cp-row > td.cp-customer::before { display: none; }
    .chem-planning-page .cp-body-table tr.cp-row > td.cp-product {
        grid-column: 1 / -1; padding-bottom: 10px !important; border-bottom: 1px solid #f1f5f9;
    }
    .chem-planning-page .cp-body-table tr.cp-row > td.cp-projection { grid-column: 1 / -1; }
    .chem-planning-page .cp-body-table .projection-input {
        width: 100%; max-width: none !important; min-height: 44px; font-size: 16px; box-sizing: border-box;
    }
    .chem-planning-page .cp-body-table td.col-hidden { display: none !important; }
    .chem-planning-page .cp-body-table tr.cp-empty { display: block; }
    .chem-planning-page .cp-body-table tr.cp-empty > td { display: block; padding: 32px 16px !important; }

    /* Modal de formato de descarga → hoja inferior con bloqueo de scroll */
    .chem-planning-page #downloadFormatModal.active { align-items: flex-end; }
    .chem-planning-page #downloadFormatModal .del-confirm-box {
        width: 100%; max-width: 100%; max-height: 90vh; max-height: 90dvh; overflow-y: auto;
        border-radius: 16px 16px 0 0; box-sizing: border-box;
        padding-bottom: calc(20px + env(safe-area-inset-bottom));
    }
    .chem-planning-page #downloadFormatModal .download-format-btn,
    .chem-planning-page #downloadFormatModal .del-confirm-actions button { min-height: 44px; width: 100%; }
    .chem-planning-page #downloadFormatModal .download-format-close-btn { min-width: 44px; min-height: 44px; }
    html.cp-modal-open, html.cp-modal-open body { overflow: hidden; }
}
</style>
@endpush
@section('content')
<div class="container user-manager chem-planning-page">
<section class="clients-manager-section">

    <div class="cp-header" style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
        <div>
            <p class="breadcrumb-clients-manager" style="margin-bottom:4px;">
                Panel de Control &gt; <strong>Planeación de Químicos</strong>
            </p>
            <h1 style="margin:0 0 4px;">Planeación de Químicos</h1>
            <p class="breadcrumb-clients-manager">Entregado, pendiente, proyección e inventario por cliente y producto</p>
        </div>
        <div class="cp-actions" style="display:flex;align-items:center;gap:10px;">
            @include('admin.components._column_visibility_menu', [
                'tableKey' => 'chemical-planning.index',
                'columnDefs' => [
                    'entregado' => 'Entregado',
                    'por_entregar' => 'Por Entregar',
                    'proyeccion' => 'Proyección',
                    'inventario' => 'Inventario',
                ],
            ])
            <button type="button" class="button-primary size-adjustment cp-export-btn"
                style="background:#ff6213;border-color:#ff6213;white-space:nowrap;"
                data-download-url="{{ route('admin.chemical-planning.export') }}">
                Exportar a Excel
            </button>
        </div>
    </div>

    <main class="table-container-clients-manager cp-table-wrap">
        <table class="clients-manager-table brand-table cp-head-table">
            <thead>
                <tr>
                    <th>CLIENTE</th>
                    <th>PRODUCTO</th>
                    <th data-col="entregado">ENTREGADO</th>
                    <th data-col="por_entregar">POR ENTREGAR</th>
                    <th data-col="proyeccion">PROYECCIÓN</th>
                    <th data-col="inventario">INVENTARIO</th>
                </tr>
            </thead>
        </table>
        <div class="table-scroll">
            <table class="clients-manager-table cp-body-table">
                <tbody>
                @forelse ($rows as $row)
                    <tr class="cp-row" style="border-bottom:1px solid #f1f5f9;">
                        <td class="cp-customer" data-label="Cliente" style="padding:14px 16px;font-weight:600;color:#111827;">{{ $row['customer_name'] }}</td>
                        <td class="cp-product" data-label="Producto" style="padding:14px 16px;">{{ $row['product_name'] }}</td>
                        <td data-col="entregado" data-label="Entregado" style="padding:14px 16px;">{{ $row['delivered'] }} {{ $row['stock_unit'] }}</td>
                        <td data-col="por_entregar" data-label="Por entregar" style="padding:14px 16px;{{ $row['pending'] > 0 ? 'color:#d97706;font-weight:600;' : '' }}">{{ $row['pending'] }} {{ $row['stock_unit'] }}</td>
                        <td data-col="proyeccion" class="cp-projection" data-label="Proyección" style="padding:14px 16px;">
                            <input type="number" class="users-manager-input projection-input" style="max-width:110px;"
                                min="0" step="0.01" inputmode="decimal"
                                aria-label="Proyección de {{ $row['product_name'] }} para {{ $row['customer_name'] }}"
                                value="{{ $row['projected_quantity'] }}"
                                data-customer-id="{{ $row['customer_id'] }}"
                                data-product-id="{{ $row['product_id'] }}">
                        </td>
                        <td data-col="inventario" data-label="Inventario" style="padding:14px 16px;font-weight:600;">{{ $row['stock'] }} {{ $row['stock_unit'] }}</td>
                    </tr>
                @empty
                    <tr class="cp-empty">
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
<script src="{{ asset('js/admin/column-visibility.js') }}"></script>
<script>
    initColumnVisibility({
        tableKey: 'chemical-planning.index',
        savedColumns: @json($visibleColumns),
        saveUrl: '{{ route('admin.column-preferences.update') }}',
    });

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
<script src="{{ asset('js/admin/download-format.js') }}"></script>
<script>
    initDownloadFormatModal();

    // Bloqueo de scroll del fondo mientras el modal de formato está abierto
    // (solo tiene efecto visual en teléfono; ver estilos .cp-modal-open).
    (function () {
        var dlModal = document.getElementById('downloadFormatModal');
        if (!dlModal || !window.MutationObserver) return;
        new MutationObserver(function () {
            document.documentElement.classList.toggle('cp-modal-open', dlModal.classList.contains('active'));
        }).observe(dlModal, { attributes: true, attributeFilter: ['class'] });
    })();
</script>
@endpush
@include('admin.components.center-toast')
@include('admin.components._download_format_modal')
</div>
@endsection
