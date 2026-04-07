@extends('admin.layouts.master')

@section('title', 'Panel Empleado | Simari Calderas')

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Panel de Empleado</h1>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Bienvenido, {{ Auth::user()->name }}</h4>
                        </div>
                        <div class="card-body">
                            <p>Este es tu panel de empleado. Aquí encontrarás las herramientas y módulos asignados a tu rol.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
