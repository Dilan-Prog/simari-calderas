{{--
    Embudo de Venta con la misma interfaz que Negocios (deals/index.blade.php): encabezado con selector de embudo y
    vistas Kanban / Lista, filtros, tira de métricas y tarjetas dh-card. Conserva lo propio del embudo: conversaciones
    de WhatsApp (chat, sin leer, ventana de 24 h) y demás contactos (correo, formulario, llamada), responsable,
    arrastre entre etapas, "Nuevo chat" y el resaltado de respuestas pendientes.
--}}
@extends('admin.layouts.master')

@section('title', 'Embudo de Venta · WhatsApp - Admin')

@push('styles')
<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700;800&display=swap" media="print" onload="this.media='all'">
@vite('resources/css/admin/pages/deals.css')
@vite('resources/css/admin/pages/whatsapp-funnel.css')
@endpush

@php
    $channelLabels = ['whatsapp' => 'WhatsApp', 'correo' => 'Correo', 'formulario' => 'Formulario', 'llamada' => 'Llamada', 'manual' => 'Manual'];

    // Se normalizan conversaciones y leads sin conversación a un mismo formato (tarjeta y fila de la lista).
    $itemsByStage = [];
    foreach ($stages as $stage) {
        $list = collect();
        foreach ($conversationsByStage->get($stage->id, collect()) as $c) {
            $customer = $c->customer;
            $linked = $linkedLeads->get($c->id);
            $list->push([
                'kind' => 'conv', 'id' => $c->id, 'lead_id' => $linked?->id,
                'name' => $customer ? trim($customer->first_name . ' ' . $customer->last_name) : $c->contact_phone,
                'company' => $customer?->company ?: $linked?->contact_company,
                'phone' => $c->contact_phone, 'email' => $customer?->email ?: $linked?->contact_email,
                'channel' => 'whatsapp', 'channel_label' => 'WhatsApp',
                'preview' => \Illuminate\Support\Str::limit($c->messages->last()?->content ?? '', 70),
                'agent' => $c->assignedUser ?? $linked?->assignee,
                'unread' => (int) $c->unread_count, 'deal' => (bool) $c->deal_id,
                'last' => $c->last_message_at, 'hot' => $linked?->reply_pending_at, 'hot_lead' => $linked?->id,
                'url' => $linked ? route('admin.leads-inbox.index', ['lead' => $linked->id]) : null,
            ]);
        }
        foreach ($leadsByStage->get($stage->id, collect()) as $lead) {
            $list->push([
                'kind' => 'lead', 'id' => $lead->id, 'lead_id' => $lead->id,
                'name' => $lead->display_name,
                'company' => $lead->contact_company ?: optional($lead->customer)->company,
                'phone' => $lead->contact_phone, 'email' => $lead->contact_email,
                'channel' => $lead->source, 'channel_label' => $channelLabels[$lead->source] ?? $lead->source_label,
                'preview' => \Illuminate\Support\Str::limit((string) $lead->funnel_preview, 70),
                'agent' => $lead->assignee, 'unread' => 0, 'deal' => (bool) $lead->deal_id,
                'last' => $lead->last_activity_at, 'hot' => $lead->reply_pending_at, 'hot_lead' => $lead->id,
                'url' => route('admin.leads-inbox.index', ['lead' => $lead->id]),
            ]);
        }
        $itemsByStage[$stage->id] = $list->sortByDesc(fn ($i) => optional($i['last'])->timestamp ?? 0)->values();
    }
    $allItems = collect($itemsByStage)->flatten(1);
    $dayAgo = now()->subDay();
    $kpis = [
        'total'      => $allItems->count(),
        'unread'     => $allItems->where('unread', '>', 0)->count(),
        'stale'      => $allItems->filter(fn ($i) => $i['last'] && $i['last']->lt($dayAgo))->count(),
        'unassigned' => $allItems->filter(fn ($i) => ! $i['agent'])->count(),
        'deals'      => $allItems->where('deal', true)->count(),
        'won'        => $stages->where('is_won', true)->sum(fn ($s) => count($itemsByStage[$s->id] ?? [])),
    ];
@endphp

