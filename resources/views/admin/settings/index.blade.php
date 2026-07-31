@extends('admin.layouts.master')
@section('title')
    Configuración del Sitio - Admin
@endsection
@section('content')
    @php
        $labels = [
            'footer.address' => 'Dirección del footer',
            'footer.phone' => 'Teléfono del footer',
            'footer.email' => 'Correo del footer',
            'footer.facebook_url' => 'URL de Facebook (footer)',
            'ecommerce.iva_rate' => 'Tasa de IVA (%)',
        ];

        $groupTitles = [
            'footer' => 'Footer',
            'ecommerce' => 'Ecommerce',
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
