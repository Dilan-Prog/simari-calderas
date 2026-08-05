{{--
    Planeación de Químicos (PDF).
    Recibe: $rows — misma Collection de filas pre-agregadas
    (cliente+producto) que ya consume ChemicalPlanningExport::map()
    (app/Exports/ChemicalPlanningExport.php), construida en
    ChemicalPlanningController::index(). Cada fila es un array asociativo
    con las claves: customer_name, product_name, delivered, pending,
    projected_quantity, stock.
--}}
@extends('admin.pdf.layouts.catalog')

@section('title', 'Planeación de Químicos')

@section('document-label', 'Planeación')
@section('document-title', 'Planeación de Químicos')
@section('document-meta-extra')
    <p>Total de filas: <span>{{ $rows->count() }}</span></p>
@endsection

@section('footer-center', 'Planeación generada el ' . now()->format('d/m/Y H:i'))

@section('content')
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:24%;">Cliente</th>
                <th style="width:26%;">Producto</th>
                <th class="th-right" style="width:12%;">Entregado</th>
                <th class="th-right" style="width:14%;">Por Entregar</th>
                <th class="th-right" style="width:12%;">Proyección</th>
                <th class="th-right" style="width:12%;">Inventario</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td class="td-name">{{ $row['customer_name'] }}</td>
                    <td>{{ $row['product_name'] }}</td>
                    <td class="td-right">{{ $row['delivered'] }}</td>
                    <td class="td-right">{{ $row['pending'] }}</td>
                    <td class="td-right">{{ $row['projected_quantity'] }}</td>
                    <td class="td-right">{{ $row['stock'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
