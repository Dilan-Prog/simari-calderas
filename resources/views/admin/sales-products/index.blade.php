@extends('admin.layouts.master')
@section('title')
    Productos - Ventas - Admin
@endsection
@section('content')
    <div class="container user-manager">
        <section class="clients-manager-section">
            <header class="clients-manager-main" style="margin-bottom:4px;">
                <div>
                    <p class="breadcrumb-clients-manager main" style="margin-bottom:4px;">
                        Panel de Control &gt; Ventas &gt; Productos
                    </p>
                    <h1>Productos</h1>
                    <p class="breadcrumb-clients-manager main">Consulta de productos para el equipo de ventas</p>
                </div>
            </header>

            {{-- Aquí va la tabla de datos personalizada (pendiente del diseño HTML). --}}
            <main id="salesProductsPanel"></main>
        </section>
    </div>
@endsection
