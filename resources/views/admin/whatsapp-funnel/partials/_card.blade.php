{{--
    Tarjeta del Embudo de Venta (diseño "Embudo de Venta v2").
    Variables: $it (arreglo normalizado en index.blade.php), $fmt (formato de dinero), $initialsOf (iniciales).
    Los data-* y [data-wf-item] son los ganchos de whatsapp-funnel-board.js (arrastre, filtros, orden, abrir la ficha).
    Con lead (data-open-url) la tarjeta abre su espacio de trabajo en la Bandeja de leads; sin lead abre el modal de chat.
--}}
@php
    $isConv = $it['kind'] === 'conv';
    $agentName = $it['agent']?->full_name;
    $avatarBg = ['#E9F6EE', '#EBF1FD', '#FEF6E7', '#F1EBFD', '#FDECEC'][$it['id'] % 5];
    $avatarFg = ['#0F7B3F', '#1D5FD0', '#B45309', '#6d28d9', '#C81E1E'][$it['id'] % 5];
    $taskKey = $it['task']['key'] ?? null;
@endphp
<div class="wf2-card {{ $it['hot'] ? 'is-hot' : '' }} {{ $it['stale'] ? 'is-alert' : '' }}" data-wf-item
    @if($isConv) data-conv-id="{{ $it['id'] }}" @else data-lead-id="{{ $it['id'] }}" @endif
    @if($it['url']) data-open-url="{{ $it['url'] }}" @endif
    @if($it['hot_lead']) data-hot-lead="{{ $it['hot_lead'] }}" @endif
    data-name="{{ $it['name'] }}" data-phone="{{ trim(($it['phone'] ?: $it['email']) . ' ' . $it['product'] . ' ' . $it['company']) }}" data-channel="{{ $it['channel'] }}"
    data-agent-id="{{ $it['agent']?->id }}" data-unread="{{ $it['unread'] }}" data-stale="{{ $it['stale'] ? 1 : 0 }}" data-win="{{ $it['win'] > 0 ? 1 : 0 }}"
    data-due="{{ $taskKey === 'vencida' ? 1 : 0 }}" data-prio="{{ $it['prio_high'] ? 1 : 0 }}"
    data-value="{{ (float) ($it['value'] ?? 0) }}" data-last-activity="{{ optional($it['last'])->timestamp }}">
    <div class="wf2-card-top">
        <div class="wf2-av-wrap">
            <span class="wf2-av" style="background: {{ $avatarBg }}; color: {{ $avatarFg }}">{{ $initialsOf($it['name']) }}</span>
            @if($isConv)<i class="wf2-wa" title="WhatsApp"></i>@endif
        </div>
        <div class="wf2-card-main">
            <div class="wf2-card-l1">
                @if($it['prio_high'])<i class="wf2-prio" title="Prioridad alta"></i>@endif
                <b>{{ $it['name'] }}</b>
                <span class="hot-dot" aria-label="Respondió"></span>
                <small>{{ $it['date'] }}</small>
            </div>
            <div class="wf2-card-l2">
                <span>{{ $it['product'] ?: ($it['company'] ?: ($it['phone'] ?: ($it['email'] ?: 'Sin datos de contacto'))) }}</span>
                @if($it['value'] !== null)
                    <strong>{{ $fmt($it['value']) }}@if(($it['currency'] ?? 'MXN') !== 'MXN') <em>{{ $it['currency'] }}</em>@endif</strong>
                @endif
            </div>
        </div>
    </div>
    @if($it['preview'])
        <div class="wf2-card-prev">{{ $it['preview'] }}</div>
    @endif
    <div class="wf2-card-tags">
        @if($it['has_window'])
            <span class="wf2-tag {{ $it['win'] > 0 ? 'is-ok' : 'is-off' }}">{{ $it['win'] > 0 ? 'Abierta · ' . $it['win'] . ' h' : 'Ventana cerrada' }}</span>
        @else
            <span class="wf2-tag wf2-chan" data-ch="{{ $it['channel'] }}">{{ $it['channel_label'] }}</span>
        @endif
        @if($it['task'])
            <span class="wf2-tag due-{{ $taskKey }}" title="{{ $it['task']['label'] }}">{{ $it['task']['label'] }}</span>
        @endif
        <span class="hot-tag">Respondió{{ $it['hot'] ? ' · ' . $it['hot']->diffForHumans(null, true, true) : '' }}</span>
        <span class="wf2-card-end">
            @if($it['unread'] > 0)<span class="wf2-unread" data-wf-unread title="Mensajes sin leer">{{ $it['unread'] }}</span>@endif
            <span class="wf2-agent" title="{{ $agentName ?: 'Sin asignar' }}" data-wf-agent>{{ $agentName ? $initialsOf($agentName) : '–' }}</span>
        </span>
    </div>
</div>
