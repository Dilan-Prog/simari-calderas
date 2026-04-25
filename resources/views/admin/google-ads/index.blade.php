@extends('admin.layouts.master')

@section('title')
    Google Ads — Conversiones | Admin
@endsection
@section('content')
    <div class="container user-manager">
        {{-- Toast notifications --}}
        @if (session('success'))
            <div class="toast-notification toast-success" id="toastNotification">
                <div class="toast-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <path d="m9 11 3 3L22 4"></path>
                    </svg>
                </div>
                <div class="toast-body">
                    <p class="toast-title">Accion realizada</p>
                    <p class="toast-message">{{ session('success') }}</p>
                </div>
                <button class="toast-close"
                    onclick="const t=this.closest('.toast-notification');t.style.animation='toastOut 0.3s ease forwards';setTimeout(()=>t.remove(),300)">✕</button>
            </div>
        @endif
        @if (session('error'))
            <div class="toast-notification toast-error" id="toastNotification">
                <div class="toast-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="#dc2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" x2="12" y1="8" y2="12"></line>
                        <line x1="12" x2="12.01" y1="16" y2="16"></line>
                    </svg>
                </div>
                <div class="toast-body">
                    <p class="toast-title">Error</p>
                    <p class="toast-message">{{ session('error') }}</p>
                </div>
                <button class="toast-close"
                    onclick="const t=this.closest('.toast-notification');t.style.animation='toastOut 0.3s ease forwards';setTimeout(()=>t.remove(),300)">✕</button>
            </div>
        @endif

        {{-- Main content --}}
        <section class="users-manager-section">

            {{-- Page header --}}
            <header class="users-manager-main">
                <div>
                    <h1>Google Ads — Conversiones</h1>
                    <p class="breadcrumb-users-manager main">
                        Registro y seguimiento de conversiones en base de datos
                    </p>
                </div>
            </header>

            {{-- Metrics cards --}}
            <div class="google-ads-cards">
                <div class="google-ads-card">
                    <p class="google-ads-card-label">Total conversiones</p>
                    <p class="google-ads-card-value">{{ number_format($totalConversions) }}</p>
                </div>
                <div class="google-ads-card">
                    <p class="google-ads-card-label">Valor total</p>
                    <p class="google-ads-card-value">${{ number_format($totalValue, 2) }}</p>
                    <p class="google-ads-card-sub">MXN acumulado</p>
                </div>
                <div class="google-ads-card">
                    <p class="google-ads-card-label">Conversiones hoy</p>
                    <p class="google-ads-card-value">{{ number_format($todayConversions) }}</p>
                </div>
                <div class="google-ads-card">
                    <p class="google-ads-card-label">Por estado</p>
                    <div style="display:flex;flex-wrap:wrap;gap:0.35rem;margin-top:0.4rem;">
                        @foreach(['stored'=>'Almacenado','pending'=>'Pendiente','sent'=>'Enviado','failed'=>'Fallido'] as $key => $label)
                            @if(($statusBreakdown[$key] ?? 0) > 0)
                                @php
                                    $cls = match($key) {
                                        'stored'  => 'status-stored',
                                        'pending' => 'status-pending',
                                        'sent'    => 'status',
                                        'failed'  => 'status-failed',
                                        default   => '',
                                    };
                                @endphp
                                <span class="users-manager-badge {{ $cls }}">
                                    {{ $label }}: {{ $statusBreakdown[$key] }}
                                </span>
                            @endif
                        @endforeach
                        @if(empty(array_filter($statusBreakdown)))
                            <span class="users-manager-badge status-stored">Sin datos</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Table container --}}
            <main class="table-container-users-manager">

                {{-- Filter bar: search + date range + CSV button --}}
                <div class="google-ads-filters">
                    {{-- Global text search --}}
                    <input type="text" id="gaSearch" class="google-ads-search-input"
                        placeholder="Buscar por GCLID, conversión, orden, estado…">

                    <span class="google-ads-filter-label">Desde:</span>
                    <input type="date" id="gaDateFrom" class="google-ads-filter-input">

                    <span class="google-ads-filter-label">Hasta:</span>
                    <input type="date" id="gaDateTo" class="google-ads-filter-input">

                    <button id="gaClearFilters" class="dt-button" type="button">Limpiar filtros</button>

                    <div class="google-ads-filters-spacer"></div>

                    {{-- CSV export button rendered by DataTables Buttons --}}
                    <div id="gaExportWrapper"></div>
                </div>

                {{-- DataTable --}}
                <div class="table-scroll" style="overflow-x:auto;">
                    <table id="gaTable" class="users-manager-table" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>GCLID</th>
                                <th>CONVERSIÓN</th>
                                <th>VALOR</th>
                                <th>MONEDA</th>
                                <th>ORDEN ID</th>
                                <th>ESTADO</th>
                                <th>TIEMPO</th>
                                <th>CREADO</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </main>

        </section>
    </div>
    @include('admin.google-ads._scripts')
@endsection

