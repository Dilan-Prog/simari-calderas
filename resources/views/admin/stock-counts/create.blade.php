@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-warehouse.css'])
@endpush
@push('scripts')
    @vite(['resources/js/admin/erp-warehouse.js'])
@endpush
@section('title')
    Nuevo conteo físico - Admin
@endsection
@section('content')
<div class="erp-page">
    <x-erp.page-header title="Nuevo conteo físico" code="ALM2"
        desc="Se toma una foto del inventario del sistema al crear el conteo."
        :crumbs="[['Almacén', null], ['Conteo físico', route('admin.stock-counts.index')], ['Nuevo', null]]" />

    @if (session('error')) <div class="wh-alert wh-alert-error" role="alert">{{ session('error') }}</div> @endif
    @if ($errors->any()) <div class="wh-alert wh-alert-error" role="alert">@foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach</div> @endif

    <form method="POST" action="{{ route('admin.stock-counts.store') }}" data-wh-count-form data-products-url="{{ route('admin.stock-counts.products') }}">
        @csrf
        <x-erp.card title="Alcance del conteo">
            <div class="erp-field wh-max-360">
                <label for="warehouse_id">Almacén</label>
                <select name="warehouse_id" id="warehouse_id" class="erp-select" required>
                    <option value="">Selecciona...</option>
                    @foreach ($warehouses as $w) <option value="{{ $w->id }}" @selected((string) old('warehouse_id') === (string) $w->id)>{{ $w->name }}</option> @endforeach
                </select>
            </div>

            <div class="wh-radio-group" role="radiogroup" aria-label="Qué productos contar">
                <label><input type="radio" name="mode" value="all" {{ old('mode', 'all') === 'all' ? 'checked' : '' }}> Todos los productos con saldo en el almacén</label>
                <label><input type="radio" name="mode" value="category" {{ old('mode') === 'category' ? 'checked' : '' }}> Una categoría</label>
                <label><input type="radio" name="mode" value="products" {{ old('mode') === 'products' ? 'checked' : '' }}> Productos seleccionados</label>
            </div>

            <div data-mode-category class="erp-field wh-max-360" style="display:none;">
                <label for="category_id">Categoría</label>
                <select name="category_id" id="category_id" class="erp-select">
                    <option value="">Selecciona...</option>
                    @foreach ($categories as $c) <option value="{{ $c->id }}" @selected((string) old('category_id') === (string) $c->id)>{{ $c->name }}</option> @endforeach
                </select>
            </div>

            <div data-mode-products style="display:none;">
                <div class="wh-pill-list" data-pills></div>
                <div class="wh-search">
                    <input type="search" class="erp-input" data-product-search placeholder="Buscar producto por nombre o SKU..." autocomplete="off" aria-label="Buscar producto">
                    <div class="wh-search-results" data-product-results></div>
                </div>
            </div>

            <div class="erp-field" style="margin-top:14px;">
                <label for="notes">Notas</label>
                <textarea name="notes" id="notes" rows="2" class="erp-input" maxlength="1000">{{ old('notes') }}</textarea>
            </div>
        </x-erp.card>

        <div class="erp-form-actions">
            <a href="{{ route('admin.stock-counts.index') }}" class="erp-btn">Cancelar</a>
            <button type="submit" class="erp-btn erp-btn--primary">Crear conteo</button>
        </div>
    </form>
</div>
@endsection
