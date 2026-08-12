@extends('admin.layouts.master')

@section('title', 'Plantillas de Automatización - Admin')

@push('styles')
<style>
:root {
    --background--white:          #ffffff;
    --header-footer-color:        #1A2535;
    --text-subwhite-color:        #D1D5DC;
    --text-description-color:     #6B7280;
    --secondary-color:            #ff6213;
    --button-primary-color:       #ff6213;
    --button-primary-color-hover: #de4a00;
    --font-family:                'Inter', sans-serif;
    --shadow-sm:                  0 1px 2px rgba(0,0,0,.06);
    --shadow-md:                  0 10px 20px rgba(0,0,0,.1);
}

.wf-page { padding: 32px; font-family: var(--font-family); display: flex; flex-direction: column; gap: 24px; }

.wf-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.wf-breadcrumb { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--text-description-color); margin-bottom: 8px; }
.wf-breadcrumb svg { flex-shrink: 0; color: #9CA3AF; }
.wf-breadcrumb-current { color: #374151; }
.wf-title { font-size: 24px; font-weight: 700; color: #111827; line-height: 1.2; margin: 0 0 6px; }
.wf-subtitle { font-size: 14px; color: var(--text-description-color); margin: 0; }

/* Grid de plantillas */
.wft-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }

.wft-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); padding: 20px; display: flex; flex-direction: column; gap: 12px; transition: box-shadow .15s; }
.wft-card:hover { box-shadow: var(--shadow-md); }

.wft-icons { display: flex; align-items: center; gap: -4px; }
.wft-icon { width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; border: 2px solid #fff; margin-left: -8px; }
.wft-icon:first-child { margin-left: 0; }
.wft-icons-empty { font-size: 12px; color: #9CA3AF; }

.wft-name { font-size: 16px; font-weight: 600; color: #111827; margin: 0; }
.wft-description { font-size: 13px; color: var(--text-description-color); margin: 0; flex-grow: 1; line-height: 1.5; }

.wft-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 38px; padding: 0 16px; background: var(--button-primary-color); color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 500; font-family: var(--font-family); cursor: pointer; transition: background .2s; width: 100%; }
.wft-btn:hover { background: var(--button-primary-color-hover); }

.wft-empty { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); padding: 48px 20px; text-align: center; }
.wft-empty-inner { display: flex; flex-direction: column; align-items: center; gap: 10px; color: #9CA3AF; }
.wft-empty-inner svg { opacity: .4; }
.wft-empty-inner p { font-size: 14px; margin: 0; }

@media (max-width: 640px) {
    .wf-page { padding: 16px; gap: 16px; }
    .wf-header { flex-direction: column; align-items: stretch; }
    .wft-grid { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
<div class="wf-page">

    @include('admin.workflows.partials._tabs')

    {{-- Header --}}
    <div class="wf-header">
        <div>
            <div class="wf-breadcrumb">
                <span>Panel de Control</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                <span>Automatizaciones</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                <span class="wf-breadcrumb-current">Plantillas</span>
            </div>
            <h1 class="wf-title">Plantillas</h1>
            <p class="wf-subtitle">Workflows listos para usar, específicos de operaciones de Equiterm</p>
        </div>
    </div>

    {{-- Grid de plantillas --}}
    @forelse($workflows as $workflow)
    @if ($loop->first)
    <div class="wft-grid">
    @endif
        <div class="wft-card">
            <div class="wft-icons">
                @forelse($workflow->action_stack as $action)
                    <span class="wft-icon" title="{{ $action['label'] }}" style="background: {{ $action['bg'] }}; color: {{ $action['color'] }};">{{ $action['initials'] }}</span>
                @empty
                    <span class="wft-icons-empty">Sin pasos configurados</span>
                @endforelse
            </div>
            <h2 class="wft-name">{{ $workflow->name }}</h2>
            <p class="wft-description">{{ $workflow->description }}</p>
            <form method="POST" action="{{ route('admin.workflows.duplicate', $workflow) }}">
                @csrf
                <button type="submit" class="wft-btn" onclick="return confirm('¿Clonar esta plantilla como un nuevo workflow editable?')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                    Usar esta plantilla
                </button>
            </form>
        </div>
    @if ($loop->last)
    </div>
    @endif
    @empty
    <div class="wft-empty">
        <div class="wft-empty-inner">
            <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 9h6v6H9z"/></svg>
            <p>Aún no hay plantillas disponibles</p>
        </div>
    </div>
    @endforelse

</div>
@endsection
