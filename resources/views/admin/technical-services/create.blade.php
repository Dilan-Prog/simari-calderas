@extends('admin.layouts.master')

@section('title', 'Nuevo Servicio Técnico')

@push('styles')
    @vite('resources/css/admin/technical-services.css')
@endpush

@section('content')
<div class="ts-page">

    {{-- ── Wizard progress bar ────────────────────── --}}
    @php
        $steps = [1 => 'Datos Generales', 2 => 'Técnicos', 3 => 'Materiales', 4 => 'Confirmar'];
        $currentStep = $service->current_step ?? 1;
    @endphp

    <div class="ts-wizard-bar">
        @foreach($steps as $num => $label)
            @php
                $stepState = $num < $currentStep ? 'completed' : ($num === $currentStep ? 'active' : 'pending');
            @endphp
            <div class="ts-step {{ $stepState }}">
                <div class="ts-step__circle">
                    @if($num < $currentStep)
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 6 9 17l-5-5"/>
                        </svg>
                    @else
                        {{ $num }}
                    @endif
                </div>
                <span class="ts-step__label">{{ $label }}</span>
            </div>
            @if(!$loop->last)
                <div class="ts-step-connector {{ $num < $currentStep ? 'ts-step-connector--done' : '' }}"></div>
            @endif
        @endforeach
    </div>

    {{-- ── Page header ─────────────────────────────── --}}
    <div class="ts-form-container">
        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.5rem">
            <a href="{{ route('admin.technical-services.index') }}" class="ts-show-back" title="Volver">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m12 19-7-7 7-7M19 12H5"/>
                </svg>
            </a>
            <div>
                <div class="ts-breadcrumb" style="margin-bottom:0.25rem">
                    <span>Panel de Control</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                    <a href="{{ route('admin.technical-services.index') }}"
                       style="color:inherit;text-decoration:none">Servicios Técnicos</a>
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                    <span class="ts-breadcrumb__current">Nuevo Servicio</span>
                </div>
                <h1 class="ts-title" style="margin:0">Programar Nuevo Servicio</h1>
            </div>
            <div style="margin-left:auto;display:flex;align-items:center;gap:0.5rem">
                <span class="ts-autosave" id="ts-autosave"></span>
            </div>
        </div>

        {{-- Quote banner --}}
        @if(isset($fromQuote) && $fromQuote)
        <div class="ts-quote-banner">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
            </svg>
            Servicio generado desde Cotización
            <strong>{{ $fromQuote->quote_number }}</strong>
            <a href="{{ route('admin.quotes.show', $fromQuote) }}">Ver cotización →</a>
        </div>
        @endif

        {{-- Step content --}}
        @if($currentStep === 1)
            @include('admin.technical-services.steps.step1')
        @elseif($currentStep === 2)
            @include('admin.technical-services.steps.step2')
        @elseif($currentStep === 3)
            @include('admin.technical-services.steps.step3')
        @elseif($currentStep === 4)
            @include('admin.technical-services.steps.step4')
        @endif

    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/admin/technical-services.js')
@endpush
