@extends('admin.layouts.master')

@section('title', 'Nuevo requerimiento - MRP - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-mrp.css'])
@endpush

@php
    $originSel = old('origin', $origin);
    $originIdSel = (int) old('origin_id', $originId);
@endphp

@section('content')
<div class="erp-page mrp-page" data-mrp-create data-search-url="{{ route('admin.mrp.search-products') }}">
    <x-erp.page-header title="Nuevo requerimiento de materiales" code="MRP1"
        desc="Elige de dónde viene la demanda. Se explotarán las listas de materiales y se compararán contra existencias y reservas."
        :crumbs="[['MRP', null], ['Requerimientos de material', route('admin.mrp.index')], ['Nuevo', null]]">
        <a href="{{ route('admin.mrp.index') }}" class="erp-btn">← Volver</a>
    </x-erp.page-header>

    @if ($errors->any())
        <div class="mrp-alert" role="alert"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('admin.mrp.store') }}">
        @csrf
        <x-erp.card title="1. Origen de la demanda">
            <div class="mrp-origin-tabs" role="radiogroup" aria-label="Origen de la demanda">
                @foreach (['sales_order' => 'Pedido', 'project' => 'Proyecto', 'technical_service' => 'Servicio técnico', 'manual' => 'Manual'] as $k => $l)
                    @continue($k === 'project' && $projects->isEmpty())
                    <label class="mrp-origin-opt">
                        <input type="radio" name="origin" value="{{ $k }}" @checked($originSel === $k) data-origin-radio>
                        <span>{{ $l }}</span>
                    </label>
                @endforeach
            </div>

            <div class="erp-form-grid">
                <div class="erp-field mrp-field-wide" data-origin-pane="sales_order">
                    <label for="mrp-origin-order">Pedido</label>
                    <select id="mrp-origin-order" name="origin_id" class="erp-select" data-origin-select="sales_order">
                        <option value="">Selecciona un pedido…</option>
                        @foreach ($orders as $o)
                            <option value="{{ $o->id }}" @selected($originSel === 'sales_order' && $originIdSel === $o->id)>
                                {{ $o->order_number }} · {{ $o->customer ? trim($o->customer->first_name . ' ' . $o->customer->last_name) : 'Sin cliente' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="erp-field mrp-field-wide" data-origin-pane="project" hidden>
                    <label for="mrp-origin-project">Proyecto</label>
                    <select id="mrp-origin-project" name="origin_id" class="erp-select" data-origin-select="project" disabled>
                        <option value="">Selecciona un proyecto…</option>
                        @foreach ($projects as $p)
                            <option value="{{ $p->id }}" @selected($originSel === 'project' && $originIdSel === $p->id)>{{ $p->project_number }} · {{ $p->name }}</option>
                        @endforeach
                    </select>
                    <small>Suma los pedidos del proyecto (explotando sus recetas) y los materiales planeados de sus servicios.</small>
                </div>
                <div class="erp-field mrp-field-wide" data-origin-pane="technical_service" hidden>
                    <label for="mrp-origin-service">Servicio técnico</label>
                    <select id="mrp-origin-service" name="origin_id" class="erp-select" data-origin-select="technical_service" disabled>
                        <option value="">Selecciona un servicio…</option>
                        @foreach ($services as $s)
                            <option value="{{ $s->id }}" @selected($originSel === 'technical_service' && $originIdSel === $s->id)>{{ $s->service_number }}</option>
                        @endforeach
                    </select>
                    <small>Usa los materiales planeados del servicio.</small>
                </div>
            </div>

            <div class="mrp-pane" data-origin-pane="manual" hidden>
                <h3 class="erp-card-title" style="margin-bottom: 10px;">Materiales</h3>
                <div class="mrp-lines-head"><span>Producto</span><span>Cantidad</span><span>Unidad</span><span></span></div>
                <div data-manual-lines></div>
                <button type="button" class="erp-btn erp-btn--sm" data-add-line style="margin-top: 8px;">+ Agregar material</button>
            </div>
        </x-erp.card>

        <x-erp.card title="2. Opciones">
            <div class="erp-form-grid">
                <div class="erp-field">
                    <label for="mrp-needed">Requerido para (fecha)</label>
                    <input type="date" id="mrp-needed" name="needed_by_date" value="{{ old('needed_by_date') }}" class="erp-input">
                </div>
                <div class="erp-field">
                    <label for="mrp-wh">Almacén para existencias</label>
                    <select id="mrp-wh" name="warehouse_id" class="erp-select">
                        <option value="">Existencia total del producto</option>
                        @foreach ($warehouses as $w)
                            <option value="{{ $w->id }}" @selected((int) old('warehouse_id') === $w->id)>{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="erp-field mrp-field-wide">
                    <label class="mrp-check"><input type="checkbox" name="include_chemicals" value="1" @checked(old('include_chemicals'))> Incluir químicos proyectados del cliente (Planeación de químicos)</label>
                </div>
                <div class="erp-field mrp-field-wide">
                    <label for="mrp-notes">Notas</label>
                    <textarea id="mrp-notes" name="notes" rows="2" class="erp-input">{{ old('notes') }}</textarea>
                </div>
            </div>
        </x-erp.card>

        <div class="erp-form-actions" style="border-top: 0;">
            <a href="{{ route('admin.mrp.index') }}" class="erp-btn">Cancelar</a>
            <button type="submit" class="erp-btn erp-btn--primary">Calcular materiales</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/admin/erp-mrp.js'])
@endpush
