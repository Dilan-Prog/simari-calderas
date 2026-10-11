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
<div class="container user-manager">
<section class="clients-manager-section wh-page">
    <div class="erp-page-head">
        <div>
            <p class="breadcrumb-clients-manager" style="margin-bottom:4px;">
                Panel de Control &gt; <a href="{{ route('admin.stock-counts.index') }}">Conteos físicos</a> &gt; <strong>Nuevo</strong>
            </p>
            <h1 class="erp-title">Nuevo conteo físico</h1>
            <p class="erp-subtitle">Se toma una foto del inventario del sistema al crear el conteo.</p>
        </div>
    </div>

    @if (session('error')) <div class="wh-alert wh-alert-error">{{ session('error') }}</div> @endif
    @if ($errors->any()) <div class="wh-alert wh-alert-error">@foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach</div> @endif

    <form method="POST" action="{{ route('admin.stock-counts.store') }}" data-wh-count-form data-products-url="{{ route('admin.stock-counts.products') }}">
        @csrf
        <div class="wh-card">
            <div class="wh-form-grid">
                <div>
                    <label for="warehouse_id">Almacén</label>
                    <select name="warehouse_id" id="warehouse_id" class="users-manager-input" required>
                        <option value="">Selecciona...</option>
                        @foreach ($warehouses as $w) <option value="{{ $w->id }}" @selected((string) old('warehouse_id') === (string) $w->id)>{{ $w->name }}</option> @endforeach
                    </select>
                </div>
            </div>

            <div class="wh-radio-group">
                <label><input type="radio" name="mode" value="all" {{ old('mode', 'all') === 'all' ? 'checked' : '' }}> Todos los productos con saldo en el almacén</label>
                <label><input type="radio" name="mode" value="category" {{ old('mode') === 'category' ? 'checked' : '' }}> Una categoría</label>
                <label><input type="radio" name="mode" value="products" {{ old('mode') === 'products' ? 'checked' : '' }}> Productos seleccionados</label>
            </div>

            <div data-mode-category class="wh-field" style="display:none; max-width:360px;">
                <label for="category_id">Categoría</label>
                <select name="category_id" id="category_id" class="users-manager-input">
                    <option value="">Selecciona...</option>
                    @foreach ($categories as $c) <option value="{{ $c->id }}" @selected((string) old('category_id') === (string) $c->id)>{{ $c->name }}</option> @endforeach
                </select>
            </div>

            <div data-mode-products style="display:none;">
                <div class="wh-pill-list" data-pills></div>
                <div class="wh-search">
                    <input type="text" class="users-manager-input" data-product-search placeholder="Buscar producto por nombre o SKU..." autocomplete="off">
                    <div class="wh-search-results" data-product-results></div>
                </div>
            </div>

            <div class="wh-field">
                <label for="notes">Notas</label>
                <textarea name="notes" id="notes" rows="2" class="users-manager-input" maxlength="1000">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="wh-actions">
            <button type="submit" class="wh-btn wh-btn-primary">Crear conteo</button>
            <a href="{{ route('admin.stock-counts.index') }}" class="wh-btn">Cancelar</a>
        </div>
    </form>
</section>
</div>
@endsection
