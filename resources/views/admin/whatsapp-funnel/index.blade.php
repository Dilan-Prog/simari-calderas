{{--
    Embudo de Venta (diseño "Embudo de Venta v2"): métricas de valor, filtros con contadores, orden, columnas por etapa con
    valor y pronóstico, tarjetas con ventana de 24 h / próxima actividad / prioridad, y vista de lista.
    Al abrir una tarjeta con lead se entra a su espacio de trabajo en la Bandeja de leads; las conversaciones sin lead
    siguen usando el modal de chat. Datos: conversaciones de WhatsApp + leads de otros canales; el valor sale del negocio ligado.
--}}
@extends('admin.layouts.master')

@section('title', 'Embudo de Venta · WhatsApp - Admin')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" media="print" onload="this.media='all'">
@vite('resources/css/admin/pages/whatsapp-funnel.css')
@endpush

@php
    $channelLabels = ['whatsapp' => 'WhatsApp', 'correo' => 'Correo', 'formulario' => 'Formulario', 'llamada' => 'Llamada', 'manual' => 'Manual'];
    $now = now();
    $dayAgo = $now->copy()->subDay();
    $palette = ['#8b93a1', '#B45309', '#1D5FD0', '#ff6213', '#7c3aed', '#0e7490'];

    $fmtK = function (float $n): string {
        if ($n >= 1000000) return '$' . rtrim(rtrim(number_format($n / 1000000, 2), '0'), '.') . 'M';
        if ($n >= 1000) return '$' . number_format(round($n / 1000)) . 'k';
        return '$' . number_format($n);
    };
    $fmt = fn (float $n): string => '$' . number_format($n);
    $dateLabel = function ($c) use ($now): string {
        if (! $c) return '';
        if ($c->isToday()) return 'Hoy';
        if ($c->isYesterday()) return 'Ayer';
        if ($c->diffInDays($now) < 7) return \Illuminate\Support\Str::ucfirst($c->copy()->locale('es')->isoFormat('dddd'));
        return $c->format('d/m/y');
    };
    $initialsOf = function (?string $n): string {
        $n = trim(preg_replace('/[^\pL\s]/u', '', (string) $n));
        if ($n === '') return '?';
        $parts = preg_split('/\s+/', $n);
        return \Illuminate\Support\Str::upper(mb_substr($parts[0], 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
    };
    $dueInfo = function ($task) use ($now): ?array {
        if (! $task) return null;
        $key = 'sin_fecha'; $label = 'Sin fecha';
        if ($task->due_at) {
            $d = $task->due_at;
            if ($d->lt($now->copy()->startOfDay())) { $key = 'vencida'; $label = 'Vencida'; }
            elseif ($d->isToday()) { $key = 'hoy'; $label = 'Hoy'; }
            elseif ($d->isTomorrow()) { $key = 'manana'; $label = 'Mañana'; }
            elseif ($d->diffInDays($now) < 7) { $key = 'semana'; $label = 'Esta semana'; }
            else { $key = 'despues'; $label = $d->format('d/m'); }
        }
        return ['key' => $key, 'label' => $label . ' · ' . $task->title];
    };

    // Conversaciones y leads sin conversación se normalizan a un mismo formato (tarjeta y fila de la lista).
    $itemsByStage = [];
    foreach ($stages as $stage) {
        $open = ! $stage->is_won && ! $stage->is_lost;
        $list = collect();
        foreach ($conversationsByStage->get($stage->id, collect()) as $c) {
            $customer = $c->customer;
            $linked = $linkedLeads->get($c->id);
            $deal = $c->deal ?? $linked?->deal;
            $lastIn = $c->messages->where('sender_type', 'contact')->last()?->sent_at;
            $winLeft = $lastIn ? max(0, 24 - (int) floor($lastIn->diffInHours($now))) : 0;
            $tasks = $linked ? $tasksByLead->get($linked->id, collect()) : collect();
            $list->push([
                'kind' => 'conv', 'id' => $c->id, 'lead_id' => $linked?->id, 'open' => $open,
                'name' => $customer ? trim($customer->first_name . ' ' . $customer->last_name) : $c->contact_phone,
                'company' => $customer?->company ?: $linked?->contact_company,
                'phone' => $c->contact_phone, 'email' => $customer?->email ?: $linked?->contact_email,
                'channel' => 'whatsapp', 'channel_label' => 'WhatsApp',
                'preview' => \Illuminate\Support\Str::limit($c->messages->last()?->content ?? '', 80),
                'agent' => $c->assignedUser ?? $linked?->assignee,
                'unread' => (int) $c->unread_count, 'deal' => (bool) $deal,
                'value' => $deal?->amount !== null ? (float) $deal->amount : null, 'currency' => $deal?->currency ?? 'MXN',
                'product' => $deal?->name ?: ($customer?->company ?: ''),
                'win' => $winLeft, 'has_window' => true,
                'last' => $c->last_message_at, 'hot' => $linked?->reply_pending_at, 'hot_lead' => $linked?->id,
                'url' => $linked ? route('admin.leads-inbox.index', ['lead' => $linked->id, 'from' => 'embudo']) : null,
                'task' => $dueInfo($tasks->first()), 'prio_high' => $tasks->contains('priority', 'alta'),
            ]);
        }
        foreach ($leadsByStage->get($stage->id, collect()) as $lead) {
            $tasks = $tasksByLead->get($lead->id, collect());
            $list->push([
                'kind' => 'lead', 'id' => $lead->id, 'lead_id' => $lead->id, 'open' => $open,
                'name' => $lead->display_name,
                'company' => $lead->contact_company ?: optional($lead->customer)->company,
                'phone' => $lead->contact_phone, 'email' => $lead->contact_email,
                'channel' => $lead->source, 'channel_label' => $channelLabels[$lead->source] ?? $lead->source_label,
                'preview' => \Illuminate\Support\Str::limit((string) $lead->funnel_preview, 80),
                'agent' => $lead->assignee, 'unread' => 0, 'deal' => (bool) $lead->deal,
                'value' => $lead->deal?->amount !== null ? (float) $lead->deal->amount : null, 'currency' => $lead->deal?->currency ?? 'MXN',
                'product' => $lead->deal?->name ?: ($lead->contact_company ?: optional($lead->customer)->company ?: ''),
                'win' => 0, 'has_window' => false,
                'last' => $lead->last_activity_at, 'hot' => $lead->reply_pending_at, 'hot_lead' => $lead->id,
                'url' => route('admin.leads-inbox.index', ['lead' => $lead->id, 'from' => 'embudo']),
                'task' => $dueInfo($tasks->first()), 'prio_high' => $tasks->contains('priority', 'alta'),
            ]);
        }
        $itemsByStage[$stage->id] = $list->map(function ($i) use ($dayAgo, $dateLabel) {
            $i['stale'] = $i['open'] && $i['last'] && $i['last']->lt($dayAgo);
            $i['date'] = $dateLabel($i['last']);
            return $i;
        })->sortByDesc(fn ($i) => optional($i['last'])->timestamp ?? 0)->values();
    }
    $all = collect($itemsByStage)->flatten(1);

    // Columnas: acento, valor, pronóstico y barra de proporción.
    $columns = [];
    $openIdx = 0;
    foreach ($stages as $stage) {
        $items = $itemsByStage[$stage->id];
        $isWon = (bool) $stage->is_won; $isLost = (bool) $stage->is_lost; $open = ! $isWon && ! $isLost;
        $accent = $isWon ? '#0F7B3F' : ($isLost ? '#9AA1AA' : $palette[$openIdx++ % count($palette)]);
        $value = (float) $items->sum(fn ($i) => $i['value'] ?? 0);
        $prob = (int) ($stage->probability ?? 0);
        $columns[$stage->id] = [
            'stage' => $stage, 'items' => $items, 'accent' => $accent, 'open' => $open, 'value' => $value, 'prob' => $prob,
            'weighted' => $open ? $value * $prob / 100 : 0,
            'unread' => $items->where('unread', '>', 0)->count(),
            'stale' => $items->where('stale', true)->count(),
        ];
    }
    $maxVal = max(1, collect($columns)->max('value'));

    $openItems = $all->where('open', true);
    $won = collect($columns)->filter(fn ($c) => $c['stage']->is_won)->flatMap(fn ($c) => $c['items']);
    $lostN = collect($columns)->filter(fn ($c) => $c['stage']->is_lost)->sum(fn ($c) => $c['items']->count());
    $staleN = $openItems->where('stale', true)->count();
    $overdueN = $all->filter(fn ($i) => ($i['task']['key'] ?? null) === 'vencida')->count();
    $winRate = ($won->count() + $lostN) > 0 ? (int) round($won->count() / ($won->count() + $lostN) * 100) : 0;
    $kpis = [
        ['Valor en embudo', $fmtK((float) $openItems->sum(fn ($i) => $i['value'] ?? 0)), $openItems->count() . ' negocios abiertos', 'var(--text)'],
        ['Pronóstico ponderado', $fmtK((float) collect($columns)->sum('weighted')), 'según probabilidad por etapa', 'var(--text)'],
        ['Ventas cerradas', $fmtK((float) $won->sum(fn ($i) => $i['value'] ?? 0)), $won->count() . ' ganadas · ' . $winRate . '% tasa de cierre', 'var(--ok)'],
        ['Requieren atención', (string) ($staleN + $overdueN), $staleN . ' sin responder · ' . $overdueN . ' tareas vencidas', ($staleN + $overdueN) ? 'var(--err)' : 'var(--ok)'],
        ['Ventanas 24 h abiertas', (string) $all->where('win', '>', 0)->count(), 'puedes escribir sin plantilla', 'var(--text)'],
    ];
    $chips = [
        ['todos', 'Todos', $all->count()],
        ['unread', 'Sin leer', $all->where('unread', '>', 0)->count()],
        ['stale', 'Sin responder', $staleN],
        ['window', 'Ventana abierta', $all->where('win', '>', 0)->count()],
        ['due', 'Tareas vencidas', $overdueN],
        ['prio', 'Prioridad alta', $all->where('prio_high', true)->count()],
    ];
@endphp

@section('content')
<div class="wf2" id="wfRoot">

    <div class="wf2-crumb">Panel de Control &gt; <span>Embudo de Venta</span></div>
    <div class="wf2-head">
        <div class="wf2-head-l">
            <h1>Embudo de Venta</h1>
            <p>Conversaciones de WhatsApp y demás contactos por etapa · arrastra una tarjeta para moverla · {{ $all->count() }} {{ $all->count() === 1 ? 'contacto' : 'contactos' }}</p>
        </div>
        <div class="wf2-head-r">
            <form method="GET" action="{{ route('admin.whatsapp-funnel.index') }}" id="wfPipelineSelectorForm" class="wf2-pipe">
                <select name="pipeline_id" id="wfPipelineSelect" onchange="document.getElementById('wfPipelineSelectorForm').submit()" aria-label="Embudo">
                    @forelse($pipelines as $p)
                        <option value="{{ $p->id }}" @selected($pipeline && $pipeline->id === $p->id)>{{ $p->name }}@if($p->is_default) (predeterminado) @endif</option>
                    @empty
                        <option value="">Sin embudos configurados</option>
                    @endforelse
                </select>
            </form>
            <span class="wf2-conn"><i class="{{ $accounts->isNotEmpty() ? 'on' : '' }}"></i>{{ $accounts->isNotEmpty() ? 'WhatsApp conectado' : 'WhatsApp sin configurar' }}</span>
            <a class="wf2-btn" href="{{ route('admin.leads-inbox.index') }}">Abrir bandeja</a>
            <button type="button" class="wf2-btn wf2-btn--primary" id="wfNewChatBtn" @disabled(! $pipeline)>+ Nuevo chat</button>
        </div>
    </div>

    @if($pipeline)
    <div class="wf2-kpis">
        @foreach($kpis as [$label, $value, $sub, $color])
            <div class="wf2-kpi">
                <div class="wf2-kpi-l">{{ $label }}</div>
                <div class="wf2-kpi-v" style="color: {{ $color }}">{{ $value }}</div>
                <div class="wf2-kpi-s">{{ $sub }}</div>
            </div>
        @endforeach
    </div>

    <div class="wf2-bar">
        <div class="wf2-search">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5" stroke-linecap="round"/></svg>
            <input type="text" id="wfSearchInput" placeholder="Buscar nombre, número o producto" autocomplete="off">
        </div>
        <select id="wfAgentFilter" class="wf2-select" aria-label="Responsable">
            <option value="">Todos los agentes</option>
            @foreach($agents as $agent)
                <option value="{{ $agent->id }}">{{ $agent->full_name }}</option>
            @endforeach
        </select>
        <select id="wfChannelFilter" class="wf2-select" aria-label="Canal">
            <option value="">Todos los canales</option>
            @foreach($channelLabels as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
        <select id="wfSort" class="wf2-select" aria-label="Ordenar">
            <option value="reciente">Más recientes</option>
            <option value="valor">Mayor valor</option>
            <option value="espera">Más tiempo sin respuesta</option>
        </select>
        <div class="wf2-chips" role="tablist" aria-label="Filtros rápidos">
            @foreach($chips as [$key, $label, $count])
                <button type="button" class="wf2-chip {{ $key === 'todos' ? 'is-on' : '' }}" data-chip="{{ $key }}" aria-pressed="{{ $key === 'todos' ? 'true' : 'false' }}">{{ $label }}<span>{{ $count }}</span></button>
            @endforeach
        </div>
        <div class="wf2-views" role="tablist" aria-label="Vista">
            <button type="button" class="wf2-view is-on" data-view-btn="kanban">Tablero</button>
            <button type="button" class="wf2-view" data-view-btn="lista">Lista</button>
        </div>
    </div>
    @endif

    @if($pipeline && $stages->isNotEmpty())
        {{-- Tablero --}}
        <div class="wf2-viewbox is-on" data-view="kanban">
            <div class="wf2-board" id="wfBoard" data-pipeline-id="{{ $pipeline->id }}"
                data-move-stage-url-template="{{ route('admin.whatsapp-funnel.move-stage', ['conversation' => '__CONV_ID__']) }}"
                data-messages-url-template="{{ route('admin.whatsapp-funnel.messages', ['conversation' => '__CONV_ID__']) }}"
                data-send-message-url-template="{{ route('admin.whatsapp-funnel.send-message', ['conversation' => '__CONV_ID__']) }}"
                data-create-deal-url-template="{{ route('admin.whatsapp-funnel.create-deal', ['conversation' => '__CONV_ID__']) }}"
                data-reassign-agent-url-template="{{ route('admin.whatsapp-funnel.reassign-agent', ['conversation' => '__CONV_ID__']) }}"
                data-move-lead-url-template="{{ route('admin.whatsapp-funnel.move-lead', ['lead' => '__CONV_ID__']) }}"
                data-new-chat-url="{{ route('admin.whatsapp-funnel.new-chat') }}">
                @foreach($columns as $col)
                    @php $st = $col['stage']; @endphp
                    <div class="wf2-col" data-stage-id="{{ $st->id }}" style="--ac: {{ $col['accent'] }}">
                        <div class="wf2-col-h">
                            <div class="wf2-col-t">
                                <i></i>
                                <b>{{ $st->name }}</b>
                                <span class="wf2-pill" data-column-count>{{ $col['items']->count() }}</span>
                                <em title="Probabilidad de cierre de la etapa">{{ $col['open'] ? $col['prob'] . '%' : '' }}</em>
                            </div>
                            <div class="wf2-col-v">
                                <strong>{{ $fmtK($col['value']) }}</strong>
                                <small>{{ $col['open'] ? 'pond. ' . $fmtK($col['weighted']) : ($st->is_won ? 'ganado' : 'perdido') }}</small>
                            </div>
                            <div class="wf2-bar-track"><div style="width: {{ (int) round($col['value'] / $maxVal * 100) }}%"></div></div>
                        </div>
                        <div class="wf2-col-list dh-column-list wf-column-list" data-stage-id="{{ $st->id }}">
                            @foreach($col['items'] as $it)
                                @include('admin.whatsapp-funnel.partials._card', ['it' => $it, 'fmt' => $fmt, 'initialsOf' => $initialsOf])
                            @endforeach
                            <div class="wf2-empty" data-wf-empty @if($col['items']->isNotEmpty()) hidden @endif>
                                <b>Sin negocios aquí</b>
                                <span>Arrastra una conversación a esta etapa.</span>
                            </div>
                        </div>
                        <div class="wf2-col-f">
                            <span>{{ $col['unread'] ? $col['unread'] . ' sin leer' : 'Todo leído' }}</span>
                            <b class="{{ $col['open'] && $col['stale'] ? 'is-bad' : '' }}">{{ $col['open'] ? ($col['stale'] ? $col['stale'] . ' sin responder' : 'Al día') : '' }}</b>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Lista --}}
        <div class="wf2-viewbox" data-view="lista">
            <div class="wf2-list">
                <div class="wf2-list-in">
                    <div class="wf2-row wf2-row--h">
                        <div>Contacto</div><div>Etapa</div><div class="r">Valor</div><div class="r">Prob.</div><div>Agente</div><div>Ventana 24 h</div><div>Próxima actividad</div><div class="r">Último</div>
                    </div>
                    @foreach($columns as $col)
                        @foreach($col['items'] as $it)
                            <div class="wf2-row {{ $it['stale'] ? 'is-alert' : '' }}" data-wf-item
                                @if($it['kind'] === 'conv') data-conv-id="{{ $it['id'] }}" @else data-lead-id="{{ $it['id'] }}" @endif
                                @if($it['url']) data-open-url="{{ $it['url'] }}" @endif
                                data-name="{{ $it['name'] }}" data-phone="{{ trim(($it['phone'] ?: $it['email']) . ' ' . $it['product'] . ' ' . $it['company']) }}" data-channel="{{ $it['channel'] }}"
                                data-agent-id="{{ $it['agent']?->id }}" data-unread="{{ $it['unread'] }}" data-stale="{{ $it['stale'] ? 1 : 0 }}" data-win="{{ $it['win'] > 0 ? 1 : 0 }}"
                                data-due="{{ ($it['task']['key'] ?? '') === 'vencida' ? 1 : 0 }}" data-prio="{{ $it['prio_high'] ? 1 : 0 }}"
                                data-value="{{ (float) ($it['value'] ?? 0) }}" data-last-activity="{{ optional($it['last'])->timestamp }}">
                                <div class="wf2-r-c">
                                    <span class="wf2-av" style="--av: {{ $col['accent'] }}">{{ $initialsOf($it['name']) }}</span>
                                    <div><b>{{ $it['name'] }}</b><small>{{ $it['product'] ?: $it['channel_label'] }}</small></div>
                                    @if($it['unread'] > 0)<span class="wf2-unread">{{ $it['unread'] }}</span>@endif
                                </div>
                                <div class="wf2-r-st"><i style="background: {{ $col['accent'] }}"></i><span>{{ $col['stage']->name }}</span></div>
                                <div class="r"><b>{{ $it['value'] !== null ? $fmt($it['value']) : '—' }}</b></div>
                                <div class="r muted">{{ $col['open'] ? $col['prob'] . '%' : '—' }}</div>
                                <div class="muted">{{ $it['agent']?->full_name ?: '—' }}</div>
                                <div>
                                    @if($it['has_window'])
                                        <span class="wf2-tag {{ $it['win'] > 0 ? 'is-ok' : 'is-off' }}">{{ $it['win'] > 0 ? 'Abierta · ' . $it['win'] . ' h' : 'Ventana cerrada' }}</span>
                                    @else
                                        <span class="wf2-tag is-off">{{ $it['channel_label'] }}</span>
                                    @endif
                                </div>
                                <div>
                                    @if($it['task'])
                                        <span class="wf2-tag due-{{ $it['task']['key'] }}">{{ $it['task']['label'] }}</span>
                                    @else
                                        <span class="muted small">Sin actividad</span>
                                    @endif
                                </div>
                                <div class="r muted small">{{ $it['date'] }}</div>
                            </div>
                        @endforeach
                    @endforeach
                    <div class="wf2-none" data-wf-none hidden>Ningún negocio coincide con los filtros.</div>
                </div>
            </div>
        </div>

    @elseif($pipeline)
        <div class="wf2-note">
            Este embudo no tiene etapas configuradas todavía. Crea uno desde <a href="{{ route('admin.pipelines.index') }}">Pipelines</a>.
        </div>
    @else
        <div class="wf2-note">
            No hay embudos de WhatsApp configurados. Crea un Pipeline con tipo "Embudo de Venta (WhatsApp)" desde <a href="{{ route('admin.pipelines.index') }}">Pipelines</a>.
        </div>
    @endif

</div>

{{-- Ficha del lead (espacio de trabajo de la Bandeja de leads) dentro de un modal. --}}
<div class="wf2-modal" id="wfLeadModal" role="dialog" aria-modal="true" aria-label="Ficha del lead" hidden>
    <div class="wf2-modal-box">
        <div class="wf2-modal-bar">
            <span>Ficha del lead</span>
            <a class="wf2-btn" id="wfLeadFull" href="#" target="_blank" rel="noopener">Abrir en la bandeja ↗</a>
            <button type="button" class="wf2-modal-x" id="wfLeadClose" aria-label="Cerrar">&times;</button>
        </div>
        <iframe id="wfLeadFrame" title="Ficha del lead" src="about:blank"></iframe>
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
