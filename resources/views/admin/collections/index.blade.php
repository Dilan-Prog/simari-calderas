@extends('admin.layouts.master')
@section('title')
    Colecciones - Admin
@endsection

@push('styles')
    @vite('resources/css/admin/pages/collections.css')
@endpush

@section('content')
<div class="container user-manager">
<section class="clients-manager-section">

    {{-- Header --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;">
        <div>
            <p class="breadcrumb-clients-manager" style="margin-bottom:4px;">
                Panel de Control &gt; <strong>Colecciones</strong>
            </p>
            <h1 style="margin:0 0 4px;">Gestión de Colecciones</h1>
            <p class="breadcrumb-clients-manager main">Agrupa productos manualmente o con reglas automáticas</p>
        </div>
        <button type="button" class="button-primary size-adjustment" id="btnNewCollection"
            style="background:#ff6213;border-color:#ff6213;white-space:nowrap;">
            + Nueva Colección
        </button>
    </div>

    {{-- Stats cards --}}
    @php
        $total      = $collections->total();
        $allColls   = \App\Models\Collection::all();
        $manuales   = $allColls->where('type', 'manual')->count();
        $automaticas = $allColls->where('type', 'automatic')->count();
        $activas    = $allColls->where('is_active', true)->count();
    @endphp
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px;">
        <div class="stat-card" style="display:flex;align-items:center;gap:16px;">
            <div style="width:48px;height:48px;background:#ff6213;border-radius:12px;
                display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                    fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/>
                    <rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/>
                </svg>
            </div>
            <div>
                <h2 style="margin:0;font-size:1.8rem;">{{ $total }}</h2>
                <p class="breadcrumb-clients-manager" style="margin:0;">Total colecciones</p>
            </div>
        </div>
        <div class="stat-card" style="display:flex;align-items:center;gap:16px;">
            <div style="width:48px;height:48px;background:#3b82f6;border-radius:12px;
                display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                    fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="10"/>
                </svg>
            </div>
            <div>
                <h2 style="margin:0;font-size:1.8rem;">{{ $manuales }}</h2>
                <p class="breadcrumb-clients-manager" style="margin:0;">Manuales</p>
            </div>
        </div>
        <div class="stat-card" style="display:flex;align-items:center;gap:16px;">
            <div style="width:48px;height:48px;background:#8b5cf6;border-radius:12px;
                display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                    fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/>
                </svg>
            </div>
            <div>
                <h2 style="margin:0;font-size:1.8rem;">{{ $automaticas }}</h2>
                <p class="breadcrumb-clients-manager" style="margin:0;">Automáticas</p>
            </div>
        </div>
        <div class="stat-card" style="display:flex;align-items:center;gap:16px;">
            <div style="width:48px;height:48px;background:#16a34a;border-radius:12px;
                display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                    fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>
                </svg>
            </div>
            <div>
                <h2 style="margin:0;font-size:1.8rem;">{{ $activas }}</h2>
                <p class="breadcrumb-clients-manager" style="margin:0;">Activas</p>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <main class="table-container-clients-manager head">
        <table class="clients-manager-table brand-table">
            <thead>
                <tr>
                    <th>NOMBRE</th>
                    <th>TIPO</th>
                    <th>PRODUCTOS</th>
                    <th>ORDEN</th>
                    <th>ESTADO</th>
                    <th>ACCIONES</th>
                </tr>
            </thead>
        </table>
        <div class="table-scroll">
            <table class="clients-manager-table">
                <tbody>
                @forelse ($collections as $collection)
                    @php
                        $isManual = $collection->type === 'manual';
                        $productCount = $collection->resolveProducts()->count();
                        $statusClass = $collection->is_active ? 'status' : 'status-inactive';
                        $statusLabel = $collection->is_active ? 'Activa' : 'Inactiva';
                    @endphp
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:12px 16px;">
                            <div style="display:flex;flex-direction:column;">
                                <span style="font-weight:700;font-size:14px;">{{ $collection->name }}</span>
                                <span style="font-size:12px;color:#6b7280;">/{{ $collection->slug }}</span>
                            </div>
                        </td>
                        <td style="padding:12px 16px;">
                            <span class="collection-type-badge {{ $isManual ? 'manual' : 'automatic' }}">
                                {{ $isManual ? 'Manual' : 'Automática' }}
                            </span>
                        </td>
                        <td style="padding:12px 16px;text-align:center;font-size:14px;color:#374151;">
                            {{ $productCount }}
                        </td>
                        <td style="padding:12px 16px;text-align:center;font-size:14px;color:#374151;">
                            {{ $collection->sort_order }}
                        </td>
                        <td style="padding:12px 16px;">
                            <span class="users-manager-badge {{ $statusClass }}" data-status="{{ $collection->is_active ? 'active' : 'inactive' }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td style="padding:12px 16px;">
                            <div class="header-right-user-manager">
                                @if ($isManual)
                                    <a href="{{ route('admin.collections.show', $collection->id) }}"
                                        class="table-users-manager-action-btn edit" title="Gestionar productos">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                                    </a>
                                @endif
                                <button type="button" class="table-users-manager-action-btn edit btn-edit-collection" data-id="{{ $collection->id }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/></svg>
                                </button>
                                <button type="button" class="table-users-manager-action-btn delete btn-delete-collection" data-id="{{ $collection->id }}" data-name="{{ $collection->name }}" data-slug="{{ $collection->slug }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding:32px 16px;text-align:center;color:#6b7280;">
                            No hay colecciones creadas todavía.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </main>

    {{-- Pagination --}}
    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;padding:0 4px;">
        <p class="breadcrumb-clients-manager">
            Mostrando <strong>{{ $collections->firstItem() ?? 0 }}–{{ $collections->lastItem() ?? 0 }}</strong>
            de <strong>{{ $collections->total() }}</strong> colecciones
        </p>
    </div>
    {{ $collections->links('admin.components.pagination') }}

</section>

@include('admin.collections.partials._create_edit_modal')
@include('admin.collections.partials._delete_modal')
@include('admin.collections.partials._scripts')
@include('admin.components.center-toast')
</div>
@endsection
