@extends('admin.layouts.master')
@push('styles')
    @vite('resources/css/admin/pages/home-sections.css')
@endpush
@section('title')
    Editar Servicio - Admin
@endsection
@section('content')
<div class="container user-manager">
<section class="clients-manager-section">

    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
        <div>
            <p class="breadcrumb-clients-manager" style="margin-bottom:4px;">
                Panel de Control &gt; <a href="{{ route('admin.service-pages.index') }}">Páginas de Servicio</a> &gt; <strong>{{ $servicePage->name }}</strong>
            </p>
            <h1 style="margin:0 0 4px;">Editar Servicio</h1>
            <p class="breadcrumb-clients-manager main">
                <a href="{{ route('service-page.show', $servicePage->slug) }}" target="_blank">Ver página pública ↗</a>
            </p>
        </div>
    </div>

    @if (session('success'))
        <div class="users-manager-badge status" style="display:inline-block;margin-bottom:16px;">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="user-manager-errors" style="display:block;margin-bottom:16px;">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.service-pages.update', $servicePage) }}" class="user-manager-modal-body" style="max-width:760px;">
        @csrf
        @method('PUT')
        @include('admin.service-pages.partials._form', ['servicePage' => $servicePage])

        <div class="user-manager-modal-footer" style="justify-content:flex-start;margin-top:24px;">
            <button type="submit" class="button-primary size-adjustment" style="background:#ff6213;border-color:#ff6213;">Guardar Cambios</button>
        </div>
    </form>

    {{-- Secciones --}}
    <div style="display:flex;justify-content:space-between;align-items:center;margin:36px 0 16px;">
        <h2 style="margin:0;">Secciones de la página</h2>
        <button type="button" class="button-primary size-adjustment" id="btnNewServiceSection"
            style="background:#ff6213;border-color:#ff6213;">
            + Nueva Sección
        </button>
    </div>

    <main class="table-container-clients-manager head">
        <table class="clients-manager-table brand-table">
            <thead>
                <tr>
                    <th style="width:32px;"></th>
                    <th>TIPO</th>
                    <th>TÍTULO</th>
                    <th>ORDEN</th>
                    <th>ESTADO</th>
                    <th>ACCIONES</th>
                </tr>
            </thead>
        </table>
        <div class="table-scroll">
            <table class="clients-manager-table" id="serviceSectionsTable">
                <tbody id="serviceSectionsTableBody">
                @php
                    $typeLabels = [
                        'banner'           => 'Banner',
                        'dual_banner'      => 'Banner Doble',
                        'product_carousel' => 'Carrusel de Productos',
                        'product_carousel_banner' => 'Carrusel con Banner',
                        'category_grid'    => 'Grid de Categorías',
                        'brand_carousel'   => 'Carrusel de Marcas',
                        'html_block'       => 'Bloque HTML',
                        'faq'              => 'Preguntas Frecuentes',
                    ];
                @endphp
                @forelse ($servicePage->sections as $section)
                    <tr class="hs-row" data-id="{{ $section->id }}" draggable="true">
                        <td style="padding:12px 8px;text-align:center;color:#9ca3af;cursor:grab;" class="hs-drag-handle">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                        </td>
                        <td style="padding:12px 16px;">
                            <span class="hs-type-badge" data-type="{{ $section->type }}">{{ $typeLabels[$section->type] ?? $section->type }}</span>
                        </td>
                        <td style="padding:12px 16px;font-weight:500;">{{ $section->title ?? '—' }}</td>
                        <td style="padding:12px 16px;text-align:center;color:#374151;" class="hs-sort-order">{{ $section->sort_order }}</td>
                        <td style="padding:12px 16px;">
                            <span class="users-manager-badge {{ $section->is_active ? 'status' : 'status-inactive' }}">
                                {{ $section->is_active ? 'Activa' : 'Inactiva' }}
                            </span>
                        </td>
                        <td style="padding:12px 16px;">
                            <div class="header-right-user-manager">
                                <button type="button" class="table-users-manager-action-btn edit btn-edit-service-section" data-id="{{ $section->id }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/></svg>
                                </button>
                                <button type="button" class="table-users-manager-action-btn delete btn-delete-service-section" data-id="{{ $section->id }}" data-title="{{ e($section->title ?? ($typeLabels[$section->type] ?? $section->type)) }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding:32px 16px;text-align:center;color:#9ca3af;">
                            No hay secciones configuradas todavía.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </main>

</section>

@include('admin.service-pages.partials._section_modal')
@include('admin.service-pages.partials._delete_section_modal')
@include('admin.service-pages.partials._section_scripts')
@include('admin.service-pages.partials._faq_scripts')
@include('admin.components.center-toast')
</div>
@endsection
