@extends('customers.layouts.master')

@section('title', $program->name . ' - Monitoreo')

@push('styles')
    @vite('resources/css/admin/technical-services.css')
    @include('customers.monitoring.partials.styles')
    <style>
        .mn-print-only { display: none; }
        @media print { .mn-print-only { display: block; margin-bottom: .75rem; } }
    </style>
@endpush

@section('content')
@php
    $num = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 3, '.', ','), '0'), '.');
    $plain = fn ($v) => $v === null ? '' : rtrim(rtrim(number_format((float) $v, 3, '.', ''), '0'), '.');
    $dm = fn ($d) => $d->locale('es')->isoFormat('D MMM YYYY');
    $dshort = fn ($d) => \Carbon\Carbon::parse($d)->locale('es')->isoFormat('D MMM');
    $rangeText = function ($lo, $hi) use ($num) {
        if ($lo !== null && $hi !== null) return $num($lo) . ' – ' . $num($hi);
        if ($lo !== null) return '≥ ' . $num($lo);
        if ($hi !== null) return '≤ ' . $num($hi);
        return '';
    };
    $me = (int) auth('customer')->id();

    $parameters = $items->where('kind', 'parameter')->values();
    $chemicals = $items->where('kind', 'chemical')->values();

    $tabUrl = fn (string $t) => route('customer.monitoring.show', array_filter(
        ['program' => $program->id, 'tab' => $t, 'equipment' => $equipmentId],
        fn ($v) => $v !== null && $v !== ''
    ));
    $tabs = ['registro' => 'Registro diario', 'semanas' => 'Semanas', 'gantt' => 'Gantt', 'graficas' => 'Gráficas'];

    $eb = $errors->getBag('entry');
    $ebe = $errors->getBag('entry_edit');
    $ebi = $errors->getBag('item');
    $ebd = $errors->getBag('dates');
    $formMarker = old('_form');
    $oq = fn ($k, $d = null) => $formMarker === 'entry' ? old($k, $d) : $d;
    $oe = fn ($k) => $formMarker === 'entry_edit' ? old($k) : null;

    $canRecord = $runState !== 'not_started' && $items->isNotEmpty();
    $defaultDate = $maxEntryDate->toDateString();
    $oneEquipment = $activeEquipment->count() === 1 ? $activeEquipment->first()->id : '';
    $equipDefault = $oq('equipment_id', $equipmentId ?? $oneEquipment);
