@extends('admin.layouts.master')

@section('title', 'Embudo de Venta · WhatsApp - Admin')

@push('styles')
@vite('resources/css/admin/pages/pipeline.css')
@vite('resources/css/admin/pages/whatsapp-funnel.css')
@endpush

@section('content')
<div class="pipeline-board-page wf-funnel-page">

    {{-- ── Header ──────────────────────────────────────────────── --}}
    <div class="pipeline-board-header">
        <div class="pipeline-board-header-left">
            <div class="pipeline-breadcrumb">
                <a href="{{ route('admin.dashboard') }}" class="pipeline-breadcrumb-link">Admin</a>
                <span>/</span>
                <span class="pipeline-breadcrumb-current">Embudo de Venta</span>
            </div>
            <h1 class="pipeline-board-title">Embudo de Venta</h1>
            <p class="pipeline-board-subtitle">
                Conversaciones de WhatsApp por etapa — arrastra las tarjetas para actualizar su estatus.
            </p>
        </div>

        <div class="pipeline-board-header-right">
            <form method="GET" action="{{ route('admin.whatsapp-funnel.index') }}" id="wfPipelineSelectorForm" class="pipeline-selector-form">
                <label for="wfPipelineSelect" class="pipeline-selector-label">Embudo</label>
                <div class="pipeline-selector-select-wrap">
                    <select name="pipeline_id" id="wfPipelineSelect" class="pipeline-selector-select"
                        onchange="document.getElementById('wfPipelineSelectorForm').submit()">
                        @forelse($pipelines as $p)
                            <option value="{{ $p->id }}" @selected($pipeline && $pipeline->id === $p->id)>
                                {{ $p->name }}@if($p->is_default) (predeterminado) @endif
                            </option>
                        @empty
                            <option value="">Sin embudos configurados</option>
                        @endforelse
                    </select>
                </div>
            </form>

            <button type="button" class="pipeline-btn-new" id="wfNewChatBtn" @disabled(!$pipeline)>
                + Nuevo chat
            </button>
        </div>
    </div>

    {{-- ── Filtros ─────────────────────────────────────────────── --}}
    @if($pipeline)
    <div class="wf-filters-bar">
        <input type="text" id="wfSearchInput" class="wf-filter-input" placeholder="Buscar por nombre, empresa o teléfono...">

        <select id="wfAgentFilter" class="wf-filter-select">
            <option value="">Todos los agentes</option>
            @foreach($agents as $agent)
                <option value="{{ $agent->id }}">{{ $agent->full_name }}</option>
            @endforeach
        </select>

        <label class="wf-filter-checkbox">
            <input type="checkbox" id="wfUnreadFilter"> Sin leer
        </label>

        <label class="wf-filter-checkbox">
            <input type="checkbox" id="wfStaleFilter"> Sin responder &gt;24h
        </label>

        <button type="button" class="pipeline-btn-secondary" id="wfApplyFiltersBtn">Filtrar</button>
    </div>
    @endif

    {{-- ── Board ───────────────────────────────────────────────── --}}
    @if($pipeline && $stages->isNotEmpty())
        <div class="pipeline-board wf-board" id="wfBoard" data-pipeline-id="{{ $pipeline->id }}"
            data-move-stage-url-template="{{ route('admin.whatsapp-funnel.move-stage', ['conversation' => '__CONV_ID__']) }}"
            data-messages-url-template="{{ route('admin.whatsapp-funnel.messages', ['conversation' => '__CONV_ID__']) }}"
            data-send-message-url-template="{{ route('admin.whatsapp-funnel.send-message', ['conversation' => '__CONV_ID__']) }}"
            data-create-deal-url-template="{{ route('admin.whatsapp-funnel.create-deal', ['conversation' => '__CONV_ID__']) }}"
            data-reassign-agent-url-template="{{ route('admin.whatsapp-funnel.reassign-agent', ['conversation' => '__CONV_ID__']) }}"
            data-new-chat-url="{{ route('admin.whatsapp-funnel.new-chat') }}">
            @foreach($stages as $stage)
                @php
                    $stageConversations = $conversationsByStage->get($stage->id, collect());
                @endphp

                <div class="pipeline-column" data-stage-id="{{ $stage->id }}">
                    <div class="pipeline-column-header">
                        <div class="pipeline-column-title-row">
                            <span class="pipeline-column-title">{{ $stage->name }}</span>
                        </div>
                        <div class="pipeline-column-stats">
                            <span class="pipeline-column-count" data-column-count>{{ $stageConversations->count() }} conversación(es)</span>
                        </div>
                    </div>

                    <div class="pipeline-column-list wf-column-list" data-stage-id="{{ $stage->id }}">
                        @forelse($stageConversations as $conversation)
                            @php
                                $customer = $conversation->customer;
                                $displayName = $customer
                                    ? trim($customer->first_name . ' ' . $customer->last_name)
                                    : $conversation->contact_phone;
                                $initials = $customer
                                    ? strtoupper(mb_substr($customer->first_name ?? '', 0, 1) . mb_substr($customer->last_name ?? '', 0, 1))
                                    : strtoupper(mb_substr($conversation->contact_phone, -2));
                                $lastMessage = $conversation->messages->last();
                            @endphp
                            <div class="pipeline-card wf-chat-card" data-conv-id="{{ $conversation->id }}"
                                data-name="{{ $displayName }}" data-phone="{{ $conversation->contact_phone }}"
                                data-agent-id="{{ $conversation->assigned_user_id }}"
                                data-unread="{{ $conversation->unread_count }}"
                                data-last-activity="{{ optional($conversation->last_message_at)->timestamp }}">
                                <div class="wf-chat-card-body">
                                    <div class="wf-chat-card-top">
                                        <span class="wf-chat-avatar">{{ $initials ?: '?' }}</span>
                                        <div class="wf-chat-card-info">
                                            <div class="wf-chat-card-name">
                                                {{ $displayName }}
                                                <span class="wf-online-dot" title="WhatsApp"></span>
                                            </div>
                                            @if($customer && $customer->company)
                                                <div class="wf-chat-card-company">{{ $customer->company }}</div>
                                            @endif
                                        </div>
                                        @if($conversation->unread_count > 0)
                                            <span class="wf-unread-badge">{{ $conversation->unread_count }}</span>
                                        @endif
                                    </div>

                                    @if($lastMessage)
                                        <div class="wf-chat-card-preview">
                                            {{ \Illuminate\Support\Str::limit($lastMessage->content ?? '(sin texto)', 60) }}
                                        </div>
                                    @endif

                                    <div class="wf-chat-card-bottom">
                                        @if($conversation->assignedUser)
                                            <span class="wf-chat-card-agent">{{ $conversation->assignedUser->full_name }}</span>
                                        @else
                                            <span class="wf-chat-card-agent wf-chat-card-agent-empty">Sin asignar</span>
                                        @endif
                                        @if($conversation->deal_id)
                                            <span class="wf-deal-badge" title="Negocio vinculado">Negocio</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="pipeline-column-empty">Sin conversaciones en esta etapa</div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    @elseif($pipeline)
        <div class="pipeline-empty-state">
            Este embudo no tiene etapas configuradas todavía. Crea uno desde <a href="{{ route('admin.pipelines.index') }}">Pipelines</a>.
        </div>
    @else
        <div class="pipeline-empty-state">
            No hay embudos de WhatsApp configurados. Crea un Pipeline con tipo "Embudo de Venta (WhatsApp)" desde <a href="{{ route('admin.pipelines.index') }}">Pipelines</a>.
        </div>
    @endif

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
@endpush
