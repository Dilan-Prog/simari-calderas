{{--
    Hoja de referencia (no es tabla de datos): explica cómo llenar la
    plantilla de Proveedores-Productos. Recibe $rows — el array que
    devuelve SupplierProductsTemplateInstructionsSheet::instructionRows()
    (app/Exports/Sheets/SupplierProductsTemplateInstructionsSheet.php),
    el mismo contenido que ya se muestra en la pestaña "Instrucciones"
    del Excel.
--}}
@extends('admin.pdf.layouts.catalog')

@section('title', 'Guía de Plantilla — Proveedores Productos')

@section('document-label', 'Guía de Plantilla')
@section('document-title', 'Proveedores Productos')

@section('footer-center', 'Guía de referencia generada el ' . now()->format('d/m/Y H:i'))

@section('content')
    @include('admin.pdf.partials.instruction-rows', ['rows' => $rows])
@endsection
