@extends('customers.layouts.master')

@section('title', 'Mis equipos')

@push('styles')
    @vite('resources/css/admin/technical-services.css')
    @include('customers.monitoring.partials.styles')
@endpush

@section('content')
@php
    $reopen = $errors->any() && old('_equipment_form');
    $editingId = old('_equipment_id');
@endphp
<div class="ts-page" id="ts-page">

    <div class="ts-breadcrumb">
        <span>Portal de servicios</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m9 18 6-6-6-6"/>
        </svg>
        <span class="ts-breadcrumb__current">Mis equipos</span>
    </div>

    <div class="ts-header-top">
        <div>
            <h1 class="ts-title">Mis equipos</h1>
            <p class="ts-subtitle">Calderas, torres de enfriamiento, chillers y demás equipos que monitoreas. Puedes ligar cada registro diario a uno de ellos.</p>
        </div>
        @if ($equipment->isNotEmpty())
            <button type="button" class="ts-btn ts-btn--primary" data-eq-open="create">+ Agregar equipo</button>
        @endif
    </div>

    @if ($equipment->isEmpty())
        <div class="ts-card mn-empty">
            <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
            </svg>
            <p>Aún no has registrado equipos.</p>
            <button type="button" class="ts-btn ts-btn--primary" data-eq-open="create">Agregar mi primer equipo</button>
        </div>
    @else
        <div class="mn-grid">
            @foreach ($equipment as $eq)
                <article class="mn-card" style="display:flex;flex-direction:column;gap:.75rem;{{ $eq->is_active ? '' : 'opacity:.75;' }}" aria-label="{{ $eq->name }}">
                    <div class="mn-card__head">
                        <h2 class="mn-card__title">{{ $eq->name }}</h2>
                        <span class="mn-badge {{ $eq->is_active ? 'mn-badge--active' : 'mn-badge--completed' }}">{{ $eq->is_active ? 'Activo' : 'Inactivo' }}</span>
                    </div>
                    <div>
                        <span class="mn-badge mn-badge--orange">{{ $eq->type_label }}</span>
                    </div>
                    <div style="font-size:.8125rem;line-height:1.55;color:var(--ts-text-body,#374151);flex:1;overflow-wrap:anywhere">
                        @if ($eq->location)
                            <div><strong>Ubicación:</strong> {{ $eq->location }}</div>
                        @endif
                        @if ($eq->notes)
                            <div class="mn-muted" style="margin-top:.25rem">{{ $eq->notes }}</div>
                        @endif
                        @if (!$eq->location && !$eq->notes)
                            <span class="mn-muted">Sin ubicación ni notas.</span>
                        @endif
                        <div class="mn-muted" style="margin-top:.5rem">
                            {{ $eq->entries_count }} {{ $eq->entries_count === 1 ? 'registro' : 'registros' }} de monitoreo
                        </div>
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:.5rem;padding-top:.75rem;border-top:1px solid #F3F4F6">
                        <button type="button" class="ts-btn mn-btn-sm" data-eq-open="edit"
                                data-action="{{ route('customer.equipment.update', $eq->id) }}"
                                data-equipment="{{ json_encode([
                                    'id' => $eq->id,
                                    'name' => $eq->name,
                                    'type' => $eq->type,
                                    'location' => $eq->location,
                                    'notes' => $eq->notes,
                                    'is_active' => (bool) $eq->is_active,
                                ], JSON_UNESCAPED_UNICODE) }}">Editar</button>
                        <button type="button" class="ts-btn ts-btn--danger mn-btn-sm" data-eq-open="delete"
                                data-action="{{ route('customer.equipment.destroy', $eq->id) }}"
                                data-name="{{ $eq->name }}"
                                data-entries="{{ $eq->entries_count }}">Eliminar</button>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>

{{-- Modal: agregar / editar --}}
<div class="mn-modal" id="eq-modal-form" role="dialog" aria-modal="true" aria-labelledby="eq-form-title" aria-hidden="true">
    <div class="mn-modal__card">
        <form method="POST" action="{{ route('customer.equipment.store') }}" id="eq-form" novalidate>
            @csrf
            <input type="hidden" name="_method" value="PUT" id="eq-method" disabled>
            <input type="hidden" name="_equipment_form" value="1">
            <input type="hidden" name="_equipment_id" value="{{ $editingId }}" id="eq-id">

            <div class="mn-modal__head">
                <h2 class="mn-modal__title" id="eq-form-title">Agregar equipo</h2>
                <button type="button" class="mn-modal__close" data-mn-close aria-label="Cerrar">&times;</button>
            </div>

            <div class="mn-modal__body">
                <div class="ts-field mn-full">
                    <label class="ts-label" for="eq-name">Nombre <span class="ts-label__req">*</span></label>
                    <input type="text" class="ts-input" id="eq-name" name="name" maxlength="120" required
                           placeholder="Ej. Caldera 1, Torre de la planta norte" value="{{ old('name') }}">
                    @error('name') <span class="mn-error">{{ $message }}</span> @enderror
                </div>

                <div class="ts-field">
                    <label class="ts-label" for="eq-type">Tipo <span class="ts-label__req">*</span></label>
                    <div class="ts-field-select-wrap">
                        <select class="ts-select-field" id="eq-type" name="type" required>
                            @foreach ($types as $key => $label)
                                <option value="{{ $key }}" {{ old('type', 'caldera') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </div>
                    @error('type') <span class="mn-error">{{ $message }}</span> @enderror
                </div>

                <div class="ts-field">
                    <label class="ts-label" for="eq-location">Ubicación (opcional)</label>
                    <input type="text" class="ts-input" id="eq-location" name="location" maxlength="150"
                           placeholder="Ej. Cuarto de máquinas" value="{{ old('location') }}">
                    @error('location') <span class="mn-error">{{ $message }}</span> @enderror
                </div>

                <div class="ts-field mn-full">
                    <label class="ts-label" for="eq-notes">Notas (opcional)</label>
                    <textarea class="ts-textarea" id="eq-notes" name="notes" rows="3" maxlength="2000"
                              placeholder="Capacidad, marca, observaciones...">{{ old('notes') }}</textarea>
                    @error('notes') <span class="mn-error">{{ $message }}</span> @enderror
                </div>

                <div class="mn-full" id="eq-active-wrap" hidden>
                    <label class="mn-check" for="eq-active">
                        <input type="checkbox" id="eq-active" name="is_active" value="1" checked>
                        Equipo activo (aparece en los formularios de registro)
                    </label>
                </div>
            </div>

            <div class="mn-modal__foot">
                <button type="button" class="ts-btn" data-mn-close>Cancelar</button>
                <button type="submit" class="ts-btn ts-btn--primary">Guardar equipo</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: confirmar eliminación --}}
