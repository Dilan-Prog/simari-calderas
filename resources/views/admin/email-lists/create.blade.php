@extends('admin.layouts.master')

@section('title', 'Nueva Lista de Correo - Admin')

@push('styles')
<style>
:root {
    --background--white:          #ffffff;
    --header-footer-color:        #1A2535;
    --text-subwhite-color:        #D1D5DC;
    --text-description-color:     #6B7280;
    --secondary-color:            #ff6213;
    --button-primary-color:       #ff6213;
    --button-primary-color-hover: #de4a00;
    --font-family:                'Inter', sans-serif;
    --shadow-sm:                  0 1px 2px rgba(0,0,0,.06);
    --shadow-md:                  0 10px 20px rgba(0,0,0,.1);
}

.el-form-page { padding: 32px; font-family: var(--font-family); display: flex; flex-direction: column; gap: 24px; max-width: 900px; margin: 0 auto; }
.el-form-breadcrumb { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--text-description-color); margin-bottom: 8px; }
.el-form-breadcrumb svg { flex-shrink: 0; color: #9CA3AF; }
.el-form-breadcrumb-current { color: #374151; }
.el-form-title { font-size: 24px; font-weight: 700; color: #111827; line-height: 1.2; margin: 0 0 6px; }
.el-form-subtitle { font-size: 14px; color: var(--text-description-color); margin: 0 0 8px; }

.el-form-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); padding: 24px; }
.el-form-section-title { font-size: 14px; font-weight: 600; color: #111827; margin: 0 0 16px; padding-bottom: 10px; border-bottom: 1px solid #F3F4F6; }
.el-form-section-title:not(:first-child) { margin-top: 28px; }
.el-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.el-form-field { display: flex; flex-direction: column; gap: 6px; }
.el-form-field-full { grid-column: 1 / -1; }
.el-form-label { font-size: 13px; font-weight: 500; color: #374151; }
.el-form-label .req { color: #DC2626; }
.el-form-input, .el-form-select, .el-form-textarea {
    width: 100%; height: 40px; padding: 0 12px; border: 1px solid #D1D5DB; border-radius: 6px;
    font-size: 13px; font-family: var(--font-family); color: #111827; background: #fff;
    transition: border-color .2s, box-shadow .2s; box-sizing: border-box;
}
.el-form-textarea { height: auto; padding: 10px 12px; min-height: 100px; resize: vertical; font-family: 'SFMono-Regular', Consolas, Menlo, monospace; }
.el-form-select { appearance: none; cursor: pointer; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236B7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; padding-right: 32px; }
.el-form-input:focus, .el-form-select:focus, .el-form-textarea:focus { outline: none; border-color: var(--secondary-color); box-shadow: 0 0 0 3px rgba(255,98,19,.12); }
.el-form-hint { font-size: 12px; color: #9CA3AF; }
.el-form-hint code { background: #F3F4F6; border-radius: 3px; padding: 1px 5px; font-size: 11px; }
.el-form-error { font-size: 12px; color: #DC2626; }
.el-form-input.is-invalid, .el-form-select.is-invalid, .el-form-textarea.is-invalid { border-color: #DC2626; }

.el-form-actions { display: flex; align-items: center; justify-content: flex-end; gap: 10px; margin-top: 24px; padding-top: 20px; border-top: 1px solid #F3F4F6; }
.el-btn-cancel { height: 40px; padding: 0 16px; border: 1px solid #D1D5DB; background: #fff; color: #374151; border-radius: 6px; font-size: 13px; font-weight: 500; font-family: var(--font-family); text-decoration: none; display: inline-flex; align-items: center; cursor: pointer; transition: background .15s; }
.el-btn-cancel:hover { background: #F9FAFB; }
.el-btn-save { height: 40px; padding: 0 20px; border: none; background: var(--button-primary-color); color: #fff; border-radius: 6px; font-size: 13px; font-weight: 500; font-family: var(--font-family); cursor: pointer; box-shadow: var(--shadow-md); transition: background .2s; }
.el-btn-save:hover { background: var(--button-primary-color-hover); }

/* Static/active toggle sections */
.el-type-section { display: none; }
.el-type-section.is-visible { display: block; }

/* Customer picker */
.el-picker-search { position: relative; margin-bottom: 12px; }
.el-picker-search svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9CA3AF; pointer-events: none; }
.el-picker-search input { padding-left: 36px; }
.el-picker-count { font-size: 12px; color: var(--text-description-color); margin-bottom: 8px; }
.el-picker-count strong { color: #111827; }
.el-picker-box { border: 1px solid #E5E7EB; border-radius: 8px; max-height: 360px; overflow-y: auto; }
.el-picker-table { width: 100%; border-collapse: collapse; }
.el-picker-table thead tr { background: #F9FAFB; }
.el-picker-table thead th { position: sticky; top: 0; background: #F9FAFB; padding: 8px 12px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: #6B7280; border-bottom: 1px solid #E5E7EB; }
.el-picker-table tbody td { padding: 8px 12px; font-size: 13px; color: #111827; border-bottom: 1px solid #F3F4F6; }
.el-picker-table tbody tr:last-child td { border-bottom: none; }
.el-picker-table tbody tr:hover { background: #FAFBFC; }
.el-picker-table tbody tr.is-hidden { display: none; }
.el-picker-table td.el-picker-check { width: 36px; }
.el-picker-table td.el-picker-email { color: #6B7280; }
.el-picker-empty { padding: 24px; text-align: center; font-size: 13px; color: #9CA3AF; }

@media (max-width: 640px) {
    .el-form-page { padding: 16px; }
    .el-form-grid { grid-template-columns: 1fr; }
    .el-form-card { padding: 16px; }
}
</style>
@endpush

@section('content')
<div class="el-form-page">

    <div>
        <div class="el-form-breadcrumb">
            <span>Panel de Control</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            <a href="{{ route('admin.email-lists.index') }}" style="color:inherit;text-decoration:none;">Listas de Correo</a>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            <span class="el-form-breadcrumb-current">Nueva</span>
        </div>
        <h1 class="el-form-title">Nueva lista de correo</h1>
        <p class="el-form-subtitle">Crea una lista estática (miembros fijos que agregas manualmente) o dinámica (se calcula con un filtro sobre los clientes)</p>
    </div>

    @if($errors->any())
    <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:14px 16px;color:#DC2626;font-size:13px;">
        <strong>Corrige los siguientes errores:</strong>
        <ul style="margin:6px 0 0;padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.email-lists.store') }}" id="email-list-form">
        @csrf

        <div class="el-form-card">
            <h2 class="el-form-section-title">Datos de la lista</h2>
            <div class="el-form-grid">

                <div class="el-form-field el-form-field-full">
                    <label class="el-form-label" for="name">Nombre de la lista <span class="req">*</span></label>
                    <input type="text" id="name" name="name" class="el-form-input @error('name') is-invalid @enderror" value="{{ old('name') }}" required maxlength="255" placeholder="Ej. Clientes activos, Newsletter mensual...">
                    @error('name') <span class="el-form-error">{{ $message }}</span> @enderror
                </div>

                <div class="el-form-field el-form-field-full">
                    <label class="el-form-label" for="type">Tipo <span class="req">*</span></label>
                    <select id="type" name="type" class="el-form-select @error('type') is-invalid @enderror" required>
                        <option value="static" {{ old('type', 'static') === 'static' ? 'selected' : '' }}>Estática — selecciono los miembros manualmente</option>
                        <option value="active" {{ old('type') === 'active' ? 'selected' : '' }}>Dinámica — se calcula con un filtro sobre los clientes</option>
                    </select>
                    @error('type') <span class="el-form-error">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- ── Sección estática: buscador + checkboxes de clientes ── --}}
            <div class="el-type-section" id="section-static">
                <h2 class="el-form-section-title">Miembros iniciales</h2>
                <p class="el-form-hint" style="margin-bottom:12px;">Selecciona los clientes que quieres agregar a la lista ahora. Puedes agregar o quitar más después de crearla.</p>

                <div class="el-picker-search">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" id="customer-filter" class="el-form-input" placeholder="Filtrar por nombre, empresa o email...">
                </div>
                <div class="el-picker-count"><strong id="customer-selected-count">0</strong> clientes seleccionados</div>

                <div class="el-picker-box">
                    <table class="el-picker-table" id="customer-picker-table">
                        <thead>
                            <tr>
                                <th class="el-picker-check"><input type="checkbox" id="customer-check-all"></th>
                                <th>Nombre</th>
                                <th>Empresa</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customers as $customer)
                            <tr data-search="{{ strtolower(trim($customer->first_name.' '.$customer->last_name).' '.$customer->company.' '.$customer->email) }}">
                                <td class="el-picker-check">
                                    <input type="checkbox" name="customer_ids[]" value="{{ $customer->id }}" class="customer-checkbox" {{ collect(old('customer_ids'))->contains($customer->id) ? 'checked' : '' }}>
                                </td>
                                <td>{{ trim($customer->first_name.' '.$customer->last_name) }}</td>
                                <td>{{ $customer->company ?: '—' }}</td>
                                <td class="el-picker-email">{{ $customer->email }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="el-picker-empty">No hay clientes activos disponibles.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ── Sección dinámica: filtro JSON ── --}}
            <div class="el-type-section" id="section-active">
                <h2 class="el-form-section-title">Filtro de clientes</h2>
                <div class="el-form-grid">
                    <div class="el-form-field el-form-field-full">
                        <label class="el-form-label" for="filter_definition">Definición del filtro (JSON)</label>
                        <textarea id="filter_definition" name="filter_definition" class="el-form-textarea @error('filter_definition') is-invalid @enderror" placeholder='[{"field":"status","operator":"equals","value":"active"}]'>{{ old('filter_definition') }}</textarea>
                        <span class="el-form-hint">
                            Escribe un arreglo JSON crudo de condiciones (aún no hay un constructor visual). Operadores soportados: <code>equals</code>, <code>not_equals</code>, <code>greater_than</code>, <code>less_than</code>, <code>contains</code>.
                            Ejemplo: <code>[{"field":"status","operator":"equals","value":"active"}]</code>. Déjalo vacío si por ahora no aplica.
                        </span>
                        @error('filter_definition') <span class="el-form-error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div class="el-form-actions">
                <a href="{{ route('admin.email-lists.index') }}" class="el-btn-cancel">Cancelar</a>
                <button type="submit" class="el-btn-save">Guardar lista</button>
            </div>
        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    var typeSelect = document.getElementById('type');
    var sectionStatic = document.getElementById('section-static');
    var sectionActive = document.getElementById('section-active');

    function toggleSections() {
        if (typeSelect.value === 'static') {
            sectionStatic.classList.add('is-visible');
            sectionActive.classList.remove('is-visible');
        } else {
            sectionStatic.classList.remove('is-visible');
            sectionActive.classList.add('is-visible');
        }
    }

    typeSelect.addEventListener('change', toggleSections);
    toggleSections();

    /* ── Filtro client-side de clientes ───────── */
    var filterInput = document.getElementById('customer-filter');
    var rows = document.querySelectorAll('#customer-picker-table tbody tr[data-search]');

    if (filterInput) {
        filterInput.addEventListener('input', function () {
            var term = this.value.trim().toLowerCase();
            rows.forEach(function (row) {
                var match = !term || row.dataset.search.indexOf(term) !== -1;
                row.classList.toggle('is-hidden', !match);
            });
        });
    }

    /* ── Checkbox "seleccionar todos" (solo filas visibles) y contador ── */
    var checkAll = document.getElementById('customer-check-all');
    var checkboxes = document.querySelectorAll('.customer-checkbox');
    var selectedCountEl = document.getElementById('customer-selected-count');

    function updateSelectedCount() {
        var count = 0;
        checkboxes.forEach(function (cb) { if (cb.checked) count++; });
        if (selectedCountEl) selectedCountEl.textContent = count;
    }

    if (checkAll) {
        checkAll.addEventListener('change', function () {
            rows.forEach(function (row) {
                if (row.classList.contains('is-hidden')) return;
                var cb = row.querySelector('.customer-checkbox');
                if (cb) cb.checked = checkAll.checked;
            });
            updateSelectedCount();
        });
    }

    checkboxes.forEach(function (cb) {
        cb.addEventListener('change', updateSelectedCount);
    });

    updateSelectedCount();

})();
</script>
@endpush
