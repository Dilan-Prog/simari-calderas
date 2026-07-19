@extends('admin.layouts.master')
@push('styles')
    @vite('resources/css/admin/pages/home-sections.css')
@endpush
@section('title')
    Slides del Slider - Admin
@endsection
@section('content')
<div class="container user-manager">
<section class="clients-manager-section">

    {{-- Header --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;">
        <div>
            <p class="breadcrumb-clients-manager" style="margin-bottom:4px;">
                <a href="{{ route('admin.home-sections.index') }}" style="color:inherit;">Secciones del Inicio</a>
                &gt; <strong>{{ $section->title ?? 'Slider Principal' }}</strong>
            </p>
            <h1 style="margin:0 0 4px;">Slides del Slider</h1>
            <p class="breadcrumb-clients-manager main">Arrastra las tarjetas para reordenar los slides del hero</p>
        </div>
        <div style="display:flex;gap:10px;">
            <a href="{{ route('admin.home-sections.index') }}" class="button-secondary size-adjustment">
                &larr; Volver
            </a>
            <button type="button" class="button-primary size-adjustment" id="btnNewSlide"
                style="background:#ff6213;border-color:#ff6213;white-space:nowrap;">
                + Nuevo Slide
            </button>
        </div>
    </div>

    {{-- Slides grid --}}
    <div id="hsSlidesGrid" class="hs-slides-grid" data-home-section-id="{{ $section->id }}">
        @forelse ($section->slides as $slide)
            <div class="hs-slide-card" data-id="{{ $slide->id }}" draggable="true"
                data-image-url="{{ e($slide->image_url) }}"
                data-badge-text="{{ e($slide->badge_text ?? '') }}"
                data-title="{{ e($slide->title) }}"
                data-title-highlight="{{ e($slide->title_highlight ?? '') }}"
                data-description="{{ e($slide->description ?? '') }}"
                data-link-url="{{ e($slide->link_url ?? '') }}"
                data-is-active="{{ $slide->is_active ? '1' : '0' }}">
                <img src="{{ $slide->image_url }}" alt="" class="hs-slide-card-thumb">
                @if ($slide->badge_text)
                    <span class="hs-slide-card-badge">{{ $slide->badge_text }}</span>
                @endif
                <div class="hs-slide-card-body">
                    <p class="hs-slide-card-title">{{ $slide->title }}{{ $slide->title_highlight ? ' ' . $slide->title_highlight : '' }}</p>
                    <span class="users-manager-badge {{ $slide->is_active ? 'status' : 'status-inactive' }}">
                        {{ $slide->is_active ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>
                <div class="hs-slide-card-actions">
                    <button type="button" class="table-users-manager-action-btn edit btn-edit-slide" data-id="{{ $slide->id }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/></svg>
                    </button>
                    <button type="button" class="table-users-manager-action-btn delete btn-delete-slide" data-id="{{ $slide->id }}" data-title="{{ e($slide->title) }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                    </button>
                </div>
            </div>
        @empty
            <p class="hs-slides-empty" id="hsSlidesEmpty">No hay slides configurados todavía. Agrega el primero.</p>
        @endforelse
    </div>

</section>

@include('admin.home-sections.partials._slide_modal')
@include('admin.home-sections.partials._delete_slide_modal')
@include('admin.home-sections.partials._slides_scripts')
</div>
@endsection
