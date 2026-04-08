@extends('admin.layouts.master')
@section('content')
<div class="admin-welcome-section">
    <h1>Bienvenido al Panel</h1>
    <p>Selecciona una sección del menú lateral para comenzar.</p>
    <div class="admin-stats-grid">
        <div class="admin-stat-card">
            <div class="admin-stat-card-label">Usuarios</div>
            <div class="admin-stat-card-value">—</div>
            <div class="admin-stat-card-sub">Registrados en el sistema</div>
        </div>
        <div class="admin-stat-card">
            <div class="admin-stat-card-label">Clientes</div>
            <div class="admin-stat-card-value">—</div>
            <div class="admin-stat-card-sub">Cuentas activas</div>
        </div>
        <div class="admin-stat-card">
            <div class="admin-stat-card-label">Órdenes</div>
            <div class="admin-stat-card-value">—</div>
            <div class="admin-stat-card-sub">Este mes</div>
        </div>
    </div>
</div>
{{-- Contenido dinámico --}}
{{-- <main class="admin-content-area" id="mainContent">
    <div class="admin-loading-state" id="loadingState">
        <div class="admin-spinner"></div>
        <span>Cargando...</span>
    </div>
</main> --}}

@endsection
