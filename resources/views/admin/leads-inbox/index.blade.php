@extends('admin.layouts.master')
@section('title')
    Bandeja de leads - Admin
@endsection
@push('styles')
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
        $subtitle = fn ($l) => optional($l->customer)->company ?: ($l->customer ? 'Cliente' : 'Sin identificar');
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
        $bubbleTypes = ['whatsapp', 'correo', 'nota', 'llamada', 'formulario'];
        $chLabel = ['whatsapp' => 'WhatsApp', 'correo' => 'Correo', 'nota' => 'Nota', 'llamada' => 'Llamada', 'formulario' => 'Formulario'];
        $phoneDigits = $selected ? preg_replace('/\D+/', '', (string) $selected->contact_phone) : '';
    @endphp

    <div class="lw-shell {{ $selected ? 'has-lead' : '' }}" data-lw>
        @if (session('success'))<div class="crm-flash ok lw-flash" role="status">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="crm-flash err lw-flash" role="alert">{{ session('error') }}</div>@endif
        @if ($errors->any())<div class="crm-flash err lw-flash" role="alert">{{ $errors->first() }}</div>@endif

        <header class="lw-top">
            <h1 class="lw-title">Bandeja de leads</h1>
            <div class="lw-toggle" role="tablist" aria-label="Vista">
                <a class="{{ ! $isBoard ? 'is-on' : '' }}" href="{{ $inboxUrl(['view' => 'bandeja'], ['lead']) }}" role="tab" aria-selected="{{ $isBoard ? 'false' : 'true' }}">Bandeja</a>
                <a class="{{ $isBoard ? 'is-on' : '' }}" href="{{ $inboxUrl(['view' => 'embudo'], ['lead']) }}" role="tab" aria-selected="{{ $isBoard ? 'true' : 'false' }}">Embudo</a>
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
                        <a class="lw-tile {{ ($activeSource ?: null) === $key ? 'is-on' : '' }}" href="{{ $inboxUrl(['source' => $key], ['lead']) }}">
                            <span class="lw-tile-n">{{ $n }}</span>
                            <span class="lw-tile-l">{{ $label }}</span>
                        </a>
                    @endforeach
                </div>

                <form method="GET" action="{{ route('admin.leads-inbox.index') }}" class="lw-search" role="search">
                    @foreach (\Illuminate\Support\Arr::except($query, ['q', 'lead', 'page']) as $k => $v)
                        @if (is_scalar($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
                    @endforeach
                    <input class="erp-input" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar nombre, teléfono o correo">
                </form>

                <div class="lw-chips">
                    @foreach ([[null, 'Todos', 'todos'], ['sin_atender', 'Sin atender', 'sin_atender'], ['mios', 'Míos', 'mios'], ['sin_asignar', 'Sin asignar', 'sin_asignar']] as [$ck, $cl, $cc])
                        <a class="lw-chip {{ ($activeChip ?: null) === $ck ? 'is-on' : '' }}" href="{{ $inboxUrl(['chip' => $ck], ['lead']) }}">{{ $cl }} <b>{{ $chipCounts[$cc] ?? 0 }}</b></a>
                    @endforeach
                </div>

                <nav class="lw-rows">
                    @forelse ($leads as $l)
                        @php $unattended = $l->status === 'nuevo'; @endphp
                        <a class="lw-row {{ $selected && $selected->id === $l->id ? 'is-active' : '' }} {{ $unattended ? 'is-unread' : '' }}" href="{{ $inboxUrl(['lead' => $l->id]) }}">
                            <span class="lw-av">{{ $initials($l->display_name) }}</span>
                            <span class="lw-row-main">
                                <span class="lw-row-top">
                                    <span class="lw-row-name">{{ $l->display_name }}</span>
                                    <span class="lw-row-time">{{ $l->preview_at ? $l->preview_at->diffForHumans(null, true, true) : '' }}</span>
                                </span>
                                <span class="lw-row-sub">{{ $subtitle($l) }}</span>
                                <span class="lw-row-prev {{ $unattended ? 'is-bold' : '' }}">{{ $l->preview ?: '—' }}</span>
                                <span class="lw-row-meta">
                                    <span class="lw-row-ch" title="{{ $l->source_label }}">{!! $icon($l->source) !!}</span>
                                    <span class="erp-inbox-tag erp-inbox-tag--{{ $l->status_tone }}">{{ $l->status_label }}</span>
                                    <span class="lw-row-who">{{ $l->assignee ? $l->assignee->first_name : 'Sin asignar' }}</span>
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
                            <h2>{{ $selected->display_name }}</h2>
                            <p>{{ $subtitle($selected) }}</p>
                        </div>
                        <div class="lw-actions">
                            @if ($selected->contact_phone)
                                <a class="erp-btn erp-btn--sm" href="tel:{{ $selected->contact_phone }}">Llamar</a>
                                <a class="erp-btn erp-btn--sm" href="https://wa.me/{{ $phoneDigits }}" target="_blank" rel="noopener">WhatsApp</a>
                            @endif
                            @if ($selected->contact_email)
                                <a class="erp-btn erp-btn--sm" href="mailto:{{ $selected->contact_email }}">Correo</a>
                            @endif
                        </div>
                    </div>

                    <div class="lw-stages">
                        <ol class="lw-chevrons">
                            @foreach ($stages as $sk => $sl)
                                <li class="{{ $selected->status === $sk ? 'is-on' : '' }}">
                                    @if ($selected->status === $sk)
                                        <span>{{ $sl }}</span>
                                    @else
                                        <form method="POST" action="{{ route('admin.leads-inbox.update', $selected) }}">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="status" value="{{ $sk }}">
                                            <button type="submit">{{ $sl }}</button>
                                        </form>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                        @if (! $selected->assigned_to)
                            <form method="POST" action="{{ route('admin.leads-inbox.assign', $selected) }}">
                                @csrf
                                <input type="hidden" name="assigned_to" value="{{ auth()->id() }}">
                                <button class="erp-btn erp-btn--primary erp-btn--sm" type="submit">Tomar lead</button>
                            </form>
                        @endif
                    </div>

                    <div class="lw-thread" data-lw-thread>
                        @forelse ($thread as $ev)
                            @php $t = $ev['type'] ?? 'lead'; $at = $ev['at'] ?? null; @endphp
                            @if (in_array($t, $bubbleTypes, true))
                                <div class="lw-msg lw-msg--{{ $t }}">
                                    <div class="lw-bubble">
                                        <div class="lw-bubble-h"><span class="lw-bubble-ch">{!! $icon($t === 'nota' ? 'manual' : $t) !!}{{ $chLabel[$t] }}</span>@if (! empty($ev['title']))<b>{{ $ev['title'] }}</b>@endif</div>
                                        @if (! empty($ev['body']))<div class="lw-bubble-b">{{ $ev['body'] }}</div>@endif
                                        <div class="lw-bubble-t">{{ $at ? $at->format('d/m/Y H:i') : '' }}</div>
                                    </div>
                                </div>
                            @else
                                <div class="lw-event"><span>{{ $ev['title'] ?? '' }}@if (! empty($ev['body'])) · {{ \Illuminate\Support\Str::limit($ev['body'], 90) }}@endif @if ($at)<em>{{ $at->format('d/m H:i') }}</em>@endif</span></div>
                            @endif
                        @empty
                            <p class="lw-empty">Aún no hay actividad en este lead.</p>
                        @endforelse
                    </div>

                    <div class="lw-composer" data-lw-composer>
                        <div class="lw-tabs" role="tablist">
                            <button type="button" class="is-on" data-tab="nota" role="tab">Nota</button>
                            <button type="button" data-tab="whatsapp" role="tab">WhatsApp</button>
                            <button type="button" data-tab="correo" role="tab">Correo</button>
                        </div>
                        <form method="POST" action="{{ route('admin.leads-inbox.notes.store', $selected) }}" class="lw-pane" data-pane="nota">
                            @csrf
                            <textarea class="erp-input" name="body" rows="3" placeholder="Escribe una nota interna…" required>{{ old('body') }}</textarea>
                            <div class="lw-pane-foot"><button class="erp-btn erp-btn--primary erp-btn--sm" type="submit">Guardar nota</button></div>
                        </form>
                        <div class="lw-pane" data-pane="whatsapp" hidden>
                            <p class="lw-soon">El envío desde la bandeja aún no está disponible.</p>
                            @if ($phoneDigits)<a class="erp-btn erp-btn--sm" href="https://wa.me/{{ $phoneDigits }}" target="_blank" rel="noopener">Abrir WhatsApp</a>@endif
                        </div>
                        <div class="lw-pane" data-pane="correo" hidden>
                            <p class="lw-soon">El envío desde la bandeja aún no está disponible.</p>
                            @if ($selected->contact_email)<a class="erp-btn erp-btn--sm" href="mailto:{{ $selected->contact_email }}" target="_blank" rel="noopener">Abrir correo</a>@endif
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
                    <div class="lw-card lw-card--head">
                        <span class="lw-av lw-av--lg">{{ $initials($selected->display_name) }}</span>
                        <div class="lw-conv-who"><h2>{{ $selected->display_name }}</h2><p>{{ $subtitle($selected) }}</p></div>
                    </div>

                    <section class="lw-card">
                        <h3>Contacto</h3>
                        <dl class="lw-kv">
                            <dt>Teléfono</dt>
                            <dd>{{ $selected->contact_phone ?: '—' }} @if ($selected->contact_phone)<a class="lw-mini" href="tel:{{ $selected->contact_phone }}">Llamar</a>@endif</dd>
                            <dt>WhatsApp</dt>
                            <dd>{{ $selected->contact_phone ?: '—' }} @if ($phoneDigits)<a class="lw-mini" href="https://wa.me/{{ $phoneDigits }}" target="_blank" rel="noopener">Escribir</a>@endif</dd>
                            <dt>Correo</dt>
                            <dd>{{ $selected->contact_email ?: '—' }} @if ($selected->contact_email)<a class="lw-mini" href="mailto:{{ $selected->contact_email }}">Escribir</a>@endif</dd>
                        </dl>
                    </section>

                    <section class="lw-card">
                        <h3>Lead</h3>
                        <form method="POST" action="{{ route('admin.leads-inbox.update', $selected) }}" class="lw-field">
                            @csrf @method('PUT')
                            <label>Etapa
                                <select class="erp-select" name="status" onchange="this.form.submit()">
                                    @foreach (\App\Models\Lead::STATUSES as $k => $label)
                                        <option value="{{ $k }}" @selected($selected->status === $k)>{{ $label }}</option>
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
                    </section>

                    <section class="lw-card">
                        <h3>Origen</h3>
                        <dl class="lw-kv">
                            <dt>Fuente</dt><dd>{{ $selected->source_label }}@if ($selected->utm_source) · {{ $selected->utm_source }}@endif</dd>
                            <dt>Campaña</dt><dd>{{ $selected->utm_campaign ?: '—' }}</dd>
                            <dt>Palabra clave</dt><dd>{{ $selected->utm_term ?: '—' }}</dd>
                            <dt>GCLID capturado</dt><dd>{{ $clickLabel }}</dd>
                        </dl>
                    </section>

                    <section class="lw-card">
                        <h3>Próxima tarea</h3>
                        @if ($nextTask)
                            <a class="lw-task" href="{{ route('admin.tasks.show', $nextTask) }}">
                                <b>{{ $nextTask->title }}</b>
                                <span>Vence {{ $nextTask->due_at ? $nextTask->due_at->format('d/m/Y H:i') : 'sin fecha' }}</span>
                            </a>
                        @else
                            <p class="lw-muted">Sin tareas pendientes.</p>
                        @endif
                    </section>

                    <div class="lw-cta">
                        @if ($selected->deal)
                            <a class="erp-btn erp-btn--primary" href="{{ route('admin.deals.show', $selected->deal) }}">Ver negocio</a>
                        @else
                            <form method="POST" action="{{ route('admin.leads-inbox.convert', $selected) }}">
                                @csrf
                                <input type="hidden" name="target" value="deal">
                                <button class="erp-btn erp-btn--primary" type="submit">Convertir a negocio</button>
                            </form>
                        @endif
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
@endsection
