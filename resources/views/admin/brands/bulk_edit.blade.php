@extends('admin.layouts.master')
@section('title')
    Editar en lote - Marcas - Admin
@endsection
@section('content')
    @php
        // Columnas configurables — 'name' va fija/pinneada en la tabla, no
        // se puede ocultar (mismo patrón que Nombre/SKU en Productos).
        $bulkEditColumns = [
            ['key' => 'slug', 'label' => 'Slug', 'group' => 'Básicos', 'type' => 'text', 'maxlength' => 150],
            ['key' => 'description', 'label' => 'Descripción', 'group' => 'Básicos', 'type' => 'textarea'],
            ['key' => 'logo_url', 'label' => 'Logo', 'group' => 'Básicos', 'type' => 'image-picker'],
            ['key' => 'is_active', 'label' => 'Activa', 'group' => 'Básicos', 'type' => 'checkbox'],

            ['key' => 'seo_title', 'label' => 'Título SEO', 'group' => 'SEO', 'type' => 'text', 'maxlength' => 60],
            ['key' => 'seo_description', 'label' => 'Descripción SEO', 'group' => 'SEO', 'type' => 'textarea', 'maxlength' => 160],
        ];
        // Todas visibles por defecto — con tan pocas columnas no hace falta
        // arrancar con un subconjunto reducido como en Productos.
        $defaultVisibleColumns = collect($bulkEditColumns)->pluck('key')->all();
        $columnGroups = collect($bulkEditColumns)->groupBy('group');
    @endphp

    <div class="prod-page prod-bulk-edit-page">
        <div class="prod-page-header">
            <div class="prod-header-top">
                <div>
                    <h1 class="prod-title">Editar en lote</h1>
                    <p class="prod-subtitle">Edita nombre, slug, descripción, logo y SEO de varias marcas a la vez</p>
                </div>
                <div class="prod-header-actions">
                    <a href="{{ route('admin.brands.index') }}" class="prod-btn-outline">
                        ← Volver a Marcas
                    </a>
                </div>
            </div>

            <form class="prod-toolbar" id="bulkEditFilterForm" method="GET">
                <div class="prod-search-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.3-4.3" />
                    </svg>
                    <input type="text" name="search" id="bulkEditSearch" class="prod-search-input"
                        placeholder="Buscar por nombre o slug..." autocomplete="off"
                        value="{{ request('search') }}" />
                </div>
                <select name="status" id="bulkEditStatusFilter" class="prod-filter-select">
                    <option value="all">Todos los estados</option>
                    <option value="active" @selected(request('status') === 'active')>Activas</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactivas</option>
                </select>
                <select name="per_page" id="bulkEditPerPage" class="prod-filter-select">
                    @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}" @selected((string) request('per_page', 25) === (string) $option)>
                            {{ $option }}
                        </option>
                    @endforeach
                </select>

                <div class="prod-bulk-views-wrap">
                    <select id="bulkEditViewSelect" class="prod-filter-select prod-bulk-view-select">
                        <option value="">Vista personalizada</option>
                        @foreach ($savedViews as $view)
                            <option value="{{ $view->id }}" data-columns="{{ json_encode($view->columns) }}" data-widths="{{ json_encode($view->widths) }}">
                                {{ $view->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="prod-bulk-columns-wrap">
                    <button type="button" class="prod-filter-select prod-bulk-columns-btn" id="bulkEditColumnsBtn">
                        Columnas
                    </button>
                    <div class="prod-bulk-columns-menu" id="bulkEditColumnsMenu">
                        @foreach ($columnGroups as $group => $cols)
                            <div class="prod-bulk-columns-group">
                                <p class="prod-bulk-columns-group-title">{{ $group }}</p>
                                @foreach ($cols as $col)
                                    <label class="prod-bulk-columns-item">
                                        <input type="checkbox" class="prod-bulk-col-toggle" value="{{ $col['key'] }}"
                                            @checked(in_array($col['key'], $defaultVisibleColumns, true))>
                                        {{ $col['label'] }}
                                    </label>
                                @endforeach
                            </div>
                        @endforeach

                        <div class="prod-bulk-view-actions">
                            <div class="prod-bulk-view-save-row">
                                <input type="text" id="bulkEditViewNameInput" class="pform-input prod-bulk-view-name-input"
                                    placeholder="Nombre de la nueva vista (máx. {{ $savedViews->count() >= 10 ? '10 alcanzado' : '10' }})"
                                    maxlength="60">
                                <button type="button" class="pform-btn primary" id="bulkEditViewSaveBtn">
                                    Guardar como vista nueva
                                </button>
                            </div>
                            <div class="prod-bulk-view-manage-row" id="bulkEditViewManageRow" style="display:none">
                                <button type="button" class="button-secondary size-adjustment" id="bulkEditViewUpdateBtn">
                                    Actualizar vista actual
                                </button>
                                <button type="button" class="button-secondary size-adjustment" id="bulkEditViewDeleteBtn">
                                    Eliminar vista actual
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="prod-content-area">
            <div class="products-table-wrapper prod-bulk-edit-table-wrapper">
                <table class="prod-bulk-edit-table">
                    <colgroup>
                        <col data-col="name">
                        @foreach ($bulkEditColumns as $col)
                            <col data-col="{{ $col['key'] }}">
                        @endforeach
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="prod-bulk-pinned-col" data-col="name">Nombre
                                <span class="prod-bulk-resize-handle" data-resize-col="name"></span>
                            </th>
                            @foreach ($bulkEditColumns as $col)
                                <th data-col="{{ $col['key'] }}">{{ $col['label'] }}
                                    <span class="prod-bulk-resize-handle" data-resize-col="{{ $col['key'] }}"></span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($brands as $brand)
                            <tr data-row-id="{{ $brand->id }}">
                                <td class="prod-bulk-pinned-col" data-col="name">
                                    <input type="text" class="prod-bulk-input" data-id="{{ $brand->id }}"
                                        data-field="name" value="{{ $brand->name }}" maxlength="120">
                                </td>

                                @foreach ($bulkEditColumns as $col)
                                    <td data-col="{{ $col['key'] }}"
                                        @class(['prod-bulk-checkbox-cell' => $col['type'] === 'checkbox'])>
                                        @switch($col['type'])
                                            @case('text')
                                                <input type="text" class="prod-bulk-input" data-id="{{ $brand->id }}"
                                                    data-field="{{ $col['key'] }}" value="{{ $brand->{$col['key']} }}"
                                                    @if (!empty($col['maxlength'])) maxlength="{{ $col['maxlength'] }}" @endif>
                                            @break

                                            @case('textarea')
                                                <textarea class="prod-bulk-input prod-bulk-textarea-cell" data-id="{{ $brand->id }}"
                                                    data-field="{{ $col['key'] }}"
                                                    @if (!empty($col['maxlength'])) maxlength="{{ $col['maxlength'] }}" @endif>{{ $brand->{$col['key']} }}</textarea>
                                            @break

                                            @case('checkbox')
                                                <input type="checkbox" class="prod-bulk-input" data-id="{{ $brand->id }}"
                                                    data-field="{{ $col['key'] }}" @checked($brand->{$col['key']})>
                                            @break

                                            @case('image-picker')
                                                {{-- Valor crudo (sin resolver por el accessor) para no
                                                     doble-resolver una ruta relativa al reabrir el picker. --}}
                                                <div class="img-picker-field">
                                                    <input type="url" class="prod-bulk-input" id="brandLogoInput{{ $brand->id }}"
                                                        data-id="{{ $brand->id }}" data-field="logo_url"
                                                        value="{{ $brand->getRawOriginal('logo_url') }}" maxlength="255">
                                                    <button type="button" class="img-picker-trigger-btn"
                                                        onclick="openImagePicker('brandLogoInput{{ $brand->id }}')">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                                        Seleccionar
                                                    </button>
                                                </div>
                                            @break
                                        @endswitch
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($bulkEditColumns) + 1 }}">
                                    <p class="prod-empty">No se encontraron marcas con los filtros actuales.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="prod-summary-bar">
                <span>Mostrando {{ $brands->count() }} de {{ $totalFiltered }} marcas</span>
            </div>

            @if ($brands instanceof \Illuminate\Pagination\LengthAwarePaginator)
                {{ $brands->links('admin.components.pagination') }}
            @endif
        </div>
    </div>

    <div id="prodBulkEditBar" class="prod-bulk-bar">
        <span class="prod-bulk-count"><span id="prodBulkEditCount">0</span> cambio(s) sin guardar</span>
        <button type="button" class="prod-bulk-btn" id="prodBulkEditDiscardBtn">Descartar cambios</button>
        <button type="button" class="prod-bulk-btn" id="prodBulkEditSaveBtn">Guardar cambios</button>
    </div>
@endsection
@include('admin.brands._bulk_edit_scripts')
