@extends('admin.layouts.master')
@section('title')
    {{ $program->name }} - Monitoreo de Clientes - Admin
@endsection
@push('styles')
    @include('admin.monitoring._styles')
@endpush
@section('content')
@php
    use App\Http\Controllers\Backend\MonitoringController;
    use App\Models\MonitoringEquipment;
    use Illuminate\Support\Carbon;

    $user = auth()->user();
    $canCreate = $user->hasPermission('monitoring', 'create');
    $canEdit = $user->hasPermission('monitoring', 'edit');
    $canDelete = $user->hasPermission('monitoring', 'delete');

    $customerName = MonitoringController::customerName($program->customer);
    $week = $program->currentWeek();
    $today = now()->startOfDay();
    if ($week) {
        $weekText = 'Semana ' . $week . ' de ' . $program->weeks;
    } elseif ($today->lt($program->start_date)) {
        $weekText = 'Inicia el ' . $program->start_date->format('d/m/Y');
    } else {
        $weekText = 'Finalizó el ' . $program->endDate()->format('d/m/Y');
    }
    $statusTone = match ($program->status) { 'active' => 'ok', 'paused' => 'warn', default => 'neu' };

    $tabLabels = [
        'resumen'   => 'Resumen',
        'gantt'     => 'Gantt',
        'graficas'  => 'Gráficas',
        'registros' => 'Registros (' . $tabCounts['registros'] . ')',
        'alertas'   => 'Alertas' . ($stats['out_of_range'] > 0 ? ' (' . $stats['out_of_range'] . ')' : ''),
    ];
    // Conserva el filtro de equipo al cambiar de pestaña.
    $tabUrl = fn (string $t) => route('admin.monitoring.show', array_filter(['program' => $program->id, 'tab' => $t, 'equipment_id' => $equipmentId]));
    $exportUrl = fn (string $type, string $format = 'xlsx') => route('admin.monitoring.export', array_merge(
        collect(request()->query())->except(['page', 'tab', 'item', 'days'])->all(), ['program' => $program->id, 'type' => $type, 'format' => $format]));

    $eqLabel = fn ($e) => $e ? $e->name : '—';
    $pct = $stats['compliance'];
    $fmtNum = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 3, '.', ','), '0'), '.');
    $rangeText = function ($min, $max) use ($fmtNum) {
        if ($min === null && $max === null) return '—';
        if ($min !== null && $max !== null) return $fmtNum($min) . ' – ' . $fmtNum($max);
        return $min !== null ? '≥ ' . $fmtNum($min) : '≤ ' . $fmtNum($max);
    };
