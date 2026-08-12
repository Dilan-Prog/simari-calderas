@extends('admin.layouts.master')

@section('title', 'Nueva Plantilla de Correo - Admin')

@push('styles')
@include('admin.email-templates.partials._styles')
@endpush

@section('content')
<div class="et-form-page">

    <div>
        <div class="et-form-breadcrumb">
            <span>Panel de Control</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            <a href="{{ route('admin.email-templates.index') }}" style="color:inherit;text-decoration:none;">Plantillas</a>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            <span class="et-form-breadcrumb-current">Nueva</span>
        </div>
        <h1 class="et-form-title">Nueva plantilla de correo</h1>
        <p class="et-form-subtitle">Crea una plantilla HTML reutilizable para campañas y secuencias</p>
    </div>

    @include('admin.email-templates.partials._form', [
        'formAction' => route('admin.email-templates.store'),
        'formMethod' => 'POST',
    ])

</div>
@endsection
