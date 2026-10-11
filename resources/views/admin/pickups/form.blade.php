@extends('admin.layouts.master')

@section('title', ($mode === 'create' ? 'Nueva recolección' : 'Editar ' . $pickup->number) . ' - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-logistics.css'])
@endpush

@section('content')
@php
    $action = $mode === 'create' ? route('admin.pickups.store') : route('admin.pickups.update', $pickup);
    $v = fn ($f) => old($f, $pickup->{$f});
    $items = old('items', $items);
@endphp
<div class="lg-page">
    <div class="lg-head">
        <div>
            <a href="{{ route('admin.pickups.index') }}" class="erp-objhead-back">← Recolecciones</a>
            <h1>{{ $mode === 'create' ? 'Nueva recolección' : 'Editar ' . $pickup->number }}</h1>
        </div>
    </div>

    <form method="POST" action="{{ $action }}" class="lg-form">
        @csrf
        @if ($mode === 'edit') @method('PUT') @endif

        <div class="lg-card">
            <h2>Datos generales</h2>
            <div class="lg-form-grid">
                <div>
                    <label>Tipo *</label>
                    <select name="type" required>
                        @foreach (\App\Support\PickupStatus::TYPES as $k => $label)
                            <option value="{{ $k }}" @selected($v('type') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Cliente</label>
                    <select name="customer_id">
                        <option value="">—</option>
                        @foreach ($customers as $c)
                            <option value="{{ $c->id }}" @selected((string) $v('customer_id') === (string) $c->id)>{{ trim($c->first_name . ' ' . $c->last_name) }}{{ $c->company ? ' · ' . $c->company : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Proveedor</label>
                    <select name="supplier_id">
                        <option value="">—</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}" @selected((string) $v('supplier_id') === (string) $s->id)>{{ $s->company_name ?: $s->contact_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Contacto</label>
                    <input type="text" name="contact_name" value="{{ $v('contact_name') }}" maxlength="160">
                </div>
                <div>
                    <label>Teléfono</label>
                    <input type="text" name="contact_phone" value="{{ $v('contact_phone') }}" maxlength="40">
                </div>
                <div class="span-2">
                    <label>Dirección de recolección *</label>
                    <textarea name="address" rows="2" required>{{ $v('address') }}</textarea>
                    @error('address') <div class="lg-err">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="lg-card">
            <h2>Programación</h2>
            <div class="lg-form-grid">
                <div>
                    <label>Fecha *</label>
                    <input type="date" name="scheduled_date" value="{{ old('scheduled_date', optional($pickup->scheduled_date)->format('Y-m-d')) }}" required>
                    @error('scheduled_date') <div class="lg-err">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label>Ventana horaria</label>
                    <input type="text" name="time_window" value="{{ $v('time_window') }}" placeholder="09:00 - 13:00" maxlength="60">
                </div>
                <div>
                    <label>Chofer</label>
                    <select name="driver_user_id">
                        <option value="">—</option>
                        @foreach ($drivers as $d)
                            <option value="{{ $d->id }}" @selected((string) $v('driver_user_id') === (string) $d->id)>{{ $d->first_name }} {{ $d->last_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Paquetería</label>
                    <select name="carrier_id">
                        <option value="">—</option>
                        @foreach ($carriers as $c)
                            <option value="{{ $c->id }}" @selected((string) $v('carrier_id') === (string) $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="full">
                    <label>Notas</label>
                    <textarea name="notes" rows="2">{{ $v('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="lg-card">
            <h2>Partidas por recolectar</h2>
            <table class="lg-items">
                <thead><tr><th style="text-align:left;">Descripción</th><th style="width:100px;text-align:left;">Cantidad</th><th style="width:110px;text-align:left;">Unidad</th><th style="width:30px;"></th></tr></thead>
                <tbody id="lg-items-body">
                @foreach ($items as $i => $it)
                    <tr>
                        <td>
                            <input type="text" data-field="description" name="items[{{ $i }}][description]" value="{{ $it['description'] ?? '' }}" maxlength="255">
                            <input type="hidden" data-field="sales_order_id" name="items[{{ $i }}][sales_order_id]" value="{{ $it['sales_order_id'] ?? '' }}">
                            <input type="hidden" data-field="product_id" name="items[{{ $i }}][product_id]" value="{{ $it['product_id'] ?? '' }}">
                        </td>
                        <td><input type="number" step="0.01" min="0" data-field="quantity" name="items[{{ $i }}][quantity]" value="{{ $it['quantity'] ?? 1 }}"></td>
                        <td><input type="text" data-field="unit" name="items[{{ $i }}][unit]" value="{{ $it['unit'] ?? '' }}" maxlength="30"></td>
                        <td><button type="button" class="lg-btn lg-remove-item" style="padding:4px 9px;">×</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <button type="button" id="lg-add-item" class="lg-btn" style="margin-top:8px;">+ Agregar partida</button>
        </div>

        <div class="lg-actions">
            <button type="submit" class="lg-btn lg-btn-primary">{{ $mode === 'create' ? 'Crear recolección' : 'Guardar cambios' }}</button>
            <a href="{{ $mode === 'create' ? route('admin.pickups.index') : route('admin.pickups.show', $pickup) }}" class="lg-btn">Cancelar</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/admin/erp-logistics.js'])
@endpush
