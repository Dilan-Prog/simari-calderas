{{--
    Rediseño de Negocios (Deals) — Fase 14 del plan CRM.
    Reemplaza board.blade.php + el index.blade.php de tabla anterior por
    una experiencia unificada: Kanban / Lista / Tabla / Forecast, todo
    renderizado client-side (deals-board.js) a partir de un único payload
    JSON que este Blade arma en un solo request.

    Contrato de variables que este view espera del controller (Fase 14,
    parte backend — DealController::index() fusionado):
      - $pipelines        Collection<Pipeline>  pipelines de canal "deals"
      - $pipeline         Pipeline|null          pipeline seleccionado
      - $stages           Collection<PipelineStage> etapas de $pipeline, ordenadas
      - $deals            Collection<Deal>       TODOS los deals de $pipeline
                           (abiertos y cerrados), con stage/owner/customer/tags
                           precargados vía with() para no disparar N+1 aquí
      - $owners           Collection<User>
      - $tags             Collection<Tag>|null   (Fase 14 — puede no existir aún)

    Cada variable se accede de forma defensiva (isset/optional/relationLoaded)
    para que la vista no truene si el backend paralelo todavía no expone
    alguna de ellas mientras ambas mitades de la fase terminan de integrarse.
--}}
@extends('admin.layouts.master')

@section('title', 'Negocios - Admin')

@push('styles')
<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700;800&display=swap" media="print" onload="this.media='all'">
@vite('resources/css/admin/pages/deals.css')
@endpush

@php
    $pipelines = $pipelines ?? collect();
    $pipeline  = $pipeline ?? null;
    $stages    = $stages ?? collect();
    $deals     = $deals ?? collect();
    $owners    = $owners ?? collect();
    $tags      = $tags ?? collect();

    $stagesPayload = $stages->map(function ($s) {
        return [
            'id'          => $s->id,
            'name'        => $s->name,
            'probability' => (int) ($s->probability ?? 0),
            'is_won'      => (bool) ($s->is_won ?? false),
            'is_lost'     => (bool) ($s->is_lost ?? false),
            'wip_limit'   => $s->wip_limit ?? null,
            'order'       => $s->order ?? 0,
        ];
    })->values();

    $ownersPayload = $owners->map(function ($o) {
        return ['id' => $o->id, 'name' => $o->name ?? trim(($o->first_name ?? '') . ' ' . ($o->last_name ?? ''))];
    })->values();

    $tagsPayload = $tags->map(function ($t) {
        return ['id' => $t->id, 'name' => $t->name, 'color' => $t->color ?? '#6b7280'];
    })->values();

    $dealsPayload = $deals->map(function ($d) {
        $tagsRel = method_exists($d, 'relationLoaded') && $d->relationLoaded('tags') ? $d->tags : collect();
        $customerName = null;
        $company = $d->company_snapshot ?? null;
        $email = $d->contact_snapshot_email ?? null;
        $phone = $d->contact_snapshot_phone ?? null;

        if ($d->customer) {
            $customerName = trim(($d->customer->first_name ?? '') . ' ' . ($d->customer->last_name ?? '')) ?: null;
            $company = $d->customer->company ?? $company;
            $email = $d->customer->email ?? $email;
            $phone = $d->customer->phone ?? $phone;
        } else {
            $customerName = $d->contact_snapshot_name ?? null;
        }

        return [
            'id'                   => $d->id,
            'folio'                => $d->folio,
            'name'                 => $d->name,
            'amount'               => $d->amount !== null ? (float) $d->amount : null,
            'currency'             => $d->currency ?? 'MXN',
            'status'               => $d->status,
            'source'               => $d->source,
            'pipeline_id'          => $d->pipeline_id,
            'stage_id'             => $d->pipeline_stage_id,
            'stage_name'           => $d->stage->name ?? null,
            'probability'          => (int) ($d->stage->probability ?? 0),
            'owner_id'             => $d->owner_id,
            'owner_name'           => $d->owner->name ?? null,
            'customer_id'          => $d->customer_id,
            'customer_name'        => $customerName,
            'company'              => $company,
            'contact_email'        => $email,
            'contact_phone'        => $phone,
            'expected_close_date'  => optional($d->expected_close_date)->format('Y-m-d'),
            'closed_at'            => optional($d->closed_at)->format('Y-m-d'),
            'created_at'           => optional($d->created_at)->toIso8601String(),
            'updated_at'           => optional($d->updated_at)->toIso8601String(),
            'lost_reason'          => $d->lost_reason,
            'tags'                 => $tagsRel->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'color' => $t->color ?? '#6b7280'])->values(),
        ];
    })->values();

    $bootPayload = [
        'currentPipelineId' => $pipeline->id ?? null,
        'stages'  => $stagesPayload,
        'owners'  => $ownersPayload,
        'tags'    => $tagsPayload,
        'deals'   => $dealsPayload,
        'csrf'    => csrf_token(),
        'urls'    => [
            // Ya existente (Fase 1) — se usa vía Blade route() para que
            // siga funcionando aunque cambie el nombre interno del prefijo.
            'moveStageTemplate'   => route('admin.deals.move-stage', ['deal' => '__DEAL_ID__']),
            'editTemplate'        => route('admin.deals.edit', ['deal' => '__DEAL_ID__']),
            // Contrato explícito de esta fase (backend paralelo construye
            // exactamente estas rutas/formas de payload — ver plan Fase 14):
            'dealDetailTemplate'  => url('/admin/negocios/__DEAL_ID__'),
            'bulkAction'          => url('/admin/negocios/accion-masiva'),
            'export'              => url('/admin/negocios/exportar'),
            'tagsBase'            => url('/admin/etiquetas-negocio'),
            // Quick action "WhatsApp" de la tarjeta (Fase 14 ↔ Fase 13):
            // busca/crea la WhatsappConversation del deal y redirige al
            // Embudo de Venta, que abre su propio modal de chat existente.
            'whatsappFromDealTemplate' => route('admin.whatsapp-funnel.from-deal', ['deal' => '__DEAL_ID__']),
        ],
    ];
