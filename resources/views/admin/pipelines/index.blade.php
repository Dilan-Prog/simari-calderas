@extends('admin.layouts.master')
@push('styles')
    @vite('resources/css/admin/pages/pipelines.css')
@endpush
@section('title')
    Pipelines - Admin
@endsection
@section('content')
    <div class="container user-manager">
        <section class="clients-manager-section">

            {{-- Header --}}
            <header class="clients-manager-main" style="margin-bottom:4px;">
                <div>
                    <p class="breadcrumb-clients-manager main" style="margin-bottom:4px;">
                        Panel de Control &gt; Pipelines
                    </p>
                    <h1>Pipelines</h1>
                    <p class="breadcrumb-clients-manager main">Administra los embudos de ventas y sus etapas</p>
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <button type="button" class="button-primary size-adjustment" id="btnNewPipeline">
                        + Nuevo pipeline
                    </button>
                </div>
            </header>

            {{-- Lista de pipelines --}}
            <main style="margin-top:20px;">
                @forelse ($pipelines as $pipeline)
                    @php
                        $stagesCount = $pipeline->stages->count();
                        $dealsCount = $pipeline->deals()->count();
                    @endphp
                    <div class="pipeline-card" data-id="{{ $pipeline->id }}">
                        <div class="pipeline-card-header">
                            <div class="pipeline-card-heading">
                                <h2 class="pipeline-card-title">{{ $pipeline->name }}</h2>
                                <div class="pipeline-card-badges">
                                    <span
                                        class="brand-status-badge {{ $pipeline->channel === \App\Models\Pipeline::CHANNEL_WHATSAPP ? 'status-active' : 'status-inactive' }}">
                                        {{ $pipeline->channel === \App\Models\Pipeline::CHANNEL_WHATSAPP ? 'Embudo de Venta (WhatsApp)' : 'Negocios' }}
                                    </span>
                                    @if ($pipeline->is_default)
                                        <span class="brand-status-badge status-active">Predeterminado</span>
                                    @endif
                                    <span
                                        class="brand-status-badge {{ $pipeline->is_active ? 'status-active' : 'status-inactive' }}">
                                        {{ $pipeline->is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </div>
                            </div>
                            <div class="pipeline-card-meta">
                                <span class="pipeline-card-meta-item">{{ $stagesCount }} {{ Str::plural('etapa', $stagesCount) }}</span>
                                <span class="pipeline-card-meta-item">{{ $dealsCount }} {{ Str::plural('negocio', $dealsCount) }}</span>
                            </div>
                            <div class="pipeline-card-actions">
                                <button type="button" class="action-btn btn-toggle-stages" data-id="{{ $pipeline->id }}"
                                    title="Ver etapas">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="m6 9 6 6 6-6" />
                                    </svg>
                                </button>
                                <button type="button" class="action-btn btn-edit-pipeline" data-id="{{ $pipeline->id }}"
                                    data-name="{{ $pipeline->name }}" data-is-default="{{ $pipeline->is_default ? 1 : 0 }}"
                                    data-is-active="{{ $pipeline->is_active ? 1 : 0 }}" data-channel="{{ $pipeline->channel }}"
                                    title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path
                                            d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
                                    </svg>
                                </button>
                                <button type="button" class="action-btn btn-delete-pipeline" data-id="{{ $pipeline->id }}"
                                    data-name="{{ $pipeline->name }}" title="Eliminar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M3 6h18" />
                                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                                        <line x1="10" x2="10" y1="11" y2="17" />
                                        <line x1="14" x2="14" y1="11" y2="17" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Panel expandible de etapas --}}
                        <div class="pipeline-stages-panel" id="pipelineStagesPanel-{{ $pipeline->id }}" style="display:none;">
                            @if ($stagesCount > 0)
                                <div class="table-scroll">
                                    <table class="clients-manager-table pipeline-stages-table">
                                        <thead>
                                            <tr>
                                                <th>ORDEN</th>
                                                <th>ETAPA</th>
                                                <th>PROBABILIDAD</th>
                                                <th>TIPO</th>
                                                <th>NEGOCIOS</th>
                                                <th>ACCIONES</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($pipeline->stages as $stage)
                                                <tr>
                                                    <td>{{ $stage->order }}</td>
                                                    <td>{{ $stage->name }}</td>
                                                    <td>{{ $stage->probability !== null ? $stage->probability . '%' : '—' }}</td>
                                                    <td>
                                                        @if ($stage->is_won)
                                                            <span class="brand-status-badge status-active">Ganada</span>
                                                        @elseif ($stage->is_lost)
                                                            <span class="brand-status-badge status-inactive">Perdida</span>
                                                        @else
                                                            <span class="pipeline-stage-type-normal">Normal</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $stage->deals()->count() }}</td>
                                                    <td>
                                                        <div class="actions-container">
                                                            <button type="button" class="action-btn btn-delete-stage"
                                                                data-id="{{ $stage->id }}" data-name="{{ $stage->name }}"
                                                                data-pipeline-id="{{ $pipeline->id }}" title="Eliminar etapa">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                    stroke-width="2" stroke-linecap="round"
                                                                    stroke-linejoin="round">
                                                                    <path d="M3 6h18" />
                                                                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                                                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                                                                    <line x1="10" x2="10" y1="11" y2="17" />
                                                                    <line x1="14" x2="14" y1="11" y2="17" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="pipeline-no-stages">Este pipeline no tiene etapas configuradas.</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="table-container-clients-manager">
                        <p style="text-align:center; padding:40px; color:#6b7280;">
                            No hay pipelines registrados.
                        </p>
                    </div>
                @endforelse
            </main>
        </section>
    </div>
@endsection
@include('admin.pipelines.partials._modal_create')
@include('admin.pipelines.partials._scripts')
