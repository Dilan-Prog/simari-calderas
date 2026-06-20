@extends('admin.layouts.master')
@section('title', 'Reporte ' . $report->report_number . ' — Paso 2')

@push('styles')
    @vite('resources/css/service-reports.css')
@endpush

@section('content')
<div class="sr-create-wrap sr-page-step2">

    {{-- Breadcrumb --}}
    <div style="font-size:12px; color:#6B7280; margin-bottom:16px; display:flex; align-items:center; gap:6px;">
        <a href="{{ route('admin.service-reports.index') }}" style="color:#6B7280; text-decoration:none;">Reportes</a>
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        <span style="color:#374151; font-weight:500;">{{ $report->report_number }} — Paso 2</span>
    </div>

    {{-- Progress --}}
    <div class="sr-progress">
        @php $stepLabels = ['Datos Generales','Mediciones / Actividades','Observaciones','Evidencia','Resumen','Firma']; @endphp
        @foreach($stepLabels as $i => $label)
            @php $n = $i + 1; $cls = $n < 2 ? 'done' : ($n === 2 ? 'active' : ''); @endphp
            <div class="sr-step-item {{ $cls }}">
                <div class="sr-step-circle">
                    @if($n < 2) ✓ @else {{ $n }} @endif
                </div>
                <span class="sr-step-label">{{ $label }}</span>
            </div>
        @endforeach
    </div>

    @if($errors->any())
        <div style="background:#FEF2F2; border:1px solid #FECACA; border-radius:6px; padding:12px 16px; margin-bottom:16px; font-size:13px; color:#DC2626;">
            <strong>Por favor corrige los siguientes errores:</strong>
            <ul style="margin:6px 0 0 18px; padding:0;">
                @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    <div class="sr-form-card">
        <div class="sr-form-header">
            <h2>Paso 2 — {{ $report->service_type_label }}</h2>
            <p>
                @if($report->usesMeasurementsForm()) Captura los parámetros y resultados del análisis
                @elseif($report->usesActivityForm()) Describe las actividades realizadas y los sistemas revisados
                @else Define los campos del reporte personalizado
                @endif
            </p>
        </div>

        <form method="POST" action="{{ route('admin.service-reports.save-step', [$report, 2]) }}" id="step2Form">
            @csrf
            <div class="sr-form-body">

                {{-- ── VARIANT A: Análisis Químico ── --}}
                @if($report->usesMeasurementsForm())
                    <div class="sr-meas-scroll">
                        <table class="sr-meas-table" id="measTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Parámetro <span style="color:#ff6213">*</span></th>
                                    <th>Unidad</th>
                                    <th>Resultado</th>
                                    <th>Límite Mín.</th>
                                    <th>Límite Máx.</th>
                                    <th>Rango</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="measBody">
                                @forelse($report->measurements as $idx => $m)
                                    <tr data-idx="{{ $idx }}">
                                        <td class="sr-meas-num">
                                            <span class="sr-meas-num-short">{{ $idx + 1 }}</span>
                                            <span class="sr-meas-num-full">Medición {{ $idx + 1 }}</span>
                                        </td>
                                        <td data-label="Parámetro"><input type="text" name="measurements[{{ $idx }}][parameter]" class="sr-input" value="{{ old("measurements.$idx.parameter", $m->parameter) }}" required placeholder="Ej: pH"></td>
                                        <td data-label="Unidad"><input type="text" name="measurements[{{ $idx }}][unit]"      class="sr-input" value="{{ old("measurements.$idx.unit", $m->unit) }}" placeholder="Unidad pH"></td>
                                        <td data-label="Resultado"><input type="text" name="measurements[{{ $idx }}][result]"   class="sr-input sr-result" value="{{ old("measurements.$idx.result", $m->result) }}" placeholder="7.4"></td>
                                        <td data-label="Límite Mín."><input type="number" step="any" name="measurements[{{ $idx }}][limit_min]" class="sr-input sr-lmin" value="{{ old("measurements.$idx.limit_min", $m->limit_min) }}" placeholder="6.5"></td>
                                        <td data-label="Límite Máx."><input type="number" step="any" name="measurements[{{ $idx }}][limit_max]" class="sr-input sr-lmax" value="{{ old("measurements.$idx.limit_max", $m->limit_max) }}" placeholder="8.5"></td>
                                        <td data-label="Rango" style="min-width:110px;">
                                            <span class="sr-range-badge sr-range-nd">— S/L</span>
                                            <div class="sr-range-bar-wrap" style="display:none;">
                                                <div class="sr-range-bar-track">
                                                    <div class="sr-range-bar-valid"></div>
                                                    <div class="sr-range-bar-pin"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="sr-meas-del"><button type="button" class="sr-btn-del-row" onclick="removeRow(this)">✕</button></td>
                                    </tr>
                                @empty
                                    <tr data-idx="0">
                                        <td class="sr-meas-num">
                                            <span class="sr-meas-num-short">1</span>
                                            <span class="sr-meas-num-full">1</span>
                                        </td>
                                        <td data-label="Parámetro"><input type="text" name="measurements[0][parameter]" class="sr-input" required placeholder="Ej: pH"></td>
                                        <td data-label="Unidad"><input type="text" name="measurements[0][unit]"      class="sr-input" placeholder="Unidad pH"></td>
                                        <td data-label="Resultado"><input type="text" name="measurements[0][result]"    class="sr-input sr-result" placeholder="7.4"></td>
                                        <td data-label="Límite Mín."><input type="number" step="any" name="measurements[0][limit_min]" class="sr-input sr-lmin" placeholder="6.5"></td>
                                        <td data-label="Límite Máx."><input type="number" step="any" name="measurements[0][limit_max]" class="sr-input sr-lmax" placeholder="8.5"></td>
                                        <td data-label="Rango" style="min-width:110px;">
                                            <span class="sr-range-badge sr-range-nd">— S/L</span>
                                            <div class="sr-range-bar-wrap" style="display:none;">
                                                <div class="sr-range-bar-track">
                                                    <div class="sr-range-bar-valid"></div>
                                                    <div class="sr-range-bar-pin"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="sr-meas-del"><button type="button" class="sr-btn-del-row" onclick="removeRow(this)">✕</button></td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="sr-btn-add-row" id="btnAddRow">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        Agregar fila
                    </button>

                {{-- ── VARIANT B: Actividades / Mantenimiento / Inspección / Limpieza / Calibración ── --}}
                @elseif($report->usesActivityForm())
                    <div class="sr-field">
                        <label class="sr-label" for="activity_content">Actividades Realizadas</label>
                        <textarea id="activity_content" name="activity_content" class="sr-textarea" rows="8" placeholder="Describe detalladamente las actividades realizadas durante el servicio…">{{ old('activity_content', $report->activity?->content) }}</textarea>
                    </div>

                    @if(isset($systemsChecked) && count($systemsChecked))
                        <div class="sr-field" style="margin-top:20px;">
                            <label class="sr-label">Sistemas Revisados</label>
                            <div class="sr-systems-grid">
                                @foreach($systemsChecked as $key => $label)
                                    @php $checked = in_array($key, old('systems_checked', $report->activity?->systems_checked ?? [])); @endphp
                                    <label class="sr-checkbox-item">
                                        <input type="checkbox" name="systems_checked[]" value="{{ $key }}" {{ $checked ? 'checked' : '' }}>
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                {{-- ── VARIANT C: Personalizado ── --}}
                @else
                    <div id="customFieldsWrap">
                        @forelse($report->customFields as $idx => $cf)
                            <div class="sr-custom-row" data-idx="{{ $idx }}">
                                <div>
                                    <label class="sr-label">Nombre del campo <span style="color:#ff6213">*</span></label>
                                    <input type="text" name="custom_fields[{{ $idx }}][field_name]" class="sr-input" value="{{ old("custom_fields.$idx.field_name", $cf->field_name) }}" required placeholder="Ej: Temperatura">
                                </div>
                                <div>
                                    <label class="sr-label">Tipo</label>
                                    <select name="custom_fields[{{ $idx }}][field_type]" class="sr-input">
                                        @foreach(['text'=>'Texto','number'=>'Número','date'=>'Fecha','boolean'=>'Sí/No','list'=>'Lista'] as $v => $l)
                                            <option value="{{ $v }}" {{ old("custom_fields.$idx.field_type", $cf->field_type) == $v ? 'selected' : '' }}>{{ $l }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="sr-label">Valor</label>
                                    <input type="text" name="custom_fields[{{ $idx }}][field_value]" class="sr-input" value="{{ old("custom_fields.$idx.field_value", $cf->field_value) }}" placeholder="Valor del campo">
                                </div>
                                <div style="padding-top:22px;">
                                    <button type="button" class="sr-btn-del-row" onclick="this.closest('.sr-custom-row').remove()">✕</button>
                                </div>
                            </div>
                        @empty
                            <div class="sr-custom-row" data-idx="0">
                                <div>
                                    <label class="sr-label">Nombre del campo <span style="color:#ff6213">*</span></label>
                                    <input type="text" name="custom_fields[0][field_name]" class="sr-input" required placeholder="Ej: Temperatura">
                                </div>
                                <div>
                                    <label class="sr-label">Tipo</label>
                                    <select name="custom_fields[0][field_type]" class="sr-input">
                                        <option value="text">Texto</option>
                                        <option value="number">Número</option>
                                        <option value="date">Fecha</option>
                                        <option value="boolean">Sí/No</option>
                                        <option value="list">Lista</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="sr-label">Valor</label>
                                    <input type="text" name="custom_fields[0][field_value]" class="sr-input" placeholder="Valor del campo">
                                </div>
                                <div style="padding-top:22px;">
                                    <button type="button" class="sr-btn-del-row" onclick="this.closest('.sr-custom-row').remove()">✕</button>
                                </div>
                            </div>
                        @endforelse
                    </div>
                    <button type="button" class="sr-btn-add-row" id="btnAddCustom">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        Agregar campo
                    </button>
                @endif

            </div>

            <div class="sr-form-footer">
                <a href="{{ route('admin.service-reports.step', [$report, 1]) }}" class="sr-btn-outline">← Anterior</a>
                <button type="submit" class="sr-btn-primary">Guardar y continuar →</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    @if($report->usesMeasurementsForm())
    // ── Measurements ──────────────────────────────────────────────────────────
    let rowCount = {{ max(count($report->measurements), 1) }};

    function calcInRange(row) {
        const result  = parseFloat(row.querySelector('.sr-result').value);
        const lminVal = row.querySelector('.sr-lmin').value;
        const lmaxVal = row.querySelector('.sr-lmax').value;
        const badge   = row.querySelector('.sr-range-badge');
        const barWrap = row.querySelector('.sr-range-bar-wrap');
        const barValid= row.querySelector('.sr-range-bar-valid');
        const barPin  = row.querySelector('.sr-range-bar-pin');

        if (!badge) return;

        const lmin = lminVal !== '' ? parseFloat(lminVal) : null;
        const lmax = lmaxVal !== '' ? parseFloat(lmaxVal) : null;

        // No result or no limits
        if (isNaN(result) || (lmin === null && lmax === null)) {
            badge.className = 'sr-range-badge sr-range-nd';
            badge.textContent = '— S/L';
            if (barWrap) barWrap.style.display = 'none';
            return;
        }

        const min = lmin !== null ? lmin : -Infinity;
        const max = lmax !== null ? lmax :  Infinity;
        const ok  = result >= min && result <= max;

        badge.className = 'sr-range-badge ' + (ok ? 'sr-range-ok' : 'sr-range-bad');
        badge.innerHTML = ok ? '✓ Dentro' : '✗ Fuera';

        // Show bar only when both limits are finite and valid
        if (lmin !== null && lmax !== null && lmax > lmin && barWrap) {
            barWrap.style.display = 'block';
            const pad      = (lmax - lmin) * 0.35;
            const dispMin  = lmin - pad;
            const dispMax  = lmax + pad;
            const dispRange= dispMax - dispMin;

            const validLeft  = ((lmin - dispMin) / dispRange) * 100;
            const validWidth = ((lmax - lmin)    / dispRange) * 100;
            barValid.style.left  = validLeft  + '%';
            barValid.style.width = validWidth + '%';
            barValid.className   = 'sr-range-bar-valid' + (ok ? '' : ' bad');

            const pinPct = Math.min(99, Math.max(1, ((result - dispMin) / dispRange) * 100));
            barPin.style.left  = pinPct + '%';
            barPin.className   = 'sr-range-bar-pin ' + (ok ? 'ok' : 'bad');
        } else if (barWrap) {
            barWrap.style.display = 'none';
        }
    }

    document.getElementById('measBody').addEventListener('input', function (e) {
        const row = e.target.closest('tr');
        if (row) calcInRange(row);
    });

    // Recalc on load
    document.querySelectorAll('#measBody tr').forEach(calcInRange);

    document.getElementById('btnAddRow').addEventListener('click', function () {
        const idx  = rowCount++;
        const tbody = document.getElementById('measBody');
        const tr = document.createElement('tr');
        tr.dataset.idx = idx;
        tr.innerHTML = `
            <td class="sr-meas-num">
                <span class="sr-meas-num-short">${idx + 1}</span>
                <span class="sr-meas-num-full">Medición ${idx + 1}</span>
            </td>
            <td data-label="Parámetro"><input type="text" name="measurements[${idx}][parameter]" class="sr-input" required placeholder="Ej: Dureza Total"></td>
            <td data-label="Unidad"><input type="text" name="measurements[${idx}][unit]"      class="sr-input" placeholder="Mg/l"></td>
            <td data-label="Resultado"><input type="text" name="measurements[${idx}][result]"    class="sr-input sr-result" placeholder="0.0"></td>
            <td data-label="Límite Mín."><input type="number" step="any" name="measurements[${idx}][limit_min]" class="sr-input sr-lmin" placeholder="0"></td>
            <td data-label="Límite Máx."><input type="number" step="any" name="measurements[${idx}][limit_max]" class="sr-input sr-lmax" placeholder="0"></td>
            <td data-label="Rango" style="min-width:110px;">
                <span class="sr-range-badge sr-range-nd">— S/L</span>
                <div class="sr-range-bar-wrap" style="display:none;">
                    <div class="sr-range-bar-track">
                        <div class="sr-range-bar-valid"></div>
                        <div class="sr-range-bar-pin"></div>
                    </div>
                </div>
            </td>
            <td class="sr-meas-del"><button type="button" class="sr-btn-del-row" onclick="removeRow(this)">✕</button></td>`;
        tbody.appendChild(tr);
    });

    window.removeRow = function (btn) {
        const tbody = document.getElementById('measBody');
        if (tbody.querySelectorAll('tr').length <= 1) return;
        btn.closest('tr').remove();
        // Re-number first column
        tbody.querySelectorAll('tr').forEach((tr, i) => {
            tr.querySelector('.sr-meas-num-short').textContent = i + 1;
            tr.querySelector('.sr-meas-num-full').textContent = 'Medición ' + (i + 1);
        });
    };
    @endif

    @if($report->usesCustomForm())
    // ── Custom fields ─────────────────────────────────────────────────────────
    let cfCount = {{ max(count($report->customFields), 1) }};

    document.getElementById('btnAddCustom').addEventListener('click', function () {
        const idx  = cfCount++;
        const wrap = document.getElementById('customFieldsWrap');
        const div  = document.createElement('div');
        div.className = 'sr-custom-row';
        div.dataset.idx = idx;
        div.innerHTML = `
            <div>
                <label class="sr-label">Nombre del campo <span style="color:#ff6213">*</span></label>
                <input type="text" name="custom_fields[${idx}][field_name]" class="sr-input" required placeholder="Ej: Temperatura">
            </div>
            <div>
                <label class="sr-label">Tipo</label>
                <select name="custom_fields[${idx}][field_type]" class="sr-input">
                    <option value="text">Texto</option>
                    <option value="number">Número</option>
                    <option value="date">Fecha</option>
                    <option value="boolean">Sí/No</option>
                    <option value="list">Lista</option>
                </select>
            </div>
            <div>
                <label class="sr-label">Valor</label>
                <input type="text" name="custom_fields[${idx}][field_value]" class="sr-input" placeholder="Valor del campo">
            </div>
            <div style="padding-top:22px;">
                <button type="button" class="sr-btn-del-row" onclick="this.closest('.sr-custom-row').remove()">✕</button>
            </div>`;
        wrap.appendChild(div);
    });
    @endif
})();
</script>
@endpush