@endphp

@section('content')
<div id="deals-hub-root" data-theme="light">

    {{-- ── Header sticky ──────────────────────────────────────── --}}
    <header class="dh-header">
        <div class="dh-header-row1">
            <div class="dh-header-left">
                <div>
                    <div class="dh-breadcrumb">Panel de Control / Negocios</div>
                    <h1 class="dh-title">Gestión de Negocios</h1>
                </div>

                <form method="GET" action="{{ route('admin.deals.index') }}" id="dhPipelineForm">
                    <div class="dh-pipeline-select-wrap">
                        <select name="pipeline_id" class="dh-select" onchange="document.getElementById('dhPipelineForm').submit()">
                            @forelse($pipelines as $p)
                                <option value="{{ $p->id }}" @selected($pipeline && $pipeline->id === $p->id)>
                                    {{ $p->name }}@if($p->is_default ?? false) (predeterminado) @endif
                                </option>
                            @empty
                                <option value="">Sin pipelines configurados</option>
                            @endforelse
                        </select>
                        <span class="dh-select-chevron">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                        </span>
                    </div>
                </form>

                <div class="dh-search-wrap">
                    <span class="dh-search-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </span>
                    <input type="text" id="dhSearch" class="dh-search-input" placeholder="Buscar por nombre, folio o cliente…" autocomplete="off">
                </div>
            </div>

            <div class="dh-header-right">
                <div class="dh-view-toggle">
                    <button type="button" class="dh-view-btn dh-view-btn-active" data-view-btn="kanban">Kanban</button>
                    <button type="button" class="dh-view-btn" data-view-btn="lista">Lista</button>
                    <button type="button" class="dh-view-btn" data-view-btn="tabla">Tabla</button>
                    <button type="button" class="dh-view-btn" data-view-btn="forecast">Forecast</button>
                </div>

                <button type="button" class="dh-btn dh-btn-icon" id="dhThemeToggle" title="Cambiar tema">
                    <svg class="dh-icon-moon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                    <svg class="dh-icon-sun" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                </button>

                <button type="button" class="dh-btn" id="dhManageTagsBtn">Etiquetas</button>
                <button type="button" class="dh-btn" id="dhExportBtn">Exportar</button>

                <a href="{{ route('admin.deals.create') }}" class="dh-btn dh-btn-primary">+ Nuevo negocio</a>
            </div>
        </div>

        {{-- Filtros rápidos --}}
        <div class="dh-filters-row">
            <span class="dh-filter-label">Filtros</span>

            <select id="dhFilterOwner" class="dh-select">
                <option value="">Todos los responsables</option>
                @foreach($owners as $owner)
                    <option value="{{ $owner->id }}">{{ $owner->name }}</option>
                @endforeach
            </select>

            <select id="dhFilterTag" class="dh-select">
                <option value="">Todas las etiquetas</option>
                @foreach($tags as $tag)
                    <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                @endforeach
            </select>

            <input type="date" id="dhFilterDateFrom" class="dh-input" title="Cierre desde">
            <input type="date" id="dhFilterDateTo" class="dh-input" title="Cierre hasta">
            <input type="number" id="dhFilterMinValue" class="dh-input" placeholder="Valor mín." style="width:110px;">
            <input type="number" id="dhFilterMaxValue" class="dh-input" placeholder="Valor máx." style="width:110px;">

            <button type="button" class="dh-clear-filters" id="dhClearFilters">Limpiar filtros</button>
        </div>

        {{-- Barra de métricas resumen --}}
        <div class="dh-metrics">
            <div class="dh-metric">
                <span class="dh-metric-label">Abiertos</span>
                <span class="dh-metric-value" id="dhMetricOpenCount">0</span>
            </div>
            <div class="dh-metric">
                <span class="dh-metric-label">Valor abierto</span>
                <span class="dh-metric-value" id="dhMetricOpenValue">—</span>
            </div>
            <div class="dh-metric">
                <span class="dh-metric-label">Pipeline ponderado</span>
                <span class="dh-metric-value" id="dhMetricWeighted">—</span>
            </div>
            <div class="dh-metric">
                <span class="dh-metric-label">Ticket promedio</span>
                <span class="dh-metric-value" id="dhMetricAvgSize">—</span>
            </div>
            <div class="dh-metric">
                <span class="dh-metric-label">Tasa de cierre</span>
                <span class="dh-metric-value" id="dhMetricWinRate">0%</span>
            </div>
            <div class="dh-metric">
                <span class="dh-metric-label">Estancados</span>
                <span class="dh-metric-value" id="dhMetricStalled">0</span>
            </div>
        </div>
    </header>

    {{-- ── Cuerpo: 4 vistas conmutables ───────────────────────── --}}
    <div class="dh-body">

        {{-- Kanban --}}
        <div class="dh-view dh-view-active" data-view="kanban">
            @if($stages->isEmpty())
                <div class="dh-empty-state">Este pipeline no tiene etapas configuradas todavía.</div>
            @else
                <div class="dh-kanban" id="dhKanban"></div>
            @endif
        </div>

        {{-- Lista (agrupada por etapa) --}}
        <div class="dh-view" data-view="lista">
            <div id="dhListGroups"></div>
        </div>

        {{-- Tabla plana --}}
        <div class="dh-view" data-view="tabla">
            <div class="dh-table-card">
                <div class="dh-table-scroll">
                    <table class="dh-table">
                        <thead>
                            <tr>
                                <th></th>
                                <th data-sort-key="folio">Folio</th>
                                <th data-sort-key="name">Nombre</th>
                                <th data-sort-key="stage_name">Etapa</th>
                                <th data-sort-key="amount">Monto</th>
                                <th data-sort-key="customer_name">Cliente</th>
                                <th data-sort-key="owner_name">Responsable</th>
                                <th data-sort-key="status">Estado</th>
                                <th data-sort-key="expected_close_date">Cierre estimado</th>
                                <th data-sort-key="created_at">Creado</th>
                            </tr>
                        </thead>
                        <tbody id="dhTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Forecast --}}
        <div class="dh-view" data-view="forecast">
            <div class="dh-forecast-grid">
                <div class="dh-panel">
                    <h3 class="dh-panel-title">Embudo de conversión <span class="dh-panel-title-sub">negocios abiertos por etapa</span></h3>
                    <div class="dh-funnel" id="dhFunnel"></div>
                </div>
                <div class="dh-panel">
                    <h3 class="dh-panel-title">Pronóstico por fecha de cierre <span class="dh-panel-title-sub">ponderado / crudo</span></h3>
                    <div class="dh-forecast-months" id="dhForecastMonths"></div>
                </div>
            </div>
            <div class="dh-panel">
                <h3 class="dh-panel-title">Rendimiento por ejecutivo</h3>
                <table class="dh-rep-table">
                    <thead>
                        <tr>
                            <th>Ejecutivo</th>
                            <th>Abiertos</th>
                            <th>Ponderado</th>
                            <th>Ganados</th>
                            <th>Tasa de cierre</th>
                        </tr>
                    </thead>
                    <tbody id="dhRepTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Barra flotante de acciones masivas ─────────────────── --}}
    <div class="dh-bulk-bar" id="dhBulkBar">
        <span class="dh-bulk-count" id="dhBulkCount">0 seleccionados</span>

        <select class="dh-select" id="dhBulkStageSelect">
            <option value="">Mover a etapa…</option>
            @foreach($stages as $stage)
                <option value="{{ $stage->id }}">{{ $stage->name }}</option>
            @endforeach
        </select>
        <button type="button" class="dh-btn" id="dhBulkStageApply">Mover</button>

        <select class="dh-select" id="dhBulkOwnerSelect">
            <option value="">Asignar responsable…</option>
            @foreach($owners as $owner)
                <option value="{{ $owner->id }}">{{ $owner->name }}</option>
            @endforeach
        </select>
        <button type="button" class="dh-btn" id="dhBulkOwnerApply">Asignar</button>

        <button type="button" class="dh-btn" id="dhBulkDelete" style="color:var(--err);">Eliminar</button>
        <button type="button" class="dh-btn" id="dhBulkClear">Cancelar</button>
    </div>

    {{-- ── Drawer lateral de detalle ───────────────────────────── --}}
    <div class="dh-drawer-overlay" id="dhDrawerOverlay"></div>
    <aside class="dh-drawer" id="dhDrawer">
        <div class="dh-drawer-header">
            <div>
                <h3 class="dh-drawer-title" id="dhDrawerTitle">Negocio</h3>
                <span class="dh-drawer-folio" id="dhDrawerFolio"></span>
            </div>
            <button type="button" class="dh-drawer-close" id="dhDrawerClose">&times;</button>
        </div>
        <div class="dh-drawer-tabs">
            <button type="button" class="dh-drawer-tab dh-drawer-tab-active" data-drawer-tab="detalle">Detalle</button>
            <button type="button" class="dh-drawer-tab" data-drawer-tab="actividad">Actividad</button>
            <button type="button" class="dh-drawer-tab" data-drawer-tab="tareas">Tareas</button>
        </div>
        <div class="dh-drawer-body" id="dhDrawerBody"></div>
        <div class="dh-drawer-actions">
            <button type="button" class="dh-btn" id="dhDrawerEdit">Editar</button>
            <button type="button" class="dh-btn" id="dhDrawerWin" style="color:var(--ok);">Ganado</button>
            <button type="button" class="dh-btn" id="dhDrawerLose" style="color:var(--err);">Perdido</button>
        </div>
    </aside>

    {{-- ── Modal ganar / perder ────────────────────────────────── --}}
    <div class="dh-modal-overlay" id="dhWinLoseModal">
        <div class="dh-modal">
            <div class="dh-modal-header">
                <h3 class="dh-modal-title" id="dhWinLoseTitle">Actualizar negocio</h3>
            </div>
            <div class="dh-modal-body">
                <div id="dhWinLoseReasonWrap" style="display:none;">
                    <label class="dh-field-label">Motivo de pérdida</label>
                    <select class="dh-select" id="dhWinLoseReasonSelect">
                        <option value="">Selecciona un motivo…</option>
                        <option value="precio">Precio</option>
                        <option value="competencia">Se fue con la competencia</option>
                        <option value="sin_presupuesto">Sin presupuesto</option>
                        <option value="tiempos">Tiempos de entrega</option>
                        <option value="sin_respuesta">Dejó de responder</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div>
                    <label class="dh-field-label">Notas</label>
                    <textarea class="dh-textarea" id="dhWinLoseNotes" placeholder="Notas adicionales (opcional)…"></textarea>
                </div>
            </div>
            <div class="dh-modal-footer">
                <button type="button" class="dh-btn" id="dhWinLoseCancel">Cancelar</button>
                <button type="button" class="dh-btn dh-btn-primary" id="dhWinLoseConfirm">Confirmar</button>
            </div>
        </div>
    </div>

    {{-- ── Modal de gestión de etiquetas ───────────────────────── --}}
    <div class="dh-modal-overlay" id="dhTagModal">
        <div class="dh-modal">
            <div class="dh-modal-header">
                <h3 class="dh-modal-title">Etiquetas de Negocios</h3>
                <button type="button" class="dh-drawer-close" id="dhTagModalClose">&times;</button>
            </div>
            <div class="dh-modal-body">
                <div id="dhTagManageList"></div>
                <div class="dh-tag-manage-row" style="border-top:1px solid var(--border); padding-top:10px; margin-top:6px;">
                    <input type="color" id="dhTagNewColor" value="#ff6213" style="width:32px;height:32px;border:none;background:none;cursor:pointer;">
                    <input type="text" class="dh-input" id="dhTagNewName" placeholder="Nueva etiqueta…">
                    <button type="button" class="dh-btn dh-btn-primary" id="dhTagNewBtn">Agregar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Toasts ───────────────────────────────────────────────── --}}
    <div class="dh-toast-stack" id="dhToastStack"></div>

    {{-- ── Payload de arranque (un solo request, todo lo demás es client-side) --}}
    <script type="application/json" id="deals-boot-data">@json($bootPayload)</script>
</div>
@endsection

@push('scripts')
@vite('resources/js/admin/deals-board.js')
@endpush
