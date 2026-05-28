@extends('admin.layouts.master')
@section('title', 'Reporte ' . $report->report_number . ' — Paso 2')

@push('styles')
<style>
    .sr-create-wrap { font-family: 'Inter', sans-serif; background: #F8F9FA; min-height: 100%; padding: 32px; }
    .sr-progress { display: flex; align-items: center; background: #fff; border-radius: 8px;
                   box-shadow: 0 1px 2px rgba(0,0,0,.06); padding: 20px 24px; margin-bottom: 24px; }
    .sr-step-item { display: flex; flex-direction: column; align-items: center; flex: 1; position: relative; }
    .sr-step-item:not(:last-child)::after {
        content: ''; position: absolute; top: 16px; left: 60%; width: 80%; height: 2px; background: #E5E7EB; z-index: 0;
    }
    .sr-step-item.done::after { background: #ff6213; }
    .sr-step-circle { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
                      font-size: 13px; font-weight: 600; z-index: 1; border: 2px solid #E5E7EB; background: #fff; color: #9CA3AF; }
    .sr-step-item.active .sr-step-circle { border-color: #ff6213; background: #ff6213; color: #fff; }
    .sr-step-item.done   .sr-step-circle { border-color: #ff6213; background: #fff; color: #ff6213; }
    .sr-step-label { font-size: 11px; color: #9CA3AF; margin-top: 6px; text-align: center; white-space: nowrap; }
    .sr-step-item.active .sr-step-label, .sr-step-item.done .sr-step-label { color: #374151; }

    .sr-form-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,.06); display: flex; flex-direction: column; max-height: 420px; overflow-y: auto; }
    .sr-form-header { padding: 20px 24px; border-bottom: 1px solid #F3F4F6; }
    .sr-form-header h2 { margin: 0 0 4px; font-size: 16px; font-weight: 600; color: #111827; }
    .sr-form-header p  { margin: 0; font-size: 13px; color: #6B7280; }
    .sr-form-body  { padding: 24px; overflow-y: auto; flex: 1; }
    .sr-form-footer { padding: 16px 24px; border-top: 1px solid #F3F4F6; display: flex; justify-content: space-between; gap: 12px; }

    .sr-label { font-size: 13px; font-weight: 500; color: #374151; display: block; margin-bottom: 6px; }
    .sr-input, .sr-select, .sr-textarea {
        height: 40px; padding: 0 12px; border-radius: 6px; border: 1px solid #D1D5DB;
        font-size: 13px; font-family: 'Inter', sans-serif; color: #111827; background: #fff;
        outline: none; width: 100%; box-sizing: border-box; transition: border-color .15s, box-shadow .15s;
    }
    .sr-textarea { height: auto; padding: 10px 12px; resize: vertical; min-height: 160px; }
    .sr-input:focus, .sr-select:focus, .sr-textarea:focus { border-color: #ff6213; box-shadow: 0 0 0 3px rgba(255,98,19,.12); }
    .sr-field { margin-bottom: 16px; }

    /* Measurements table */
    .sr-meas-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .sr-meas-table thead th { background: #1A2535; color: #D1D5DC; font-size: 11px; font-weight: 600;
                               text-transform: uppercase; letter-spacing: .06em; padding: 10px 12px; text-align: left; }
    .sr-meas-table tbody tr { border-bottom: 1px solid #F3F4F6; }
    .sr-meas-table tbody td { padding: 6px 8px; vertical-align: middle; }
    .sr-meas-table .sr-input { height: 34px; font-size: 12px; }
    .sr-meas-in-range   { display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #D1D5DB; }
    .sr-meas-in-range.ok  { background: #16A34A; }
    .sr-meas-in-range.bad { background: #DC2626; }
    .sr-btn-add-row {
        margin-top: 12px; height: 36px; padding: 0 16px; border-radius: 6px; border: 1px dashed #D1D5DB;
        background: #fff; color: #6B7280; font-size: 13px; font-family: 'Inter', sans-serif;
        cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: border-color .15s, color .15s;
    }
    .sr-btn-add-row:hover { border-color: #ff6213; color: #ff6213; }
    .sr-btn-del-row { width: 28px; height: 28px; border-radius: 4px; border: none; background: transparent;
                      color: #9CA3AF; cursor: pointer; display: flex; align-items: center; justify-content: center; }
    .sr-btn-del-row:hover { background: #FEF2F2; color: #DC2626; }

    /* Checkboxes */
    .sr-systems-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
    .sr-checkbox-item { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #374151;
                        padding: 8px 12px; border: 1px solid #E5E7EB; border-radius: 6px; cursor: pointer;
                        transition: border-color .15s, background .15s; }
    .sr-checkbox-item:hover { border-color: #ff6213; background: #FFF7ED; }
    .sr-checkbox-item input[type=checkbox]:checked + span { color: #111827; font-weight: 500; }

    /* Buttons */
    .sr-btn-primary { height: 40px; padding: 0 20px; border-radius: 8px; background: #ff6213; color: #fff;
                      font-size: 13px; font-weight: 500; font-family: 'Inter', sans-serif; border: none; cursor: pointer; transition: background .15s; }
    .sr-btn-primary:hover { background: #de4a00; }
    .sr-btn-outline { height: 40px; padding: 0 20px; border-radius: 8px; border: 1px solid #D1D5DB;
                      background: #fff; color: #374151; font-size: 13px; font-family: 'Inter', sans-serif; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
    .sr-btn-outline:hover { background: #F9FAFB; color: #374151; }

    /* Custom fields */
    .sr-custom-row { display: grid; grid-template-columns: 2fr 1fr 2fr auto; gap: 8px; align-items: end; margin-bottom: 10px; }

    @media (max-width: 768px) {
        .sr-create-wrap { padding: 16px; }
        .sr-systems-grid { grid-template-columns: 1fr 1fr; }
        .sr-custom-row { grid-template-columns: 1fr 1fr; }
        .sr-step-label { display: none; }
    }
</style>
@endpush

@section('content')
<div class="sr-create-wrap">

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
                    <div style="overflow-x:auto;">
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
                                        <td style="color:#9CA3AF; font-size:12px; width:32px; text-align:center;">{{ $idx + 1 }}</td>
                                        <td><input type="text" name="measurements[{{ $idx }}][parameter]" class="sr-input" value="{{ old("measurements.$idx.parameter", $m->parameter) }}" required placeholder="Ej: pH"></td>
                                        <td><input type="text" name="measurements[{{ $idx }}][unit]"      class="sr-input" value="{{ old("measurements.$idx.unit", $m->unit) }}" placeholder="Unidad pH"></td>
                                        <td><input type="text" name="measurements[{{ $idx }}][result]"   class="sr-input sr-result" value="{{ old("measurements.$idx.result", $m->result) }}" placeholder="7.4"></td>
                                        <td><input type="number" step="any" name="measurements[{{ $idx }}][limit_min]" class="sr-input sr-lmin" value="{{ old("measurements.$idx.limit_min", $m->limit_min) }}" placeholder="6.5"></td>
                                        <td><input type="number" step="any" name="measurements[{{ $idx }}][limit_max]" class="sr-input sr-lmax" value="{{ old("measurements.$idx.limit_max", $m->limit_max) }}" placeholder="8.5"></td>
                                        <td style="text-align:center;"><span class="sr-meas-in-range {{ $m->in_range === true ? 'ok' : ($m->in_range === false ? 'bad' : '') }}" id="ir-{{ $idx }}"></span></td>
                                        <td><button type="button" class="sr-btn-del-row" onclick="removeRow(this)">✕</button></td>
                                    </tr>
                                @empty
                                    <tr data-idx="0">
                                        <td style="color:#9CA3AF; font-size:12px; width:32px; text-align:center;">1</td>
                                        <td><input type="text" name="measurements[0][parameter]" class="sr-input" required placeholder="Ej: pH"></td>
                                        <td><input type="text" name="measurements[0][unit]"      class="sr-input" placeholder="Unidad pH"></td>
                                        <td><input type="text" name="measurements[0][result]"    class="sr-input sr-result" placeholder="7.4"></td>
                                        <td><input type="number" step="any" name="measurements[0][limit_min]" class="sr-input sr-lmin" placeholder="6.5"></td>
                                        <td><input type="number" step="any" name="measurements[0][limit_max]" class="sr-input sr-lmax" placeholder="8.5"></td>
                                        <td style="text-align:center;"><span class="sr-meas-in-range" id="ir-0"></span></td>
                                        <td><button type="button" class="sr-btn-del-row" onclick="removeRow(this)">✕</button></td>
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
        const result = parseFloat(row.querySelector('.sr-result').value);
        const lmin   = row.querySelector('.sr-lmin').value;
        const lmax   = row.querySelector('.sr-lmax').value;
        const dot    = row.querySelector('.sr-meas-in-range');
        if (isNaN(result) || (lmin === '' && lmax === '')) { dot.className = 'sr-meas-in-range'; return; }
        const min = lmin !== '' ? parseFloat(lmin) : -Infinity;
        const max = lmax !== '' ? parseFloat(lmax) :  Infinity;
        const ok  = result >= min && result <= max;
        dot.className = 'sr-meas-in-range ' + (ok ? 'ok' : 'bad');
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
            <td style="color:#9CA3AF;font-size:12px;width:32px;text-align:center;">${idx + 1}</td>
            <td><input type="text" name="measurements[${idx}][parameter]" class="sr-input" required placeholder="Ej: Dureza Total"></td>
            <td><input type="text" name="measurements[${idx}][unit]"      class="sr-input" placeholder="Mg/l"></td>
            <td><input type="text" name="measurements[${idx}][result]"    class="sr-input sr-result" placeholder="0.0"></td>
            <td><input type="number" step="any" name="measurements[${idx}][limit_min]" class="sr-input sr-lmin" placeholder="0"></td>
            <td><input type="number" step="any" name="measurements[${idx}][limit_max]" class="sr-input sr-lmax" placeholder="0"></td>
            <td style="text-align:center;"><span class="sr-meas-in-range" id="ir-${idx}"></span></td>
            <td><button type="button" class="sr-btn-del-row" onclick="removeRow(this)">✕</button></td>`;
        tbody.appendChild(tr);
    });

    window.removeRow = function (btn) {
        const tbody = document.getElementById('measBody');
        if (tbody.querySelectorAll('tr').length <= 1) return;
        btn.closest('tr').remove();
        // Re-number first column
        tbody.querySelectorAll('tr').forEach((tr, i) => {
            tr.cells[0].textContent = i + 1;
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
