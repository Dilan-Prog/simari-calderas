@extends('admin.layouts.master')

@section('title', 'Negocios · Kanban - Admin')

@push('styles')
@vite('resources/css/admin/pages/pipeline.css')
@endpush

@section('content')
<div class="pipeline-board-page">

    {{-- ── Header ──────────────────────────────────────────────── --}}
    <div class="pipeline-board-header">
        <div class="pipeline-board-header-left">
            <div class="pipeline-breadcrumb">
                <a href="{{ route('admin.dashboard') }}" class="pipeline-breadcrumb-link">Admin</a>
                <span>/</span>
                <span class="pipeline-breadcrumb-current">Negocios</span>
            </div>
            <h1 class="pipeline-board-title">Negocios</h1>
            <p class="pipeline-board-subtitle">
                Pipeline de ventas — arrastra las tarjetas entre etapas para actualizar su estatus.
            </p>
        </div>

        <div class="pipeline-board-header-right">
            {{-- Selector de pipeline: GET simple, navega al cambiar --}}
            <form method="GET" action="{{ route('admin.deals.index') }}" id="pipelineSelectorForm" class="pipeline-selector-form">
                <label for="pipelineSelect" class="pipeline-selector-label">Pipeline</label>
                <div class="pipeline-selector-select-wrap">
                    <select
                        name="pipeline_id"
                        id="pipelineSelect"
                        class="pipeline-selector-select"
                        onchange="document.getElementById('pipelineSelectorForm').submit()"
                    >
                        @forelse($pipelines as $p)
                            <option value="{{ $p->id }}" @selected($pipeline && $pipeline->id === $p->id)>
                                {{ $p->name }}@if($p->is_default) (predeterminado) @endif
                            </option>
                        @empty
                            <option value="">Sin pipelines configurados</option>
                        @endforelse
                    </select>
                </div>
            </form>

            <a href="{{ route('admin.deals.table') }}" class="pipeline-btn-secondary">
                Ver tabla
            </a>

            <a href="{{ route('admin.deals.create') }}" class="pipeline-btn-new">
                + Nuevo negocio
            </a>
        </div>
    </div>

    {{-- ── Board ───────────────────────────────────────────────── --}}
    @if($pipeline && $stages->isNotEmpty())
        <div
            class="pipeline-board"
            id="pipelineBoard"
            data-pipeline-id="{{ $pipeline->id }}"
            data-move-stage-url-template="{{ route('admin.deals.move-stage', ['deal' => '__DEAL_ID__']) }}"
        >
            @foreach($stages as $stage)
                @php
                    $stageDeals = $dealsByStage->get($stage->id, collect());
                    $stageTotal = $stageDeals->sum('amount');
                @endphp

                <div class="pipeline-column" data-stage-id="{{ $stage->id }}">
                    <div class="pipeline-column-header">
                        <div class="pipeline-column-title-row">
                            <span class="pipeline-column-title">{{ $stage->name }}</span>
                            <span class="pipeline-column-probability">{{ (int) $stage->probability }}%</span>
                        </div>
                        <div class="pipeline-column-stats">
                            <span class="pipeline-column-count" data-column-count>{{ $stageDeals->count() }} negocio(s)</span>
                            <span class="pipeline-column-total" data-column-total>${{ number_format($stageTotal, 2) }}</span>
                        </div>
                    </div>

                    {{-- Contenedor sortable: el JS de drag&drop se inicializa sobre este nodo --}}
                    <div class="pipeline-column-list" data-stage-id="{{ $stage->id }}">
                        @forelse($stageDeals as $deal)
                            <div class="pipeline-card" data-deal-id="{{ $deal->id }}" data-amount="{{ $deal->amount }}">
                                <a href="{{ route('admin.deals.show', $deal) }}" class="pipeline-card-link">
                                    <div class="pipeline-card-top">
                                        <span class="pipeline-card-folio">{{ $deal->folio }}</span>

                                        @if($deal->owner)
                                            @php
                                                $ownerInitials = strtoupper(
                                                    mb_substr($deal->owner->first_name ?? '', 0, 1)
                                                    . mb_substr($deal->owner->last_name ?? '', 0, 1)
                                                );
                                            @endphp
                                            <span class="pipeline-card-owner-avatar" title="{{ $deal->owner->full_name }}">
                                                {{ $ownerInitials ?: '?' }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="pipeline-card-name">{{ $deal->name }}</div>

                                    <div class="pipeline-card-customer">
                                        @if($deal->customer)
                                            {{ trim($deal->customer->first_name . ' ' . $deal->customer->last_name) }}
                                            @if($deal->customer->company)
                                                <span class="pipeline-card-company">({{ $deal->customer->company }})</span>
                                            @endif
                                        @elseif($deal->company_snapshot)
                                            {{ $deal->company_snapshot }}
                                        @else
                                            <span class="pipeline-card-no-customer">Sin cliente asignado</span>
                                        @endif
                                    </div>

                                    <div class="pipeline-card-bottom">
                                        <span class="pipeline-card-amount">
                                            {{ $deal->currency ?? 'MXN' }} ${{ number_format($deal->amount ?? 0, 2) }}
                                        </span>
                                    </div>
                                </a>
                            </div>
                        @empty
                            <div class="pipeline-column-empty">Sin negocios en esta etapa</div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    @elseif($pipeline)
        <div class="pipeline-empty-state">
            Este pipeline no tiene etapas configuradas todavía.
        </div>
    @else
        <div class="pipeline-empty-state">
            No hay pipelines configurados. Crea uno para empezar a gestionar negocios.
        </div>
    @endif

</div>
@endsection

@push('scripts')
@vite('resources/js/admin/pipeline-board.js')
@endpush
