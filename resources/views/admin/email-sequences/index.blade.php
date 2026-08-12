@extends('admin.layouts.master')

@section('title', 'Secuencias de Correo - Admin')

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

.es-page { padding: 32px; font-family: var(--font-family); display: flex; flex-direction: column; gap: 24px; }

.es-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.es-breadcrumb { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--text-description-color); margin-bottom: 8px; }
.es-breadcrumb svg { flex-shrink: 0; color: #9CA3AF; }
.es-breadcrumb-current { color: #374151; }
.es-title { font-size: 24px; font-weight: 700; color: #111827; line-height: 1.2; margin: 0 0 6px; }
.es-subtitle { font-size: 14px; color: var(--text-description-color); margin: 0; }
.es-header-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; flex-wrap: wrap; }
.es-btn-new { display: inline-flex; align-items: center; gap: 8px; height: 40px; padding: 0 16px; background: var(--button-primary-color); color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 500; font-family: var(--font-family); text-decoration: none; cursor: pointer; box-shadow: var(--shadow-md); transition: background .2s; white-space: nowrap; flex-shrink: 0; }
.es-btn-new:hover { background: var(--button-primary-color-hover); color: #fff; }

.es-table-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); overflow: hidden; }
.es-table-scroll { overflow-x: auto; }
.es-table { width: 100%; border-collapse: collapse; min-width: 820px; }
.es-table thead tr { background: var(--header-footer-color); height: 44px; }
.es-table thead th { padding: 0 20px; text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: var(--text-subwhite-color); white-space: nowrap; }
.es-table tbody tr { height: 56px; border-bottom: 1px solid #F3F4F6; transition: background .15s; }
.es-table tbody tr:last-child { border-bottom: none; }
.es-table tbody tr:hover { background: rgba(255,247,237,.4); }
.es-table td { padding: 0 20px; vertical-align: middle; }
.es-td-name { font-size: 14px; color: #111827; font-weight: 500; }
.es-td-name a { color: inherit; text-decoration: none; }
.es-td-name a:hover { color: var(--secondary-color); text-decoration: underline; }
.es-td-owner { font-size: 13px; color: var(--text-description-color); }
.es-count { font-size: 14px; font-weight: 600; color: #111827; font-variant-numeric: tabular-nums; }
.es-count-label { font-size: 12px; color: var(--text-description-color); font-weight: 400; margin-left: 4px; }
.es-status-pill { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; white-space: nowrap; }
.es-status-active { background: #F0FDF4; color: #15803D; border: 1px solid #BBF7D0; }
.es-status-inactive { background: #F3F4F6; color: #6B7280; border: 1px solid #E5E7EB; }

.es-actions { display: flex; align-items: center; gap: 4px; }
.es-action-btn { width: 32px; height: 32px; border-radius: 6px; border: none; background: transparent; display: inline-flex; align-items: center; justify-content: center; color: var(--text-description-color); text-decoration: none; cursor: pointer; transition: background .15s, color .15s; padding: 0; }
.es-action-btn:hover { background: #F3F4F6; color: var(--secondary-color); }
.es-action-btn-delete:hover { background: #FEF2F2 !important; color: #DC2626 !important; }

.es-empty-row td { padding: 48px 20px; text-align: center; }
.es-empty-inner { display: flex; flex-direction: column; align-items: center; gap: 10px; color: #9CA3AF; }
.es-empty-inner svg { opacity: .4; }
.es-empty-inner p { font-size: 14px; margin: 0; }

@media (max-width: 640px) {
    .es-page { padding: 16px; gap: 16px; }
    .es-header { flex-direction: column; align-items: stretch; }
    .es-btn-new { justify-content: center; }
}
</style>
@endpush

@section('content')
<div class="es-page">

    {{-- Header --}}
    <div class="es-header">
        <div>
            <div class="es-breadcrumb">
                <span>Panel de Control</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                <span class="es-breadcrumb-current">Secuencias de correo</span>
            </div>
            <h1 class="es-title">Secuencias de correo</h1>
            <p class="es-subtitle">Secuencias automáticas de correos escalonados que se envían a los clientes inscritos</p>
        </div>
        <div class="es-header-actions">
            <a href="{{ route('admin.email-sequences.create') }}" class="es-btn-new">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                Nueva secuencia
            </a>
        </div>
    </div>

    @if(session('success'))
    <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;padding:14px 16px;color:#15803D;font-size:13px;">
        {{ session('success') }}
    </div>
    @endif

    {{-- Table card --}}
    <div class="es-table-card">
        <div class="es-table-scroll">
            <table class="es-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Propietario</th>
                        <th>Pasos</th>
                        <th>Inscripciones activas</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($emailSequences as $emailSequence)
                    <tr data-sequence-row="{{ $emailSequence->id }}">
                        <td class="es-td-name">
                            <a href="{{ route('admin.email-sequences.edit', $emailSequence) }}">{{ $emailSequence->name }}</a>
                        </td>
                        <td class="es-td-owner">{{ $emailSequence->owner->name ?? '—' }}</td>
                        <td>
                            <span class="es-count">{{ $emailSequence->steps->count() }}</span>
                            <span class="es-count-label">pasos</span>
                        </td>
                        <td>
                            <span class="es-count">{{ $emailSequence->enrollments()->where('status', 'active')->count() }}</span>
                            <span class="es-count-label">activas</span>
                        </td>
                        <td>
                            @if($emailSequence->is_active)
                                <span class="es-status-pill es-status-active">Activa</span>
                            @else
                                <span class="es-status-pill es-status-inactive">Inactiva</span>
                            @endif
                        </td>
                        <td>
                            <div class="es-actions">
                                <a href="{{ route('admin.email-sequences.edit', $emailSequence) }}" class="es-action-btn" title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                </a>
                                <form method="POST" action="{{ route('admin.email-sequences.destroy', $emailSequence) }}" onsubmit="return confirm('¿Seguro que deseas eliminar la secuencia \'{{ $emailSequence->name }}\'? Esta acción no se puede deshacer.');" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="es-action-btn es-action-btn-delete" title="Eliminar">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr class="es-empty-row">
                        <td colspan="6">
                            <div class="es-empty-inner">
                                <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                <p>Aún no hay secuencias de correo creadas.</p>
                                <a href="{{ route('admin.email-sequences.create') }}" style="font-size:13px;color:#ff6213;text-decoration:none;">Crear la primera secuencia</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