<div class="mn-modal" id="eq-modal-delete" role="alertdialog" aria-modal="true" aria-labelledby="eq-del-title" aria-hidden="true">
    <div class="mn-modal__card mn-modal__card--sm">
        <form method="POST" action="#" id="eq-delete-form">
            @csrf
            @method('DELETE')
            <div class="mn-modal__head">
                <h2 class="mn-modal__title" id="eq-del-title">¿Eliminar este equipo?</h2>
                <button type="button" class="mn-modal__close" data-mn-close aria-label="Cerrar">&times;</button>
            </div>
            <div class="mn-confirm">
                <strong id="eq-del-name"></strong>
                <div id="eq-del-plain">se eliminará de tu cuenta. Esta acción no se puede deshacer.</div>
                <div id="eq-del-history" hidden>tiene registros de monitoreo, por lo que no se elimina: se <strong>desactivará</strong> y conservará su historial.</div>
            </div>
            <div class="mn-modal__foot">
                <button type="button" class="ts-btn" data-mn-close>Cancelar</button>
                <button type="submit" class="ts-btn ts-btn--danger" id="eq-del-btn">Eliminar</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
@include('customers.monitoring.partials.modal-js')
<script>
(function () {
    var storeUrl = @json(route('customer.equipment.store'));
    var updateTpl = @json(route('customer.equipment.update', ['equipment' => '__ID__']));
    var reopen = @json((bool) $reopen);
    var editingId = @json($editingId ? (int) $editingId : null);

    var modal = document.getElementById('eq-modal-form');
    var delModal = document.getElementById('eq-modal-delete');
    var form = document.getElementById('eq-form');
    var method = document.getElementById('eq-method');
    var idInput = document.getElementById('eq-id');
    var title = document.getElementById('eq-form-title');
    var activeWrap = document.getElementById('eq-active-wrap');
    var fields = ['name', 'type', 'location', 'notes'];

    function clearErrors() { form.querySelectorAll('.mn-error').forEach(function (e) { e.remove(); }); }
    function setValues(d) {
        fields.forEach(function (f) { form.elements[f].value = (d && d[f]) || (f === 'type' ? 'caldera' : ''); });
        form.elements['is_active'].checked = d ? !!d.is_active : true;
    }
    function openCreate(trigger) {
        clearErrors();
        form.action = storeUrl;
        method.disabled = true;
        idInput.value = '';
        activeWrap.hidden = true;
        title.textContent = 'Agregar equipo';
        setValues(null);
        window.mnModal.setTrigger(trigger);
        window.mnModal.open(modal, '#eq-name');
    }
    function openEdit(trigger) {
        var d = JSON.parse(trigger.getAttribute('data-equipment'));
        clearErrors();
        form.action = trigger.getAttribute('data-action');
        method.disabled = false;
        idInput.value = d.id;
        activeWrap.hidden = false;
        title.textContent = 'Editar equipo';
        setValues(d);
        window.mnModal.setTrigger(trigger);
        window.mnModal.open(modal, '#eq-name');
    }

    document.addEventListener('click', function (ev) {
        var t = ev.target.closest('[data-eq-open]');
        if (!t) return;
        var kind = t.getAttribute('data-eq-open');
        if (kind === 'create') openCreate(t);
        else if (kind === 'edit') openEdit(t);
        else if (kind === 'delete') {
            var withHistory = parseInt(t.getAttribute('data-entries'), 10) > 0;
            document.getElementById('eq-delete-form').action = t.getAttribute('data-action');
            document.getElementById('eq-del-name').textContent = t.getAttribute('data-name');
            document.getElementById('eq-del-plain').hidden = withHistory;
            document.getElementById('eq-del-history').hidden = !withHistory;
            document.getElementById('eq-del-btn').textContent = withHistory ? 'Desactivar' : 'Eliminar';
            document.getElementById('eq-del-title').textContent = withHistory ? '¿Desactivar este equipo?' : '¿Eliminar este equipo?';
            window.mnModal.setTrigger(t);
            window.mnModal.open(delModal, '.ts-btn');
        }
    });

    // Tras un error de validación se reabre el modal con lo que escribió (old input).
    if (reopen) {
        if (editingId) {
            form.action = updateTpl.replace('__ID__', editingId);
            method.disabled = false;
            activeWrap.hidden = false;
            form.elements['is_active'].checked = @json((bool) old('is_active'));
            title.textContent = 'Editar equipo';
        }
        window.mnModal.open(modal, '#eq-name');
    }
})();
</script>
@endpush
