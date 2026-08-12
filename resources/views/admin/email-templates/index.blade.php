@extends('admin.layouts.master')

@section('title', 'Plantillas de Correo - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/email-marketing.css'])
@endpush

@section('content')
<div class="et-page">

    {{-- Header --}}
    <div class="et-header">
        <div>
            <div class="et-breadcrumb">
                <span>Panel de Control</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                <span>Marketing por Correo</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                <span class="et-breadcrumb-current">Plantillas</span>
            </div>
            <h1 class="et-title">Plantillas de Correo</h1>
            <p class="et-subtitle">Administra las plantillas HTML usadas en campañas y secuencias de correo</p>
        </div>
        <div>
            @permiso('email-marketing', 'create')
            <a href="{{ route('admin.email-templates.create') }}" class="et-btn-new">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                Nueva plantilla
            </a>
            @endpermiso
        </div>
    </div>

    {{-- Table --}}
    <div class="et-table-card">
        <div class="et-table-scroll">
            <table class="et-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Asunto</th>
                        <th>Tipo</th>
                        <th>Creada por</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($emailTemplates as $emailTemplate)
                    <tr>
                        <td class="et-td-name">{{ $emailTemplate->name }}</td>
                        <td class="et-td-subject" title="{{ $emailTemplate->subject }}">{{ $emailTemplate->subject }}</td>
                        <td><span class="et-type-pill">{{ $emailTemplate->type }}</span></td>
                        <td class="et-td-fecha">{{ $emailTemplate->creator?->name ?? '—' }}</td>
                        <td class="et-td-fecha">{{ $emailTemplate->created_at?->format('d M Y') }}</td>
                        <td>
                            <div class="et-actions">
                                @permiso('email-marketing', 'edit')
                                <a href="{{ route('admin.email-templates.edit', $emailTemplate) }}" class="et-action-btn" title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                </a>
                                @endpermiso
                                @permiso('email-marketing', 'delete')
                                <form method="POST" action="{{ route('admin.email-templates.destroy', $emailTemplate) }}" onsubmit="return confirm('¿Eliminar esta plantilla?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="et-action-btn et-action-btn-delete" title="Eliminar">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                    </button>
                                </form>
                                @endpermiso
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr class="et-empty-row">
                        <td colspan="6">
                            <div class="et-empty-inner">
                                <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h5"/></svg>
                                <p>Todavía no hay plantillas de correo creadas.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($emailTemplates->total() > 0)
        <div class="et-pagination-bar">
            <span class="et-pagination-info">
                Mostrando <strong>{{ $emailTemplates->firstItem() }}-{{ $emailTemplates->lastItem() }}</strong>
                de <strong>{{ $emailTemplates->total() }}</strong> plantillas
            </span>
            {{ $emailTemplates->links('admin.components.pagination') }}
        </div>
        @endif
    </div>

</div>
@endsection
