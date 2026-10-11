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
<div class="erp-page">
    <x-erp.page-header :title="$mode === 'create' ? 'Nueva recolección' : 'Editar ' . $pickup->number" code="LOG2"
        :crumbs="[['Logística', null], ['Recolecciones', route('admin.pickups.index')], [$mode === 'create' ? 'Nueva' : $pickup->number, null]]" />

    @if ($errors->any())
        <div class="lg-alert" role="alert">Revisa los campos marcados.</div>
    @endif

    <form method="POST" action="{{ $action }}">
        @csrf
        @if ($mode === 'edit') @method('PUT') @endif

        <x-erp.card title="Datos generales">
            <div class="erp-form-grid">
                <label class="erp-field">Tipo *
                    <select name="type" class="erp-select" required>
                        @foreach (\App\Support\PickupStatus::TYPES as $k => $label)
                            <option value="{{ $k }}" @selected($v('type') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="erp-field">Cliente
                    <select name="customer_id" class="erp-select">
                        <option value="">—</option>
                        @foreach ($customers as $c)
                            <option value="{{ $c->id }}" @selected((string) $v('customer_id') === (string) $c->id)>{{ trim($c->first_name . ' ' . $c->last_name) }}{{ $c->company ? ' · ' . $c->company : '' }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="erp-field">Proveedor
                    <select name="supplier_id" class="erp-select">
                        <option value="">—</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}" @selected((string) $v('supplier_id') === (string) $s->id)>{{ $s->company_name ?: $s->contact_name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="erp-field">Contacto
                    <input type="text" name="contact_name" class="erp-input" value="{{ $v('contact_name') }}" maxlength="160">
                </label>
                <label class="erp-field">Teléfono
                    <input type="text" name="contact_phone" class="erp-input" value="{{ $v('contact_phone') }}" maxlength="40">
                </label>
                <label class="erp-field lg-span-2">Dirección de recolección *
                    <textarea name="address" rows="2" class="erp-input" required>{{ $v('address') }}</textarea>
                    @error('address') <span class="lg-err">{{ $message }}</span> @enderror
                </label>
            </div>
        </x-erp.card>

        <x-erp.card title="Programación">
            <div class="erp-form-grid">
                <label class="erp-field">Fecha *
                    <input type="date" name="scheduled_date" class="erp-input" value="{{ old('scheduled_date', optional($pickup->scheduled_date)->format('Y-m-d')) }}" required>
                    @error('scheduled_date') <span class="lg-err">{{ $message }}</span> @enderror
                </label>
                <label class="erp-field">Ventana horaria
                    <input type="text" name="time_window" class="erp-input" value="{{ $v('time_window') }}" placeholder="09:00 - 13:00" maxlength="60">
                </label>
                <label class="erp-field">Chofer
                    <select name="driver_user_id" class="erp-select">
                        <option value="">—</option>
                        @foreach ($drivers as $d)
                            <option value="{{ $d->id }}" @selected((string) $v('driver_user_id') === (string) $d->id)>{{ $d->first_name }} {{ $d->last_name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="erp-field">Paquetería
                    <select name="carrier_id" class="erp-select">
                        <option value="">—</option>
                        @foreach ($carriers as $c)
                            <option value="{{ $c->id }}" @selected((string) $v('carrier_id') === (string) $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="erp-field lg-full">Notas
                    <textarea name="notes" rows="2" class="erp-input">{{ $v('notes') }}</textarea>
                </label>
            </div>
        </x-erp.card>

        <x-erp.card title="Partidas por recolectar">
            <div class="erp-table-wrap">
                <table class="lg-items">
                    <thead><tr><th>Descripción</th><th style="width:110px;">Cantidad</th><th style="width:120px;">Unidad</th><th style="width:44px;"></th></tr></thead>
                    <tbody id="lg-items-body">
                    @foreach ($items as $i => $it)
                        <tr>
                            <td>
                                <input type="text" class="erp-input" data-field="description" name="items[{{ $i }}][description]" value="{{ $it['description'] ?? '' }}" maxlength="255" aria-label="Descripción">
                                <input type="hidden" data-field="sales_order_id" name="items[{{ $i }}][sales_order_id]" value="{{ $it['sales_order_id'] ?? '' }}">
                                <input type="hidden" data-field="product_id" name="items[{{ $i }}][product_id]" value="{{ $it['product_id'] ?? '' }}">
                            </td>
                            <td><input type="number" class="erp-input" step="0.01" min="0" data-field="quantity" name="items[{{ $i }}][quantity]" value="{{ $it['quantity'] ?? 1 }}" aria-label="Cantidad"></td>
                            <td><input type="text" class="erp-input" data-field="unit" name="items[{{ $i }}][unit]" value="{{ $it['unit'] ?? '' }}" maxlength="30" aria-label="Unidad"></td>
                            <td><button type="button" class="erp-btn erp-btn--sm lg-remove-item" aria-label="Quitar partida">×</button></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <button type="button" id="lg-add-item" class="erp-btn erp-btn--sm" style="margin-top:8px;">+ Agregar partida</button>
        </x-erp.card>

        <div class="erp-form-actions">
            <a href="{{ $mode === 'create' ? route('admin.pickups.index') : route('admin.pickups.show', $pickup) }}" class="erp-btn">Cancelar</a>
            <button type="submit" class="erp-btn erp-btn--primary">{{ $mode === 'create' ? 'Crear recolección' : 'Guardar cambios' }}</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/admin/erp-logistics.js'])
@endpush
