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

        // Etiquetas, rangos y tipos de las claves catalog.* vienen del
        // controller (SettingController::CATALOG_FIELDS) para que la vista y
        // la validación nunca se desincronicen.
        $catalogFields = $catalogFields ?? \App\Http\Controllers\Backend\SettingController::CATALOG_FIELDS;
        foreach ($catalogFields as $catalogKey => $catalogSpec) {
            $labels[$catalogKey] = $catalogSpec['label'];
        }
        $oldValues = old('values', []);
        $oldValues = is_array($oldValues) ? $oldValues : [];

        $groupTitles = [
            'catalog' => 'Catálogo y etiquetas',
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

                            @if ($groupName === 'catalog')
                                @php
                                    $catalogByKey = $settings->keyBy('key');
                                    $catalogLayout = [
                                        [
                                            'id' => 'best_seller',
                                            'title' => 'Etiqueta «Más vendido»',
                                            'preview' => 'Más vendido',
                                            'help' => 'Se asigna a los productos que más venden dentro de su categoría directa: el % superior de la categoría según las unidades pagadas en la ventana de días. Solo aparece si hay ventas reales: se requieren las unidades mínimas por producto y el mínimo de productos con ventas en esa categoría; mientras haya pocas ventas casi no se mostrará, y es a propósito.',
                                            'keys' => ['badge_enabled_best_seller', 'badge_color_best_seller', 'best_seller_top_percent', 'best_seller_days', 'best_seller_min_units', 'best_seller_min_products'],
                                        ],
                                        [
                                            'id' => 'discount',
                                            'title' => 'Etiqueta de descuento (-X%)',
                                            'preview' => '-15%',
                                            'help' => 'Solo se muestra cuando el producto tiene un precio anterior real mayor al actual y el descuento alcanza el porcentaje mínimo.',
                                            'keys' => ['badge_enabled_discount', 'badge_color_discount', 'discount_min_percent'],
                                        ],
                                        [
                                            'id' => 'last_units',
                                            'title' => 'Etiqueta «Últimas piezas»',
                                            'preview' => 'Últimas piezas',
                                            'help' => 'Se muestra en productos disponibles cuyo stock es de 1 hasta el máximo indicado.',
                                            'keys' => ['badge_enabled_last_units', 'badge_color_last_units', 'last_units_threshold'],
                                        ],
                                        [
                                            'id' => 'new',
                                            'title' => 'Etiqueta «Nuevo»',
                                            'preview' => 'Nuevo',
                                            'help' => 'Siempre se muestra en productos marcados manualmente como nuevos. Si indicas días mayores a 0, también en los productos creados en ese lapso.',
                                            'keys' => ['badge_enabled_new', 'badge_color_new', 'new_days'],
                                        ],
                                        [
                                            'id' => 'listing',
                                            'title' => 'Listado y filtros',
                                            'preview' => null,
                                            'help' => 'La caché guarda el resultado del listado; se limpia sola al editar productos, categorías o estos ajustes.',
                                            'keys' => ['fast_shipping_label', 'filter_max_visible', 'cache_ttl_minutes'],
                                        ],
                                    ];
                                    $catalogPlaced = [];
                                @endphp

                                <p class="catalog-settings-intro">
                                    Define cuándo se muestra cada etiqueta en las tarjetas de producto del catálogo
                                    (máximo dos por producto, con prioridad Más vendido, descuento, Últimas piezas y
                                    Nuevo) y los ajustes generales del listado. Los cambios se aplican al guardar.
                                </p>

                                @foreach ($catalogLayout as $block)
                                    <fieldset class="catalog-settings-block" id="catalog_block_{{ $block['id'] }}">
                                        <legend>{{ $block['title'] }}</legend>
                                        <p class="catalog-settings-help">{{ $block['help'] }}</p>

                                        @foreach ($block['keys'] as $shortKey)
                                            @php
                                                $setting = $catalogByKey->get('catalog.' . $shortKey);
                                            @endphp
                                            @continue(!$setting)
                                            @php
                                                $catalogPlaced[] = $setting->key;
                                                $spec = $catalogFields[$setting->key] ?? ['type' => 'string', 'label' => $setting->key];
                                                $fieldName = "values[{$setting->key}]";
                                                $fieldId = 'setting_' . str_replace('.', '_', $setting->key);
                                                $label = $spec['label'];
                                                $current = array_key_exists($setting->key, $oldValues) && is_scalar($oldValues[$setting->key])
                                                    ? (string) $oldValues[$setting->key]
                                                    : (string) $setting->value;
                                                $fieldError = $errors->first($setting->key);
                                                $errId = $fieldId . '_error';
                                                $hintId = $fieldId . '_hint';
                                            @endphp

                                            <div class="pform-field catalog-settings-field">
                                                @if ($spec['type'] === 'bool')
                                                    <input type="hidden" name="{{ $fieldName }}" value="0">
                                                    <label class="catalog-settings-check" for="{{ $fieldId }}">
                                                        <input type="checkbox" id="{{ $fieldId }}" name="{{ $fieldName }}"
                                                            value="1" {{ $current === '1' || $current === 'true' ? 'checked' : '' }}
                                                            @if ($fieldError) aria-invalid="true" aria-describedby="{{ $errId }}" @endif>
                                                        <span>{{ $label }}</span>
                                                    </label>
                                                @elseif ($spec['type'] === 'color')
                                                    @php
                                                        $isHex = (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $current);
                                                        $previewBg = $isHex ? strtoupper($current) : '#CCCCCC';
                                                        $readable = \App\Http\Controllers\Backend\SettingController::readableTextColor($previewBg);
                                                        $ratioText = number_format($readable['ratio'], 2, '.', '');
                                                        $lowContrast = $readable['ratio'] < 4.5;
                                                    @endphp
                                                    <label class="pform-label" for="{{ $fieldId }}">{{ $label }}</label>
                                                    <div class="catalog-color-row" data-catalog-color>
                                                        <input type="color" class="catalog-color-picker"
                                                            value="{{ strtolower($previewBg) }}"
                                                            aria-label="Selector de color: {{ $label }}" data-color-picker>
                                                        <input type="text" id="{{ $fieldId }}" name="{{ $fieldName }}"
                                                            class="pform-input catalog-color-text {{ $fieldError ? 'is-invalid' : '' }}"
                                                            value="{{ $current }}" maxlength="7" size="9"
                                                            pattern="#[0-9a-fA-F]{6}" placeholder="#RRGGBB"
                                                            autocomplete="off" spellcheck="false" data-color-text
                                                            aria-describedby="{{ $hintId }}{{ $fieldError ? ' ' . $errId : '' }}"
                                                            @if ($fieldError) aria-invalid="true" @endif>
                                                        @if ($block['preview'])
                                                            <span class="catalog-badge-preview" data-color-preview
                                                                style="background:{{ $previewBg }};color:{{ $readable['color'] }}"
                                                                role="img" aria-label="Vista previa de la etiqueta: {{ $block['preview'] }}">{{ $block['preview'] }}</span>
                                                        @endif
                                                    </div>
                                                    <p class="catalog-settings-hint {{ $lowContrast ? 'is-warning' : '' }}" id="{{ $hintId }}" data-color-contrast aria-live="polite">
                                                        @if ($lowContrast)
                                                            Atención: el contraste del texto de la etiqueta es de {{ $ratioText }}:1 y no alcanza el mínimo de 4.5:1; elige un tono más claro u oscuro.
                                                        @else
                                                            Contraste del texto automático: {{ $ratioText }}:1 (cumple el mínimo de 4.5:1). Formato #RRGGBB.
                                                        @endif
                                                    </p>
                                                @elseif ($spec['type'] === 'int' || $spec['type'] === 'decimal')
                                                    <label class="pform-label" for="{{ $fieldId }}">{{ $label }}</label>
                                                    <input type="number" id="{{ $fieldId }}" name="{{ $fieldName }}"
                                                        class="pform-input catalog-number-input {{ $fieldError ? 'is-invalid' : '' }}"
                                                        value="{{ $current }}" inputmode="{{ $spec['type'] === 'int' ? 'numeric' : 'decimal' }}"
                                                        min="{{ $spec['min'] }}" max="{{ $spec['max'] }}" step="{{ $spec['step'] }}"
                                                        required
                                                        aria-describedby="{{ $hintId }}{{ $fieldError ? ' ' . $errId : '' }}"
                                                        @if ($fieldError) aria-invalid="true" @endif>
                                                    <p class="catalog-settings-hint" id="{{ $hintId }}">
                                                        Valor entre {{ $spec['min'] }} y {{ $spec['max'] }}.
                                                    </p>
                                                @else
                                                    <label class="pform-label" for="{{ $fieldId }}">{{ $label }}</label>
                                                    <input type="text" id="{{ $fieldId }}" name="{{ $fieldName }}"
                                                        class="pform-input {{ $fieldError ? 'is-invalid' : '' }}"
                                                        value="{{ $current }}"
                                                        @isset($spec['min']) minlength="{{ $spec['min'] }}" maxlength="{{ $spec['max'] }}" required @endisset
                                                        aria-describedby="{{ $hintId }}{{ $fieldError ? ' ' . $errId : '' }}"
                                                        @if ($fieldError) aria-invalid="true" @endif>
                                                    <p class="catalog-settings-hint" id="{{ $hintId }}">
                                                        @isset($spec['min'])
                                                            Entre {{ $spec['min'] }} y {{ $spec['max'] }} caracteres.
                                                        @endisset
                                                    </p>
                                                @endif

                                                @if ($fieldError)
                                                    <span class="field-error-msg" id="{{ $errId }}" role="alert">{{ $fieldError }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </fieldset>
                                @endforeach

                                @foreach ($settings->reject(fn ($s) => in_array($s->key, $catalogPlaced, true)) as $setting)
                                    @php
                                        $fieldName = "values[{$setting->key}]";
                                        $fieldId = 'setting_' . str_replace('.', '_', $setting->key);
                                    @endphp
                                    <div class="pform-field">
                                        <label class="pform-label" for="{{ $fieldId }}">{{ ucfirst(str_replace(['_', '-'], ' ', last(explode('.', $setting->key)))) }}</label>
                                        <input type="text" id="{{ $fieldId }}" name="{{ $fieldName }}" class="pform-input"
                                            value="{{ $setting->value }}">
                                        <p class="pform-hint">Clave: <code>{{ $setting->key }}</code></p>
                                    </div>
                                @endforeach
                            @else
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
                            @endif
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

    @push('styles')
        <style>
            .catalog-settings-intro { font-size: 14px; color: #374151; margin: -8px 0 20px; line-height: 1.5; }
            .catalog-settings-block { border: 1px solid #d1d5db; border-radius: 8px; padding: 16px 20px 20px; margin: 0 0 20px; min-width: 0; }
            .catalog-settings-block legend { font-size: 15px; font-weight: 700; color: #111827; padding: 0 8px; }
            .catalog-settings-help { font-size: 13px; color: #4b5563; margin: 0 0 16px; line-height: 1.5; }
            .catalog-settings-hint { font-size: 12px; color: #4b5563; margin: 6px 0 0; min-height: 1em; }
            .catalog-settings-hint.is-warning { color: #92400e; font-weight: 600; }
            .catalog-settings-check { display: inline-flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 500; color: #374151; cursor: pointer; }
            .catalog-settings-check input { width: 18px; height: 18px; accent-color: var(--secondary-color, #0054ff); }
            .catalog-number-input { max-width: 180px; }
            .catalog-color-row { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
            .catalog-color-picker { width: 44px; height: 40px; padding: 2px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; cursor: pointer; }
            .catalog-color-text { max-width: 130px; font-family: monospace; text-transform: uppercase; }
            .catalog-badge-preview { display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 700; line-height: 1.3; }
            .catalog-settings-block input:focus-visible { outline: 2px solid var(--secondary-color, #0054ff); outline-offset: 2px; }
        </style>
    @endpush

    @push('scripts')
        <script>
            (function () {
                function luminance(hex) {
                    var c = [1, 3, 5].map(function (i) {
                        var v = parseInt(hex.substr(i, 2), 16) / 255;
                        return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
                    });
                    return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
                }
                function ratio(a, b) {
                    var la = luminance(a), lb = luminance(b);
                    return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05);
                }

                document.querySelectorAll('[data-catalog-color]').forEach(function (row) {
                    var picker = row.querySelector('[data-color-picker]');
                    var text = row.querySelector('[data-color-text]');
                    var preview = row.querySelector('[data-color-preview]');
                    var note = row.parentNode.querySelector('[data-color-contrast]');

                    function refresh(hex) {
                        var white = ratio(hex, '#FFFFFF'), dark = ratio(hex, '#111111');
                        var fg = white >= dark ? '#FFFFFF' : '#111111';
                        var best = Math.max(white, dark);
                        if (preview) {
                            preview.style.background = hex;
                            preview.style.color = fg;
                        }
                        if (note) {
                            var low = best < 4.5;
                            note.classList.toggle('is-warning', low);
                            note.textContent = low
                                ? 'Atención: el contraste del texto de la etiqueta es de ' + best.toFixed(2) + ':1 y no alcanza el mínimo de 4.5:1; elige un tono más claro u oscuro.'
                                : 'Contraste del texto automático: ' + best.toFixed(2) + ':1 (cumple el mínimo de 4.5:1). Formato #RRGGBB.';
                        }
                    }

                    picker.addEventListener('input', function () {
                        text.value = picker.value.toUpperCase();
                        text.classList.remove('is-invalid');
                        refresh(text.value);
                    });
                    text.addEventListener('input', function () {
                        var v = text.value.trim();
                        if (/^#[0-9a-fA-F]{6}$/.test(v)) {
                            picker.value = v.toLowerCase();
                            refresh(v.toUpperCase());
                        }
                    });
                });
            })();
        </script>
    @endpush
@endsection
