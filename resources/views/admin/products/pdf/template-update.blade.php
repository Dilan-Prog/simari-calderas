{{--
    Hoja de referencia (no es tabla de datos): explica cómo llenar la
    plantilla de actualización de productos. Recibe $rows — el array que
    devuelve ProductsUpdateTemplateInstructionsSheet::instructionRows()
    (app/Exports/Sheets/ProductsUpdateTemplateInstructionsSheet.php), el
    mismo contenido que ya se muestra en la pestaña "Instrucciones" del
    Excel.
--}}
@extends('admin.pdf.layouts.catalog')

@section('title', 'Guía de Plantilla — Actualizar Productos')

@section('document-label', 'Guía de Plantilla')
@section('document-title', 'Actualizar Productos')

@section('footer-center', 'Guía de referencia generada el ' . now()->format('d/m/Y H:i'))

@section('content')
    @include('admin.pdf.partials.instruction-rows', ['rows' => $rows])
@endsection
