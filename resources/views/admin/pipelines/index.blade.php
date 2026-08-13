{{--
    Pipelines — Admin ERP (rediseño sobre mockup "Pipelines Admin.dc.html").
    Alcance recortado: solo 2 tipos/canales — Negocios (channel=deals) y
    Chats (channel=whatsapp). Sin "Servicios", sin Descripción/Equipo
    responsable/Días de inactividad.

    Contrato de datos real (PipelineController::index()):
      - $pipelines  Collection<Pipeline> con ->stages cargadas
      - $kpis       ['pipelines_total','pipelines_activos','etapas_total','negocios_total','valor_ponderado_total']
      - Por pipeline: ->etapas_count, ->negocios_count, ->valor_ponderado (formateado
          "$1,234"), ->tiene_negocios, ->reassign_targets ([{id,name}] mismo channel)
      - Por stage: ->negocios_count, ->valor_bruto (formateado o null en whatsapp),
          ->valor_ponderado (formateado)

    Todo se accede de forma defensiva (?? / optional()) por si el backend
    paralelo aún no expone algún atributo mientras ambas mitades integran.
--}}
@extends('admin.layouts.master')

@section('title', 'Pipelines - Admin')

@push('styles')
<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700;800&display=swap" media="print" onload="this.media='all'">
@vite('resources/css/admin/pages/pipelines.css')
@endpush

@php
    use App\Models\Pipeline;

    $pipelines = $pipelines ?? collect();

    $CHANNEL_META = [
        Pipeline::CHANNEL_DEALS => ['label' => 'Negocios', 'class' => 'pp-kind-deals'],
        Pipeline::CHANNEL_WHATSAPP => ['label' => 'Chats', 'class' => 'pp-kind-whatsapp'],
    ];

    $fmtMoney = function ($n) {
        return '$' . number_format((float) $n, 0, '.', ',');
    };

    $countByChannel = [
        'todos' => $pipelines->count(),
        Pipeline::CHANNEL_DEALS => $pipelines->where('channel', Pipeline::CHANNEL_DEALS)->count(),
        Pipeline::CHANNEL_WHATSAPP => $pipelines->where('channel', Pipeline::CHANNEL_WHATSAPP)->count(),
    ];

    // Contrato real del controller (PipelineController::index()): $kpis
    // trae claves en español; cada $pipeline trae etapas_count/negocios_count/
    // valor_ponderado (ya formateado "$1,234")/tiene_negocios/reassign_targets,
    // y cada stage trae negocios_count/valor_bruto (formateado o null en
    // whatsapp)/valor_ponderado (formateado). Accedido de forma defensiva
    // por si el backend paralelo aún no expone algo mientras integran.
    $kpis = $kpis ?? [];
    $kpiTotal = $kpis['pipelines_total'] ?? $pipelines->count();
    $kpiActive = $kpis['pipelines_activos'] ?? $pipelines->where('is_active', true)->count();
    $kpiStages = $kpis['etapas_total'] ?? $pipelines->sum(fn ($p) => $p->stages->count());
    $kpiDeals = $kpis['negocios_total'] ?? $pipelines->sum(fn ($p) => $p->negocios_count ?? 0);
    $kpiWeightedFmt = $kpis['valor_ponderado_total'] ?? $fmtMoney($pipelines->sum(fn ($p) => (float) str_replace(['$', ','], '', $p->valor_ponderado ?? 0)));

    $tabs = [
        'todos' => 'Todos',
        Pipeline::CHANNEL_DEALS => 'Negocios',
        Pipeline::CHANNEL_WHATSAPP => 'Chats',
    ];

    $stageColors = ['#6b7280', '#b45309', '#0f6fbd', '#6d28d9', '#0f7a4f', '#c81e1e', '#0e7490', '#8a6d1f'];
@endphp

