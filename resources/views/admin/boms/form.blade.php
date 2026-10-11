@extends('admin.layouts.master')

@section('title', ($isEdit ? 'Editar receta' : 'Nueva receta') . ' - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-boms.css'])
@endpush

@php
    $initialLines = old('lines')
        ? collect(old('lines'))->map(fn ($l) => [
            'component_product_id' => $l['component_product_id'] ?? '',
            'name' => $l['component_name'] ?? '',
            'quantity' => $l['quantity'] ?? 1,
            'unit' => $l['unit'] ?? '',
            'scrap_percent' => $l['scrap_percent'] ?? 0,
            'notes' => $l['notes'] ?? '',
        ])->values()
        : $lines->map(fn ($l) => [
            'component_product_id' => $l->component_product_id,
            'name' => ($l->component?->name ?? '') . ($l->component?->sku ? ' (' . $l->component->sku . ')' : ''),
            'quantity' => rtrim(rtrim(number_format((float) $l->quantity, 4, '.', ''), '0'), '.'),
            'unit' => $l->unit,
            'scrap_percent' => (float) $l->scrap_percent,
            'notes' => $l->notes,
        ])->values();
    $action = $isEdit ? route('admin.boms.update', $bom) : route('admin.boms.store');
    $productId = old('product_id', $product?->id);
@endphp

@section('content')
<div class="erp-page bom-page">
    <div class="erp-page-head">
        <div>
            <a href="{{ $isEdit ? route('admin.boms.show', $bom) : route('admin.boms.index') }}" class="erp-objhead-back">← Volver</a>
            <h1 class="erp-title">{{ $isEdit ? 'Editar receta v' . $bom->version : 'Nueva receta' }}</h1>
        </div>
    </div>

    @if ($errors->any())
        <div class="bom-alert">
            <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" data-bom-form
          data-search-url="{{ route('admin.boms.search-products') }}"
          data-lines='@json($initialLines)'>
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="bom-card bom-pad">
            <div class="bom-grid">
                <div class="bom-field bom-field-wide">
                    <label>Producto fabricado</label>
                    @if ($isEdit)
                        <div class="bom-static">{{ $product->name }} <span class="bom-muted">{{ $product->sku }}</span></div>
                    @else
                        <div class="bom-picker" data-product-picker>
                            <input type="hidden" name="product_id" value="{{ $productId }}" data-picker-id>
                            <input type="text" class="bom-input" autocomplete="off" placeholder="Busca por nombre o SKU…"
                                   value="{{ $product ? $product->name . ($product->sku ? ' (' . $product->sku . ')' : '') : '' }}" data-picker-input>
                            <div class="bom-picker-list" hidden></div>
                        </div>
                    @endif
                </div>
                <div class="bom-field">
                    <label>Nombre de la versión (opcional)</label>
                    <input type="text" name="name" class="bom-input" maxlength="255" value="{{ old('name', $bom->name) }}" placeholder="Ej. Con quemador importado">
                </div>
                <div class="bom-field">
                    <label>Cantidad que produce la receta</label>
                    <input type="number" name="yield_quantity" class="bom-input" step="0.0001" min="0.0001" value="{{ old('yield_quantity', rtrim(rtrim(number_format((float) $bom->yield_quantity, 4, '.', ''), '0'), '.') ?: 1) }}">
                </div>
                <div class="bom-field bom-check">
                    <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $bom->is_active))> Marcar como receta vigente</label>
                    <small class="bom-muted">Al activarla se desactivan las demás versiones del producto.</small>
                </div>
                <div class="bom-field bom-field-wide">
                    <label>Notas</label>
                    <textarea name="notes" rows="2" class="bom-input" maxlength="5000">{{ old('notes', $bom->notes) }}</textarea>
                </div>
            </div>
        </div>

        <div class="bom-card bom-pad">
            <div class="bom-section-head">
                <h2>Componentes</h2>
                <button type="button" class="bom-btn" data-add-line>+ Agregar componente</button>
            </div>
            <div class="bom-lines-head">
                <span>Componente</span><span>Cantidad</span><span>Unidad</span><span>Merma %</span><span>Notas</span><span></span>
            </div>
            <div data-lines></div>
            <p class="bom-muted" data-lines-empty>Sin componentes. Usa «Agregar componente».</p>
        </div>

        <div class="bom-actions">
            <a href="{{ $isEdit ? route('admin.boms.show', $bom) : route('admin.boms.index') }}" class="bom-btn">Cancelar</a>
            <button type="submit" class="bom-btn bom-btn-primary">{{ $isEdit ? 'Guardar cambios' : 'Crear receta' }}</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/admin/erp-boms.js'])
@endpush
