@extends('admin.layouts.master')

@section('title')
Paqueterías - Admin
@endsection
@push('styles')
@vite(['resources/css/admin/pages/delivery.css'])
@endpush

@section('content')
{{-- Adaptación de la clase .quotes-btn-filter para los botones de las tarjetas --}}



<div class="dash-page">
    
    {{-- Header --}}
    <div class="dash-header">
        <h1 class="dash-title">Paqueterías</h1>
        <button class="button-primary size-adjustment" onclick="document.getElementById('modalCrearOverlay').classList.add('active')">
    + Nueva Paquetería
</button>
    </div>

    {{-- Grid 3 columnas --}}
    <div class="dash-stats-grid" style="grid-template-columns: repeat(3, 1fr);">
        
        @foreach($paqueterias as $paquete)
            <div class="dash-stat-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                
                <div>
                    {{-- Icono y Status --}}
                    <div class="dash-stat-card-top">
                        <div class="dash-stat-icon blue">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"/>
                                <path d="M12 22V12"/>
                                <polyline points="3.29 7 12 12 20.71 7"/>
                                <path d="m7.5 4.27 9 5.15"/>
                            </svg>
                        </div>
                        
                        {{-- Usando la clase dash-stat-badge --}}
                        @if($paquete->estado == 'Activa')
                            <span class="dash-stat-badge">{{ $paquete->estado }}</span>
                        @else
                            <span class="dash-stat-badge" style="color: #6b7280; background: #f3f4f6;">{{ $paquete->estado }}</span>
                        @endif
                    </div>

                    {{-- Textos --}}
                    <div style="margin-bottom: 24px;">
                        <h4 class="dash-stat-value" style="font-size: 16px; margin-bottom: 8px;">{{ $paquete->nombre }}</h4>
                        <p style="margin: 0 0 4px 0; font-size: 13px; color: #6b7280;">Tiempo: {{ $paquete->tiempo_entrega }}</p>
                        <p style="margin: 0; font-size: 13px; color: #6b7280;">Cobertura: {{ $paquete->cobertura }}</p>
                    </div>
                </div>

                {{-- Botones Editar--}}
                <div style="display: flex; gap: 12px;">
                    <button class="card-btn-edit" onclick="document.getElementById('modalEditarOverlay{{ $paquete->id }}').classList.add('active')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                        </svg>
                        Editar
                    </button>
                {{-- Botones Eliminar--}}                    
                    <button class="card-btn-delete" onclick="document.getElementById('modalEliminarOverlay{{ $paquete->id }}').classList.add('active')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <polyline points="3 6 5 6 21 6"></polyline>
        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
    </svg>
</button>
                </div>

            </div>
            @include('admin.delivery.partials._edit_modal')
            @include('admin.delivery.partials._modal_delete')
        @endforeach

    </div>
</div>
@include('admin.delivery.partials._modal_create')
@endsection
