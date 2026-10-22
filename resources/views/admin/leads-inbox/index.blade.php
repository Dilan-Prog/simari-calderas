@extends('admin.layouts.master')
@section('title')
    Bandeja de leads - Admin
@endsection
@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700;800&display=swap">
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-crm.css'])
@endpush
@push('scripts')
    @vite(['resources/js/admin/erp-crm.js'])
@endpush
@section('content')
    @php
        $isBoard = ($view ?? 'bandeja') === 'embudo';
        $query = request()->query();
        // URL de la bandeja conservando los parametros actuales; $drop los quita, $set los fija.
        $inboxUrl = function (array $set = [], array $drop = []) use ($query) {
            $params = array_merge(\Illuminate\Support\Arr::except($query, $drop), $set);
            return route('admin.leads-inbox.index', array_filter($params, fn ($v) => $v !== null && $v !== ''));
        };
        $initials = function (?string $name): string {
            $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
            $parts = array_values(array_filter($parts, fn ($p) => preg_match('/\pL/u', $p)));
            if (! $parts) { return '?'; }
            $a = mb_substr($parts[0], 0, 1);
            $b = count($parts) > 1 ? mb_substr($parts[1], 0, 1) : '';
            return mb_strtoupper($a . $b);
        };
        $subtitle = fn ($l) => $l->contact_company ?: optional($l->customer)->company ?: ($l->customer ? 'Cliente' : 'Sin identificar');
        $chIcon = [
            'whatsapp' => 'M12 3a9 9 0 0 0-7.7 13.6L3 21l4.5-1.2A9 9 0 1 0 12 3z',
            'llamada' => 'M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z',
            'correo' => 'M3 6h18v12H3zM3 7l9 7 9-7',
            'formulario' => 'M6 3h9l4 4v14H6zM9 12h7M9 16h7',
            'manual' => 'M12 5v14M5 12h14',
        ];
        $icon = fn ($k) => '<svg class="lw-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' . ($chIcon[$k] ?? $chIcon['manual']) . '"/></svg>';
        $stages = ['nuevo' => 'Nuevo', 'contactado' => 'Contactado', 'calificado' => 'Calificado', 'convertido' => 'Convertido'];
        $activeSource = request('source');
        $activeChip = request('chip');
        $thread = isset($timeline) ? array_reverse(is_array($timeline) ? $timeline : (array) $timeline) : [];
        // El chat solo muestra mensajes (WhatsApp, correo), notas y el renglon de "Lead creado"; el resto vive en la Bitacora.
        $bubbleTypes = ['whatsapp', 'correo', 'nota'];
        $chLabel = ['whatsapp' => 'WhatsApp', 'correo' => 'Correo', 'nota' => 'Nota interna'];
        $phoneDigits = $selected ? preg_replace('/\D+/', '', (string) $selected->whatsapp_target) : '';
        $firstName = $selected ? trim(explode(' ', trim((string) $selected->contact_name))[0] ?? '') : '';
        // Respuestas rapidas (texto estatico): {nombre} se reemplaza por el primer nombre del lead.
        $quickReplies = [
            'Saludo' => 'Hola {nombre}, gracias por escribir a Equiterm Industries. ¿En qué podemos ayudarte?',
            'Pedir datos' => 'Para preparar tu cotización, ¿nos compartes tu nombre, empresa, ciudad y correo electrónico?',
            'Cotización en proceso' => 'Perfecto, ya estamos preparando tu cotización. Te la enviamos por este medio en breve.',
            'Confirmar modelo' => '¿Nos confirmas el modelo exacto y la cantidad de piezas que necesitas?',
            'Horario' => 'Nuestro horario de atención es de lunes a viernes de 9:00 a 18:00 h y sábados de 9:00 a 14:00 h.',
            'Agendar llamada' => '¿Te parece si te llamamos para revisar los detalles? Indícanos el horario que te acomode.',
        ];
        $quickReplies = array_map(fn ($t) => str_replace('{nombre}', $firstName !== '' ? $firstName : 'buen día', $t), $quickReplies);
    @endphp

    <div class="lw-shell {{ $selected ? 'has-lead' : '' }}" data-lw>
        @if (session('success'))<div class="crm-flash ok lw-flash" role="status">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="crm-flash err lw-flash" role="alert">{{ session('error') }}</div>@endif
        @if ($errors->any())<div class="crm-flash err lw-flash" role="alert">{{ $errors->first() }}</div>@endif

        <header class="lw-top">
            <h1 class="lw-title">Bandeja de leads</h1>
            <div class="lw-toggle" role="tablist" aria-label="Vista">
                <a class="{{ ! $isBoard ? 'is-on' : '' }}" href="{{ $inboxUrl(['view' => 'bandeja'], ['lead']) }}" role="tab" aria-selected="{{ $isBoard ? 'false' : 'true' }}">Bandeja</a>
                <a href="{{ route('admin.whatsapp-funnel.index') }}" role="tab" aria-selected="false" title="Ir al Embudo de Venta">Embudo</a>
            </div>
            <details class="crm-new lw-new">
                <summary class="erp-btn erp-btn--sm">+ Nuevo lead</summary>
                <form method="POST" action="{{ route('admin.leads-inbox.store') }}" class="erp-card crm-new-form">
                    @csrf
                    <h2 class="erp-card-title">Registrar lead manual</h2>
                    <label class="erp-field">Nombre<input class="erp-input" type="text" name="name" value="{{ old('name') }}"></label>
                    <label class="erp-field">Teléfono<input class="erp-input" type="text" name="phone" value="{{ old('phone') }}"></label>
                    <label class="erp-field">Correo<input class="erp-input" type="text" name="email" value="{{ old('email') }}"></label>
                    <label class="erp-field">Origen
                        <select class="erp-select" name="source">
                            @foreach (\App\Models\Lead::SOURCES as $k => $label)
                                <option value="{{ $k }}" @selected($k === 'manual')>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="erp-field">Notas<textarea class="erp-input" name="notes" rows="3">{{ old('notes') }}</textarea></label>
                    <div class="erp-form-actions"><button class="erp-btn erp-btn--primary" type="submit">Registrar</button></div>
                </form>
            </details>
            <button type="button" class="lw-theme-toggle" data-lw-theme-toggle aria-label="Modo oscuro" aria-pressed="false" title="Modo oscuro">
                <svg class="lw-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg><span>Modo oscuro</span>
            </button>
            <div class="lw-kpis">
                <span class="lw-kpi {{ ($kpis['sin_atender'] ?? 0) ? 'is-bad' : '' }}"><strong>{{ $kpis['sin_atender'] ?? 0 }}</strong> sin atender</span>
                <span class="lw-kpi"><strong>{{ isset($kpis['primera_respuesta_min']) ? $kpis['primera_respuesta_min'] . ' min' : '—' }}</strong> 1.ª respuesta</span>
                @if (! $isBoard && $selected)
                    <button type="button" class="erp-btn erp-btn--sm lw-info-btn" data-lw-info>Datos</button>
                @endif
            </div>
        </header>

        @if ($isBoard)
            @include('admin.leads-inbox._board', ['board' => $board])
        @else
        <div class="lw">
            {{-- ───────── Panel izquierdo: lista ───────── --}}
            <aside class="lw-list" aria-label="Leads">
                <div class="lw-tiles">
                    @php
                        $tiles = [
                            [null, 'Todos', $channels['todos'] ?? 0],
                            ['whatsapp', 'WhatsApp', $channels['whatsapp'] ?? 0],
                            ['llamada', 'Llamadas', $channels['llamada'] ?? 0],
                            ['correo', 'Correo', $channels['correo'] ?? 0],
                            ['formulario', 'Formulario', $channels['formulario'] ?? 0],
                        ];
                    @endphp
                    @foreach ($tiles as [$key, $label, $n])
                        <a class="lw-tile {{ ($activeSource ?: null) === $key ? 'is-on' : '' }}" href="{{ $inboxUrl(['source' => $key], ['lead']) }}" title="{{ $label }}" aria-label="{{ $label }}: {{ $n }}">
                            @if ($key === null)
                                <svg class="lw-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                            @else
                                {!! $icon($key) !!}
                            @endif
                            <span class="lw-tile-n">{{ $n }}</span>
                            <span class="lw-tile-l">{{ $label }}</span>
                        </a>
                    @endforeach
                </div>

                <form method="GET" action="{{ route('admin.leads-inbox.index') }}" class="lw-search" role="search">
                    @foreach (\Illuminate\Support\Arr::except($query, ['q', 'lead', 'page']) as $k => $v)
                        @if (is_scalar($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
                    @endforeach
                    <svg class="lw-search-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                    <input class="erp-input" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar nombre, teléfono o correo">
                </form>

                <div class="lw-chips">
                    @foreach ([[null, 'Todos', 'todos'], ['sin_atender', 'Sin atender', 'sin_atender'], ['mios', 'Míos', 'mios'], ['sin_asignar', 'Sin asignar', 'sin_asignar']] as [$ck, $cl, $cc])
                        <a class="lw-chip {{ ($activeChip ?: null) === $ck ? 'is-on' : '' }}" href="{{ $inboxUrl(['chip' => $ck], ['lead']) }}">{{ $cl }} <b>{{ $chipCounts[$cc] ?? 0 }}</b></a>
                    @endforeach
                </div>

                @php $rowStages = \App\Support\LeadStages::defaultPipeline()?->stages()->get() ?? collect(); @endphp
                <nav class="lw-rows">
                    @forelse ($leads as $l)
                        @php $unattended = $l->status === 'nuevo'; @endphp
                        <a class="lw-row {{ $selected && $selected->id === $l->id ? 'is-active' : '' }} {{ $unattended ? 'is-unread' : '' }}" href="{{ $inboxUrl(['lead' => $l->id]) }}">
                            <span class="lw-av-wrap">
                                <span class="lw-av">{{ $initials($l->display_name) }}</span>
                                <span class="lw-row-ch lw-row-ch--{{ $l->source }}" title="{{ $l->source_label }}">{!! $icon($l->source) !!}</span>
                            </span>
                            <span class="lw-row-main">
                                <span class="lw-row-top">
                                    <span class="lw-row-name">{{ $l->display_name }}</span>
                                    <span class="lw-row-time">{{ $l->preview_at ? $l->preview_at->diffForHumans(null, true, true) : '' }}</span>
                                </span>
                                <span class="lw-row-line">
                                    <span class="lw-row-prev {{ $unattended ? 'is-bold' : '' }}">{{ $l->preview ?: '—' }}</span>
                                    @if ($unattended)<span class="lw-unread" title="Sin atender" aria-label="Sin atender"></span>@endif
                                </span>
                                <span class="lw-row-meta">
                                    <span class="lw-stage">{{ \App\Support\LeadStages::current($l, $rowStages ?? null)?->name ?? $l->status_label }}</span>
                                    @include('admin.leads-inbox._channels', ['lead' => $l, 'icon' => $icon])
                                    <span class="lw-row-who" title="{{ $l->assignee ? $l->assignee->full_name : 'Sin asignar' }}">{{ $l->assignee ? $initials($l->assignee->full_name) : '—' }}</span>
                                </span>
                            </span>
                        </a>
                    @empty
                        <x-erp.empty title="No hay leads con estos filtros" text="Ajusta la búsqueda o el canal para ver más conversaciones." />
                    @endforelse
                </nav>
            </aside>

            {{-- ───────── Panel central: conversación ───────── --}}
            <section class="lw-conv" aria-label="Conversación">
                @if ($selected)
                    <div class="lw-conv-head">
                        <a class="lw-back" href="{{ $inboxUrl([], ['lead']) }}">&larr; Volver</a>
                        <span class="lw-av lw-av--lg">{{ $initials($selected->display_name) }}</span>
                        <div class="lw-conv-who">
                            <div class="lw-conv-name">
                                <h2>{{ $selected->display_name }}</h2>
                                <p>{{ $subtitle($selected) }}</p>
                                <div class="lw-conv-ch">@include('admin.leads-inbox._channels', ['lead' => $selected, 'icon' => $icon])</div>
                            </div>
                            @if ($wa['ready'] ?? false)
                                @php
                                    $winMin = $wa['window_open'] && $wa['window_until'] ? max(0, (int) now()->diffInMinutes($wa['window_until'], false)) : 0;
                                    $winPct = max(0, min(100, round($winMin / 1440 * 100)));
                                @endphp
                                <div class="lw-wa-state {{ $wa['window_open'] ? 'is-open' : 'is-closed' }}">
                                    <span class="lw-wa-state-t">
                                        @if ($wa['window_open'])
                                            Ventana de 24 h abierta · {{ intdiv($winMin, 60) }} h {{ str_pad((string) ($winMin % 60), 2, '0', STR_PAD_LEFT) }} min restantes
                                        @else
                                            Ventana de 24 h cerrada · solo plantillas
                                        @endif
                                    </span>
                                    @if ($wa['window_open'])
                                        <span class="lw-wa-bar" aria-hidden="true"><span style="width: {{ $winPct }}%"></span></span>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="lw-actions">
                            @if ($selected->contact_phone)
                                <a class="erp-btn erp-btn--sm" href="tel:{{ $selected->contact_phone }}" title="Llamar"><svg class="lw-ic lw-ic--call" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/></svg><span>Llamar</span></a>
                            @endif
                            @if ($phoneDigits)
                                <a class="erp-btn erp-btn--sm" href="https://wa.me/{{ $phoneDigits }}" target="_blank" rel="noopener" title="WhatsApp"><svg class="lw-ic lw-ic--wa" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a9 9 0 0 0-7.7 13.6L3 21l4.5-1.2A9 9 0 1 0 12 3z"/></svg><span>WhatsApp</span></a>
                            @endif
                            @if ($selected->contact_email)
                                <a class="erp-btn erp-btn--sm" href="mailto:{{ $selected->contact_email }}" title="Correo"><svg class="lw-ic lw-ic--mail" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18v12H3zM3 7l9 7 9-7"/></svg><span>Correo</span></a>
                            @endif
                            <button type="button" class="lw-info-toggle" data-lw-info title="Ficha del lead" aria-label="Ficha del lead"><svg class="lw-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M15 3v18"/></svg></button>
                        </div>
                    </div>

                    @php
                        $pipeStages = \App\Support\LeadStages::stages($selected);
                        $curStage = \App\Support\LeadStages::current($selected, $pipeStages);
                        $lostStage = $pipeStages->firstWhere('is_lost', true);
                    @endphp
                    <div class="lw-stages">
                        <ol class="lw-chevrons">
                            @foreach ($pipeStages->where('is_lost', false) as $ps)
                                <li class="{{ $curStage && $curStage->id === $ps->id ? 'is-on' : '' }}">
                                    @if ($curStage && $curStage->id === $ps->id)
                                        <span>{{ $ps->name }}</span>
                                    @else
                                        <form method="POST" action="{{ route('admin.leads-inbox.stage.update', $selected) }}">
                                            @csrf
                                            <input type="hidden" name="pipeline_stage_id" value="{{ $ps->id }}">
                                            <button type="submit">{{ $ps->name }}</button>
                                        </form>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                        @if ($lostStage)
                            <form method="POST" action="{{ route('admin.leads-inbox.stage.update', $selected) }}" class="lw-lost {{ $curStage && $curStage->id === $lostStage->id ? 'is-on' : '' }}">
                                @csrf
                                <input type="hidden" name="pipeline_stage_id" value="{{ $lostStage->id }}">
                                <button type="submit" @disabled($curStage && $curStage->id === $lostStage->id)>{{ $lostStage->name }}</button>
                            </form>
                        @endif
                        @if (! $selected->assigned_to)
                            <form method="POST" action="{{ route('admin.leads-inbox.assign', $selected) }}">
                                @csrf
                                <input type="hidden" name="assigned_to" value="{{ auth()->id() }}">
                                <button class="erp-btn erp-btn--primary erp-btn--sm" type="submit">Tomar lead</button>
                            </form>
                        @endif
                    </div>

                    <div class="lw-thread" data-lw-thread>
                        @php $lastDay = null; @endphp
                        @forelse ($thread as $ev)
                            @php
                                $t = $ev['type'] ?? 'lead'; $at = $ev['at'] ?? null;
                                $dayKey = $at ? $at->format('Y-m-d') : null;
                                $dayLabel = ! $at ? null : ($at->isToday() ? 'Hoy' : ($at->isYesterday() ? 'Ayer' : $at->format('d/m/Y')));
                            @endphp
                            @if (in_array($t, $bubbleTypes, true) || $t === 'lead')
                                @if ($dayKey && $dayKey !== $lastDay)
                                    @php $lastDay = $dayKey; @endphp
                                    <div class="lw-day"><span>{{ $dayLabel }}</span></div>
                                @endif
                            @endif
                            @if (in_array($t, $bubbleTypes, true))
                                @php $isOut = ! empty($ev['out']); @endphp
                                <div class="lw-msg lw-msg--{{ $t }} {{ $isOut ? 'is-out' : '' }}">
                                    <div class="lw-bubble">
                                        @if ($t === 'correo' && ! empty($ev['title']))
                                            <div class="lw-bubble-h"><span class="lw-bubble-ch">{!! $icon('correo') !!}<b>{{ $ev['title'] }}</b></span></div>
                                        @elseif ($t === 'nota')
                                            <div class="lw-bubble-h"><span class="lw-bubble-ch">Nota interna{{ ! empty($ev['title']) ? ' de ' . \Illuminate\Support\Str::after($ev['title'], 'Nota de ') : '' }} · solo equipo</span></div>
                                        @endif
                                        @if (! empty($ev['body']))<div class="lw-bubble-b">{{ $ev['body'] }}</div>@endif
                                        <div class="lw-bubble-t"><span>{{ $at ? $at->format('H:i') : '' }}</span>@if ($isOut && $t === 'whatsapp')<svg class="lw-tick" viewBox="0 0 16 11" width="16" height="11" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 5.8l2.6 2.6L9.5 2.5M6.5 8.4L12.4 2.5"/></svg>@endif</div>
                                    </div>
                                </div>
                            @elseif ($t === 'lead')
                                <div class="lw-event"><span>{{ $ev['title'] ?? '' }}@if ($at)<em>{{ $at->format('H:i') }}</em>@endif</span></div>
                            @endif
                        @empty
                            <p class="lw-empty">Aún no hay actividad en este lead.</p>
                        @endforelse
                    </div>

                    <div class="lw-composer" data-lw-composer>
                        <div class="lw-tabs" role="tablist">
                            <button type="button" class="is-on" data-tab="whatsapp" role="tab">WhatsApp</button>
                            <button type="button" data-tab="correo" role="tab">Correo</button>
                            <button type="button" data-tab="nota" role="tab">Nota interna</button>
                            <span class="lw-tabs-hint">Escribe <b>/</b> para respuestas rápidas</span>
                        </div>
                        @if (($wa['ready'] ?? false) && $wa['window_open'])
                            <div class="lw-quick" data-lw-quick aria-label="Respuestas rápidas">
                                @foreach ($quickReplies as $qLabel => $qText)
                                    <button type="button" data-quick-text="{{ $qText }}">{{ $qLabel }}</button>
                                @endforeach
                            </div>
                        @endif
                        <div class="lw-pane lw-wa" data-pane="whatsapp" data-wa-templates="{{ route('admin.leads-inbox.whatsapp.templates', $selected) }}">
                            @if (! ($wa['ready'] ?? false))
                                <p class="lw-soon">{{ $wa['reason'] ?? 'WhatsApp no está disponible para este lead.' }}</p>
                                @if ($phoneDigits)<a class="erp-btn erp-btn--sm" href="https://wa.me/{{ $phoneDigits }}" target="_blank" rel="noopener">Abrir WhatsApp</a>@endif
                            @else
                                <form method="POST" action="{{ route('admin.leads-inbox.whatsapp.send', $selected) }}" data-wa-form>
                                    @csrf
                                    @unless ($wa['window_open'])
                                        <div class="lw-wa-closed">
                                            <b>Ventana de 24 h cerrada</b>
                                            <span>Para {{ $wa['number'] }}: WhatsApp solo permite plantillas aprobadas hasta que el cliente vuelva a escribir.</span>
                                        </div>
                                    @endunless
                                    <div class="lw-wa-mode {{ $wa['window_open'] ? '' : 'is-closed' }}" role="radiogroup" aria-label="Tipo de mensaje">
                                        <label><input type="radio" name="type" value="text" data-wa-mode @checked(old('type', $wa['window_open'] ? 'text' : 'template') === 'text') @disabled(! $wa['window_open'])> Mensaje</label>
                                        <label><input type="radio" name="type" value="template" data-wa-mode @checked(old('type', $wa['window_open'] ? 'text' : 'template') === 'template')> Plantilla</label>
                                    </div>
                                    <div data-wa-part="text" class="lw-input-row">
                                        <div class="lw-box">
                                            <button type="button" class="lw-emoji-btn" data-lw-emoji-toggle title="Emoji" aria-label="Insertar emoji" @disabled(! $wa['window_open'])><svg class="lw-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><path d="M9 9.5h.01M15 9.5h.01" stroke-width="2.6"/></svg></button>
                                            <textarea class="erp-input" name="text" rows="1" maxlength="4000" placeholder="Escribe tu mensaje de WhatsApp…" data-lw-text @disabled(! $wa['window_open'])>{{ old('text') }}</textarea>
                                        </div>
                                        <button class="lw-send lw-send--wa" type="submit" title="Enviar por WhatsApp" aria-label="Enviar por WhatsApp"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/></svg><span class="sr-only">Enviar por WhatsApp</span></button>
                                        <div class="lw-emoji" data-lw-emoji hidden>
                                            @foreach (['😀','😊','👍','🙏','👋','✅','🙌','😉','🤝','📞','📄','🔧','🔥','💧','⏰','🙂'] as $emo)<button type="button" data-lw-emoji-pick="{{ $emo }}">{{ $emo }}</button>@endforeach
                                        </div>
                                    </div>
                                    <div data-wa-part="template" hidden>
                                        <select class="erp-select" name="template_name" data-wa-template data-old="{{ old('template_name') }}">
                                            <option value="">Cargando plantillas…</option>
                                        </select>
                                        <input type="hidden" name="language" value="{{ old('language', 'es_MX') }}" data-wa-language>
                                        <div class="lw-wa-params" data-wa-params></div>
                                        <p class="lw-wa-hint" data-wa-hint hidden></p>
                                    </div>
                                    <div class="lw-pane-foot" data-wa-part="template" hidden><button class="lw-send lw-send--wa" type="submit" title="Enviar por WhatsApp" aria-label="Enviar por WhatsApp"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/></svg><span class="sr-only">Enviar por WhatsApp</span></button></div>
                                </form>
                            @endif
                        </div>
                        <div class="lw-pane" data-pane="correo" hidden>
                            <p class="lw-soon">El envío desde la bandeja aún no está disponible.</p>
                            @if ($selected->contact_email)<a class="erp-btn erp-btn--sm" href="mailto:{{ $selected->contact_email }}" target="_blank" rel="noopener">Abrir correo</a>@endif
                        </div>
                        <div class="lw-pane lw-nota" data-pane="nota" hidden>
                            <form method="POST" action="{{ route('admin.leads-inbox.notes.store', $selected) }}">
                                @csrf
                                <div class="lw-input-row">
                                    <div class="lw-box lw-box--nota"><textarea class="erp-input" name="body" rows="1" maxlength="2000" placeholder="Nota para el equipo (el cliente no la ve)…" required></textarea></div>
                                    <button class="lw-send lw-send--nota" type="submit" title="Guardar nota interna" aria-label="Guardar nota interna"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/></svg><span class="sr-only">Guardar nota interna</span></button>
                                </div>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="lw-placeholder">
                        <x-erp.empty title="Selecciona un lead" text="Elige una conversación de la lista para ver su historial y responder." />
                    </div>
                @endif
            </section>

            {{-- ───────── Panel derecho: datos ───────── --}}
            <aside class="lw-info" aria-label="Datos del lead">
                @if ($selected)
                    @php
                        $click = $selected->click_identifier;
                        $clickLabel = $click ? ('Sí · ' . strtoupper($click['type'] ?? '')) : 'No';
                    @endphp
                    <div class="lw-info-head">
                        <span class="lw-info-title">Ficha del lead</span>
                        <button type="button" class="lw-info-close" data-lw-info aria-label="Cerrar ficha">&times;</button>
                    </div>

                    <details class="lw-card lw-sec" data-sec="contacto" open>
                        <summary class="lw-sec-h"><h3>Contacto</h3><span class="lw-sec-chev" aria-hidden="true"></span></summary>
                        <div class="lw-sec-b">
                        <dl class="lw-kv">
                            <dt>Empresa</dt>
                            <dd>{{ $selected->contact_company ?: optional($selected->customer)->company ?: '—' }}</dd>
                            <dt>Teléfono</dt>
                            <dd>{{ $selected->contact_phone ?: '—' }} @if ($selected->contact_phone)<a class="lw-mini" href="tel:{{ $selected->contact_phone }}">Llamar</a>@endif</dd>
                            <dt>WhatsApp</dt>
                            <dd>{{ $selected->whatsapp_display ?: '—' }} @if ($phoneDigits)<a class="lw-mini" href="#" data-wa-open>Escribir</a>@endif</dd>
                            <dt>Correo</dt>
                            <dd>{{ $selected->contact_email ?: '—' }} @if ($selected->contact_email)<a class="lw-mini" href="mailto:{{ $selected->contact_email }}">Escribir</a>@endif</dd>
                            <dt>Sitio web</dt>
                            <dd>@if ($selected->contact_website)<a class="lw-mini" href="{{ $selected->contact_website }}" target="_blank" rel="noopener noreferrer">{{ preg_replace('#^https?://(www\.)?#i', '', rtrim($selected->contact_website, '/')) }}</a>@else —@endif</dd>
                        </dl>
                        <details class="lw-edit" data-lw-edit @if ($errors->hasAny(['name', 'company', 'phone', 'whatsapp', 'email', 'website'])) open @endif>
                            <summary class="erp-btn erp-btn--sm">Editar contacto</summary>
                            <form method="POST" action="{{ route('admin.leads-inbox.contact.update', $selected) }}" class="lw-edit-form">
                                @csrf @method('PUT')
                                <label>Nombre <input class="erp-input" type="text" name="name" maxlength="150" value="{{ old('name', $selected->contact_name) }}"></label>
                                <label>Empresa <input class="erp-input" type="text" name="company" maxlength="150" value="{{ old('company', $selected->contact_company ?: optional($selected->customer)->company) }}" placeholder="Nombre de la empresa"></label>
                                <label>Teléfono <input class="erp-input" type="text" name="phone" maxlength="40" inputmode="tel" value="{{ old('phone', $selected->contact_phone) }}" placeholder="449 123 4567"></label>
                                <label>WhatsApp <input class="erp-input" type="text" name="whatsapp" maxlength="40" inputmode="tel" value="{{ old('whatsapp', $selected->contact_whatsapp) }}" placeholder="Vacío = el mismo que el teléfono"></label>
                                <label>Correo <input class="erp-input" type="email" name="email" maxlength="150" value="{{ old('email', $selected->contact_email) }}" placeholder="correo@empresa.com"></label>
                                <label>Sitio web <input class="erp-input" type="text" name="website" maxlength="255" value="{{ old('website', $selected->contact_website) }}" placeholder="www.empresa.com"></label>
                                <p class="lw-muted">Sin lada se asume México (+52).</p>
                                <div class="lw-pane-foot"><button class="erp-btn erp-btn--primary erp-btn--sm" type="submit">Guardar datos</button></div>
                            </form>
                        </details>
                        </div>
                    </details>

                    <details class="lw-card lw-sec" data-sec="lead" open>
                        <summary class="lw-sec-h"><h3>Lead</h3><span class="lw-sec-chev" aria-hidden="true"></span></summary>
                        <div class="lw-sec-b">
                        <form method="POST" action="{{ route('admin.leads-inbox.stage.update', $selected) }}" class="lw-field">
                            @csrf
                            <label>Etapa (Embudo de Venta)
                                <select class="erp-select" name="pipeline_stage_id" onchange="this.form.submit()">
                                    @foreach ($pipeStages ?? \App\Support\LeadStages::stages($selected) as $ps)
                                        <option value="{{ $ps->id }}" @selected(($curStage ?? \App\Support\LeadStages::current($selected))?->id === $ps->id)>{{ $ps->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </form>
                        <form method="POST" action="{{ route('admin.leads-inbox.assign', $selected) }}" class="lw-field">
                            @csrf
                            <label>Responsable
                                <select class="erp-select" name="assigned_to" onchange="this.form.submit()">
                                    <option value="">Sin asignar</option>
                                    @foreach ($users as $u)
                                        <option value="{{ $u->id }}" @selected((string) $selected->assigned_to === (string) $u->id)>{{ $u->first_name }} {{ $u->last_name }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </form>
                        <dl class="lw-kv"><dt>Creado</dt><dd>{{ optional($selected->created_at)->format('d/m/Y H:i') }}</dd></dl>
                        </div>
                    </details>

                    @include('admin.leads-inbox._deal', ['lead' => $selected, 'users' => $users])

                    <details class="lw-card lw-sec" data-sec="origen">
                        <summary class="lw-sec-h"><h3>Origen</h3><span class="lw-sec-chev" aria-hidden="true"></span></summary>
                        <div class="lw-sec-b">
                        <dl class="lw-kv">
                            <dt>Fuente</dt><dd>{{ $selected->source_label }}@if ($selected->utm_source) · {{ $selected->utm_source }}@endif</dd>
                            <dt>Campaña</dt><dd>{{ $selected->utm_campaign ?: '—' }}</dd>
                            <dt>Palabra clave</dt><dd>{{ $selected->utm_term ?: '—' }}</dd>
                            <dt>GCLID capturado</dt><dd>{{ $clickLabel }}</dd>
                        </dl>
                        </div>
                    </details>

                    <details class="lw-card lw-sec lw-notes" data-sec="notas" @if ($errors->has('body') || old('body')) open data-force-open @endif>
                        <summary class="lw-sec-h"><h3>Notas</h3><span class="lw-sec-chev" aria-hidden="true"></span></summary>
                        <div class="lw-sec-b">
                        <form method="POST" action="{{ route('admin.leads-inbox.notes.store', $selected) }}">
                            @csrf
                            <textarea class="erp-input" name="body" rows="3" placeholder="Escribe una nota interna…" required>{{ old('body') }}</textarea>
                            <div class="lw-pane-foot"><button class="erp-btn erp-btn--primary erp-btn--sm" type="submit">Guardar nota</button></div>
                        </form>
                        </div>
                    </details>

                    <details class="lw-card lw-sec" data-sec="tarea" open>
                        <summary class="lw-sec-h"><h3>Próxima tarea</h3><span class="lw-sec-chev" aria-hidden="true"></span></summary>
                        <div class="lw-sec-b">
                        @if ($nextTask)
                            <a class="lw-task" href="{{ route('admin.tasks.show', $nextTask) }}">
                                <b>{{ $nextTask->title }}</b>
                                <span>Vence {{ $nextTask->due_at ? $nextTask->due_at->format('d/m/Y H:i') : 'sin fecha' }}</span>
                            </a>
                        @else
                            <p class="lw-muted">Sin tareas pendientes.</p>
                        @endif
                        <button type="button" class="erp-btn erp-btn--sm lw-newtask" data-task-modal-open="lead">+ Crear tarea</button>
                        </div>
                    </details>

                    @includeIf('admin.leads-inbox._log', ['lead' => $selected])

                    <div class="lw-cta">
                        @if ($selected->customer_id)
                            <a class="erp-btn" href="{{ route('admin.clients.information', $selected->customer_id) }}">Ver cliente</a>
                        @else
                            <button type="button" class="erp-btn" data-lead-customer="{{ route('admin.leads-inbox.customer.form', $selected) }}">Agregar como cliente</button>
                        @endif
                        <a class="erp-btn erp-btn--link" href="{{ route('admin.leads-inbox.show', $selected) }}">Abrir ficha completa</a>
                    </div>
                @endif
            </aside>
        </div>
        @endif
    </div>

    @if ($selected)
        @include('admin.tasks._form_modal', [
            'mode' => 'lead',
            'users' => $users,
            'action' => route('admin.leads-inbox.task.store', $selected),
            'eyebrow' => 'Lead · ' . $selected->display_name,
            'modalTitle' => 'Nueva tarea del lead',
            'relatedLabel' => 'Lead ' . $selected->display_name,
            'defaultAssignee' => $selected->assigned_to ?: auth()->id(),
        ])
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var composer = document.querySelector('[data-lw-composer]');
                if (!composer) return;
                var pane = composer.querySelector('[data-pane="whatsapp"]');
                var tab = composer.querySelector('.lw-tabs [data-tab="whatsapp"]');
                var loaded = false;

                function loadTemplates() {
                    if (loaded || !pane) return;
                    var select = pane.querySelector('[data-wa-template]');
                    if (!select) return;
                    loaded = true;
                    var params = pane.querySelector('[data-wa-params]');
                    var lang = pane.querySelector('[data-wa-language]');
                    var hint = pane.querySelector('[data-wa-hint]');
                    var templates = [];

                    function renderParams() {
                        var t = templates.find(function (x) { return x.name === select.value; });
                        params.innerHTML = '';
                        if (lang) lang.value = (t && t.language) || 'es_MX';
                        ((t && t.params) || []).forEach(function (label, i) {
                            var l = document.createElement('label');
                            l.textContent = label + ' ';
                            var inp = document.createElement('input');
                            inp.className = 'erp-input'; inp.type = 'text'; inp.name = 'params[]'; inp.maxLength = 1024; inp.required = true;
                            if (i === 0 && /nombre/i.test(label)) inp.value = {!! json_encode(trim(explode(' ', (string) $selected->contact_name)[0] ?? '')) !!};
                            l.appendChild(inp);
                            params.appendChild(l);
                        });
                        syncMode();
                    }

                    fetch(pane.dataset.waTemplates, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            templates = data.templates || [];
                            select.innerHTML = '';
                            var first = document.createElement('option');
                            first.value = ''; first.textContent = templates.length ? 'Elige una plantilla aprobada…' : 'No hay plantillas aprobadas';
                            select.appendChild(first);
                            templates.forEach(function (t) {
                                var o = document.createElement('option');
                                o.value = t.name; o.textContent = t.label + ' (' + t.name + ')';
                                select.appendChild(o);
                            });
                            if (select.dataset.old) { select.value = select.dataset.old; }
                            if (!templates.length && hint) {
                                hint.hidden = false;
                                hint.textContent = 'Crea y aprueba plantillas en Meta (WhatsApp Manager). Aparecerán aquí en unos minutos.';
                            }
                            renderParams();
                        })
                        .catch(function () { select.innerHTML = '<option value="">No se pudieron cargar las plantillas</option>'; });
                    select.addEventListener('change', renderParams);
                }

                function syncMode() {
                    if (!pane) return;
                    var checked = pane.querySelector('[data-wa-mode]:checked');
                    var mode = checked ? checked.value : 'template';
                    pane.querySelectorAll('[data-wa-part]').forEach(function (p) { p.hidden = p.dataset.waPart !== mode; });
                    var tpl = pane.querySelector('[data-wa-template]');
                    if (tpl) tpl.disabled = mode !== 'template';
                    pane.querySelectorAll('[data-wa-params] input').forEach(function (i) { i.disabled = mode !== 'template'; });
                    var txt = pane.querySelector('textarea[name="text"]');
                    if (txt) txt.required = mode === 'text' && !txt.disabled;
                }

                function openWhatsapp() { if (tab) { tab.click(); } composer.scrollIntoView({ block: 'nearest' }); }

                if (tab) tab.addEventListener('click', function () { loadTemplates(); syncMode(); });
                if (tab && tab.classList.contains('is-on')) { loadTemplates(); syncMode(); }
                if (pane) pane.addEventListener('change', syncMode);
                @if (session('composer_tab') === 'whatsapp')
                    openWhatsapp();
                @endif
            });
        </script>
    @endif
@endsection
