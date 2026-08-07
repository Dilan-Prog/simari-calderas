@extends('admin.layouts.master')
@section('title')
    Editar en lote - Proveedores Productos - Admin
@endsection
@section('content')
    <div class="prod-page prod-bulk-edit-page">
        <div class="prod-page-header">
            <div class="prod-header-top">
                <div>
                    <h1 class="prod-title">
                        @if ($lockedSupplier)
                            Editar en lote — Productos de {{ $lockedSupplier->company_name }}
                        @else
                            Editar en lote — Proveedores Productos
                        @endif
                    </h1>
                    <p class="prod-subtitle">Edita SKU, costo, lead time y proveedor principal de varios vínculos
                        proveedor-producto a la vez</p>
                </div>
                <div class="prod-header-actions">
                    @if ($lockedSupplier)
                        <a href="{{ route('admin.suppliers.information', $lockedSupplier->id) }}" class="prod-btn-outline">
                            ← Volver a {{ $lockedSupplier->company_name }}
                        </a>
                    @else
                        <a href="{{ route('admin.suppliers.index') }}" class="prod-btn-outline">
                            ← Volver a Proveedores
                        </a>
                    @endif
                </div>
            </div>

            <form class="prod-toolbar" id="spBulkEditFilterForm" method="GET">
                <div class="prod-search-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.3-4.3" />
                    </svg>
                    <input type="text" name="search" id="spBulkEditSearch" class="prod-search-input"
                        placeholder="Buscar producto por nombre o SKU..." autocomplete="off"
                        value="{{ request('search') }}" />
                </div>
                @if ($lockedSupplier)
                    <input type="hidden" name="supplier_id" id="spBulkEditSupplierFilter" value="{{ $lockedSupplier->id }}">
                    <span class="prod-filter-select sp-locked-supplier-badge" title="Editor bloqueado a este proveedor">
                        {{ $lockedSupplier->company_name }}
                    </span>
                @else
                    <select name="supplier_id" id="spBulkEditSupplierFilter" class="prod-filter-select">
                        <option value="">Todos los proveedores</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>
                                {{ $supplier->company_name }}@if ($supplier->status !== 'active') ({{ $supplier->status }}) @endif
                            </option>
                        @endforeach
                    </select>
                @endif
                <select name="per_page" id="spBulkEditPerPage" class="prod-filter-select">
                    @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}" @selected((string) request('per_page', 25) === (string) $option)>
                            {{ $option }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="prod-content-area">
            <div class="products-table-wrapper prod-bulk-edit-table-wrapper">
                <table class="prod-bulk-edit-table">
                    <colgroup>
                        <col data-col="product">
                        <col data-col="supplier">
                        <col data-col="sku">
                        <col data-col="cost">
                        <col data-col="lead_time_days">
                        <col data-col="is_primary">
                    </colgroup>
                    <thead>
                        <tr>
                            <th data-col="product">Producto
                                <span class="prod-bulk-resize-handle" data-resize-col="product"></span>
                            </th>
                            <th data-col="supplier">Proveedor
                                <span class="prod-bulk-resize-handle" data-resize-col="supplier"></span>
                            </th>
                            <th data-col="sku">SKU Proveedor
                                <span class="prod-bulk-resize-handle" data-resize-col="sku"></span>
                            </th>
                            <th data-col="cost">Costo
                                <span class="prod-bulk-resize-handle" data-resize-col="cost"></span>
                            </th>
                            <th data-col="lead_time_days">Lead Time (días)
                                <span class="prod-bulk-resize-handle" data-resize-col="lead_time_days"></span>
                            </th>
                            <th data-col="is_primary">Es Principal
                                <span class="prod-bulk-resize-handle" data-resize-col="is_primary"></span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($links as $link)
                            <tr data-row-id="{{ $link->id }}">
                                <td class="prod-bulk-readonly" data-col="product">
                                    {{ $link->product->name ?? '—' }}
                                    <br><span style="color:#9ca3af">{{ $link->product->sku ?? '' }}</span>
                                </td>
                                <td class="prod-bulk-readonly" data-col="supplier">{{ $link->supplier->company_name ?? '—' }}</td>
                                <td data-col="sku">
                                    <input type="text" class="prod-bulk-input" data-id="{{ $link->id }}"
                                        data-field="sku" value="{{ $link->sku }}" maxlength="100">
                                </td>
                                <td data-col="cost">
                                    <input type="text" class="prod-bulk-input" data-id="{{ $link->id }}"
                                        data-field="cost" value="{{ $link->cost }}">
                                </td>
                                <td data-col="lead_time_days">
                                    <input type="number" min="0" class="prod-bulk-input" data-id="{{ $link->id }}"
                                        data-field="lead_time_days" value="{{ $link->lead_time_days }}">
                                </td>
                                <td class="prod-bulk-checkbox-cell" data-col="is_primary">
                                    <input type="checkbox" class="prod-bulk-input" data-id="{{ $link->id }}"
                                        data-field="is_primary" @checked($link->is_primary)>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <p class="prod-empty">No se encontraron vínculos proveedor-producto con los filtros
                                        actuales.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="prod-summary-bar">
                <span>Mostrando {{ $links->count() }} de {{ $totalFiltered }} vínculos</span>
            </div>

            @if ($links instanceof \Illuminate\Pagination\LengthAwarePaginator)
                {{ $links->links('admin.components.pagination') }}
            @endif
        </div>
    </div>

    <div id="spBulkEditBar" class="prod-bulk-bar">
        <span class="prod-bulk-count"><span id="spBulkEditCount">0</span> cambio(s) sin guardar</span>
        <button type="button" class="prod-bulk-btn" id="spBulkEditDiscardBtn">Descartar cambios</button>
        <button type="button" class="prod-bulk-btn" id="spBulkEditSaveBtn">Guardar cambios</button>
    </div>
@endsection
@include('admin.supplier.partials._bulk_edit_products_scripts')
