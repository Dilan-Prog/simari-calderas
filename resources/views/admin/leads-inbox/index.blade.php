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
    <div class="erp-page">
        <div class="erp-page-head">
            <div>
                <h1 class="erp-title">Bandeja de leads</h1>
                <p class="erp-subtitle">WhatsApp, formularios, llamadas y correos en un solo lugar, con su atribución de anuncio.</p>
            </div>
            <details class="crm-new">
                <summary class="erp-btn primary">+ Nuevo lead</summary>
                <form method="POST" action="{{ route('admin.leads-inbox.store') }}" class="crm-card" style="position:absolute; z-index:5; width:340px; margin-top:6px;">
                    @csrf
                    <div class="crm-form-row"><input type="text" name="name" placeholder="Nombre" value="{{ old('name') }}"></div>
                    <div class="crm-form-row"><input type="text" name="phone" placeholder="Teléfono" value="{{ old('phone') }}"></div>
                    <div class="crm-form-row"><input type="text" name="email" placeholder="Correo" value="{{ old('email') }}"></div>
                    <div class="crm-form-row">
                        <select name="source">
                            @foreach (\App\Models\Lead::SOURCES as $k => $label)
                                <option value="{{ $k }}" @selected($k === 'manual')>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="crm-form-row"><textarea name="notes" placeholder="Notas">{{ old('notes') }}</textarea></div>
                    <button class="erp-btn primary" type="submit">Registrar</button>
                </form>
            </details>
        </div>

        @if (session('success'))<div class="crm-flash ok">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="crm-flash err">{{ session('error') }}</div>@endif
        @if ($errors->any())<div class="crm-flash err">{{ $errors->first() }}</div>@endif

        <div class="crm-summary">
            <div class="crm-stat"><strong class="erp-tone-{{ $counts['nuevos'] ? 'warn' : 'ok' }}">{{ $counts['nuevos'] }}</strong><span>Nuevos sin atender</span></div>
            <div class="crm-stat"><strong>{{ $counts['abiertos'] }}</strong><span>Leads abiertos</span></div>
        </div>

        <form method="GET" class="crm-filters">
            <label>Buscar
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Nombre, teléfono o correo">
            </label>
            <label>Origen
                <select name="source" data-crm-autosubmit>
                    <option value="">Todos</option>
                    @foreach (\App\Models\Lead::SOURCES as $k => $label)
                        <option value="{{ $k }}" @selected(request('source') === $k)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>Estado
                <select name="status" data-crm-autosubmit>
                    <option value="">Abiertos</option>
                    @foreach (\App\Models\Lead::STATUSES as $k => $label)
                        <option value="{{ $k }}" @selected(request('status') === $k)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>Campaña
                <select name="campaign" data-crm-autosubmit>
                    <option value="">Todas</option>
                    @foreach ($campaigns as $c)
                        <option value="{{ $c }}" @selected(request('campaign') === $c)>{{ $c }}</option>
                    @endforeach
                </select>
            </label>
            <label>Asignado
                <select name="assigned_to" data-crm-autosubmit>
                    <option value="">Todos</option>
                    <option value="sin" @selected(request('assigned_to') === 'sin')>Sin asignar</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" @selected((string) request('assigned_to') === (string) $u->id)>{{ $u->first_name }} {{ $u->last_name }}</option>
                    @endforeach
                </select>
            </label>
            <button class="erp-btn" type="submit">Filtrar</button>
        </form>

        @if (count($chips))
            <div class="crm-chips">
                @foreach ($chips as $chip)
                    <a class="crm-chip-filter" href="{{ $chip['remove'] }}" title="Quitar filtro">{{ $chip['label'] }}: {{ $chip['value'] }} ✕</a>
                @endforeach
                <a class="crm-chip-filter" href="{{ route('admin.leads-inbox.index') }}">Limpiar todo</a>
            </div>
        @endif

        <div class="crm-table-wrap">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th>Contacto</th><th>Origen</th><th>Estado</th><th>Campaña</th><th>Clic</th><th>Asignado</th><th>Última actividad</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($leads as $lead)
                    <tr>
                        <td>
                            <a href="{{ route('admin.leads-inbox.show', $lead) }}">{{ $lead->display_name }}</a>
                            <div class="crm-muted">{{ $lead->contact_phone }} {{ $lead->contact_email }}</div>
                        </td>
                        <td>{{ $lead->source_label }}</td>
                        <td><span class="erp-chip erp-tone-{{ $lead->status_tone }}">{{ $lead->status_label }}</span></td>
                        <td>{{ $lead->utm_campaign ?: '—' }}<div class="crm-muted">{{ $lead->utm_source }}</div></td>
                        <td class="crm-mono">{{ $lead->click_identifier ? strtoupper($lead->click_identifier['type']) : '—' }}</td>
                        <td>{{ $lead->assignee ? $lead->assignee->first_name : 'Sin asignar' }}</td>
                        <td>{{ optional($lead->last_activity_at)->format('d/m/Y H:i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="crm-empty">No hay leads con estos filtros.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="crm-pager">{{ $leads->links() }}</div>
    </div>
@endsection
