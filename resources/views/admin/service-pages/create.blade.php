@extends('admin.layouts.master')
@push('styles')
    @vite('resources/css/admin/pages/home-sections.css')
@endpush
@section('title')
    Nuevo Servicio - Admin
@endsection
@section('content')
<div class="container user-manager">
<section class="clients-manager-section">

    <div style="margin-bottom:24px;">
        <p class="breadcrumb-clients-manager" style="margin-bottom:4px;">
            Panel de Control &gt; <a href="{{ route('admin.service-pages.index') }}">Páginas de Servicio</a> &gt; <strong>Nuevo</strong>
        </p>
        <h1 style="margin:0 0 4px;">Nuevo Servicio</h1>
        <p class="breadcrumb-clients-manager main">Después de crearlo podrás agregar sus secciones.</p>
    </div>

    @if ($errors->any())
        <div class="user-manager-errors" style="display:block;margin-bottom:16px;">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.service-pages.store') }}" class="user-manager-modal-body" style="max-width:760px;">
        @csrf
        @include('admin.service-pages.partials._form', ['servicePage' => null])

        <div class="user-manager-modal-footer" style="justify-content:flex-start;margin-top:24px;">
            <a href="{{ route('admin.service-pages.index') }}" class="button-secondary size-adjustment">Cancelar</a>
            <button type="submit" class="button-primary size-adjustment" style="background:#ff6213;border-color:#ff6213;">Crear Servicio</button>
        </div>
    </form>

</section>
@include('admin.service-pages.partials._faq_scripts')
@include('admin.components.center-toast')
</div>
@endsection
