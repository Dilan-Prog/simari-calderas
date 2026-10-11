@extends('admin.layouts.master')

@section('title', ($mode === 'create' ? 'Nuevo envío B2B' : 'Editar envío') . ' - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-logistics.css'])
@endpush

@section('content')
@php
    $action = $mode === 'create' ? route('admin.shipments.store-b2b') : route('admin.shipments.update', $shipment);
    $v = fn ($f) => old($f, $shipment->{$f});
    $dateVal = fn ($f) => old($f, optional($shipment->{$f})->format('Y-m-d'));
@endphp
<div class="erp-page">
    <x-erp.page-header :title="$mode === 'create' ? 'Nuevo envío B2B' : 'Editar envío ' . $shipment->guia" code="LOG1"
        desc="Envío a cliente ligado a un pedido B2B o a una cotización."
        :crumbs="[['Logística', null], ['Envíos', route('admin.shipments.index')], [$mode === 'create' ? 'Nuevo envío B2B' : $shipment->guia, null]]" />

    @if ($errors->any())
        <div class="lg-alert" role="alert">Revisa los campos marcados.</div>
    @endif

    <form method="POST" action="{{ $action }}">
        @csrf
        @if ($mode === 'edit') @method('PUT') @endif

        @if ($mode === 'create')
            <x-erp.card title="Documento de origen" sub="Al elegirlo se precargan cliente, destinatario y dirección.">
                <label class="erp-field" style="max-width:420px;">Pedido B2B o cotización
                    <select id="lg-source" name="source" class="erp-select" data-create-url="{{ route('admin.shipments.create') }}">
                        <option value="">Selecciona…</option>
                        <optgroup label="Pedidos B2B">
                            @foreach (($orders ?? []) as $o)
                                <option value="sales_order:{{ $o->id }}" @selected($shipment->shippable_type === 'sales_order' && (int) $shipment->shippable_id === $o->id)>{{ $o->order_number }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Cotizaciones">
                            @foreach (($quotes ?? []) as $q)
                                <option value="quote:{{ $q->id }}" @selected($shipment->shippable_type === 'quote' && (int) $shipment->shippable_id === $q->id)>{{ $q->quote_number }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </label>
                @error('shippable_type') <div class="lg-err">Selecciona el pedido o cotización de origen.</div> @enderror
                @error('shippable_id') <div class="lg-err">Selecciona el pedido o cotización de origen.</div> @enderror
                <input type="hidden" name="shippable_type" value="{{ $v('shippable_type') }}">
                <input type="hidden" name="shippable_id" value="{{ $v('shippable_id') }}">
                <input type="hidden" name="customer_id" value="{{ $v('customer_id') }}">
                <input type="hidden" name="quote_id" value="{{ $v('quote_id') }}">
            </x-erp.card>
        @endif

        <x-erp.card title="Guía y paquetería">
            <div class="erp-form-grid">
                <label class="erp-field">Guía *
                    <input type="text" name="guia" class="erp-input" value="{{ $v('guia') }}" maxlength="80" required>
                    @error('guia') <span class="lg-err">{{ $message }}</span> @enderror
                </label>
                <label class="erp-field">Paquetería
                    <select name="carrier_id" class="erp-select">
                        <option value="">Sin paquetería (entrega propia)</option>
                        @foreach ($carriers as $c)
                            <option value="{{ $c->id }}" @selected((string) $v('carrier_id') === (string) $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="erp-field">Número de pedido / referencia
                    <input type="text" name="order_number_ref" class="erp-input" value="{{ $v('order_number_ref') }}" maxlength="60">
                </label>
                <label class="erp-field">Fecha de envío *
                    <input type="date" name="fecha_envio" class="erp-input" value="{{ $dateVal('fecha_envio') }}" required>
                    @error('fecha_envio') <span class="lg-err">{{ $message }}</span> @enderror
                </label>
                <label class="erp-field">Entrega estimada
                    <input type="date" name="eta" class="erp-input" value="{{ $dateVal('eta') }}">
                    @error('eta') <span class="lg-err">{{ $message }}</span> @enderror
                </label>
                <label class="erp-field">Bultos *
                    <input type="number" name="bultos" class="erp-input" min="1" value="{{ old('bultos', $shipment->bultos ?? 1) }}" required>
                </label>
                <label class="erp-field">Peso (kg)
                    <input type="number" step="0.01" min="0" name="peso" class="erp-input" value="{{ $v('peso') }}">
                </label>
                <label class="erp-field">Costo
                    <input type="number" step="0.01" min="0" name="costo" class="erp-input" value="{{ $v('costo') }}">
                </label>
            </div>
        </x-erp.card>

        <x-erp.card title="Destinatario">
            <div class="erp-form-grid">
                <label class="erp-field">Persona que recibe *
                    <input type="text" name="recipient_name" class="erp-input" value="{{ $v('recipient_name') }}" maxlength="160" required>
                    @error('recipient_name') <span class="lg-err">{{ $message }}</span> @enderror
                </label>
                <label class="erp-field">Empresa que recibe
                    <input type="text" name="recipient_company" class="erp-input" value="{{ $v('recipient_company') }}" maxlength="160">
                </label>
                <label class="erp-field">Teléfono
                    <input type="text" name="recipient_phone" class="erp-input" value="{{ $v('recipient_phone') }}" maxlength="40">
                </label>
                <label class="erp-field">Correo
                    <input type="email" name="recipient_email" class="erp-input" value="{{ $v('recipient_email') }}" maxlength="160">
                    @error('recipient_email') <span class="lg-err">{{ $message }}</span> @enderror
                </label>
                <label class="erp-field lg-span-2">Dirección de salida
                    <textarea name="origin_address" rows="2" class="erp-input">{{ $v('origin_address') }}</textarea>
                </label>
                <label class="erp-field lg-span-2">Dirección de entrega *
                    <textarea name="delivery_address" rows="2" class="erp-input" required>{{ $v('delivery_address') }}</textarea>
                    @error('delivery_address') <span class="lg-err">{{ $message }}</span> @enderror
                </label>
            </div>
        </x-erp.card>

        <div class="erp-form-actions">
            <a href="{{ $mode === 'create' ? route('admin.shipments.index') : route('admin.shipments.show', $shipment) }}" class="erp-btn">Cancelar</a>
            <button type="submit" class="erp-btn erp-btn--primary">{{ $mode === 'create' ? 'Registrar envío' : 'Guardar cambios' }}</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/admin/erp-logistics.js'])
@endpush
