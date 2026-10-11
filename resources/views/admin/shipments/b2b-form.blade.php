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
<div class="lg-page">
    <div class="lg-head">
        <div>
            <a href="{{ route('admin.shipments.index') }}" class="erp-objhead-back">← Envíos</a>
            <h1>{{ $mode === 'create' ? 'Nuevo envío B2B' : 'Editar envío ' . $shipment->guia }}</h1>
            <p>Envío a cliente ligado a un pedido B2B o a una cotización.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="lg-card" style="border-color:#fecaca;background:#fef2f2;">
            <strong>Revisa los campos marcados.</strong>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" class="lg-form">
        @csrf
        @if ($mode === 'edit') @method('PUT') @endif

        @if ($mode === 'create')
            <div class="lg-card">
                <h2>Documento de origen</h2>
                <label for="lg-source">Pedido B2B o cotización</label>
                <select id="lg-source" name="source" data-create-url="{{ route('admin.shipments.create') }}">
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
                @error('shippable_type') <div class="lg-err">Selecciona el pedido o cotización de origen.</div> @enderror
                @error('shippable_id') <div class="lg-err">Selecciona el pedido o cotización de origen.</div> @enderror
                <input type="hidden" name="shippable_type" value="{{ $v('shippable_type') }}">
                <input type="hidden" name="shippable_id" value="{{ $v('shippable_id') }}">
                <input type="hidden" name="customer_id" value="{{ $v('customer_id') }}">
                <input type="hidden" name="quote_id" value="{{ $v('quote_id') }}">
            </div>
        @endif

        <div class="lg-card">
            <h2>Guía y paquetería</h2>
            <div class="lg-form-grid">
                <div>
                    <label>Guía *</label>
                    <input type="text" name="guia" value="{{ $v('guia') }}" maxlength="80" required>
                    @error('guia') <div class="lg-err">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label>Paquetería</label>
                    <select name="carrier_id">
                        <option value="">Sin paquetería (entrega propia)</option>
                        @foreach ($carriers as $c)
                            <option value="{{ $c->id }}" @selected((string) $v('carrier_id') === (string) $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Número de pedido / referencia</label>
                    <input type="text" name="order_number_ref" value="{{ $v('order_number_ref') }}" maxlength="60">
                </div>
                <div>
                    <label>Fecha de envío *</label>
                    <input type="date" name="fecha_envio" value="{{ $dateVal('fecha_envio') }}" required>
                    @error('fecha_envio') <div class="lg-err">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label>Entrega estimada</label>
                    <input type="date" name="eta" value="{{ $dateVal('eta') }}">
                    @error('eta') <div class="lg-err">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label>Bultos *</label>
                    <input type="number" name="bultos" min="1" value="{{ old('bultos', $shipment->bultos ?? 1) }}" required>
                </div>
                <div>
                    <label>Peso (kg)</label>
                    <input type="number" step="0.01" min="0" name="peso" value="{{ $v('peso') }}">
                </div>
                <div>
                    <label>Costo</label>
                    <input type="number" step="0.01" min="0" name="costo" value="{{ $v('costo') }}">
                </div>
            </div>
        </div>

        <div class="lg-card">
            <h2>Destinatario</h2>
            <div class="lg-form-grid">
                <div>
                    <label>Persona que recibe *</label>
                    <input type="text" name="recipient_name" value="{{ $v('recipient_name') }}" maxlength="160" required>
                    @error('recipient_name') <div class="lg-err">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label>Empresa que recibe</label>
                    <input type="text" name="recipient_company" value="{{ $v('recipient_company') }}" maxlength="160">
                </div>
                <div>
                    <label>Teléfono</label>
                    <input type="text" name="recipient_phone" value="{{ $v('recipient_phone') }}" maxlength="40">
                </div>
                <div>
                    <label>Correo</label>
                    <input type="email" name="recipient_email" value="{{ $v('recipient_email') }}" maxlength="160">
                    @error('recipient_email') <div class="lg-err">{{ $message }}</div> @enderror
                </div>
                <div class="span-2">
                    <label>Dirección de salida</label>
                    <textarea name="origin_address" rows="2">{{ $v('origin_address') }}</textarea>
                </div>
                <div class="span-2">
                    <label>Dirección de entrega *</label>
                    <textarea name="delivery_address" rows="2" required>{{ $v('delivery_address') }}</textarea>
                    @error('delivery_address') <div class="lg-err">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="lg-actions">
            <button type="submit" class="lg-btn lg-btn-primary">{{ $mode === 'create' ? 'Registrar envío' : 'Guardar cambios' }}</button>
            <a href="{{ $mode === 'create' ? route('admin.shipments.index') : route('admin.shipments.show', $shipment) }}" class="lg-btn">Cancelar</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/admin/erp-logistics.js'])
@endpush