@section('content')
<div class="pipelines-page" id="pipelinesPage">

    {{-- Breadcrumb + header --}}
    <div class="pp-breadcrumb">
        <a href="{{ route('admin.dashboard') ?? '#' }}">Panel de Control</a>
        <span class="pp-breadcrumb-sep">&rsaquo;</span>
        <span class="pp-breadcrumb-current">Pipelines</span>
    </div>

    <div class="pp-header">
        <div>
            <h1 class="pp-title">Pipelines</h1>
            <p class="pp-subtitle">Administra los embudos de negocios y chats, sus etapas y las reglas de cada una.</p>
        </div>
        <div class="pp-header-actions">
            <button type="button" class="pp-btn pp-btn-secondary" id="ppOpenTemplates">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="7" height="16" rx="1.5"/><rect x="14" y="4" width="7" height="10" rx="1.5"/></svg>
                Plantillas
            </button>
            <button type="button" class="pp-btn pp-btn-primary" id="ppOpenCreate">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
                Nuevo pipeline
            </button>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="pp-kpis">
        <div class="pp-kpi-card">
            <div class="pp-kpi-label"><span class="pp-kpi-dot" style="background:#141516;"></span>Pipelines</div>
            <div class="pp-kpi-row"><span class="pp-kpi-value">{{ $kpiTotal }}</span><span class="pp-kpi-sub">{{ $kpiActive }} activos</span></div>
        </div>
        <div class="pp-kpi-card">
            <div class="pp-kpi-label"><span class="pp-kpi-dot" style="background:#0f6fbd;"></span>Etapas configuradas</div>
            <div class="pp-kpi-row"><span class="pp-kpi-value">{{ $kpiStages }}</span><span class="pp-kpi-sub">en todos los embudos</span></div>
        </div>
        <div class="pp-kpi-card">
            <div class="pp-kpi-label"><span class="pp-kpi-dot" style="background:#6d28d9;"></span>Negocios en curso</div>
            <div class="pp-kpi-row"><span class="pp-kpi-value">{{ $kpiDeals }}</span><span class="pp-kpi-sub">asignados a una etapa</span></div>
        </div>
        <div class="pp-kpi-card">
            <div class="pp-kpi-label"><span class="pp-kpi-dot" style="background:#0f7a4f;"></span>Valor ponderado</div>
            <div class="pp-kpi-row"><span class="pp-kpi-value pp-kpi-value-success">{{ $kpiWeightedFmt }}</span><span class="pp-kpi-sub">suma de embudos</span></div>
        </div>
    </div>

    <div class="pp-panel">

        {{-- Tabs --}}
        <div class="pp-tabs" id="ppTabs">
            @foreach ($tabs as $key => $label)
                <button type="button" class="pp-tab {{ $key === 'todos' ? 'pp-tab-active' : '' }}" data-tab="{{ $key }}">
                    {{ $label }}
                    <span class="pp-tab-badge">{{ $countByChannel[$key] ?? 0 }}</span>
                </button>
            @endforeach
        </div>

        {{-- Toolbar --}}
        <div class="pp-toolbar">
            <div class="pp-search-field">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3" stroke-linecap="round"/></svg>
                <input type="text" id="ppSearch" class="pp-search-input" placeholder="Buscar pipeline o etapa…" autocomplete="off">
            </div>
            <select id="ppEstado" class="pp-select">
                <option value="todos">Activos e inactivos</option>
                <option value="activo">Solo activos</option>
                <option value="inactivo">Solo inactivos</option>
            </select>
            <button type="button" class="pp-btn pp-btn-secondary" id="ppToggleAll">Expandir todo</button>
        </div>

        {{-- Lista de pipelines --}}
        <div id="ppRows">
            @forelse ($pipelines as $pipeline)
                @php
                    $meta = $CHANNEL_META[$pipeline->channel] ?? ['label' => $pipeline->channel, 'class' => ''];
                    $stages = $pipeline->stages ?? collect();
                    $stagesCount = $pipeline->etapas_count ?? $stages->count();
                    $dealsCount = $pipeline->negocios_count ?? $stages->sum(fn ($s) => $s->negocios_count ?? 0);
                    $weightedFmt = $pipeline->valor_ponderado ?? $fmtMoney(0);
                    $hasDeals = $pipeline->tiene_negocios ?? ($dealsCount > 0);
                    $reassignTargets = $pipeline->reassign_targets ?? $pipelines->where('channel', $pipeline->channel)->reject(fn ($p) => $p->id === $pipeline->id)->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values();
                    $maxStageDeals = max(1, $stages->max(fn ($s) => $s->negocios_count ?? 0) ?? 1);
                    $searchBlob = mb_strtolower($pipeline->name . ' ' . $stages->pluck('name')->implode(' '));
                    $stagesForEdit = $stages->map(function ($s) {
                        return [
                            'id' => $s->id,
                            'name' => $s->name,
                            'probability' => (int) ($s->probability ?? 0),
                            'is_won' => (bool) $s->is_won,
                            'is_lost' => (bool) $s->is_lost,
                            'wip_limit' => $s->wip_limit,
                        ];
                    })->values();
                    $targetsForDelete = collect($reassignTargets)->map(function ($t) {
                        return is_array($t) ? $t : ['id' => $t->id, 'name' => $t->name];
                    })->values();
                @endphp
                <div class="pp-row"
                     data-pipeline-row="{{ $pipeline->id }}"
                     data-channel="{{ $pipeline->channel }}"
                     data-active="{{ $pipeline->is_active ? '1' : '0' }}"
                     data-search="{{ $searchBlob }}">

                    <div class="pp-row-head">
                        <div class="pp-row-icon {{ $meta['class'] }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18l-6 7v6l-6-2v-4z" stroke-linejoin="round"/></svg>
                        </div>
                        <div class="pp-row-main">
                            <div class="pp-row-name-line">
                                <span class="pp-row-name">{{ $pipeline->name }}</span>
                                <span class="pp-badge {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                                @if ($pipeline->is_default)
                                    <span class="pp-badge pp-badge-default">Predeterminado</span>
                                @endif
                                <span class="pp-badge {{ $pipeline->is_active ? 'pp-badge-active' : 'pp-badge-inactive' }}">
                                    {{ $pipeline->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </div>
                        </div>
                        <div class="pp-row-stats">
                            <div class="pp-row-stat">
                                <div class="pp-row-stat-label">Etapas</div>
                                <div class="pp-row-stat-value">{{ $stagesCount }}</div>
                            </div>
                            <div class="pp-row-stat">
                                <div class="pp-row-stat-label">Negocios</div>
                                <div class="pp-row-stat-value">{{ $dealsCount }}</div>
                            </div>
                            <div class="pp-row-stat pp-row-stat-wide">
                                <div class="pp-row-stat-label">Valor ponderado</div>
                                <div class="pp-row-stat-value">{{ $weightedFmt }}</div>
                            </div>
                        </div>
                        <div class="pp-row-actions">
                            <button type="button" class="pp-icon-btn pp-btn-toggle" title="Ver etapas" data-id="{{ $pipeline->id }}">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="pp-caret"><path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <button type="button" class="pp-icon-btn pp-btn-edit" title="Editar"
                                data-id="{{ $pipeline->id }}"
                                data-name="{{ $pipeline->name }}"
                                data-channel="{{ $pipeline->channel }}"
                                data-is-default="{{ $pipeline->is_default ? 1 : 0 }}"
                                data-is-active="{{ $pipeline->is_active ? 1 : 0 }}"
                                data-deals-count="{{ $dealsCount }}"
                                data-stages='@json($stagesForEdit)'>
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4v16h16v-7M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <button type="button" class="pp-icon-btn pp-btn-duplicate" title="Duplicar" data-id="{{ $pipeline->id }}">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M15 5H6a1 1 0 00-1 1v9" stroke-linecap="round"/></svg>
                            </button>
                            <button type="button" class="pp-icon-btn pp-icon-btn-danger pp-btn-delete" title="Eliminar"
                                data-id="{{ $pipeline->id }}"
                                data-name="{{ $pipeline->name }}"
                                data-channel="{{ $pipeline->channel }}"
                                data-deals-count="{{ $dealsCount }}"
                                data-has-deals="{{ $hasDeals ? 1 : 0 }}"
                                data-etapas-count="{{ $stagesCount }}"
                                data-targets='@json($targetsForDelete)'>
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4h8v2m1 0v14H7V6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="pp-row-panel" id="ppPanel-{{ $pipeline->id }}" style="display:none;">
                        @if ($stagesCount > 0)
                            <div class="pp-funnel">
                                @foreach ($stages as $i => $stage)
                                    @php
                                        $sDeals = $stage->negocios_count ?? 0;
                                        $h = max(16, (int) round(78 * ($sDeals / $maxStageDeals)));
                                        $color = $stageColors[$i % count($stageColors)];
                                    @endphp
                                    <div class="pp-funnel-col">
                                        <div class="pp-funnel-bar" style="height:{{ $h }}px;background:{{ $color }}22;border-bottom-color:{{ $color }};"></div>
                                        <div class="pp-funnel-name">{{ $stage->name }}</div>
                                        <div class="pp-funnel-meta">{{ $sDeals }} · {{ (int) ($stage->probability ?? 0) }}%</div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="pp-stages-card">
                                <div class="pp-stages-table-scroll">
                                    <table class="pp-stages-table">
                                        <thead>
                                            <tr>
                                                <th class="pp-col-order">Orden</th>
                                                <th>Etapa</th>
                                                <th class="pp-col-prob">Probabilidad</th>
                                                <th class="pp-col-tipo">Tipo</th>
                                                <th class="pp-col-right">Negocios</th>
                                                <th class="pp-col-right">Valor</th>
                                                <th class="pp-col-right">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody data-pipeline-id="{{ $pipeline->id }}">
                                            @foreach ($stages as $i => $stage)
                                                @php
                                                    $color = $stageColors[$i % count($stageColors)];
                                                    $prob = (int) ($stage->probability ?? 0);
                                                    $tipo = $stage->is_won ? 'ganada' : ($stage->is_lost ? 'perdida' : 'normal');
                                                    $tipoLabel = ['normal' => 'Normal', 'ganada' => 'Ganada', 'perdida' => 'Perdida'][$tipo];
                                                    $sDeals = $stage->negocios_count ?? 0;
                                                    $sValue = $stage->valor_bruto ?? '—';
                                                @endphp
                                                <tr data-stage-id="{{ $stage->id }}">
                                                    <td class="pp-col-order">{{ $i }}</td>
                                                    <td>
                                                        <span class="pp-stage-dot" style="background:{{ $color }};"></span>
                                                        <span class="pp-stage-name">{{ $stage->name }}</span>
                                                        @if (($stage->wip_limit ?? null) !== null)
                                                            <span class="pp-wip-badge">WIP {{ $stage->wip_limit }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="pp-col-prob">
                                                        <div class="pp-prob-track"><div class="pp-prob-fill" style="width:{{ $prob }}%;background:{{ $color }};"></div></div>
                                                        <span class="pp-prob-value">{{ $prob }}%</span>
                                                    </td>
                                                    <td class="pp-col-tipo"><span class="pp-tipo-badge pp-tipo-{{ $tipo }}">{{ $tipoLabel }}</span></td>
                                                    <td class="pp-col-right">{{ $sDeals }}</td>
                                                    <td class="pp-col-right">{{ $sValue }}</td>
                                                    <td class="pp-col-right">
                                                        <div class="pp-stage-move-actions">
                                                            <button type="button" class="pp-mini-btn pp-btn-stage-up" title="Subir" data-pipeline-id="{{ $pipeline->id }}" data-stage-id="{{ $stage->id }}">
                                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 15l6-6 6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                            </button>
                                                            <button type="button" class="pp-mini-btn pp-btn-stage-down" title="Bajar" data-pipeline-id="{{ $pipeline->id }}" data-stage-id="{{ $stage->id }}">
                                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="pp-stages-footer">
                                    <button type="button" class="pp-link-btn pp-btn-edit-link" data-id="{{ $pipeline->id }}">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
                                        Agregar o reordenar etapas
                                    </button>
                                    <div class="pp-stages-footer-note">Las etapas de tipo Ganada y Perdida cierran el negocio y no admiten siguiente etapa.</div>
                                </div>
                            </div>
                        @else
                            <p class="pp-no-stages">Este pipeline no tiene etapas configuradas.</p>
                        @endif
                    </div>
                </div>
            @empty
            @endforelse
        </div>

        {{-- Estado vacío: sin pipelines en absoluto --}}
        @if ($pipelines->isEmpty())
            <div class="pp-empty" id="ppEmptyAbsolute">
                <svg width="92" height="92" viewBox="0 0 96 96" fill="none">
                    <path d="M16 26h64l-22 26v20l-20-7V52z" stroke="#d5d8dc" stroke-width="2.5" stroke-linejoin="round"/>
                    <circle cx="72" cy="66" r="14" fill="#ffffff" stroke="var(--secondary-color)" stroke-width="2.5"/>
                    <path d="M72 60v12M66 66h12" stroke="var(--secondary-color)" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
                <div class="pp-empty-title">Todavía no hay pipelines</div>
                <div class="pp-empty-text">Crea el primer embudo para empezar a mover negocios entre etapas.</div>
                <div class="pp-empty-actions">
                    <button type="button" class="pp-btn pp-btn-primary" id="ppEmptyTemplateBtn">Empezar con una plantilla</button>
                </div>
            </div>
        @endif

        {{-- Estado vacío: sin resultados de filtro (oculto por defecto, JS lo muestra) --}}
        <div class="pp-empty" id="ppEmptyFiltered" style="display:none;">
            <svg width="92" height="92" viewBox="0 0 96 96" fill="none">
                <path d="M16 26h64l-22 26v20l-20-7V52z" stroke="#d5d8dc" stroke-width="2.5" stroke-linejoin="round"/>
                <circle cx="72" cy="66" r="14" fill="#ffffff" stroke="var(--secondary-color)" stroke-width="2.5"/>
                <path d="M72 60v12M66 66h12" stroke="var(--secondary-color)" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
            <div class="pp-empty-title">Ningún pipeline coincide</div>
            <div class="pp-empty-text">Prueba con otro nombre de etapa, cambia de pestaña o quita el filtro de estado.</div>
            <div class="pp-empty-actions">
                <button type="button" class="pp-btn pp-btn-secondary" id="ppClearFilters">Limpiar filtros</button>
                <button type="button" class="pp-btn pp-btn-primary" id="ppEmptyTemplateBtn2">Empezar con una plantilla</button>
            </div>
        </div>

        @if ($pipelines->isNotEmpty())
            <div class="pp-count-label" id="ppCountLabel">Mostrando {{ $pipelines->count() }} de {{ $pipelines->count() }} pipelines</div>
        @endif
    </div>
</div>

@include('admin.pipelines.partials._modal_editor', ['pipelines' => $pipelines, 'CHANNEL_META' => $CHANNEL_META])
@include('admin.pipelines.partials._modal_templates')
@include('admin.pipelines.partials._modal_delete', ['pipelines' => $pipelines])

@endsection

@push('scripts')
@vite(['resources/js/admin/pipelines-index.js'])
@endpush
