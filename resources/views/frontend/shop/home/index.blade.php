@extends('frontend.shop.layouts.master')

@php
    $shopVite = ['resources/css/frontend/shop/home.css', 'resources/js/frontend/shop/home.js'];
@endphp

@section('title', 'Equiterm Industries — Calderas, Calentadores y Tratamiento de Agua')
@section('description', 'Ingeniería térmica industrial: calderas, calentamiento y tratamiento de agua, con proyectos llave en mano en toda la República Mexicana.')

@section('content')
<div class="eq-shop-home">
    @forelse ($sections as $section)
        @include('frontend.shop.home.sections.' . str_replace('_', '-', $section->type), ['section' => $section])
    @empty
        <p class="eq-shop-home__empty">Aún no hay secciones configuradas para esta página. Ve a "Inicio - Secciones" en el panel de administración para agregar contenido.</p>
    @endforelse
</div>
@endsection