@section('content')
<div id="deals-hub-root" data-theme="light" class="wf-hub">

    {{-- ── Header ──────────────────────────────────────────────── --}}
    <header class="dh-header">
        <div class="dh-header-row1">
            <div class="dh-header-left">
                <div>
                    <div class="dh-breadcrumb">Panel de Control / Embudo de Venta</div>
                    <h1 class="dh-title">Embudo de Venta</h1>
                </div>

                <form method="GET" action="{{ route('admin.whatsapp-funnel.index') }}" id="wfPipelineSelectorForm">
                    <div class="dh-pipeline-select-wrap">
                        <select name="pipeline_id" id="wfPipelineSelect" class="dh-select"
                            onchange="document.getElementById('wfPipelineSelectorForm').submit()">
                            @forelse($pipelines as $p)
                                <option value="{{ $p->id }}" @selected($pipeline && $pipeline->id === $p->id)>
                                    {{ $p->name }}@if($p->is_default) (predeterminado) @endif
                                </option>
                            @empty
                                <option value="">Sin embudos configurados</option>
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
                    <input type="text" id="wfSearchInput" class="dh-search-input" placeholder="Buscar por nombre, empresa, teléfono o correo…" autocomplete="off" @disabled(! $pipeline)>
                </div>
            </div>

            <div class="dh-header-right">
                <div class="dh-view-toggle">
                    <button type="button" class="dh-view-btn dh-view-btn-active" data-view-btn="kanban">Kanban</button>
                    <button type="button" class="dh-view-btn" data-view-btn="lista">Lista</button>
                </div>

                <button type="button" class="dh-btn dh-btn-icon" id="dhThemeToggle" title="Cambiar tema">
                    <svg class="dh-icon-moon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
                    <svg class="dh-icon-sun" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                </button>

                <a href="{{ route('admin.leads-inbox.index') }}" class="dh-btn">Bandeja de leads</a>
                <button type="button" class="dh-btn dh-btn-primary" id="wfNewChatBtn" @disabled(! $pipeline)>+ Nuevo chat</button>
            </div>
        </div>

        @if($pipeline)
        {{-- Filtros rápidos --}}
        <div class="dh-filters-row">
            <span class="dh-filter-label">Filtros</span>

            <select id="wfAgentFilter" class="dh-select">
                <option value="">Todos los responsables</option>
                @foreach($agents as $agent)
                    <option value="{{ $agent->id }}">{{ $agent->full_name }}</option>
                @endforeach
            </select>

            <select id="wfChannelFilter" class="dh-select">
                <option value="">Todos los canales</option>
                @foreach($channelLabels as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>

            <label class="wf-check"><input type="checkbox" id="wfUnreadFilter"> Sin leer</label>
            <label class="wf-check"><input type="checkbox" id="wfStaleFilter"> Sin responder &gt;24 h</label>

            <button type="button" class="dh-clear-filters" id="wfClearFilters">Limpiar filtros</button>
        </div>

        {{-- Barra de métricas resumen --}}
        <div class="dh-metrics">
            <div class="dh-metric">
                <span class="dh-metric-label">Contactos</span>
                <span class="dh-metric-value">{{ $kpis['total'] }}</span>
            </div>
            <div class="dh-metric">
                <span class="dh-metric-label">Sin leer</span>
                <span class="dh-metric-value">{{ $kpis['unread'] }}</span>
            </div>
            <div class="dh-metric">
                <span class="dh-metric-label">Sin responder &gt;24 h</span>
                <span class="dh-metric-value">{{ $kpis['stale'] }}</span>
            </div>
            <div class="dh-metric">
                <span class="dh-metric-label">Sin responsable</span>
                <span class="dh-metric-value">{{ $kpis['unassigned'] }}</span>
            </div>
            <div class="dh-metric">
                <span class="dh-metric-label">Con negocio</span>
                <span class="dh-metric-value">{{ $kpis['deals'] }}</span>
            </div>
            <div class="dh-metric">
                <span class="dh-metric-label">Venta cerrada</span>
                <span class="dh-metric-value">{{ $kpis['won'] }}</span>
            </div>
        </div>
        @endif
    </header>

    {{-- ── Cuerpo: vistas conmutables ─────────────────────────── --}}
    <div class="dh-body">
    @if($pipeline && $stages->isNotEmpty())

        {{-- Kanban --}}
        <div class="dh-view dh-view-active" data-view="kanban">
            <div class="dh-kanban wf-board" id="wfBoard" data-pipeline-id="{{ $pipeline->id }}"
                data-move-stage-url-template="{{ route('admin.whatsapp-funnel.move-stage', ['conversation' => '__CONV_ID__']) }}"
                data-messages-url-template="{{ route('admin.whatsapp-funnel.messages', ['conversation' => '__CONV_ID__']) }}"
                data-send-message-url-template="{{ route('admin.whatsapp-funnel.send-message', ['conversation' => '__CONV_ID__']) }}"
                data-create-deal-url-template="{{ route('admin.whatsapp-funnel.create-deal', ['conversation' => '__CONV_ID__']) }}"
                data-reassign-agent-url-template="{{ route('admin.whatsapp-funnel.reassign-agent', ['conversation' => '__CONV_ID__']) }}"
                data-move-lead-url-template="{{ route('admin.whatsapp-funnel.move-lead', ['lead' => '__CONV_ID__']) }}"
                data-new-chat-url="{{ route('admin.whatsapp-funnel.new-chat') }}">
                @foreach($stages as $stage)
                    @php
                        $items = $itemsByStage[$stage->id];
                        $stageUnread = $items->where('unread', '>', 0)->count();
                        $stageStale = $items->filter(fn ($i) => $i['last'] && $i['last']->lt($dayAgo))->count();
                        [$healthCls, $healthTxt] = $items->isEmpty() ? ['dh-health-good', 'OK']
                            : ($stageStale / $items->count() >= 0.5 ? ['dh-health-bad', 'En riesgo']
                            : ($stageStale > 0 ? ['dh-health-warn', 'Atención'] : ['dh-health-good', 'Saludable']));
                    @endphp
                    <div class="dh-column" data-stage-id="{{ $stage->id }}">
                        <div class="dh-column-header">
                            <div class="dh-column-title-row">
                                <span class="dh-column-title">{{ $stage->name }}</span>
                                <span class="dh-column-probability">{{ (int) ($stage->probability ?? 0) }}%</span>
                            </div>
                            <div class="dh-column-stats">
                                <span data-column-count>{{ $items->count() }} contacto(s)</span>
                                <span>{{ $stageUnread }} sin leer</span>
                            </div>
                            <div class="dh-column-wip">
                                {{ $stageStale }} sin responder &gt;24 h
                                <span class="dh-column-health {{ $healthCls }}">{{ $healthTxt }}</span>
                            </div>
                        </div>
                        <div class="dh-column-list wf-column-list" data-stage-id="{{ $stage->id }}">
                            @foreach($items as $it)
                                @include('admin.whatsapp-funnel.partials._card', ['it' => $it])
                            @endforeach
                            <div class="dh-column-empty" data-wf-empty @if($items->isNotEmpty()) hidden @endif>Sin contactos en esta etapa</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Lista --}}
        <div class="dh-view" data-view="lista">
            <div class="dh-table-card">
                <div class="dh-table-scroll">
                    <table class="dh-table">
                        <thead>
                            <tr>
                                <th>Contacto</th>
                                <th>Canal</th>
                                <th>Etapa</th>
                                <th>Responsable</th>
                                <th>Último movimiento</th>
                                <th>Sin leer</th>
                                <th>Negocio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stages as $stage)
                                @foreach($itemsByStage[$stage->id] as $it)
                                    <tr data-wf-item @if($it['kind'] === 'conv') data-conv-id="{{ $it['id'] }}" @else data-lead-id="{{ $it['id'] }}" data-lead-url="{{ $it['url'] }}" @endif
                                        data-name="{{ $it['name'] }}" data-phone="{{ $it['phone'] ?: $it['email'] }}" data-channel="{{ $it['channel'] }}"
                                        data-agent-id="{{ $it['agent']?->id }}" data-unread="{{ $it['unread'] }}" data-last-activity="{{ optional($it['last'])->timestamp }}">
                                        <td class="dh-td-name">{{ $it['name'] }}@if($it['company'])<small class="wf-td-sub">{{ $it['company'] }}</small>@endif</td>
                                        <td><span class="wf-chan" data-ch="{{ $it['channel'] }}">{{ $it['channel_label'] }}</span></td>
                                        <td><span class="dh-stage-pill">{{ $stage->name }}</span></td>
                                        <td>{{ $it['agent']?->full_name ?: '—' }}</td>
                                        <td>{{ $it['last'] ? $it['last']->diffForHumans() : '—' }}</td>
                                        <td>{{ $it['unread'] ?: '—' }}</td>
                                        <td>{{ $it['deal'] ? 'Sí' : '—' }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @elseif($pipeline)
        <div class="dh-empty-state">
            Este embudo no tiene etapas configuradas todavía. Crea uno desde <a href="{{ route('admin.pipelines.index') }}">Pipelines</a>.
        </div>
    @else
        <div class="dh-empty-state">
            No hay embudos de WhatsApp configurados. Crea un Pipeline con tipo "Embudo de Venta (WhatsApp)" desde <a href="{{ route('admin.pipelines.index') }}">Pipelines</a>.
        </div>
    @endif
    </div>

</div>

@include('admin.whatsapp-funnel.partials._modal_chat')
@include('admin.whatsapp-funnel.partials._modal_new_chat')

@endsection

@php
    // Se arman como arrays PHP planos antes del @json — un @json() inline
    // con arrow functions anidadas (map dentro de map con literales [])
    // confunde el extractor de argumentos de la directiva de Blade
    // (corta la expresión en el primer ']' interno). Ver también el mismo
    // patrón ya usado en workflows/index.blade.php.
    $wfStagesJs = $stages->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values()->all();
    $wfAccountsJs = $accounts->map(fn ($a) => ['id' => $a->id, 'name' => $a->name])->values()->all();
    $wfAgentsJs = $agents->map(fn ($u) => ['id' => $u->id, 'name' => $u->full_name])->values()->all();
    $wfDealPipelinesJs = $dealPipelines->map(function ($p) {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'stages' => $p->stages->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values()->all(),
        ];
    })->values()->all();
@endphp

@push('scripts')
<script>
    window.__wfFunnelData = {
        stages: @json($wfStagesJs),
        accounts: @json($wfAccountsJs),
        agents: @json($wfAgentsJs),
        templates: @json($templates),
        dealPipelines: @json($wfDealPipelinesJs),
        dealsIndexUrl: @json(route('admin.deals.index')),
    };
</script>
@vite('resources/js/admin/whatsapp-funnel-board.js')
@vite('resources/js/admin/hot-highlight.js')
@endpush
