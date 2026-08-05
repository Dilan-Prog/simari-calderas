{{--
    Catálogo de Proveedores-Productos (PDF).
    Recibe: $links — Collection de App\Models\SupplierProduct con
    supplier y product precargados (el controller es responsable de la
    query). Mismas 7 columnas que SupplierProductsExport
    (app/Exports/SupplierProductsExport.php), sin reducir — caben todas
    en una hoja A4 sin problema de espacio.
--}}
@extends('admin.pdf.layouts.catalog')

@section('title', 'Catálogo de Proveedores Productos')

@section('document-label', 'Catálogo')
@section('document-title', 'Proveedores Productos')
@section('document-meta-extra')
    <p>Total de vínculos: <span>{{ $links->count() }}</span></p>
@endsection

@section('footer-center', 'Catálogo generado el ' . now()->format('d/m/Y H:i'))

@section('content')
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:20%;">Proveedor</th>
                <th style="width:14%;">SKU Producto</th>
                <th style="width:24%;">Nombre Producto</th>
                <th style="width:14%;">SKU Proveedor</th>
                <th class="th-right" style="width:12%;">Costo</th>
                <th class="th-right" style="width:8%;">Lead Time (días)</th>
                <th style="width:8%;">Es Principal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($links as $link)
                <tr>
                    <td class="td-name">{{ $link->supplier->company_name ?? '—' }}</td>
                    <td class="td-sku">{{ $link->product->sku ?? '—' }}</td>
                    <td>{{ $link->product->name ?? '—' }}</td>
                    <td class="td-sku">{{ $link->sku ?? '—' }}</td>
                    <td class="td-right">${{ number_format($link->cost, 2) }}</td>
                    <td class="td-right">{{ $link->lead_time_days ?? '—' }}</td>
                    <td>{{ $link->is_primary ? 'Sí' : 'No' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