@endphp
<div class="ts-page" id="ts-page">

    <div class="ts-breadcrumb mn-no-print">
        <span>Portal de servicios</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m9 18 6-6-6-6"/>
        </svg>
        <a href="{{ route('customer.monitoring.index') }}" style="color:inherit;text-decoration:none">Monitoreo</a>
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m9 18 6-6-6-6"/>
        </svg>
        <span class="ts-breadcrumb__current">{{ $program->name }}</span>
    </div>

    <div class="ts-header-top">
        <div>
            <h1 class="ts-title" style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem">
                {{ $program->name }}
                <span class="mn-badge mn-badge--{{ $program->status }}">{{ $program->status_label }}</span>
            </h1>
            <p class="ts-subtitle">
                {{ $dm($program->start_date) }} &ndash; {{ $dm($endDate) }} &middot; {{ $program->weeks }} {{ $program->weeks === 1 ? 'semana' : 'semanas' }}
                &middot;
                <strong>
                    @if ($currentWeek)
                        Semana {{ $currentWeek }} de {{ $program->weeks }}
                    @elseif ($runState === 'not_started')
                        Aún no inicia
                    @else
                        Programa concluido
                    @endif
                </strong>
            </p>
        </div>
        <div class="ts-header-actions mn-header-actions mn-no-print" style="display:flex;flex-wrap:wrap;gap:.5rem">
            <button type="button" class="ts-btn" data-mn-open="mn-modal-dates">Ajustar fechas</button>
            <button type="button" class="ts-btn ts-btn--primary" data-mn-open="mn-modal-item">+ Agregar parámetro</button>
        </div>
    </div>

    <div class="mn-kpis" aria-label="Indicadores del programa">
        <div class="mn-kpi"><div class="mn-kpi__label">Registros</div><div class="mn-kpi__value">{{ $stats['entries'] }}</div></div>
        <div class="mn-kpi"><div class="mn-kpi__label">Lecturas</div><div class="mn-kpi__value">{{ $stats['readings'] }}</div></div>
        <div class="mn-kpi"><div class="mn-kpi__label">Dosificaciones</div><div class="mn-kpi__value">{{ $stats['doses'] }}</div></div>
        <div class="mn-kpi"><div class="mn-kpi__label">Fuera de rango</div><div class="mn-kpi__value {{ $stats['out_of_range'] > 0 ? 'mn-kpi__value--bad' : '' }}">{{ $stats['out_of_range'] }}</div></div>
        <div class="mn-kpi"><div class="mn-kpi__label">Cumplimiento</div><div class="mn-kpi__value">{{ $stats['compliance'] !== null ? $stats['compliance'] . '%' : '—' }}</div></div>
        <div class="mn-kpi"><div class="mn-kpi__label">Último registro</div><div class="mn-kpi__value mn-kpi__value--sm">{{ $stats['last_entry_date'] ? \Carbon\Carbon::parse($stats['last_entry_date'])->format('d/m/Y') : 'Sin registros' }}</div></div>
    </div>

    <nav class="mn-tabs mn-no-print" aria-label="Secciones del programa">
        @foreach ($tabs as $key => $label)
            <a class="mn-tab" href="{{ $tabUrl($key) }}" @if ($tab === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>

    {{-- Filtro de equipo (comparte las pestañas Semanas / Gantt / Gráficas; en Registro va junto a la semana) --}}
    @if ($tab !== 'registro' && $allEquipment->isNotEmpty())
        <form method="GET" action="{{ route('customer.monitoring.show', $program->id) }}" class="mn-toolbar mn-no-print">
            <input type="hidden" name="tab" value="{{ $tab }}">
            @if ($tab === 'graficas' && $chartItem)
                <div class="ts-field">
                    <label class="ts-label" for="mn-f-item">Parámetro o químico</label>
                    <div class="ts-field-select-wrap">
                        <select class="ts-select-field" id="mn-f-item" name="item" data-autosubmit>
                            @if ($parameters->isNotEmpty())
                                <optgroup label="Lecturas del agua">
                                    @foreach ($parameters as $it)
                                        <option value="{{ $it->id }}" @selected($chartItem->id === $it->id)>{{ $it->name }}{{ $it->unit ? ' (' . $it->unit . ')' : '' }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @if ($chemicals->isNotEmpty())
                                <optgroup label="Dosificación de químicos">
                                    @foreach ($chemicals as $it)
                                        <option value="{{ $it->id }}" @selected($chartItem->id === $it->id)>{{ $it->name }}{{ $it->unit ? ' (' . $it->unit . ')' : '' }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </div>
                </div>
            @endif
            <div class="ts-field">
                <label class="ts-label" for="mn-f-eq">Equipo</label>
                <div class="ts-field-select-wrap">
                    <select class="ts-select-field" id="mn-f-eq" name="equipment" data-autosubmit>
                        <option value="">Todos los equipos</option>
                        @foreach ($allEquipment as $eq)
                            <option value="{{ $eq->id }}" @selected($equipmentId === $eq->id)>{{ $eq->name }}{{ $eq->is_active ? '' : ' (inactivo)' }}</option>
                        @endforeach
                    </select>
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </div>
            </div>
            <noscript><button type="submit" class="ts-btn">Filtrar</button></noscript>
            @if ($tab === 'gantt')
                <div class="mn-toolbar--end">
                    <button type="button" class="ts-btn" onclick="window.print()">Imprimir</button>
                </div>
            @endif
        </form>
    @elseif ($tab === 'gantt')
        <div class="mn-toolbar mn-no-print"><div class="mn-toolbar--end"><button type="button" class="ts-btn" onclick="window.print()">Imprimir</button></div></div>
    @endif

    {{-- =========================== REGISTRO DIARIO =========================== --}}
    @if ($tab === 'registro')
        <section class="ts-card mn-block mn-no-print" aria-labelledby="mn-new-title">
            <h2 class="mn-section-title" id="mn-new-title">Nuevo registro</h2>

            @if ($items->isEmpty())
                <div class="mn-alert mn-alert--info">Este programa aún no tiene parámetros ni químicos. Agrega el primero con el botón <strong>Agregar parámetro</strong>.</div>
            @elseif ($runState === 'not_started')
                <div class="mn-alert mn-alert--warn">El programa inicia el {{ $dm($program->start_date) }}. Podrás capturar registros a partir de esa fecha.</div>
            @endif

            @if ($eb->any())
                <div class="mn-alert mn-alert--err" role="alert" id="mn-entry-errors">Revisa los datos marcados en el formulario.</div>
            @endif

            <form method="POST" action="{{ route('customer.monitoring.entries.store', $program->id) }}" id="mn-entry-form" novalidate>
                @csrf
                <input type="hidden" name="_form" value="entry">
                <fieldset style="border:0;margin:0;padding:0;min-width:0" @disabled(!$canRecord)>
                    <div class="mn-form-grid">
                        <div class="ts-field mn-col-3">
                            <label class="ts-label" for="mn-date">Fecha <span class="ts-label__req">*</span></label>
                            <input type="date" class="ts-input {{ $eb->has('entry_date') ? 'mn-input-error' : '' }}" id="mn-date" name="entry_date" required
                                   min="{{ $program->start_date->toDateString() }}" max="{{ $defaultDate }}"
                                   value="{{ $oq('entry_date', $defaultDate) }}">
                            @if ($eb->has('entry_date')) <span class="mn-error">{{ $eb->first('entry_date') }}</span> @endif
                        </div>

                        <div class="ts-field mn-col-3">
                            <label class="ts-label" for="mn-equipment">Equipo (opcional)</label>
                            <div class="ts-field-select-wrap">
                                <select class="ts-select-field {{ $eb->has('equipment_id') ? 'mn-input-error' : '' }}" id="mn-equipment" name="equipment_id">
                                    <option value="">Sin equipo específico</option>
                                    @foreach ($activeEquipment as $eq)
                                        <option value="{{ $eq->id }}" @selected((string) $equipDefault === (string) $eq->id)>{{ $eq->name }}</option>
                                    @endforeach
                                </select>
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                            </div>
                            @if ($eb->has('equipment_id')) <span class="mn-error">{{ $eb->first('equipment_id') }}</span>
                            @elseif ($activeEquipment->isEmpty() && Route::has('customer.equipment.index'))
                                <span class="mn-hint"><a href="{{ route('customer.equipment.index') }}">Registra tus equipos</a> para ligarlos aquí.</span>
                            @endif
                        </div>

                        <div class="ts-field mn-col-6">
                            <label class="ts-label" for="mn-item">Parámetro o químico <span class="ts-label__req">*</span></label>
                            <div class="ts-field-select-wrap">
                                <select class="ts-select-field {{ $eb->has('item_id') ? 'mn-input-error' : '' }}" id="mn-item" name="item_id" required>
                                    <option value="">Selecciona...</option>
                                    @if ($parameters->isNotEmpty())
                                        <optgroup label="Lecturas del agua">
                                            @foreach ($parameters as $it)
                                                <option value="{{ $it->id }}" data-unit="{{ $it->unit }}" data-kind="parameter"
                                                        data-hint="{{ $rangeText($it->limit_min, $it->limit_max) }}"
                                                        @selected((string) $oq('item_id') === (string) $it->id)>{{ $it->name }}{{ $it->unit ? ' (' . $it->unit . ')' : '' }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                    @if ($chemicals->isNotEmpty())
                                        <optgroup label="Dosificación de químicos">
                                            @foreach ($chemicals as $it)
                                                <option value="{{ $it->id }}" data-unit="{{ $it->unit }}" data-kind="chemical"
                                                        data-hint="{{ $it->planned_daily_dose !== null ? 'Dosis diaria planeada: ' . $num($it->planned_daily_dose) . ($it->unit ? ' ' . $it->unit : '') : '' }}"
                                                        @selected((string) $oq('item_id') === (string) $it->id)>{{ $it->name }}{{ $it->unit ? ' (' . $it->unit . ')' : '' }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                </select>
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                            </div>
                            @if ($eb->has('item_id')) <span class="mn-error">{{ $eb->first('item_id') }}</span> @endif
                        </div>

                        <div class="ts-field mn-col-4">
                            <label class="ts-label" for="mn-value"><span id="mn-value-label">Valor</span> <span class="ts-label__req">*</span></label>
                            <div class="mn-unit-wrap">
                                <input type="text" inputmode="decimal" autocomplete="off" class="ts-input {{ $eb->has('value') ? 'mn-input-error' : '' }}"
                                       id="mn-value" name="value" required value="{{ $oq('value') }}" aria-describedby="mn-value-hint">
                                <span class="mn-unit-wrap__unit" id="mn-value-unit"></span>
                            </div>
                            <span class="mn-hint" id="mn-value-hint" aria-live="polite">Selecciona un elemento para ver su unidad y rango esperado.</span>
                            @if ($eb->has('value')) <span class="mn-error">{{ $eb->first('value') }}</span> @endif
                        </div>

                        <div class="ts-field mn-col-6">
                            <label class="ts-label" for="mn-note">Nota (opcional)</label>
                            <input type="text" class="ts-input {{ $eb->has('note') ? 'mn-input-error' : '' }}" id="mn-note" name="note" maxlength="255"
                                   placeholder="Observaciones del día" value="{{ $oq('note') }}">
                            @if ($eb->has('note')) <span class="mn-error">{{ $eb->first('note') }}</span> @endif
                        </div>

                        <div class="mn-col-2" style="align-self:end">
                            <button type="submit" class="ts-btn ts-btn--primary" style="width:100%;justify-content:center">Guardar registro</button>
                        </div>
                    </div>
                </fieldset>
            </form>
        </section>

        <section class="mn-block" aria-labelledby="mn-entries-title">
            <h2 class="mn-section-title" id="mn-entries-title">Registros</h2>

            <form method="GET" action="{{ route('customer.monitoring.show', $program->id) }}" class="mn-toolbar mn-no-print">
                <input type="hidden" name="tab" value="registro">
                <div class="ts-field">
                    <label class="ts-label" for="mn-f-week">Semana</label>
                    <div class="ts-field-select-wrap">
                        <select class="ts-select-field" id="mn-f-week" name="week" data-autosubmit>
                            <option value="all" @selected($week === null)>Todas las semanas</option>
                            @for ($w = 1; $w <= $program->weeks; $w++)
                                <option value="{{ $w }}" @selected($week === $w)>Semana {{ $w }} ({{ $dshort($program->weekStart($w)) }} – {{ $dshort($program->weekEnd($w)) }}){{ $currentWeek === $w ? ' · actual' : '' }}</option>
                            @endfor
                        </select>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </div>
                </div>
                @if ($allEquipment->isNotEmpty())
                    <div class="ts-field">
                        <label class="ts-label" for="mn-f-eq2">Equipo</label>
                        <div class="ts-field-select-wrap">
                            <select class="ts-select-field" id="mn-f-eq2" name="equipment" data-autosubmit>
                                <option value="">Todos los equipos</option>
                                @foreach ($allEquipment as $eq)
                                    <option value="{{ $eq->id }}" @selected($equipmentId === $eq->id)>{{ $eq->name }}{{ $eq->is_active ? '' : ' (inactivo)' }}</option>
                                @endforeach
                            </select>
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </div>
                    </div>
                @endif
                <noscript><button type="submit" class="ts-btn">Filtrar</button></noscript>
            </form>

            @if ($entries->isEmpty())
                <div class="ts-card mn-empty">
                    <p>No hay registros {{ $week !== null ? 'en la semana ' . $week : 'todavía' }}{{ $equipmentId ? ' para este equipo' : '' }}.</p>
                    @if ($week !== null)
                        <a class="ts-btn mn-btn-sm" href="{{ route('customer.monitoring.show', array_filter(['program' => $program->id, 'tab' => 'registro', 'week' => 'all', 'equipment' => $equipmentId])) }}">Ver todas las semanas</a>
                    @endif
                </div>
            @else
                <div class="mn-table-wrap">
                    <table class="mn-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Equipo</th>
                                <th>Elemento</th>
                                <th class="mn-num">Valor</th>
                                <th>Estado</th>
                                <th>Nota</th>
                                <th>Registró</th>
                                <th class="mn-no-print"><span class="sr-only" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($entries as $e)
                                @php
                                    $it = $e->item;
                                    $mine = (int) $e->recorded_by_customer_id === $me;
                                    $limitTxt = $rangeText($it?->limit_min, $it?->limit_max);
                                @endphp
                                <tr>
                                    <td style="white-space:nowrap">{{ $e->entry_date->format('d/m/Y') }}</td>
                                    <td>{{ $e->equipment?->name ?? '—' }}</td>
                                    <td>
                                        {{ $it?->name }}
                                        <div><span class="mn-badge {{ $e->entry_type === 'dose' ? 'mn-badge--chem' : 'mn-badge--param' }}">{{ $e->entry_type === 'dose' ? 'Dosificación' : 'Lectura' }}</span></div>
                                    </td>
                                    <td class="mn-num" style="white-space:nowrap"><strong>{{ $num($e->value) }}</strong> {{ $it?->unit }}</td>
                                    <td>
                                        @if ($e->entry_type === 'dose')
                                            <span class="mn-badge mn-badge--neutral">Aplicado</span>
                                        @elseif ($e->in_range === true)
                                            <span class="mn-badge mn-badge--ok"><span aria-hidden="true">&#10004;</span> En rango</span>
                                        @elseif ($e->in_range === false)
                                            <span class="mn-badge mn-badge--bad"><span aria-hidden="true">&#10006;</span> Fuera de rango</span>
                                            @if ($limitTxt) <div class="mn-muted" style="font-size:.6875rem;margin-top:.125rem">Esperado: {{ $limitTxt }}</div> @endif
                                        @else
                                            <span class="mn-badge mn-badge--neutral">Sin rango definido</span>
                                        @endif
                                    </td>
                                    <td style="max-width:260px;overflow-wrap:anywhere">{{ $e->note ?: '—' }}</td>
                                    <td>{{ $mine ? 'Tú' : ($e->recorded_by_user_id ? 'Equiterm' : '—') }}</td>
                                    <td class="mn-actions mn-no-print">
                                        @if ($mine)
                                            <button type="button" class="ts-btn mn-btn-sm" data-entry-edit
                                                    data-action="{{ route('customer.monitoring.entries.update', $e->id) }}"
                                                    data-entry="{{ json_encode([
                                                        'id' => $e->id,
                                                        'date' => $e->entry_date->toDateString(),
                                                        'equipment_id' => $e->equipment_id,
                                                        'value' => $plain($e->value),
                                                        'note' => $e->note,
                                                        'item' => $it?->name,
                                                        'unit' => $it?->unit,
                                                    ], JSON_UNESCAPED_UNICODE) }}">Editar</button>
                                            <button type="button" class="ts-btn ts-btn--danger mn-btn-sm" data-confirm
                                                    data-action="{{ route('customer.monitoring.entries.destroy', $e->id) }}"
                                                    data-title="¿Eliminar este registro?"
                                                    data-message="{{ ($it?->name ?? 'Registro') . ' del ' . $e->entry_date->format('d/m/Y') }} (valor {{ $num($e->value) }}) se eliminará. Esta acción no se puede deshacer."
                                                    data-label="Eliminar">Eliminar</button>
                                        @else
                                            <span class="mn-muted" title="Solo se pueden modificar los registros que capturaste tú">&mdash;</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($entries->hasPages())
                    <div class="ts-pagination mn-no-print">
                        <span class="ts-pagination__info">Mostrando {{ $entries->firstItem() }}–{{ $entries->lastItem() }} de {{ $entries->total() }}</span>
                        <div class="ts-pagination__links">{{ $entries->links() }}</div>
                    </div>
                @endif
            @endif
        </section>

        {{-- Elementos del programa --}}
        <section class="ts-card mn-no-print">
            <details class="mn-details" @if ($errors->has('item_delete')) open @endif>
                <summary>Parámetros y químicos del programa ({{ $items->count() }})</summary>
                @if ($items->isEmpty())
                    <p class="mn-muted" style="margin:0">Sin elementos todavía.</p>
                @else
                    <div class="mn-table-wrap">
                        <table class="mn-table" style="min-width:640px">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Unidad</th>
                                    <th>Rango esperado</th>
                                    <th>Semanas</th>
                                    <th>Origen</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $it)
                                    @php $canDelete = $it->added_by === 'customer' && $it->entries_count === 0; @endphp
                                    <tr>
                                        <td><strong>{{ $it->name }}</strong></td>
                                        <td><span class="mn-badge {{ $it->kind === 'chemical' ? 'mn-badge--chem' : 'mn-badge--param' }}">{{ $it->kind === 'chemical' ? 'Químico' : 'Lectura del agua' }}</span></td>
                                        <td>{{ $it->unit ?: '—' }}</td>
                                        <td>{{ $it->kind === 'chemical' ? '—' : ($rangeText($it->limit_min, $it->limit_max) ?: 'Sin rango') }}</td>
                                        <td>{{ $it->start_week === $it->end_week ? 'Semana ' . $it->start_week : 'Semanas ' . $it->start_week . ' a ' . $it->end_week }}</td>
                                        <td>{{ $it->added_by === 'customer' ? 'Agregado por ti' : 'Equiterm' }}</td>
                                        <td class="mn-actions">
                                            @if ($canDelete)
                                                <button type="button" class="ts-btn ts-btn--danger mn-btn-sm" data-confirm
                                                        data-action="{{ route('customer.monitoring.items.destroy', $it->id) }}"
                                                        data-title="¿Quitar del programa?"
                                                        data-message="{{ $it->name }} se quitará de tu programa."
                                                        data-label="Quitar">Quitar</button>
                                            @elseif ($it->added_by === 'customer')
                                                <span class="mn-muted" title="Ya tiene registros">Con registros</span>
                                            @else
                                                <span class="mn-muted">&mdash;</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="mn-muted" style="margin:.75rem 0 0">Los parámetros, límites y químicos definidos por Equiterm no se pueden modificar desde el portal. Solo puedes quitar los que tú agregaste y que todavía no tienen registros.</p>
                @endif
            </details>
        </section>
    @endif

    {{-- =========================== SEMANAS =========================== --}}
    @if ($tab === 'semanas')
        <div class="mn-weeks">
            @foreach ($weekSummaries as $w)
                @php
                    $pctDays = $w['days_expected'] > 0 ? min(100, (int) round($w['days_logged'] / $w['days_expected'] * 100)) : 0;
                @endphp
                <article class="mn-week {{ $w['is_current'] ? 'mn-week--current' : '' }} {{ $w['is_future'] ? 'mn-week--future' : '' }}"
                         aria-label="Semana {{ $w['n'] }}" @if ($w['is_current']) aria-current="true" @endif>
                    <div class="mn-week__head">
                        <div>
                            <h2 class="mn-week__title">Semana {{ $w['n'] }}</h2>
                            <div class="mn-week__sub">{{ $dshort($w['start']) }} &ndash; {{ $dshort($w['end']) }}</div>
                        </div>
                        @if ($w['is_current'])
                            <span class="mn-badge mn-badge--orange">Semana actual</span>
                        @elseif ($w['is_future'])
                            <span class="mn-badge mn-badge--neutral">Próxima</span>
                        @endif
                    </div>

                    @if (!$w['is_future'])
                        <div>
                            <div class="mn-progress-label">
                                <span>Días con registro</span>
                                <span>{{ $w['days_logged'] }} de {{ $w['days_expected'] }}</span>
                            </div>
                            <div class="mn-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pctDays }}" aria-label="Días registrados en la semana {{ $w['n'] }}">
                                <span style="width: {{ $pctDays }}%"></span>
                            </div>
                        </div>
                        <div style="display:flex;flex-wrap:wrap;gap:.375rem">
                            <span class="mn-badge mn-badge--param">{{ $w['readings_count'] }} {{ $w['readings_count'] === 1 ? 'lectura' : 'lecturas' }}</span>
                            <span class="mn-badge mn-badge--chem">{{ $w['doses_count'] }} {{ $w['doses_count'] === 1 ? 'dosificación' : 'dosificaciones' }}</span>
                            @if ($w['out_of_range'] > 0)
                                <span class="mn-badge mn-badge--bad"><span aria-hidden="true">&#10006;</span> {{ $w['out_of_range'] }} fuera de rango</span>
                            @elseif ($w['readings_count'] > 0)
                                <span class="mn-badge mn-badge--ok"><span aria-hidden="true">&#10004;</span> Todo en rango</span>
                            @endif
                        </div>
                    @else
                        <p class="mn-muted" style="margin:0">Aún no comienza esta semana.</p>
                    @endif

                    @if (count($w['parameters']))
                        <div>
                            <h3 class="mn-week__sect">Lecturas del agua</h3>
                            <div class="mn-mini-scroll">
                                <table class="mn-mini">
                                    <thead><tr><th>Parámetro</th><th class="mn-num">Prom.</th><th class="mn-num">Mín.</th><th class="mn-num">Máx.</th><th class="mn-num">Fuera</th></tr></thead>
                                    <tbody>
                                        @foreach ($w['parameters'] as $pm)
                                            <tr>
                                                <td>{{ $pm['name'] }}{{ $pm['unit'] ? ' (' . $pm['unit'] . ')' : '' }}</td>
                                                <td class="mn-num"><strong>{{ $num($pm['avg']) }}</strong></td>
                                                <td class="mn-num">{{ $num($pm['min']) }}</td>
                                                <td class="mn-num">{{ $num($pm['max']) }}</td>
                                                <td class="mn-num {{ $pm['out_of_range'] > 0 ? 'mn-bad-text' : '' }}">{{ $pm['out_of_range'] > 0 ? '✖ ' . $pm['out_of_range'] : '0' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if (count($w['chemicals']))
                        <div>
                            <h3 class="mn-week__sect">Químicos: aplicado vs. planeado</h3>
                            <div class="mn-mini-scroll">
                                <table class="mn-mini">
                                    <thead><tr><th>Químico</th><th class="mn-num">Aplicado</th><th class="mn-num">Plan</th><th class="mn-num">Días</th></tr></thead>
                                    <tbody>
                                        @foreach ($w['chemicals'] as $ch)
                                            <tr>
                                                <td>{{ $ch['name'] }}</td>
                                                <td class="mn-num"><strong>{{ $num($ch['total']) }}</strong> {{ $ch['unit'] }}</td>
                                                <td class="mn-num">{{ $ch['planned_total'] !== null ? $num($ch['planned_total']) . ' ' . $ch['unit'] : '—' }}</td>
                                                <td class="mn-num">{{ $ch['days'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if (!$w['is_future'] && !count($w['parameters']) && !count($w['chemicals']))
                        <p class="mn-muted" style="margin:0">Sin registros esta semana.</p>
                    @endif
                </article>
            @endforeach
        </div>
    @endif

    {{-- =========================== GANTT =========================== --}}
    @if ($tab === 'gantt')
        <div class="mn-print-only">
            <strong>{{ $program->name }}</strong> &middot; {{ $dm($program->start_date) }} &ndash; {{ $dm($endDate) }}
            @if ($equipmentId) &middot; Equipo: {{ $allEquipment->firstWhere('id', $equipmentId)?->name }} @endif
        </div>
        <section class="ts-card" aria-label="Diagrama de Gantt">
            <x-monitoring.gantt :data="$gantt" />
        </section>
    @endif

    {{-- =========================== GRÁFICAS =========================== --}}
    @if ($tab === 'graficas')
        @if (!$chartItem)
            <div class="ts-card mn-empty">
                <p>Este programa aún no tiene parámetros ni químicos para graficar.</p>
            </div>
        @else
            @if ($allEquipment->isEmpty())
                {{-- Sin equipos no hay filtro de equipo: el selector de elemento va aparte --}}
                <form method="GET" action="{{ route('customer.monitoring.show', $program->id) }}" class="mn-toolbar mn-no-print">
                    <input type="hidden" name="tab" value="graficas">
                    <div class="ts-field">
                        <label class="ts-label" for="mn-f-item-b">Parámetro o químico</label>
                        <div class="ts-field-select-wrap">
                            <select class="ts-select-field" id="mn-f-item-b" name="item" data-autosubmit>
                                @foreach ($items as $it)
                                    <option value="{{ $it->id }}" @selected($chartItem->id === $it->id)>{{ $it->name }}{{ $it->unit ? ' (' . $it->unit . ')' : '' }}</option>
                                @endforeach
                            </select>
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </div>
                    </div>
                    <noscript><button type="submit" class="ts-btn">Ver</button></noscript>
                </form>
            @endif

            <section class="ts-card" aria-label="Gráfica de {{ $chartItem->name }}">
                <x-monitoring.line-chart :series="$series" />

                @php
                    $st = $itemStats;
                    $isChem = $chartItem->kind === 'chemical';
                @endphp
                @if ($st && (int) $st->n > 0)
                    <div class="mn-chart-stats">
                        <div class="mn-kpi"><div class="mn-kpi__label">{{ $isChem ? 'Dosificaciones' : 'Lecturas' }}</div><div class="mn-kpi__value">{{ (int) $st->n }}</div></div>
                        <div class="mn-kpi"><div class="mn-kpi__label">Días con registro</div><div class="mn-kpi__value">{{ (int) $st->days }}</div></div>
                        @if ($isChem)
                            <div class="mn-kpi"><div class="mn-kpi__label">Total aplicado</div><div class="mn-kpi__value mn-kpi__value--sm">{{ $num($st->sum_v) }} {{ $chartItem->unit }}</div></div>
                            <div class="mn-kpi"><div class="mn-kpi__label">Promedio por registro</div><div class="mn-kpi__value mn-kpi__value--sm">{{ $num($st->avg_v) }} {{ $chartItem->unit }}</div></div>
                            <div class="mn-kpi"><div class="mn-kpi__label">Dosis diaria planeada</div><div class="mn-kpi__value mn-kpi__value--sm">{{ $chartItem->planned_daily_dose !== null ? $num($chartItem->planned_daily_dose) . ' ' . $chartItem->unit : '—' }}</div></div>
                        @else
                            <div class="mn-kpi"><div class="mn-kpi__label">Promedio</div><div class="mn-kpi__value mn-kpi__value--sm">{{ $num($st->avg_v) }} {{ $chartItem->unit }}</div></div>
                            <div class="mn-kpi"><div class="mn-kpi__label">Mínimo</div><div class="mn-kpi__value mn-kpi__value--sm">{{ $num($st->min_v) }} {{ $chartItem->unit }}</div></div>
                            <div class="mn-kpi"><div class="mn-kpi__label">Máximo</div><div class="mn-kpi__value mn-kpi__value--sm">{{ $num($st->max_v) }} {{ $chartItem->unit }}</div></div>
                            <div class="mn-kpi"><div class="mn-kpi__label">Fuera de rango</div><div class="mn-kpi__value mn-kpi__value--sm {{ (int) $st->oor > 0 ? 'mn-kpi__value--bad' : '' }}">{{ (int) $st->oor }}</div></div>
                            <div class="mn-kpi"><div class="mn-kpi__label">Rango esperado</div><div class="mn-kpi__value mn-kpi__value--sm">{{ $rangeText($chartItem->limit_min, $chartItem->limit_max) ?: 'Sin rango' }}</div></div>
                        @endif
                    </div>
                @else
                    <p class="mn-muted" style="margin:1rem 0 0">Todavía no hay registros de este elemento{{ $equipmentId ? ' para el equipo seleccionado' : '' }}.</p>
                @endif
            </section>
        @endif
    @endif
</div>

{{-- ============ Modal: editar registro ============ --}}
<div class="mn-modal" id="mn-modal-edit" role="dialog" aria-modal="true" aria-labelledby="mn-edit-title" aria-hidden="true">
    <div class="mn-modal__card">
        <form method="POST" action="#" id="mn-edit-form" novalidate>
            @csrf
            @method('PUT')
            <input type="hidden" name="_form" value="entry_edit">
            <input type="hidden" name="_entry_id" value="{{ old('_entry_id') }}" id="ee-id">
            <input type="hidden" name="_entry_item" value="{{ old('_entry_item') }}" id="ee-item-h">
            <input type="hidden" name="_entry_unit" value="{{ old('_entry_unit') }}" id="ee-unit-h">
            <div class="mn-modal__head">
                <h2 class="mn-modal__title" id="mn-edit-title">Editar registro</h2>
                <button type="button" class="mn-modal__close" data-mn-close aria-label="Cerrar">&times;</button>
            </div>
            <div class="mn-modal__body">
                <div class="mn-full mn-muted">Elemento: <strong id="ee-item" style="color:var(--ts-text-title,#111827)"></strong></div>

                <div class="ts-field">
                    <label class="ts-label" for="ee-date">Fecha <span class="ts-label__req">*</span></label>
                    <input type="date" class="ts-input" id="ee-date" name="entry_date" required
                           min="{{ $program->start_date->toDateString() }}" max="{{ $defaultDate }}" value="{{ $oe('entry_date') }}">
                    @if ($ebe->has('entry_date')) <span class="mn-error">{{ $ebe->first('entry_date') }}</span> @endif
                </div>

                <div class="ts-field">
                    <label class="ts-label" for="ee-value">Valor <span class="ts-label__req">*</span> <span id="ee-unit" class="mn-muted"></span></label>
                    <input type="text" inputmode="decimal" autocomplete="off" class="ts-input" id="ee-value" name="value" required value="{{ $oe('value') }}">
                    @if ($ebe->has('value')) <span class="mn-error">{{ $ebe->first('value') }}</span> @endif
                </div>

                <div class="ts-field mn-full">
                    <label class="ts-label" for="ee-equipment">Equipo (opcional)</label>
                    <div class="ts-field-select-wrap">
                        <select class="ts-select-field" id="ee-equipment" name="equipment_id">
                            <option value="">Sin equipo específico</option>
                            @foreach ($allEquipment as $eq)
                                <option value="{{ $eq->id }}">{{ $eq->name }}{{ $eq->is_active ? '' : ' (inactivo)' }}</option>
                            @endforeach
                        </select>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </div>
                    @if ($ebe->has('equipment_id')) <span class="mn-error">{{ $ebe->first('equipment_id') }}</span> @endif
                </div>

                <div class="ts-field mn-full">
                    <label class="ts-label" for="ee-note">Nota (opcional)</label>
                    <input type="text" class="ts-input" id="ee-note" name="note" maxlength="255" value="{{ $oe('note') }}">
                    @if ($ebe->has('note')) <span class="mn-error">{{ $ebe->first('note') }}</span> @endif
                </div>
            </div>
            <div class="mn-modal__foot">
                <button type="button" class="ts-btn" data-mn-close>Cancelar</button>
                <button type="submit" class="ts-btn ts-btn--primary">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

{{-- ============ Modal: ajustar fechas ============ --}}
<div class="mn-modal" id="mn-modal-dates" role="dialog" aria-modal="true" aria-labelledby="mn-dates-title" aria-hidden="true">
    <div class="mn-modal__card mn-modal__card--sm">
        <form method="POST" action="{{ route('customer.monitoring.dates', $program->id) }}" novalidate>
            @csrf
            @method('PUT')
            <input type="hidden" name="_form" value="dates">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="mn-modal__head">
                <h2 class="mn-modal__title" id="mn-dates-title">Ajustar fechas del programa</h2>
                <button type="button" class="mn-modal__close" data-mn-close aria-label="Cerrar">&times;</button>
            </div>
            <div class="mn-modal__body" style="grid-template-columns:1fr">
                <div class="ts-field">
                    <label class="ts-label" for="md-start">Fecha de inicio <span class="ts-label__req">*</span></label>
                    <input type="date" class="ts-input {{ $ebd->has('start_date') ? 'mn-input-error' : '' }}" id="md-start" name="start_date" required
                           value="{{ ($formMarker === 'dates' ? old('start_date') : null) ?: $program->start_date->toDateString() }}">
                    @if ($ebd->has('start_date')) <span class="mn-error">{{ $ebd->first('start_date') }}</span> @endif
                </div>
                <div class="mn-muted" aria-live="polite">
                    Duración: {{ $program->weeks }} {{ $program->weeks === 1 ? 'semana' : 'semanas' }} (no cambia).
                    Terminaría el <strong id="md-end">{{ $dm($endDate) }}</strong>.
                </div>
                <div class="mn-alert mn-alert--info" style="margin:0">No puedes dejar registros fuera de las nuevas fechas.</div>
            </div>
            <div class="mn-modal__foot">
                <button type="button" class="ts-btn" data-mn-close>Cancelar</button>
                <button type="submit" class="ts-btn ts-btn--primary">Guardar fechas</button>
            </div>
        </form>
    </div>
</div>

{{-- ============ Modal: agregar parámetro ============ --}}
<div class="mn-modal" id="mn-modal-item" role="dialog" aria-modal="true" aria-labelledby="mn-item-title" aria-hidden="true">
    <div class="mn-modal__card">
        <form method="POST" action="{{ route('customer.monitoring.items.store', $program->id) }}" id="mn-item-form" novalidate>
            @csrf
            <input type="hidden" name="_form" value="item">
            <div class="mn-modal__head">
                <h2 class="mn-modal__title" id="mn-item-title">Agregar parámetro</h2>
                <button type="button" class="mn-modal__close" data-mn-close aria-label="Cerrar">&times;</button>
            </div>
            <div class="mn-modal__body">
                <div class="ts-field mn-full">
                    <label class="ts-label" for="ai-kind">Tipo <span class="ts-label__req">*</span></label>
                    <div class="ts-field-select-wrap">
                        <select class="ts-select-field" id="ai-kind" name="kind" required>
                            <option value="parameter" @selected(old('kind', 'parameter') === 'parameter')>Lectura del agua (pH, dureza, TDS...)</option>
                            <option value="chemical" @selected(old('kind') === 'chemical')>Dosificación de un químico</option>
                        </select>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </div>
                    @if ($ebi->has('kind')) <span class="mn-error">{{ $ebi->first('kind') }}</span> @endif
                </div>

                <div class="ts-field">
                    <label class="ts-label" for="ai-name">Nombre <span class="ts-label__req">*</span></label>
                    <input type="text" class="ts-input {{ $ebi->has('name') ? 'mn-input-error' : '' }}" id="ai-name" name="name" maxlength="120" required
                           placeholder="Ej. Conductividad" value="{{ old('name') }}">
                    @if ($ebi->has('name')) <span class="mn-error">{{ $ebi->first('name') }}</span> @endif
                </div>

                <div class="ts-field">
                    <label class="ts-label" for="ai-unit">Unidad (opcional)</label>
                    <input type="text" class="ts-input {{ $ebi->has('unit') ? 'mn-input-error' : '' }}" id="ai-unit" name="unit" maxlength="20"
                           placeholder="Ej. ppm, mg/L, L" value="{{ old('unit') }}">
                    @if ($ebi->has('unit')) <span class="mn-error">{{ $ebi->first('unit') }}</span> @endif
                </div>

                <div class="ts-field" data-limit-field>
                    <label class="ts-label" for="ai-min">Límite mínimo (opcional)</label>
                    <input type="text" inputmode="decimal" class="ts-input {{ $ebi->has('limit_min') ? 'mn-input-error' : '' }}" id="ai-min" name="limit_min" value="{{ old('limit_min') }}">
                    @if ($ebi->has('limit_min')) <span class="mn-error">{{ $ebi->first('limit_min') }}</span> @endif
                </div>

                <div class="ts-field" data-limit-field>
                    <label class="ts-label" for="ai-max">Límite máximo (opcional)</label>
                    <input type="text" inputmode="decimal" class="ts-input {{ $ebi->has('limit_max') ? 'mn-input-error' : '' }}" id="ai-max" name="limit_max" value="{{ old('limit_max') }}">
                    @if ($ebi->has('limit_max')) <span class="mn-error">{{ $ebi->first('limit_max') }}</span> @endif
                </div>

                <div class="ts-field">
                    <label class="ts-label" for="ai-sw">Desde la semana <span class="ts-label__req">*</span></label>
                    <input type="number" class="ts-input {{ $ebi->has('start_week') ? 'mn-input-error' : '' }}" id="ai-sw" name="start_week" min="1" max="{{ $program->weeks }}" step="1" required
                           value="{{ old('start_week', 1) }}">
                    @if ($ebi->has('start_week')) <span class="mn-error">{{ $ebi->first('start_week') }}</span> @endif
                </div>

                <div class="ts-field">
                    <label class="ts-label" for="ai-ew">Hasta la semana <span class="ts-label__req">*</span></label>
                    <input type="number" class="ts-input {{ $ebi->has('end_week') ? 'mn-input-error' : '' }}" id="ai-ew" name="end_week" min="1" max="{{ $program->weeks }}" step="1" required
                           value="{{ old('end_week', $program->weeks) }}">
                    @if ($ebi->has('end_week')) <span class="mn-error">{{ $ebi->first('end_week') }}</span> @endif
                </div>

                <div class="mn-full mn-muted" id="ai-limit-note">Si defines límites, las lecturas fuera de ese rango se marcarán automáticamente.</div>
            </div>
            <div class="mn-modal__foot">
                <button type="button" class="ts-btn" data-mn-close>Cancelar</button>
                <button type="submit" class="ts-btn ts-btn--primary">Agregar al programa</button>
            </div>
        </form>
    </div>
</div>

{{-- ============ Modal: confirmar eliminación (registros y elementos) ============ --}}
<div class="mn-modal" id="mn-modal-confirm" role="alertdialog" aria-modal="true" aria-labelledby="mn-confirm-title" aria-hidden="true">
    <div class="mn-modal__card mn-modal__card--sm">
        <form method="POST" action="#" id="mn-confirm-form">
            @csrf
            @method('DELETE')
            <div class="mn-modal__head">
                <h2 class="mn-modal__title" id="mn-confirm-title">¿Eliminar?</h2>
                <button type="button" class="mn-modal__close" data-mn-close aria-label="Cerrar">&times;</button>
            </div>
            <div class="mn-confirm" id="mn-confirm-msg"></div>
            <div class="mn-modal__foot">
                <button type="button" class="ts-btn" data-mn-close>Cancelar</button>
                <button type="submit" class="ts-btn ts-btn--danger" id="mn-confirm-btn">Eliminar</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
@include('customers.monitoring.partials.modal-js')
<script>
(function () {
    var weeks = @json($program->weeks);
    var editUrlTpl = @json(route('customer.monitoring.entries.update', ['entry' => '__ID__']));
    var marker = @json($formMarker);
    var oldEntryId = @json(old('_entry_id'));
    var oldEquip = @json(old('equipment_id'));

    // Filtros que se envían solos al cambiar.
    document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
        el.addEventListener('change', function () { el.form.submit(); });
    });

    // Unidad y rango esperado según el elemento elegido en el formulario rápido.
    var itemSel = document.getElementById('mn-item');
    if (itemSel) {
        var hint = document.getElementById('mn-value-hint');
        var unit = document.getElementById('mn-value-unit');
        var label = document.getElementById('mn-value-label');
        var sync = function () {
            var opt = itemSel.options[itemSel.selectedIndex];
            if (!opt || !opt.value) {
                hint.textContent = 'Selecciona un elemento para ver su unidad y rango esperado.';
                unit.textContent = ''; label.textContent = 'Valor';
                return;
            }
            var chem = opt.getAttribute('data-kind') === 'chemical';
            label.textContent = chem ? 'Cantidad aplicada' : 'Valor medido';
            unit.textContent = opt.getAttribute('data-unit') || '';
            var h = opt.getAttribute('data-hint');
            if (chem) hint.textContent = h || 'Captura la cantidad dosificada.';
            else hint.textContent = h ? 'Rango esperado: ' + h + (opt.getAttribute('data-unit') ? ' ' + opt.getAttribute('data-unit') : '') : 'Este parámetro no tiene rango definido.';
        };
        itemSel.addEventListener('change', sync);
        sync();
    }

    var errBox = document.getElementById('mn-entry-errors');
    if (errBox) errBox.scrollIntoView({ block: 'center' });

    // ---- Editar registro
    var editModal = document.getElementById('mn-modal-edit');
    var editForm = document.getElementById('mn-edit-form');
    function openEdit(trigger) {
        var d = JSON.parse(trigger.getAttribute('data-entry'));
        editForm.action = trigger.getAttribute('data-action');
        document.getElementById('ee-id').value = d.id;
        document.getElementById('ee-item').textContent = d.item || '';
        document.getElementById('ee-item-h').value = d.item || '';
        document.getElementById('ee-unit-h').value = d.unit || '';
        document.getElementById('ee-unit').textContent = d.unit ? '(' + d.unit + ')' : '';
        document.getElementById('ee-date').value = d.date;
        document.getElementById('ee-value').value = d.value;
        document.getElementById('ee-equipment').value = d.equipment_id ? String(d.equipment_id) : '';
        document.getElementById('ee-note').value = d.note || '';
        editModal.querySelectorAll('.mn-error').forEach(function (e) { e.remove(); });
        window.mnModal.setTrigger(trigger);
        window.mnModal.open(editModal, '#ee-value');
    }

    // ---- Confirmación de eliminación genérica
    var confirmModal = document.getElementById('mn-modal-confirm');
    function openConfirm(trigger) {
        document.getElementById('mn-confirm-form').action = trigger.getAttribute('data-action');
        document.getElementById('mn-confirm-title').textContent = trigger.getAttribute('data-title');
        document.getElementById('mn-confirm-msg').textContent = trigger.getAttribute('data-message');
        document.getElementById('mn-confirm-btn').textContent = trigger.getAttribute('data-label') || 'Eliminar';
        window.mnModal.setTrigger(trigger);
        window.mnModal.open(confirmModal, '.ts-btn');
    }

    document.addEventListener('click', function (ev) {
        var e1 = ev.target.closest('[data-entry-edit]');
        if (e1) { openEdit(e1); return; }
        var c1 = ev.target.closest('[data-confirm]');
        if (c1) openConfirm(c1);
    });

    // ---- Agregar parámetro: los químicos no llevan límites
    var kindSel = document.getElementById('ai-kind');
    function syncKind() {
        var chem = kindSel.value === 'chemical';
        document.querySelectorAll('[data-limit-field]').forEach(function (el) { el.hidden = chem; });
        document.getElementById('ai-limit-note').hidden = chem;
        document.getElementById('mn-item-title').textContent = chem ? 'Agregar químico' : 'Agregar parámetro';
    }
    kindSel.addEventListener('change', syncKind);
    syncKind();

    // ---- Ajustar fechas: calcula el fin con las mismas semanas
    var start = document.getElementById('md-start');
    start.addEventListener('input', function () {
        var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(start.value);
        if (!m) return;
        var d = new Date(Date.UTC(+m[1], +m[2] - 1, +m[3]));
        d.setUTCDate(d.getUTCDate() + weeks * 7 - 1);
        document.getElementById('md-end').textContent = d.toLocaleDateString('es-MX', { timeZone: 'UTC', day: 'numeric', month: 'short', year: 'numeric' });
    });

    // ---- Reabrir el modal correspondiente tras un error de validación
    if (marker === 'entry_edit' && oldEntryId) {
        editForm.action = editUrlTpl.replace('__ID__', oldEntryId);
        document.getElementById('ee-item').textContent = @json(old('_entry_item'));
        var ou = @json(old('_entry_unit'));
        document.getElementById('ee-unit').textContent = ou ? '(' + ou + ')' : '';
        if (oldEquip !== null) document.getElementById('ee-equipment').value = String(oldEquip || '');
        window.mnModal.open(editModal, '#ee-value');
    } else if (marker === 'dates' && @json($ebd->any())) {
        window.mnModal.open('mn-modal-dates', '#md-start');
    } else if (marker === 'item' && @json($ebi->any())) {
        window.mnModal.open('mn-modal-item', '#ai-name');
    }
})();
</script>
@endpush
