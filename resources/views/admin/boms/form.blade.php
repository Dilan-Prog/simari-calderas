@extends('admin.layouts.master')

@section('title', ($isEdit ? 'Editar lista de materiales' : 'Nueva lista de materiales') . ' - Admin')

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
    $backUrl = $isEdit ? route('admin.boms.show', $bom) : route('admin.boms.index');
@endphp

@section('content')
<div class="erp-page bom-page">
    <x-erp.page-header :title="$isEdit ? 'Editar lista de materiales v' . $bom->version : 'Nueva lista de materiales'" code="PLA3"
        :crumbs="[['Planeación', null], ['Listas de materiales', route('admin.boms.index')], [$isEdit ? 'Editar v' . $bom->version : 'Nueva', null]]">
        <a href="{{ $backUrl }}" class="erp-btn">← Volver</a>
    </x-erp.page-header>

    @if ($errors->any())
        <div class="bom-alert" role="alert">
            <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" data-bom-form
          data-search-url="{{ route('admin.boms.search-products') }}"
          data-lines='@json($initialLines)'>
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <x-erp.card title="Datos generales">
            <div class="erp-form-grid">
                <div class="erp-field bom-field-wide">
                    <label for="bom-product">Producto fabricado</label>
                    @if ($isEdit)
                        <div class="bom-static">{{ $product->name }} <span class="bom-muted">{{ $product->sku }}</span></div>
                    @else
                        <div class="bom-picker" data-product-picker>
                            <input type="hidden" name="product_id" value="{{ $productId }}" data-picker-id>
                            <input type="text" id="bom-product" class="erp-input" autocomplete="off" placeholder="Busca por nombre o SKU…"
                                   value="{{ $product ? $product->name . ($product->sku ? ' (' . $product->sku . ')' : '') : '' }}" data-picker-input>
                            <div class="bom-picker-list" hidden></div>
                        </div>
                    @endif
                </div>
                <div class="erp-field">
                    <label for="bom-name">Nombre de la versión (opcional)</label>
                    <input type="text" id="bom-name" name="name" class="erp-input" maxlength="255" value="{{ old('name', $bom->name) }}" placeholder="Ej. Con quemador importado">
                </div>
                <div class="erp-field">
                    <label for="bom-yield">Cantidad que produce la receta</label>
                    <input type="number" id="bom-yield" name="yield_quantity" class="erp-input" step="0.0001" min="0.0001" value="{{ old('yield_quantity', rtrim(rtrim(number_format((float) $bom->yield_quantity, 4, '.', ''), '0'), '.') ?: 1) }}">
                </div>
                <div class="erp-field bom-field-wide">
                    <label class="bom-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $bom->is_active))> Marcar como receta vigente</label>
                    <small>Al activarla se desactivan las demás versiones del producto.</small>
                </div>
                <div class="erp-field bom-field-wide">
                    <label for="bom-notes">Notas</label>
                    <textarea id="bom-notes" name="notes" rows="2" class="erp-input" maxlength="5000">{{ old('notes', $bom->notes) }}</textarea>
                </div>
            </div>
        </x-erp.card>

        <x-erp.card title="Componentes" sub="Los subensambles se explotan con su propia receta vigente.">
            <x-slot:actions>
                <button type="button" class="erp-btn erp-btn--sm" data-add-line>+ Agregar componente</button>
            </x-slot:actions>
            <div class="bom-lines-head">
                <span>Componente</span><span>Cantidad</span><span>Unidad</span><span>Merma %</span><span>Notas</span><span></span>
            </div>
            <div data-lines></div>
            <p class="bom-muted" data-lines-empty>Sin componentes. Usa «Agregar componente».</p>
        </x-erp.card>

        <div class="erp-form-actions" style="border-top: 0;">
            <a href="{{ $backUrl }}" class="erp-btn">Cancelar</a>
            <button type="submit" class="erp-btn erp-btn--primary">{{ $isEdit ? 'Guardar cambios' : 'Crear lista de materiales' }}</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/admin/erp-boms.js'])
@endpush
