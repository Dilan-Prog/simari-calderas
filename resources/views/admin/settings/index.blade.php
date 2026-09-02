@extends('admin.layouts.master')
@section('title')
    Configuración del Sitio - Admin
@endsection
@section('content')
    @php
        $labels = [
            'footer.address_street' => 'Dirección — Calle y número',
            'footer.address_colony' => 'Dirección — Colonia',
            'footer.address_postal_code' => 'Dirección — Código Postal',
            'footer.address_city' => 'Dirección — Ciudad',
            'footer.address_state' => 'Dirección — Estado',
            'footer.phone' => 'WhatsApp de contacto (texto mostrado)',
            'footer.phone_link' => 'WhatsApp de contacto (solo dígitos, para el enlace wa.me — sin +, espacios ni guiones, ej. 5214494577320)',
            'footer.email' => 'Correo del footer',
            'footer.facebook_url' => 'URL de Facebook (footer)',
            'ecommerce.iva_rate' => 'Tasa de IVA (%)',
            'ecommerce.usd_to_mxn_rate' => 'Tipo de cambio USD → MXN (valor por defecto)',
            'ecommerce.cash_discount_percent' => 'Descuento por pago de contado (%)',
            'pool_calculator.tarifa_kwh' => 'Tarifa eléctrica (MXN/kWh)',
            'pool_calculator.cop_nominal' => 'COP nominal (ficha técnica a 27°C)',
            'pool_calculator.horas_operacion_dia' => 'Horas de operación al día',
            'pool_calculator.ciudades_temp_ambiente' => 'Temperaturas ambiente de diseño por ciudad (JSON)',
        ];

        $groupTitles = [
            'footer' => 'Footer',
            'ecommerce' => 'Ecommerce',
            'pool_calculator' => 'Calculadora de Bombas de Calor',
        ];
    @endphp

    <div class="container user-manager">
        <section class="clients-manager-section">

            {{-- Header --}}
            <header class="clients-manager-main" style="margin-bottom:4px;">
                <div>
                    <p class="breadcrumb-clients-manager main" style="margin-bottom:4px;">
                        Panel de Control &gt; Configuración del Sitio
                    </p>
                    <h1>Configuración del Sitio</h1>
                    <p class="breadcrumb-clients-manager main">Administra la información de contacto y otros valores
                        generales del sitio</p>
                </div>
            </header>

            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                @method('PUT')

                <div class="pform-panel-wrap" style="margin-top:20px;">
                    @forelse ($groups as $groupName => $settings)
                        <div class="pform-panel">
                            <h2 class="pform-panel-title">
                                {{ $groupTitles[$groupName] ?? ucfirst(str_replace(['_', '-'], ' ', $groupName ?? 'General')) }}
                            </h2>

                            @foreach ($settings as $setting)
                                @php
                                    $fieldName = "values[{$setting->key}]";
                                    $fieldId = 'setting_' . str_replace('.', '_', $setting->key);
                                    $label =
                                        $labels[$setting->key] ??
                                        ucfirst(str_replace(['_', '-'], ' ', last(explode('.', $setting->key))));
                                @endphp

                                <div class="pform-field">
                                    @if ($setting->type === 'boolean')
                                        <label class="pform-label" for="{{ $fieldId }}">{{ $label }}</label>
                                        <input type="hidden" name="{{ $fieldName }}" value="0">
                                        <input type="checkbox" id="{{ $fieldId }}" name="{{ $fieldName }}" value="1"
                                            {{ $setting->value ? 'checked' : '' }}>
                                    @elseif ($setting->type === 'json')
                                        <label class="pform-label" for="{{ $fieldId }}">{{ $label }}</label>
                                        <textarea id="{{ $fieldId }}" name="{{ $fieldName }}" class="pform-textarea" rows="6">{{ json_encode(json_decode($setting->value ?? '', true) ?? [], JSON_PRETTY_PRINT) }}</textarea>
                                        <p class="pform-hint">Clave: <code>{{ $setting->key }}</code></p>
                                    @else
                                        <label class="pform-label" for="{{ $fieldId }}">{{ $label }}</label>
                                        <input type="text" id="{{ $fieldId }}" name="{{ $fieldName }}" class="pform-input"
                                            value="{{ $setting->value }}">
                                        <p class="pform-hint">Clave: <code>{{ $setting->key }}</code></p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <div class="pform-panel">
                            <p class="pform-hint">No hay configuraciones registradas todavía.</p>
                        </div>
                    @endforelse

                    @if ($groups->isNotEmpty())
                        <button type="submit" class="pform-btn primary">Guardar configuración</button>
                    @endif
                </div>
            </form>
        </section>
    </div>
@endsection
