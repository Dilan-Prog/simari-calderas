{{--
    Tarjeta del Embudo de Venta con el mismo diseño que las tarjetas de Negocios (dh-card).
    Variable: $it (arreglo normalizado en index.blade.php): kind (conv|lead), id, lead_id, name, company, phone, email,
    channel, channel_label, preview, agent (User|null), unread, deal, last (Carbon|null), hot (Carbon|null), url.
    Los data-* y [data-wf-item] son los ganchos de whatsapp-funnel-board.js (arrastre, filtros, abrir chat).
--}}
@php
    $isConv = $it['kind'] === 'conv';
    $agentName = $it['agent']?->full_name;
    $initialsOf = fn (?string $n) => $n ? strtoupper(mb_substr(preg_replace('/[^\pL\s]/u', '', $n) ?: $n, 0, 1) . (str_contains(trim($n), ' ') ? mb_substr(trim(strstr(trim($n), ' ')), 0, 1) : '')) : '';
    $hours = $it['last'] ? $it['last']->diffInHours(now()) : null;
    [$trendCls, $trendTxt] = $hours === null ? ['dh-trend-flat', '–']
        : ($hours < 24 ? ['dh-trend-up', '▲ reciente'] : ($hours < 72 ? ['dh-trend-flat', '• ' . max(1, (int) floor($hours / 24)) . ' d sin mover'] : ['dh-trend-down', '▼ frío']));
@endphp
<div class="dh-card wf-card {{ $it['hot'] ? 'is-hot' : '' }}" data-wf-item
    @if($isConv) data-conv-id="{{ $it['id'] }}" @else data-lead-id="{{ $it['id'] }}" data-lead-url="{{ $it['url'] }}" @endif
    @if($it['hot_lead']) data-hot-lead="{{ $it['hot_lead'] }}" @endif
    data-name="{{ $it['name'] }}" data-phone="{{ $it['phone'] ?: $it['email'] }}" data-channel="{{ $it['channel'] }}"
    data-agent-id="{{ $it['agent']?->id }}" data-unread="{{ $it['unread'] }}"
    data-last-activity="{{ optional($it['last'])->timestamp }}">
    <div class="dh-card-top">
        <span class="dh-card-folio wf-chan" data-ch="{{ $it['channel'] }}">{{ $it['channel_label'] }}</span>
        <span class="hot-dot" aria-label="Respondió"></span>
        @if($it['unread'] > 0)
            <span class="wf-pill wf-pill--unread" data-wf-unread title="Mensajes sin leer">{{ $it['unread'] }}</span>
        @endif
        @if($agentName)
            <span class="dh-card-owner-avatar" title="{{ $agentName }}" data-wf-avatar>{{ $initialsOf($agentName) }}</span>
        @endif
    </div>
    <div class="dh-card-name">{{ $it['name'] }}</div>
    <div class="dh-card-customer">{{ $it['company'] ?: ($it['phone'] ?: ($it['email'] ?: 'Sin datos de contacto')) }}</div>
    @if($it['preview'])
        <div class="wf-card-preview">{{ $it['preview'] }}</div>
    @endif
    <div class="dh-card-bottom">
        <span class="wf-agent-label {{ $agentName ? '' : 'is-empty' }}" data-wf-agent>{{ $agentName ?: 'Sin asignar' }}</span>
        <span class="dh-card-trend {{ $trendCls }}">{{ $trendTxt }}</span>
    </div>
    <div class="wf-card-tags">
        <span class="hot-tag">Respondió{{ $it['hot'] ? ' · ' . $it['hot']->diffForHumans(null, true, true) : '' }}</span>
        @if($it['deal'])
            <span class="wf-pill wf-pill--deal" title="Negocio vinculado">Negocio</span>
        @endif
    </div>
    <div class="dh-card-quick-actions">
        @if($it['phone'])
            <a class="dh-quick-btn" href="tel:{{ $it['phone'] }}" title="Llamar" data-wf-quick>&#9742;</a>
        @else
            <span class="dh-quick-btn is-disabled" title="Sin teléfono">&#9742;</span>
        @endif
        @if($it['email'])
            <a class="dh-quick-btn" href="mailto:{{ $it['email'] }}" title="Correo" data-wf-quick>&#9993;</a>
        @else
            <span class="dh-quick-btn is-disabled" title="Sin correo">&#9993;</span>
        @endif
        @if($isConv)
            <button type="button" class="dh-quick-btn" data-wf-open-chat title="Abrir chat de WhatsApp" data-wf-quick>&#128172;</button>
        @else
            <a class="dh-quick-btn" href="{{ $it['url'] }}" title="Abrir en la bandeja de leads" data-wf-quick>&#128172;</a>
        @endif
        @if($isConv && $it['url'])
            <a class="dh-quick-btn" href="{{ $it['url'] }}" title="Ver el lead en la bandeja" data-wf-quick>&#128221;</a>
        @elseif(! $isConv)
            <a class="dh-quick-btn" href="{{ $it['url'] }}" title="Ver el lead en la bandeja" data-wf-quick>&#128221;</a>
        @else
            <span class="dh-quick-btn is-disabled" title="Sin lead ligado">&#128221;</span>
        @endif
    </div>
</div>