@endphp
<div class="erp-page mon-page">

    <x-erp.object-header
        :eyebrow="'Programa de monitoreo'"
        :title="$program->name"
        :subtitle="$customerName . ($program->customer?->company ? ' · ' . $program->customer->company : '')"
        :status="$program->status_label"
        :tone="$statusTone"
        :back="route('admin.monitoring.index')"
        :facts="[
            ['label' => 'Periodo', 'value' => $program->start_date->format('d/m/Y') . ' – ' . $program->endDate()->format('d/m/Y')],
            ['label' => 'Avance', 'value' => $weekText],
            ['label' => 'Cumplimiento', 'value' => $pct === null ? null : $pct . '%'],
            ['label' => 'Fuera de rango', 'value' => (string) $stats['out_of_range']],
        ]">
        <button type="button" class="erp-btn" data-open="monEquipmentModal">Equipos ({{ $equipment->count() }})</button>
        <details class="mon-menu">
            <summary class="erp-btn">Exportar ▾</summary>
            <div class="mon-menu-list">
                <a href="{{ $exportUrl('entries') }}">Registros · Excel</a>
                <a href="{{ $exportUrl('entries', 'csv') }}">Registros · CSV</a>
                <a href="{{ $exportUrl('weeks') }}">Resumen semanal · Excel</a>
                <a href="{{ $exportUrl('weeks', 'csv') }}">Resumen semanal · CSV</a>
            </div>
        </details>
        @if ($canEdit)
            <a href="{{ route('admin.monitoring.edit', $program) }}" class="erp-btn erp-btn--primary">Editar programa</a>
        @endif
        @if ($canDelete)
            <form method="POST" action="{{ route('admin.monitoring.destroy', $program) }}" onsubmit="return confirm('¿Eliminar este programa con todos sus elementos y registros? Esta acción no se puede deshacer.');">
                @csrf @method('DELETE')
                <button type="submit" class="erp-btn erp-btn--danger">Eliminar</button>
            </form>
        @endif
    </x-erp.object-header>

    @if ($errors->any() && ! old('_modal'))
        <div class="mon-flash mon-flash--err" role="alert">
            @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <div class="erp-card">
        <div class="erp-tabnav mon-tabs" role="tablist">
            @foreach ($tabLabels as $key => $label)
                <a href="{{ $tabUrl($key) }}" role="tab" class="erp-tab {{ $tab === $key ? 'is-active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>

        <div class="mon-panel">

            {{-- Filtro de equipo (común a Resumen / Gantt / Gráficas) --}}
            @if (in_array($tab, ['resumen', 'gantt', 'graficas'], true))
                <div class="mon-panel-toolbar mon-noprint">
                    <form method="GET" action="{{ route('admin.monitoring.show', $program) }}">
                        <input type="hidden" name="tab" value="{{ $tab }}">
                        @if ($tab === 'graficas' && ! empty($itemId))<input type="hidden" name="item" value="{{ $itemId }}">@endif
                        <span class="mon-lbl">Equipo</span>
                        <select name="equipment_id" class="erp-select" onchange="this.form.submit()" aria-label="Filtrar por equipo">
                            <option value="">Todos los equipos</option>
                            @foreach ($equipment as $eq)
                                <option value="{{ $eq->id }}" @selected($equipmentId === $eq->id)>{{ $eq->name }}{{ $eq->is_active ? '' : ' (inactivo)' }}</option>
                            @endforeach
                        </select>
                        @if ($tab === 'graficas')
                            <span class="mon-lbl">Elemento</span>
                            <select name="item" class="erp-select" onchange="this.form.submit()" aria-label="Elemento a graficar">
                                @foreach ($items as $it)
                                    <option value="{{ $it->id }}" @selected(($itemId ?? null) === $it->id)>{{ $it->name }}{{ $it->unit ? ' (' . $it->unit . ')' : '' }} · {{ $it->isChemical() ? 'Químico' : 'Parámetro' }}</option>
                                @endforeach
                            </select>
                        @endif
                    </form>
                    @if ($tab === 'gantt')
                        <span class="spacer"></span>
                        <button type="button" class="erp-btn" onclick="window.print()">Imprimir diagrama</button>
                    @endif
                </div>
            @endif

            {{-- ═════════════ RESUMEN ═════════════ --}}
            @if ($tab === 'resumen')
                <div class="mon-stats">
                    <div class="mon-stat"><b>{{ $stats['entries'] }}</b><span>Registros totales</span></div>
                    <div class="mon-stat"><b>{{ $stats['readings'] }}</b><span>Lecturas del agua</span></div>
                    <div class="mon-stat"><b>{{ $stats['doses'] }}</b><span>Dosificaciones</span></div>
                    <div class="mon-stat"><b class="{{ $stats['out_of_range'] > 0 ? 'mon-bad' : '' }}">{{ $stats['out_of_range'] }}</b><span>Fuera de rango</span></div>
                    <div class="mon-stat"><b>{{ $pct === null ? '—' : $pct . '%' }}</b><span>Cumplimiento (lecturas en rango)</span></div>
                    <div class="mon-stat">
                        <b style="font-size:16px;">{{ $stats['last_entry_date'] ? Carbon::parse($stats['last_entry_date'])->format('d/m/Y') : '—' }}</b>
                        <span>Último registro</span>
                    </div>
                </div>

                @if ($program->notes)
                    <p class="mon-muted" style="margin:-6px 0 16px;white-space:pre-line;"><strong>Notas:</strong> {{ $program->notes }}</p>
                @endif

                <h3 class="erp-card-title" style="margin:0 0 8px;">Elementos del programa</h3>
                @if ($items->isEmpty())
                    <x-erp.empty title="Este programa aún no tiene elementos" text="Agrega parámetros del agua y químicos para que el cliente pueda capturar sus lecturas.">
                        @if ($canEdit)<a href="{{ route('admin.monitoring.edit', $program) }}" class="erp-btn erp-btn--primary">Editar programa</a>@endif
                    </x-erp.empty>
                @else
                    <div class="erp-table-wrap" style="margin-bottom:22px;">
                        <table class="erp-table">
                            <thead><tr><th>Elemento</th><th>Tipo</th><th>Rango / dosis planeada</th><th>Semanas</th><th class="mon-num">Registros</th></tr></thead>
                            <tbody>
                            @foreach ($items as $it)
                                <tr>
                                    <td>
                                        <div class="erp-cell-main">{{ $it->name }}</div>
                                        @if ($it->isChemical() && $it->product)<div class="erp-cell-sub">Producto: {{ $it->product->name }}</div>@endif
                                        @if ($it->added_by === 'customer')<span class="mon-badge mon-badge--cust">Agregado por el cliente</span>@endif
                                    </td>
                                    <td><span class="mon-badge {{ $it->isChemical() ? 'mon-badge--chem' : 'mon-badge--param' }}">{{ $it->isChemical() ? 'Químico' : 'Parámetro' }}</span></td>
                                    <td>
                                        @if ($it->isChemical())
                                            {{ $it->planned_daily_dose !== null ? $fmtNum($it->planned_daily_dose) . ' ' . $it->unit . ' / día' : '—' }}
                                        @else
                                            {{ $rangeText($it->limit_min, $it->limit_max) }} {{ $it->unit }}
                                        @endif
                                    </td>
                                    <td>{{ $it->start_week }} – {{ $it->end_week }}</td>
                                    <td class="mon-num">{{ $it->entries_count }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <h3 class="erp-card-title" style="margin:0 0 8px;">Semana por semana{{ $equipmentId ? ' · ' . ($equipment->firstWhere('id', $equipmentId)?->name) : '' }}</h3>
                <div class="mon-weeks">
                    @foreach ($weekSummaries as $w)
                        <div class="mon-week {{ $w['is_current'] ? 'is-current' : '' }} {{ $w['is_future'] ? 'is-future' : '' }}">
                            <div class="mon-week-head">
                                <div>
                                    <strong>Semana {{ $w['n'] }}</strong>
                                    <div class="mon-muted">{{ Carbon::parse($w['start'])->format('d/m') }} – {{ Carbon::parse($w['end'])->format('d/m/Y') }}</div>
                                </div>
                                @if ($w['is_current'])<x-erp.chip tone="acc" label="Actual" />
                                @elseif ($w['is_future'])<x-erp.chip tone="neu" label="Próxima" />
                                @elseif ($w['days_expected'] > 0 && $w['days_logged'] >= $w['days_expected'])<x-erp.chip tone="ok" label="Completa" />
                                @endif
                            </div>
                            <div class="mon-week-line">
                                <span>Lecturas <b>{{ $w['readings_count'] }}</b></span>
                                <span>Dosis <b>{{ $w['doses_count'] }}</b></span>
                                <span>Días <b>{{ $w['days_logged'] }}/{{ $w['days_expected'] }}</b></span>
                                <span class="{{ $w['out_of_range'] > 0 ? 'mon-bad' : '' }}">Fuera de rango <b>{{ $w['out_of_range'] }}</b></span>
                            </div>

                            @if (! empty($w['parameters']))
                                <table class="mon-mini">
                                    <thead><tr><th>Parámetro</th><th class="n">Prom.</th><th class="n">Mín – Máx</th><th class="n">Rango</th></tr></thead>
                                    <tbody>
                                    @foreach ($w['parameters'] as $p)
                                        <tr>
                                            <td>{{ $p['name'] }}@if ($p['out_of_range'] > 0) <span class="mon-bad" title="{{ $p['out_of_range'] }} fuera de rango">●{{ $p['out_of_range'] }}</span>@endif</td>
                                            <td class="n">{{ $fmtNum($p['avg']) }} {{ $p['unit'] }}</td>
                                            <td class="n">{{ $fmtNum($p['min']) }} – {{ $fmtNum($p['max']) }}</td>
                                            <td class="n mon-muted">{{ $rangeText($p['limit_min'], $p['limit_max']) }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            @endif

                            @if (! empty($w['chemicals']))
                                <table class="mon-mini" style="margin-top:8px;">
                                    <thead><tr><th>Químico</th><th class="n">Dosificado</th><th class="n">Planeado</th><th class="n">Días</th></tr></thead>
                                    <tbody>
                                    @foreach ($w['chemicals'] as $c)
                                        <tr>
                                            <td>{{ $c['name'] }}</td>
                                            <td class="n">{{ $fmtNum($c['total']) }} {{ $c['unit'] }}</td>
                                            <td class="n mon-muted">{{ $c['planned_total'] === null ? '—' : $fmtNum($c['planned_total']) . ' ' . $c['unit'] }}</td>
                                            <td class="n">{{ $c['days'] }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            @endif

                            @if (empty($w['parameters']) && empty($w['chemicals']))
                                <p class="mon-muted" style="margin:0;">{{ $w['is_future'] ? 'Aún no inicia.' : 'Sin registros esta semana.' }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- ═════════════ GANTT ═════════════ --}}
            @if ($tab === 'gantt')
                <x-monitoring.gantt :data="$gantt" />
            @endif

            {{-- ═════════════ GRÁFICAS ═════════════ --}}
            @if ($tab === 'graficas')
                @if ($items->isEmpty())
                    <x-erp.empty title="Sin elementos para graficar" text="Agrega parámetros o químicos al programa." />
                @else
                    <div class="mon-chart-wrap">
                        <x-monitoring.line-chart :series="$series" :height="260" />
                    </div>
                @endif
            @endif

            {{-- ═════════════ REGISTROS ═════════════ --}}
            @if ($tab === 'registros')
                <div class="mon-panel-toolbar mon-noprint">
                    <form method="GET" action="{{ route('admin.monitoring.show', $program) }}">
                        <input type="hidden" name="tab" value="registros">
                        <select name="item_id" class="erp-select" aria-label="Elemento">
                            <option value="">Todos los elementos</option>
                            @foreach ($items as $it)
                                <option value="{{ $it->id }}" @selected((int) ($entryFilters['item_id'] ?? 0) === $it->id)>{{ $it->name }}</option>
                            @endforeach
                        </select>
                        <select name="equipment_id" class="erp-select" aria-label="Equipo">
                            <option value="">Todos los equipos</option>
                            @foreach ($equipment as $eq)
                                <option value="{{ $eq->id }}" @selected((int) ($entryFilters['equipment_id'] ?? 0) === $eq->id)>{{ $eq->name }}</option>
                            @endforeach
                        </select>
                        <input type="date" name="from" class="erp-select" value="{{ $entryFilters['from'] ?? '' }}" aria-label="Desde" title="Desde">
                        <input type="date" name="to" class="erp-select" value="{{ $entryFilters['to'] ?? '' }}" aria-label="Hasta" title="Hasta">
                        <label style="display:flex;gap:5px;align-items:center;font-size:12.5px;font-weight:600;">
                            <input type="checkbox" name="out" value="1" @checked(! empty($entryFilters['out']))> Solo fuera de rango
                        </label>
                        <button type="submit" class="erp-btn">Filtrar</button>
                        @if (! empty(array_filter($entryFilters)))
                            <a href="{{ $tabUrl('registros') }}" class="erp-btn erp-btn--link">Limpiar</a>
                        @endif
                    </form>
                    <span class="spacer"></span>
                    @if ($canCreate)
                        <button type="button" class="erp-btn erp-btn--primary" data-entry-new>+ Agregar registro</button>
                    @endif
                </div>

                <div class="erp-table-wrap">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>Fecha</th><th>Equipo</th><th>Elemento</th><th class="mon-num">Valor</th><th>Rango</th>
                                <th>Estado</th><th>Nota</th><th>Registró</th>
                                @if ($canEdit || $canDelete)<th></th>@endif
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($entries as $e)
                            @php
                                $by = $e->recorded_by_user_id
                                    ? ['Personal', $recorders['users'][$e->recorded_by_user_id] ?? '—']
                                    : ($e->recorded_by_customer_id ? ['Cliente', $recorders['customers'][$e->recorded_by_customer_id] ?? '—'] : ['', '—']);
                            @endphp
                            <tr>
                                <td>{{ $e->entry_date->format('d/m/Y') }}<div class="erp-cell-sub">Semana {{ $program->weekOf($e->entry_date) }}</div></td>
                                <td>{{ $eqLabel($e->equipment) }}</td>
                                <td>
                                    {{ $e->item?->name ?? '—' }}
                                    <div class="erp-cell-sub">{{ $e->entry_type === 'dose' ? 'Dosificación' : 'Lectura' }}</div>
                                </td>
                                <td class="mon-num"><b>{{ $fmtNum($e->value) }}</b> {{ $e->item?->unit }}</td>
                                <td class="mon-muted">{{ $e->entry_type === 'dose' ? '—' : $rangeText($e->item?->limit_min, $e->item?->limit_max) }}</td>
                                <td>
                                    @if ($e->in_range === null) <span class="mon-muted">—</span>
                                    @elseif ($e->in_range) <x-erp.chip tone="ok" label="En rango" />
                                    @else <x-erp.chip tone="bad" label="Fuera de rango" /> @endif
                                </td>
                                <td style="max-width:220px;">{{ $e->note ?: '—' }}</td>
                                <td>{{ $by[1] }}@if ($by[0])<div class="erp-cell-sub">{{ $by[0] }}</div>@endif</td>
                                @if ($canEdit || $canDelete)
                                    <td style="white-space:nowrap;">
                                        @if ($canEdit)
                                            <button type="button" class="erp-btn erp-btn--sm" data-entry-edit
                                                data-id="{{ $e->id }}" data-item="{{ $e->item_id }}" data-equipment="{{ $e->equipment_id }}"
                                                data-date="{{ $e->entry_date->format('Y-m-d') }}" data-value="{{ $e->value + 0 }}" data-note="{{ $e->note }}">Editar</button>
                                        @endif
                                        @if ($canDelete)
                                            <form method="POST" action="{{ route('admin.monitoring.entries.destroy', [$program, $e]) }}" style="display:inline;" onsubmit="return confirm('¿Eliminar este registro?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="erp-btn erp-btn--sm erp-btn--danger">Eliminar</button>
                                            </form>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="9"><x-erp.empty title="Sin registros" text="Aún no hay registros con estos filtros. El cliente los captura desde su portal; también puedes agregarlos tú en su nombre." /></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="erp-wl-footer" style="border-top:0;padding-left:0;padding-right:0;">
                    <span>{{ $entries->total() }} {{ $entries->total() === 1 ? 'registro' : 'registros' }}</span>
                    @if ($entries->hasPages())<div>{{ $entries->links() }}</div>@endif
                </div>
            @endif

            {{-- ═════════════ ALERTAS ═════════════ --}}
            @if ($tab === 'alertas')
                <div class="mon-panel-toolbar mon-noprint">
                    <form method="GET" action="{{ route('admin.monitoring.show', $program) }}">
                        <input type="hidden" name="tab" value="alertas">
                        <span class="mon-lbl">Periodo</span>
                        <select name="days" class="erp-select" onchange="this.form.submit()">
                            @foreach ([7 => 'Últimos 7 días', 30 => 'Últimos 30 días', 90 => 'Últimos 90 días', 365 => 'Último año'] as $d => $l)
                                <option value="{{ $d }}" @selected($days === $d)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                @include('admin.monitoring._alerts_table', ['alerts' => $alerts, 'showProgram' => false])
            @endif
        </div>
    </div>
</div>

{{-- ═════════════ MODAL: registro ═════════════ --}}
@if ($canCreate || $canEdit)
<div class="mon-modal" id="monEntryModal" role="dialog" aria-modal="true" aria-labelledby="monEntryTitle">
    <div class="mon-modal-box">
        <div class="mon-modal-head">
            <h3 id="monEntryTitle">Agregar registro</h3>
            <button type="button" class="mon-x" data-close aria-label="Cerrar">×</button>
        </div>
        @if (old('_modal') === 'entry' && $errors->any())
            <div class="mon-flash mon-flash--err">@foreach ($errors->all() as $er)<div>{{ $er }}</div>@endforeach</div>
        @endif
        <form method="POST" action="{{ route('admin.monitoring.entries.store', $program) }}" id="monEntryForm">
            @csrf
            <input type="hidden" name="_method" value="PUT" id="monEntryMethod" disabled>
            <input type="hidden" name="_modal" value="entry">
            <input type="hidden" name="_entry_id" value="{{ old('_entry_id') }}" id="monEntryId">
            <div class="mon-grid2">
                <label class="erp-field full">Elemento *
                    <select name="item_id" class="erp-select" required id="monEntryItem">
                        <option value="">Selecciona…</option>
                        @foreach ($items as $it)
                            <option value="{{ $it->id }}" data-unit="{{ $it->unit }}" @selected((int) old('item_id') === $it->id)>{{ $it->name }} · {{ $it->isChemical() ? 'Dosificación' : 'Lectura' }}{{ $it->unit ? ' (' . $it->unit . ')' : '' }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="erp-field">Equipo
                    <select name="equipment_id" class="erp-select" id="monEntryEquipment">
                        <option value="">Sin equipo</option>
                        @foreach ($equipment as $eq)
                            <option value="{{ $eq->id }}" @selected((int) old('equipment_id') === $eq->id)>{{ $eq->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="erp-field">Fecha *
                    <input type="date" name="entry_date" class="erp-input" required id="monEntryDate"
                        min="{{ $program->start_date->format('Y-m-d') }}" max="{{ $program->endDate()->format('Y-m-d') }}"
                        value="{{ old('entry_date', min(max(now()->startOfDay(), $program->start_date), $program->endDate())->format('Y-m-d')) }}">
                </label>
                <label class="erp-field">Valor *
                    <input type="number" step="any" name="value" class="erp-input" required id="monEntryValue" value="{{ old('value') }}">
                </label>
                <label class="erp-field full">Nota
                    <input type="text" name="note" class="erp-input" maxlength="255" id="monEntryNote" value="{{ old('note') }}">
                </label>
            </div>
            <p class="mon-muted" style="margin:10px 0 0;">Se guardará como registrado por el personal ({{ $user->first_name }}) en nombre del cliente.</p>
            <div class="erp-form-actions">
                <button type="button" class="erp-btn" data-close>Cancelar</button>
                <button type="submit" class="erp-btn erp-btn--primary">Guardar registro</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- ═════════════ MODAL: equipos del cliente ═════════════ --}}
<div class="mon-modal" id="monEquipmentModal" role="dialog" aria-modal="true" aria-labelledby="monEquipmentTitle">
    <div class="mon-modal-box mon-modal-box--wide">
        <div class="mon-modal-head">
            <h3 id="monEquipmentTitle">Equipos de {{ $customerName }}</h3>
            <button type="button" class="mon-x" data-close aria-label="Cerrar">×</button>
        </div>
        @if (old('_modal') === 'equipment' && $errors->any())
            <div class="mon-flash mon-flash--err">@foreach ($errors->all() as $er)<div>{{ $er }}</div>@endforeach</div>
        @endif

        @forelse ($equipment as $eq)
            <div class="mon-eq-row">
                @if ($canEdit)
                    <form method="POST" action="{{ route('admin.monitoring.equipment.update', $eq) }}">
                        @csrf @method('PUT')
                        <input type="hidden" name="_modal" value="equipment">
                        <label class="erp-field">Nombre
                            <input type="text" name="name" class="erp-input" maxlength="120" required value="{{ $eq->name }}">
                        </label>
                        <label class="erp-field">Tipo
                            <select name="type" class="erp-select">
                                @foreach (MonitoringEquipment::TYPES as $k => $l)
                                    <option value="{{ $k }}" @selected($eq->type === $k)>{{ $l }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="erp-field">Ubicación
                            <input type="text" name="location" class="erp-input" maxlength="150" value="{{ $eq->location }}">
                        </label>
                        <div class="mon-eq-actions">
                            <input type="hidden" name="is_active" value="0">
                            <label style="display:flex;gap:4px;align-items:center;font-size:12px;"><input type="checkbox" name="is_active" value="1" @checked($eq->is_active)> Activo</label>
                            <button type="submit" class="erp-btn erp-btn--sm erp-btn--primary">Guardar</button>
                        </div>
                    </form>
                @else
                    <strong>{{ $eq->name }}</strong> · {{ $eq->type_label }}{{ $eq->location ? ' · ' . $eq->location : '' }}{{ $eq->is_active ? '' : ' (inactivo)' }}
                @endif
                <div class="mon-eq-meta">
                    <span>{{ $eq->created_via === 'customer' ? 'Agregado por el cliente' : 'Agregado por el personal' }}</span>
                    @if ($canDelete)
                        <form method="POST" action="{{ route('admin.monitoring.equipment.destroy', $eq) }}" style="display:inline-block;" onsubmit="return confirm('¿Eliminar este equipo? Sus registros se conservan sin equipo asignado.');">
                            @csrf @method('DELETE')
                            <button type="submit" class="erp-btn erp-btn--sm erp-btn--danger">Eliminar</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <p class="mon-muted">Este cliente aún no tiene equipos registrados.</p>
        @endforelse

        @if ($canCreate)
            <h4 style="margin:16px 0 8px;font-size:14px;">Agregar equipo</h4>
            <form method="POST" action="{{ route('admin.monitoring.equipment.store', $program->customer_id) }}" class="mon-grid2">
                @csrf
                <input type="hidden" name="_modal" value="equipment">
                <label class="erp-field">Nombre *
                    <input type="text" name="name" class="erp-input" maxlength="120" required value="{{ old('_modal') === 'equipment' ? old('name') : '' }}" placeholder="Ej. Caldera 1">
                </label>
                <label class="erp-field">Tipo *
                    <select name="type" class="erp-select">
                        @foreach (MonitoringEquipment::TYPES as $k => $l)
                            <option value="{{ $k }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="erp-field full">Ubicación
                    <input type="text" name="location" class="erp-input" maxlength="150" placeholder="Ej. Cuarto de máquinas, Planta 1">
                </label>
                <input type="hidden" name="is_active" value="1">
                <div class="full" style="text-align:right;">
                    <button type="submit" class="erp-btn erp-btn--primary">Agregar equipo</button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const open = (el) => el && el.classList.add('is-open');
    const close = (el) => el && el.classList.remove('is-open');

    document.querySelectorAll('[data-open]').forEach((b) => b.addEventListener('click', () => open(document.getElementById(b.dataset.open))));
    document.querySelectorAll('.mon-modal').forEach((m) => {
        m.addEventListener('click', (e) => { if (e.target === m || e.target.closest('[data-close]')) close(m); });
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') document.querySelectorAll('.mon-modal.is-open').forEach(close); });

    // Cerrar el menú Exportar al hacer clic fuera
    document.addEventListener('click', (e) => {
        document.querySelectorAll('details.mon-menu[open]').forEach((d) => { if (!d.contains(e.target)) d.removeAttribute('open'); });
    });

    // Modal de registro (alta / edición)
    const entryModal = document.getElementById('monEntryModal');
    if (entryModal) {
        const form = document.getElementById('monEntryForm');
        const method = document.getElementById('monEntryMethod');
        const title = document.getElementById('monEntryTitle');
        const storeUrl = @json(route('admin.monitoring.entries.store', $program));
        const updateTpl = @json(route('admin.monitoring.entries.update', [$program, '__ID__']));
        const f = (id) => document.getElementById(id);

        const openNew = () => {
            form.action = storeUrl; method.disabled = true; f('monEntryId').value = '';
            title.textContent = 'Agregar registro';
            f('monEntryItem').value = ''; f('monEntryEquipment').value = ''; f('monEntryValue').value = ''; f('monEntryNote').value = '';
            open(entryModal);
        };
        const openEdit = (b) => {
            form.action = updateTpl.replace('__ID__', b.dataset.id); method.disabled = false; f('monEntryId').value = b.dataset.id;
            title.textContent = 'Editar registro';
            f('monEntryItem').value = b.dataset.item; f('monEntryEquipment').value = b.dataset.equipment || '';
            f('monEntryDate').value = b.dataset.date; f('monEntryValue').value = b.dataset.value; f('monEntryNote').value = b.dataset.note || '';
            open(entryModal);
        };
        document.querySelectorAll('[data-entry-new]').forEach((b) => b.addEventListener('click', openNew));
        document.querySelectorAll('[data-entry-edit]').forEach((b) => b.addEventListener('click', () => openEdit(b)));
    }

    // Reabrir el modal que falló la validación
    @if (old('_modal') === 'entry' && $errors->any())
        (function () {
            const m = document.getElementById('monEntryModal'); if (!m) return;
            const id = document.getElementById('monEntryId').value;
            if (id) {
                document.getElementById('monEntryForm').action = @json(route('admin.monitoring.entries.update', [$program, '__ID__'])).replace('__ID__', id);
                document.getElementById('monEntryMethod').disabled = false;
                document.getElementById('monEntryTitle').textContent = 'Editar registro';
            }
            m.classList.add('is-open');
        })();
    @elseif (old('_modal') === 'equipment' && $errors->any())
        document.getElementById('monEquipmentModal').classList.add('is-open');
    @endif
})();
</script>
@endpush
