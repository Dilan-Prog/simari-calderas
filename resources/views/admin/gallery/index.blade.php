@extends('admin.layouts.master')
@push('styles')
    @vite('resources/css/admin/pages/gallery.css')
@endpush
@section('title')
    Galería de Imágenes - Admin
@endsection
@section('content')
<div class="container user-manager">
<section class="clients-manager-section">

    @php
        $tabLabels = [
            'galeria'     => 'Galería',
            'productos'   => 'Productos',
            'marcas'      => 'Marcas',
            'categorias'  => 'Categorías',
            'colecciones' => 'Colecciones',
            'banners'     => 'Banners',
        ];
    @endphp

    {{-- Header --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;gap:12px;flex-wrap:wrap;">
        <div>
            <p class="breadcrumb-clients-manager" style="margin-bottom:4px;">
                Panel de Control &gt; <strong>Galería de Imágenes</strong>
            </p>
            <h1 style="margin:0 0 4px;">Galería de Imágenes</h1>
            <p class="breadcrumb-clients-manager main">
                {{ $tab === 'galeria'
                    ? 'Sube y administra tu biblioteca de imágenes'
                    : 'Imágenes en uso: ' . $tabLabels[$tab] }}
            </p>
        </div>
        @if ($tab === 'galeria')
            <button type="button" class="button-primary size-adjustment" id="btnGalleryUpload"
                style="background:#ff6213;border-color:#ff6213;white-space:nowrap;">
                + Subir imágenes
            </button>
        @endif
    </div>

    {{-- Tabs --}}
    <div class="gal-tabs">
        @foreach ($tabLabels as $key => $label)
            <a href="{{ route('admin.gallery.index', $key === 'galeria' ? [] : ['tab' => $key]) }}"
                class="gal-tab {{ $tab === $key ? 'is-active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    {{-- Grid --}}
    @if ($tab === 'galeria')
        <div class="gal-grid">
            @forelse ($galleryImages as $image)
                <div class="gal-card">
                    <div class="gal-card__img">
                        <img src="{{ $image->url }}" alt="{{ $image->original_name ?? 'Imagen' }}" loading="lazy">
                        <div class="gal-card__overlay">
                            <button type="button" class="gal-action" title="Ver" onclick="galOpenLightbox('{{ $image->url }}')">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                            <button type="button" class="gal-action" title="Copiar URL" onclick="galCopyUrl('{{ $image->url }}', this)">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                            </button>
                            <button type="button" class="gal-action gal-action--danger" title="Eliminar"
                                onclick="galAskDelete({{ $image->id }}, '{{ addslashes($image->original_name ?? 'esta imagen') }}')">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="gal-card__meta">
                        <div class="gal-card__label" title="{{ $image->original_name }}">{{ $image->original_name ?? 'Sin nombre' }}</div>
                        <div class="gal-card__sublabel">{{ $image->created_at?->format('d/m/Y') }}</div>
                    </div>
                </div>
            @empty
                <div class="gal-empty">
                    <p>Tu galería está vacía. Sube tus primeras imágenes con el botón <strong>+ Subir imágenes</strong>.</p>
                </div>
            @endforelse
        </div>
        @if ($galleryImages->hasPages())
            {{ $galleryImages->links('admin.components.pagination') }}
        @endif
    @else
        <div class="gal-grid">
            @forelse ($items as $item)
                <div class="gal-card">
                    <div class="gal-card__img">
                        <img src="{{ $item['url'] }}" alt="{{ $item['label'] }}" loading="lazy">
                        <div class="gal-card__overlay">
                            <button type="button" class="gal-action" title="Ver" onclick="galOpenLightbox('{{ $item['url'] }}')">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                            <button type="button" class="gal-action" title="Copiar URL" onclick="galCopyUrl('{{ $item['url'] }}', this)">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="gal-card__meta">
                        <div class="gal-card__label" title="{{ $item['label'] }}">{{ $item['label'] }}</div>
                        <div class="gal-card__sublabel">{{ $item['sublabel'] }}</div>
                    </div>
                </div>
            @empty
                <div class="gal-empty">
                    <p>No hay imágenes en {{ $tabLabels[$tab] }} todavía.</p>
                </div>
            @endforelse
        </div>
        @if ($paginator && $paginator->hasPages())
            {{ $paginator->links('admin.components.pagination') }}
        @endif
    @endif

</section>

{{-- Lightbox --}}
<div class="gal-lightbox" id="galLightbox" onclick="this.classList.remove('active')">
    <img src="" alt="" id="galLightboxImg" onclick="event.stopPropagation()">
</div>

@if ($tab === 'galeria')
    @include('admin.gallery.partials._upload_modal')
    @include('admin.gallery.partials._delete_modal')
@endif
@include('admin.gallery.partials._scripts')
@include('admin.components.center-toast')
</div>
@endsection
