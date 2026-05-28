@extends('admin.layouts.master')
@section('title', 'Reporte ' . $report->report_number . ' — Firma')

@push('styles')
<style>
    .sr-create-wrap { font-family: 'Inter', sans-serif; background: #F8F9FA; min-height: 100%; padding: 32px; }
    .sr-progress { display: flex; align-items: center; background: #fff; border-radius: 8px;
                   box-shadow: 0 1px 2px rgba(0,0,0,.06); padding: 20px 24px; margin-bottom: 24px; }
    .sr-step-item { display: flex; flex-direction: column; align-items: center; flex: 1; position: relative; }
    .sr-step-item:not(:last-child)::after { content: ''; position: absolute; top: 16px; left: 60%; width: 80%; height: 2px; background: #E5E7EB; z-index: 0; }
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

    /* Canvas */
    .sr-canvas-wrap { border: 2px solid #D1D5DB; border-radius: 8px; overflow: hidden;
                       cursor: crosshair; background: #fff; position: relative; }
    .sr-canvas-wrap:focus-within { border-color: #ff6213; }
    #signatureCanvas { display: block; width: 100%; touch-action: none; }
    .sr-canvas-toolbar { padding: 8px 12px; background: #F9FAFB; border-top: 1px solid #F3F4F6;
                          display: flex; align-items: center; justify-content: space-between; }
    .sr-canvas-hint { font-size: 12px; color: #9CA3AF; }
    .sr-btn-clear { height: 30px; padding: 0 14px; border-radius: 6px; border: 1px solid #D1D5DB;
                    background: #fff; font-size: 12px; color: #6B7280; cursor: pointer; transition: background .15s; }
    .sr-btn-clear:hover { background: #FEF2F2; color: #DC2626; border-color: #FECACA; }

    /* Empty state overlay */
    .sr-canvas-empty {
        position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
        pointer-events: none; font-size: 13px; color: #D1D5DB; gap: 8px;
    }
    .sr-canvas-empty.hidden { display: none; }

    .sr-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .sr-label { font-size: 13px; font-weight: 500; color: #374151; display: block; margin-bottom: 6px; }
    .sr-label .sr-req { color: #ff6213; }
    .sr-input { height: 40px; padding: 0 12px; border-radius: 6px; border: 1px solid #D1D5DB;
                font-size: 13px; font-family: 'Inter', sans-serif; color: #111827; background: #fff;
                outline: none; width: 100%; box-sizing: border-box; transition: border-color .15s, box-shadow .15s; }
    .sr-input:focus { border-color: #ff6213; box-shadow: 0 0 0 3px rgba(255,98,19,.12); }
    .sr-field { margin-bottom: 16px; }
    .sr-error { font-size: 12px; color: #DC2626; display: block; margin-top: 4px; }

    .sr-btn-primary { height: 40px; padding: 0 20px; border-radius: 8px; background: #ff6213; color: #fff;
                      font-size: 13px; font-weight: 500; font-family: 'Inter', sans-serif; border: none; cursor: pointer; transition: background .15s; }
    .sr-btn-primary:hover:not(:disabled) { background: #de4a00; }
    .sr-btn-primary:disabled { opacity: .5; cursor: not-allowed; }
    .sr-btn-outline { height: 40px; padding: 0 20px; border-radius: 8px; border: 1px solid #D1D5DB;
                      background: #fff; color: #374151; font-size: 13px; font-family: 'Inter', sans-serif;
                      cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
    .sr-btn-outline:hover { background: #F9FAFB; color: #374151; }

    @media (max-width: 768px) {
        .sr-create-wrap { padding: 16px; }
        .sr-grid-2 { grid-template-columns: 1fr; }
        .sr-step-label { display: none; }
    }
</style>
@endpush

@section('content')
<div class="sr-create-wrap">

    <div style="font-size:12px; color:#6B7280; margin-bottom:16px; display:flex; align-items:center; gap:6px;">
        <a href="{{ route('admin.service-reports.index') }}" style="color:#6B7280; text-decoration:none;">Reportes</a>
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        <span style="color:#374151; font-weight:500;">{{ $report->report_number }} — Firma</span>
    </div>

    <div class="sr-progress">
        @php $stepLabels = ['Datos Generales','Mediciones / Actividades','Observaciones','Evidencia','Resumen','Firma']; @endphp
        @foreach($stepLabels as $i => $label)
            @php $n = $i + 1; $cls = $n < 6 ? 'done' : ($n === 6 ? 'active' : ''); @endphp
            <div class="sr-step-item {{ $cls }}">
                <div class="sr-step-circle">@if($n < 6) ✓ @else {{ $n }} @endif</div>
                <span class="sr-step-label">{{ $label }}</span>
            </div>
        @endforeach
    </div>

    @if($errors->any())
        <div style="background:#FEF2F2; border:1px solid #FECACA; border-radius:6px; padding:12px 16px; margin-bottom:16px; font-size:13px; color:#DC2626;">
            @foreach($errors->all() as $error) <div>{{ $error }}</div> @endforeach
        </div>
    @endif

    <div class="sr-form-card">
        <div class="sr-form-header">
            <h2>Paso 6 — Firma del Responsable</h2>
            <p>Firma en el recuadro con el ratón o con el dedo en dispositivos táctiles</p>
        </div>

        <form method="POST" action="{{ route('admin.service-reports.sign', $report) }}" id="signForm">
            @csrf
            <input type="hidden" name="signature_data" id="signatureData">

            <div class="sr-form-body">

                {{-- Canvas --}}
                <div class="sr-field">
                    <label class="sr-label">Firma <span class="sr-req">*</span></label>
                    <div class="sr-canvas-wrap">
                        <canvas id="signatureCanvas" height="200"></canvas>
                        <div class="sr-canvas-empty" id="canvasEmpty">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                            Firmar aquí
                        </div>
                        <div class="sr-canvas-toolbar">
                            <span class="sr-canvas-hint">Dibuja tu firma en el área superior</span>
                            <button type="button" class="sr-btn-clear" id="btnClear">Limpiar</button>
                        </div>
                    </div>
                </div>

                {{-- Firmante --}}
                <div class="sr-grid-2" style="margin-top:8px;">
                    <div class="sr-field">
                        <label class="sr-label" for="signature_name">Nombre del Firmante <span class="sr-req">*</span></label>
                        <input type="text" id="signature_name" name="signature_name" class="sr-input {{ $errors->has('signature_name') ? 'is-invalid' : '' }}"
                            value="{{ old('signature_name') }}" required maxlength="150">
                        @error('signature_name') <span class="sr-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="sr-field">
                        <label class="sr-label" for="signature_position">Cargo / Puesto <span class="sr-req">*</span></label>
                        <input type="text" id="signature_position" name="signature_position" class="sr-input {{ $errors->has('signature_position') ? 'is-invalid' : '' }}"
                            value="{{ old('signature_position') }}" required maxlength="100">
                        @error('signature_position') <span class="sr-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="sr-field">
                        <label class="sr-label" for="signature_phone">Teléfono</label>
                        <input type="text" id="signature_phone" name="signature_phone" class="sr-input"
                            value="{{ old('signature_phone') }}" maxlength="30">
                    </div>
                </div>

            </div>

            <div class="sr-form-footer">
                <a href="{{ route('admin.service-reports.step', [$report, 5]) }}" class="sr-btn-outline">← Anterior</a>
                <button type="submit" class="sr-btn-primary" id="btnSign" disabled>Firmar y Completar</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const canvas  = document.getElementById('signatureCanvas');
    const ctx     = canvas.getContext('2d');
    const empty   = document.getElementById('canvasEmpty');
    const signBtn = document.getElementById('btnSign');
    const dataIn  = document.getElementById('signatureData');
    let drawing   = false;
    let hasStroke = false;

    // Size canvas to its CSS width
    function resize() {
        const w = canvas.offsetWidth;
        const saved = canvas.toDataURL();
        canvas.width = w;
        if (hasStroke) {
            const img = new Image();
            img.onload = () => ctx.drawImage(img, 0, 0);
            img.src = saved;
        }
    }
    resize();
    window.addEventListener('resize', resize);

    ctx.strokeStyle = '#111827';
    ctx.lineWidth   = 2;
    ctx.lineCap     = 'round';
    ctx.lineJoin    = 'round';

    function getPos(e) {
        const rect = canvas.getBoundingClientRect();
        const src  = e.touches ? e.touches[0] : e;
        return { x: src.clientX - rect.left, y: src.clientY - rect.top };
    }

    function startDraw(e) {
        e.preventDefault();
        drawing = true;
        const p = getPos(e);
        ctx.beginPath();
        ctx.moveTo(p.x, p.y);
    }

    function draw(e) {
        if (!drawing) return;
        e.preventDefault();
        const p = getPos(e);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
        if (!hasStroke) {
            hasStroke = true;
            empty.classList.add('hidden');
            signBtn.disabled = false;
        }
    }

    function stopDraw() { drawing = false; }

    canvas.addEventListener('mousedown',  startDraw);
    canvas.addEventListener('mousemove',  draw);
    canvas.addEventListener('mouseup',    stopDraw);
    canvas.addEventListener('mouseleave', stopDraw);
    canvas.addEventListener('touchstart', startDraw, { passive: false });
    canvas.addEventListener('touchmove',  draw,      { passive: false });
    canvas.addEventListener('touchend',   stopDraw);

    document.getElementById('btnClear').addEventListener('click', function () {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        hasStroke = false;
        empty.classList.remove('hidden');
        signBtn.disabled = true;
        dataIn.value = '';
    });

    document.getElementById('signForm').addEventListener('submit', function (e) {
        if (!hasStroke) {
            e.preventDefault();
            alert('Por favor dibuja tu firma antes de continuar.');
            return;
        }
        dataIn.value = canvas.toDataURL('image/png');
    });
})();
</script>
@endpush
