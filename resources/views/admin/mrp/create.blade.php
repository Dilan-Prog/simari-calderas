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
    <div class="erp-page-head">
        <div>
            <a href="{{ route('admin.mrp.index') }}" class="erp-objhead-back">← Volver</a>
            <h1 class="erp-title">Nuevo requerimiento de materiales</h1>
            <p class="erp-subtitle">Elige de dónde viene la demanda. Se explotarán las listas de materiales y se compararán contra existencias y reservas.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="mrp-alert"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('admin.mrp.store') }}" class="mrp-card mrp-pad">
        @csrf
        <h2 class="mrp-h2">1. Origen de la demanda</h2>
        <div class="mrp-origin-tabs" role="radiogroup">
            @foreach (['sales_order' => 'Pedido', 'project' => 'Proyecto', 'technical_service' => 'Servicio técnico', 'manual' => 'Manual'] as $k => $l)
                @continue($k === 'project' && $projects->isEmpty())
                <label class="mrp-origin-opt">
                    <input type="radio" name="origin" value="{{ $k }}" @checked($originSel === $k) data-origin-radio>
                    <span>{{ $l }}</span>
                </label>
            @endforeach
        </div>

        <div class="mrp-grid">
            <div class="mrp-field mrp-wide" data-origin-pane="sales_order">
                <label>Pedido</label>
                <select name="origin_id" class="mrp-input" data-origin-select="sales_order">
                    <option value="">Selecciona un pedido…</option>
                    @foreach ($orders as $o)
                        <option value="{{ $o->id }}" @selected($originSel === 'sales_order' && $originIdSel === $o->id)>
                            {{ $o->order_number }} · {{ $o->customer ? trim($o->customer->first_name . ' ' . $o->customer->last_name) : 'Sin cliente' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="mrp-field mrp-wide" data-origin-pane="project" hidden>
                <label>Proyecto</label>
                <select name="origin_id" class="mrp-input" data-origin-select="project" disabled>
                    <option value="">Selecciona un proyecto…</option>
                    @foreach ($projects as $p)
                        <option value="{{ $p->id }}" @selected($originSel === 'project' && $originIdSel === $p->id)>{{ $p->project_number }} · {{ $p->name }}</option>
                    @endforeach
                </select>
                <small class="mrp-muted">Suma los pedidos del proyecto (explotando sus recetas) y los materiales planeados de sus servicios.</small>
            </div>
            <div class="mrp-field mrp-wide" data-origin-pane="technical_service" hidden>
                <label>Servicio técnico</label>
                <select name="origin_id" class="mrp-input" data-origin-select="technical_service" disabled>
                    <option value="">Selecciona un servicio…</option>
                    @foreach ($services as $s)
                        <option value="{{ $s->id }}" @selected($originSel === 'technical_service' && $originIdSel === $s->id)>{{ $s->service_number }}</option>
                    @endforeach
                </select>
                <small class="mrp-muted">Usa los materiales planeados del servicio.</small>
            </div>
        </div>

        <div class="mrp-pane" data-origin-pane="manual" hidden>
            <h2 class="mrp-h2">Materiales</h2>
            <div class="mrp-lines-head"><span>Producto</span><span>Cantidad</span><span>Unidad</span><span></span></div>
            <div data-manual-lines></div>
            <button type="button" class="mrp-btn" data-add-line>+ Agregar material</button>
        </div>

        <h2 class="mrp-h2">2. Opciones</h2>
        <div class="mrp-grid">
            <div class="mrp-field">
                <label>Requerido para (fecha)</label>
                <input type="date" name="needed_by_date" value="{{ old('needed_by_date') }}" class="mrp-input">
            </div>
            <div class="mrp-field">
                <label>Almacén para existencias</label>
                <select name="warehouse_id" class="mrp-input">
                    <option value="">Existencia total del producto</option>
                    @foreach ($warehouses as $w)
                        <option value="{{ $w->id }}" @selected((int) old('warehouse_id') === $w->id)>{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mrp-field mrp-wide">
                <label class="mrp-check"><input type="checkbox" name="include_chemicals" value="1" @checked(old('include_chemicals'))> Incluir químicos proyectados del cliente (Planeación de químicos)</label>
            </div>
            <div class="mrp-field mrp-wide">
                <label>Notas</label>
                <textarea name="notes" rows="2" class="mrp-input">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="mrp-actions">
            <a href="{{ route('admin.mrp.index') }}" class="mrp-btn">Cancelar</a>
            <button type="submit" class="mrp-btn mrp-btn-primary">Calcular materiales</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/admin/erp-mrp.js'])
@endpush
