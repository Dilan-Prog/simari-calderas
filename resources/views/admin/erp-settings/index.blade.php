@extends('admin.layouts.master')
@section('title')
    Configuración ERP - Admin
@endsection

@push('styles')
    @vite('resources/css/admin/pages/erp-settings.css')
@endpush

@section('content')
    @php
        // Ajustes erp.* — ninguno está sembrado todavía en este pase, pero se
        // deja el array de labels listo para cuando se agreguen (mismo patrón
        // que admin/settings/index.blade.php).
        $labels = [];
    @endphp

    <div class="container user-manager">
        <section class="clients-manager-section">

            {{-- Header --}}
            <header class="clients-manager-main" style="margin-bottom:4px;">
                <div>
                    <p class="breadcrumb-clients-manager main" style="margin-bottom:4px;">
                        Panel de Control &gt; Configuración ERP
                    </p>
                    <h1>Configuración ERP</h1>
                    <p class="breadcrumb-clients-manager main">Ajustes propios del módulo ERP y catálogos de Servicios
                        Técnicos / Reportes de Servicio</p>
                </div>
            </header>

            <div class="pform-panel-wrap" style="margin-top:20px;">

                {{-- ── 0. Términos y condiciones del cotizador ───────────────────────── --}}
                <div class="pform-panel" id="quote-terms">
                    <div class="erp-panel-header">
                        <span class="erp-panel-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                <path d="M14 2v6h6" />
                                <path d="M16 13H8" />
                                <path d="M16 17H8" />
                                <path d="M10 9H8" />
                            </svg>
                        </span>
                        <div class="erp-panel-heading">
                            <h2 class="pform-panel-title">Términos y condiciones del cotizador</h2>
                            <p class="erp-panel-subtitle">Texto predeterminado que se precarga en cada cotización nueva y aparece en su PDF.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.erp-settings.quote-terms.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="pform-field">
                            <label class="pform-label" for="erp_quote_terms">Texto predeterminado</label>
                            <textarea id="erp_quote_terms" name="quote_terms" class="pform-textarea" rows="14" maxlength="20000"
                                placeholder="Ej. Vigencia de la cotización, forma de pago, tiempos de entrega, garantía...">{{ old('quote_terms', $quoteTerms) }}</textarea>
                            <p class="pform-hint">Cada cotización se puede ajustar sin cambiar este texto. Cambiarlo aquí no modifica las cotizaciones ya creadas.</p>
                            @error('quote_terms')
                                <p class="pform-hint" style="color:#c81e1e;">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="pform-btn primary">Guardar términos</button>
                    </form>
                </div>

                {{-- ── 1. Ajustes generales (group_name = 'erp') ─────────────────────── --}}
                <div class="pform-panel">
                    <div class="erp-panel-header">
                        <span class="erp-panel-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 7h-9" />
                                <path d="M14 17H5" />
                                <circle cx="17" cy="17" r="3" />
                                <circle cx="7" cy="7" r="3" />
                            </svg>
                        </span>
                        <div class="erp-panel-heading">
                            <h2 class="pform-panel-title">Ajustes Generales ERP</h2>
                            <p class="erp-panel-subtitle">Valores propios del módulo ERP, separados de Configuración del Sitio.</p>
                        </div>
                    </div>

                    @if ($settings->isEmpty())
                        <div class="erp-empty-state">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10" />
                                <path d="M12 16v-4" />
                                <path d="M12 8h.01" />
                            </svg>
                            <p>Sin ajustes configurados todavía. Aparecerán aquí en cuanto se agregue el primer valor <code>erp.*</code>.</p>
                        </div>
                    @else
                        <form method="POST" action="{{ route('admin.erp-settings.update') }}">
                            @csrf
                            @method('PUT')

                            @foreach ($settings as $setting)
                                @php
                                    $fieldName = "values[{$setting->key}]";
                                    $fieldId = 'erp_setting_' . str_replace('.', '_', $setting->key);
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

                            <button type="submit" class="pform-btn primary">Guardar ajustes</button>
                        </form>
                    @endif
                </div>

                {{-- ── 2. Tipos de Servicio (Servicios Técnicos) ─────────────────────── --}}
                <div class="pform-panel">
                    <div class="erp-panel-header">
                        <span class="erp-panel-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                        </span>
                        <div class="erp-panel-heading">
                            <h2 class="pform-panel-title">Tipos de Servicio (Servicios Técnicos)</h2>
                            <p class="erp-panel-subtitle">Catálogo usado al capturar un servicio técnico nuevo.</p>
                        </div>
                    </div>

                    <div class="table-scroll" style="margin-bottom:20px;">
                        <table class="clients-manager-table">
                            <thead>
                                <tr>
                                    <th>NOMBRE</th>
                                    <th style="width:120px;">ESTADO</th>
                                    <th style="width:160px;">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($serviceTypes as $serviceType)
                                    <tr>
                                        @if (Route::has('admin.erp-settings.service-types.update'))
                                            <form method="POST"
                                                action="{{ route('admin.erp-settings.service-types.update', $serviceType) }}"
                                                id="svcTypeForm{{ $serviceType->id }}">
                                                @csrf
                                                @method('PUT')
                                            </form>
                                        @endif
                                        <td>
                                            <input type="text" class="pform-input" name="name"
                                                value="{{ $serviceType->name }}" maxlength="100" required
                                                @if (Route::has('admin.erp-settings.service-types.update')) form="svcTypeForm{{ $serviceType->id }}" @endif>
                                        </td>
                                        <td>
                                            <label style="display:flex; align-items:center; gap:6px; font-size:13px; color:#374151;">
                                                <input type="checkbox" name="is_active" value="1"
                                                    {{ $serviceType->is_active ? 'checked' : '' }}
                                                    @if (Route::has('admin.erp-settings.service-types.update')) form="svcTypeForm{{ $serviceType->id }}" @endif>
                                                {{ $serviceType->is_active ? 'Activo' : 'Inactivo' }}
                                            </label>
                                        </td>
                                        <td>
                                            <div class="actions-container">
                                                @if (Route::has('admin.erp-settings.service-types.update'))
                                                    <button type="submit" form="svcTypeForm{{ $serviceType->id }}"
                                                        class="pform-btn outline" style="padding:6px 10px;">Guardar</button>
                                                @endif
                                                @if (Route::has('admin.erp-settings.service-types.destroy'))
                                                    <form method="POST"
                                                        action="{{ route('admin.erp-settings.service-types.destroy', $serviceType) }}"
                                                        onsubmit="return confirm('¿Eliminar el tipo de servicio &quot;{{ $serviceType->name }}&quot;? Si está en uso se desactivará en su lugar.');"
                                                        style="display:inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="pform-btn outline"
                                                            style="padding:6px 10px; color:#dc2626; border-color:#fca5a5;">Eliminar</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" style="text-align:center; padding:24px; color:#6b7280;">
                                            No hay tipos de servicio registrados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if (Route::has('admin.erp-settings.service-types.store'))
                        <form method="POST" action="{{ route('admin.erp-settings.service-types.store') }}"
                            style="display:flex; align-items:flex-end; gap:10px;">
                            @csrf
                            <div class="pform-field" style="margin-bottom:0; flex:1;">
                                <label class="pform-label" for="newServiceTypeName">Nuevo tipo de servicio</label>
                                <input type="text" id="newServiceTypeName" class="pform-input" name="name"
                                    maxlength="100" placeholder="Ej. Mantenimiento preventivo" required>
                            </div>
                            <button type="submit" class="pform-btn primary">+ Agregar</button>
                        </form>
                    @endif
                </div>

                {{-- ── 3. Tipos de Reporte (Reportes de Servicio) ────────────────────── --}}
                <div class="pform-panel">
                    <div class="erp-panel-header">
                        <span class="erp-panel-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"></rect><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><path d="M12 11h4"></path><path d="M12 16h4"></path><path d="M8 11h.01"></path><path d="M8 16h.01"></path></svg>
                        </span>
                        <div class="erp-panel-heading">
                            <h2 class="pform-panel-title">Tipos de Reporte (Reportes de Servicio)</h2>
                            <p class="erp-panel-subtitle">Define qué sub-formulario del wizard se muestra al capturar un reporte de servicio.</p>
                        </div>
                    </div>

                    @if (!Schema::hasTable('service_report_types'))
                        <div class="erp-empty-state">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10" />
                                <path d="M12 16v-4" />
                                <path d="M12 8h.01" />
                            </svg>
                            <p>La tabla <code>service_report_types</code> todavía no está migrada. Esta sección se
                                habilitará automáticamente una vez que se ejecute esa migración.</p>
                        </div>
                    @else
                        <div class="table-scroll" style="margin-bottom:20px;">
                            <table class="clients-manager-table">
                                <thead>
                                    <tr>
                                        <th>CLAVE</th>
                                        <th>NOMBRE</th>
                                        <th style="width:150px;">COMPORTAMIENTO</th>
                                        <th style="width:120px;">ESTADO</th>
                                        <th style="width:160px;">ACCIONES</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($serviceReportTypes as $reportType)
                                        <tr>
                                            @if (Route::has('admin.erp-settings.report-types.update'))
                                                <form method="POST"
                                                    action="{{ route('admin.erp-settings.report-types.update', $reportType) }}"
                                                    id="reportTypeForm{{ $reportType->id }}">
                                                    @csrf
                                                    @method('PUT')
                                                </form>
                                            @endif
                                            <td><code>{{ $reportType->key }}</code></td>
                                            <td>
                                                <input type="text" class="pform-input" name="label"
                                                    value="{{ $reportType->label }}" maxlength="100" required
                                                    @if (Route::has('admin.erp-settings.report-types.update')) form="reportTypeForm{{ $reportType->id }}" @endif>
                                            </td>
                                            <td>
                                                {{-- Solo lectura: cambiar el comportamiento de un tipo ya en uso
                                                     podría romper reportes existentes que dependen de qué
                                                     sub-formulario del wizard se les muestra. --}}
                                                <span class="erp-behavior-badge {{ $reportType->form_behavior }}">{{ $reportType->form_behavior }}</span>
                                            </td>
                                            <td>
                                                <label style="display:flex; align-items:center; gap:6px; font-size:13px; color:#374151;">
                                                    <input type="checkbox" name="is_active" value="1"
                                                        {{ $reportType->is_active ? 'checked' : '' }}
                                                        @if (Route::has('admin.erp-settings.report-types.update')) form="reportTypeForm{{ $reportType->id }}" @endif>
                                                    {{ $reportType->is_active ? 'Activo' : 'Inactivo' }}
                                                </label>
                                            </td>
                                            <td>
                                                <div class="actions-container">
                                                    @if (Route::has('admin.erp-settings.report-types.update'))
                                                        <button type="submit" form="reportTypeForm{{ $reportType->id }}"
                                                            class="pform-btn outline" style="padding:6px 10px;">Guardar</button>
                                                    @endif
                                                    @if (Route::has('admin.erp-settings.report-types.destroy'))
                                                        <form method="POST"
                                                            action="{{ route('admin.erp-settings.report-types.destroy', $reportType) }}"
                                                            onsubmit="return confirm('¿Eliminar el tipo de reporte &quot;{{ $reportType->label }}&quot;? Si está en uso se desactivará en su lugar.');"
                                                            style="display:inline;">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="pform-btn outline"
                                                                style="padding:6px 10px; color:#dc2626; border-color:#fca5a5;">Eliminar</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" style="text-align:center; padding:24px; color:#6b7280;">
                                                No hay tipos de reporte registrados.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if (Route::has('admin.erp-settings.report-types.store'))
                            <form method="POST" action="{{ route('admin.erp-settings.report-types.store') }}"
                                style="display:flex; align-items:flex-end; gap:10px; flex-wrap:wrap;">
                                @csrf
                                <div class="pform-field" style="margin-bottom:0; flex:1; min-width:160px;">
                                    <label class="pform-label" for="newReportTypeKey">Clave</label>
                                    <input type="text" id="newReportTypeKey" class="pform-input" name="key"
                                        maxlength="60" placeholder="ej. leak_test" required>
                                </div>
                                <div class="pform-field" style="margin-bottom:0; flex:1; min-width:160px;">
                                    <label class="pform-label" for="newReportTypeLabel">Nombre</label>
                                    <input type="text" id="newReportTypeLabel" class="pform-input" name="label"
                                        maxlength="100" placeholder="Ej. Prueba de fugas" required>
                                </div>
                                <div class="pform-field" style="margin-bottom:0; min-width:180px;">
                                    <label class="pform-label" for="newReportTypeBehavior">Comportamiento</label>
                                    <select id="newReportTypeBehavior" class="pform-select" name="form_behavior"
                                        required>
                                        <option value="measurements">Mediciones</option>
                                        <option value="activity">Actividad</option>
                                        <option value="custom">Personalizado</option>
                                    </select>
                                </div>
                                <button type="submit" class="pform-btn primary">+ Agregar</button>
                            </form>
                        @else
                            <p class="pform-hint">
                                El formulario para agregar nuevos tipos de reporte se habilitará cuando se agreguen
                                las rutas <code>admin.erp-settings.report-types.store</code> a
                                <code>routes/admin.php</code>.
                            </p>
                        @endif
                    @endif
                </div>

                {{-- ── Parámetros de análisis de agua (límites del Reporte de análisis químico) ── --}}
                @if (Route::has('admin.erp-settings.water-analysis.index'))
                    <div class="pform-panel" id="water-analysis">
                        <div class="erp-panel-header">
                            <span class="erp-panel-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 2v7.5L4.5 19a2 2 0 0 0 1.8 3h11.4a2 2 0 0 0 1.8-3L14 9.5V2" /><path d="M8.5 2h7" /><path d="M7 16h10" /></svg>
                            </span>
                            <div class="erp-panel-heading">
                                <h2 class="pform-panel-title">Parámetros de análisis de agua</h2>
                                <p class="erp-panel-subtitle">Valores mínimo y máximo de dureza, pH, alcalinidad, conductividad y STD del Reporte de análisis químico del portal del cliente.</p>
                            </div>
                        </div>
                        <a href="{{ route('admin.erp-settings.water-analysis.index') }}" class="pform-btn primary">Configurar parámetros</a>
                    </div>
                @endif

                {{-- ── 4. Encuesta de satisfacción (Califica este servicio) ───────────── --}}
                @include('admin.erp-settings._survey')

            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        // Paneles desplegables (como el footer): el encabezado de cada panel abre/cierra su contenido.
        // En teléfono arrancan cerrados; en escritorio abiertos. Se abre solo el panel del #hash o con avisos/errores.
        (function () {
            var phone = window.matchMedia('(max-width: 720px)').matches;
            var hash = (location.hash || '').replace('#', '');
            document.querySelectorAll('.pform-panel').forEach(function (panel, idx) {
                var header = panel.querySelector(':scope > .erp-panel-header');
                if (!header || panel.dataset.accordion) return;
                panel.dataset.accordion = '1';
                var body = document.createElement('div');
                body.className = 'erp-panel-body';
                body.id = 'erpPanelBody' + idx;
                while (header.nextSibling) body.appendChild(header.nextSibling);
                panel.appendChild(body);

                var chevron = document.createElement('span');
                chevron.className = 'erp-panel-chevron';
                chevron.setAttribute('aria-hidden', 'true');
                chevron.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>';
                header.appendChild(chevron);
                header.setAttribute('role', 'button');
                header.setAttribute('tabindex', '0');
                header.setAttribute('aria-controls', body.id);

                var forceOpen = (panel.id && panel.id === hash) || !!body.querySelector('.alert, .user-manager-errors, .is-invalid, .field-error-msg');
                function set(open) {
                    panel.classList.toggle('is-collapsed', !open);
                    header.setAttribute('aria-expanded', open ? 'true' : 'false');
                }
                set(forceOpen || !phone);
                function toggle() { set(panel.classList.contains('is-collapsed')); }
                header.addEventListener('click', toggle);
                header.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
                });
            });
        })();

        // Teléfono: cada tabla se apila como tarjetas; la etiqueta de cada celda sale del encabezado (CSS en erp-settings.css).
        document.querySelectorAll('.table-scroll > .clients-manager-table').forEach(function (table) {
            var labels = Array.from(table.querySelectorAll('thead th')).map(function (th) { return th.textContent.trim(); });
            table.classList.add('erp-stack-table');
            table.querySelectorAll('tbody tr').forEach(function (tr) {
                Array.from(tr.children).filter(function (c) { return c.tagName === 'TD'; }).forEach(function (td, i) {
                    if (labels[i] && !td.hasAttribute('data-label')) td.setAttribute('data-label', labels[i]);
                });
            });
        });
    </script>
@endpush
