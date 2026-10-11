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
        $chipItems = collect($chips)->map(fn ($c) => ['label' => $c['label'] . ': ' . $c['value'], 'url' => $c['remove']])->all();
    @endphp
    <div class="erp-page">
        <x-erp.page-header
            title="Bandeja de leads"
            code="VTA1"
            desc="WhatsApp, formularios, llamadas y correos en un solo lugar, con su atribución de anuncio."
            :crumbs="[['Ventas', null], ['Bandeja de leads', null]]">
            <details class="crm-new">
                <summary class="erp-btn erp-btn--primary">+ Nuevo lead</summary>
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
        </x-erp.page-header>

        @if (session('success'))<div class="crm-flash ok" role="status">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="crm-flash err" role="alert">{{ session('error') }}</div>@endif
        @if ($errors->any())<div class="crm-flash err" role="alert">{{ $errors->first() }}</div>@endif

        <div class="erp-tiles crm-tiles">
            <x-erp.kpi-tile title="Nuevos sin atender" :value="$counts['nuevos']" :tone="$counts['nuevos'] ? 'warn' : 'ok'"
                caption="Requieren primer contacto" :href="route('admin.leads-inbox.index', ['status' => 'nuevo'])" />
            <x-erp.kpi-tile title="Leads abiertos" :value="$counts['abiertos']" tone="info"
                caption="Nuevos, contactados y calificados" :href="route('admin.leads-inbox.index')" />
        </div>

        <x-erp.worklist :chips="$chipItems">
            <x-slot:toolbar>
                <form method="GET" class="crm-toolbar-form" role="search">
                    <label class="erp-field erp-field--grow">Buscar
                        <input class="erp-input" type="text" name="q" value="{{ request('q') }}" placeholder="Nombre, teléfono o correo">
                    </label>
                    <label class="erp-field">Origen
                        <select class="erp-select" name="source" data-crm-autosubmit>
                            <option value="">Todos</option>
                            @foreach (\App\Models\Lead::SOURCES as $k => $label)
                                <option value="{{ $k }}" @selected(request('source') === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="erp-field">Estado
                        <select class="erp-select" name="status" data-crm-autosubmit>
                            <option value="">Abiertos</option>
                            @foreach (\App\Models\Lead::STATUSES as $k => $label)
                                <option value="{{ $k }}" @selected(request('status') === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="erp-field">Campaña
                        <select class="erp-select" name="campaign" data-crm-autosubmit>
                            <option value="">Todas</option>
                            @foreach ($campaigns as $c)
                                <option value="{{ $c }}" @selected(request('campaign') === $c)>{{ $c }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="erp-field">Asignado
                        <select class="erp-select" name="assigned_to" data-crm-autosubmit>
                            <option value="">Todos</option>
                            <option value="sin" @selected(request('assigned_to') === 'sin')>Sin asignar</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}" @selected((string) request('assigned_to') === (string) $u->id)>{{ $u->first_name }} {{ $u->last_name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button class="erp-btn" type="submit">Filtrar</button>
                    @if (count($chipItems))<a class="erp-btn erp-btn--link" href="{{ route('admin.leads-inbox.index') }}">Limpiar todo</a>@endif
                </form>
            </x-slot:toolbar>

            <nav class="erp-inbox-list" aria-label="Leads">
                @forelse ($leads as $lead)
                    @php
                        $click = $lead->click_identifier ? strtoupper($lead->click_identifier['type']) : null;
                        $tags = [['t' => $lead->source_label, 'tone' => 'neu']];
                        if ($lead->utm_campaign) { $tags[] = ['t' => $lead->utm_campaign, 'tone' => 'acc']; }
                        if ($click) { $tags[] = ['t' => $click, 'tone' => 'info']; } else { $tags[] = ['t' => 'Sin clic', 'tone' => 'neu']; }
                        $tags[] = ['t' => $lead->assignee ? $lead->assignee->first_name : 'Sin asignar', 'tone' => $lead->assignee ? 'ok' : 'warn'];
                    @endphp
                    <x-erp.inbox-row
                        :href="route('admin.leads-inbox.show', $lead)"
                        :name="$lead->display_name"
                        :source="$lead->source"
                        :time="optional($lead->last_activity_at)->format('d/m/Y H:i')"
                        :sub="trim($lead->contact_phone . ' ' . $lead->contact_email)"
                        :preview="$lead->utm_source ? 'Fuente: ' . $lead->utm_source : null"
                        :tags="$tags"
                        :status="$lead->status_label"
                        :tone="$lead->status_tone"
                        :unread="$lead->status === 'nuevo'" />
                @empty
                    <x-erp.empty title="No hay leads con estos filtros" text="Ajusta o limpia los filtros para ver más conversaciones." />
                @endforelse
            </nav>

            <x-slot:footer>{{ $leads->links() }}</x-slot:footer>
        </x-erp.worklist>
    </div>
@endsection
