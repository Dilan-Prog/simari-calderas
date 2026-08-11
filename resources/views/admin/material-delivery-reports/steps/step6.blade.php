@extends('admin.layouts.master')
@section('title', 'Reporte ' . $report->report_number . ' — Firma')

@push('styles')
    @vite('resources/css/material-delivery-reports.css')
@endpush

@section('content')
<div class="mdr-create-wrap mdr-page-step6">

    <div style="font-size:12px; color:#6B7280; margin-bottom:16px; display:flex; align-items:center; gap:6px;">
        <a href="{{ route('admin.material-delivery-reports.index') }}" style="color:#6B7280; text-decoration:none;">Reportes</a>
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        <span style="color:#374151; font-weight:500;">{{ $report->report_number }} — Firma</span>
    </div>

    <div class="mdr-progress">
        @php $stepLabels = ['Datos Generales','Líneas Entregadas','Observaciones','Evidencia Fotográfica','Resumen','Firma']; @endphp
        @foreach($stepLabels as $i => $label)
            @php $n = $i + 1; $cls = $n < 6 ? 'done' : ($n === 6 ? 'active' : ''); @endphp
            <div class="mdr-step-item {{ $cls }}">
                <div class="mdr-step-circle">@if($n < 6) ✓ @else {{ $n }} @endif</div>
                <span class="mdr-step-label">{{ $label }}</span>
            </div>
        @endforeach
    </div>

    @if($errors->any())
        <div style="background:#FEF2F2; border:1px solid #FECACA; border-radius:6px; padding:12px 16px; margin-bottom:16px; font-size:13px; color:#DC2626;">
            @foreach($errors->all() as $error) <div>{{ $error }}</div> @endforeach
        </div>
    @endif

    <div class="mdr-form-card">
        <div class="mdr-form-header">
            <h2>Paso 6 — Firma de Quien Recibe el Material</h2>
            <p>Firme en el recuadro con el ratón o con el dedo en dispositivos táctiles</p>
        </div>

        <form method="POST" action="{{ route('admin.material-delivery-reports.sign', $report) }}" id="signForm">
            @csrf
            <input type="hidden" name="signature_data" id="signatureData">

            <div class="mdr-form-body">

                {{-- Canvas --}}
                <div class="mdr-field">
                    <label class="mdr-label">Firma <span class="mdr-req">*</span></label>
                    <span class="mdr-mobile-sign-hint">✍️ Firme con el dedo en el área de abajo</span>
                    <div class="mdr-mobile-canvas-bar">
                        <button type="button" class="mdr-btn-clear" id="btnClearMobile">Limpiar firma</button>
                    </div>
                    <div class="mdr-canvas-wrap">
                        <canvas id="signatureCanvas" height="200"></canvas>
                        <div class="mdr-canvas-empty" id="canvasEmpty">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                            Firmar aquí
                        </div>
                        <div class="mdr-canvas-toolbar">
                            <span class="mdr-canvas-hint">Dibuja la firma en el área superior</span>
                            <button type="button" class="mdr-btn-clear" id="btnClear">Limpiar</button>
                        </div>
                    </div>
                </div>

                {{-- Quien recibe --}}
                <div class="mdr-grid-2" style="margin-top:8px;">
                    <div class="mdr-field">
                        <label class="mdr-label" for="received_by_name">Nombre de Quien Recibe <span class="mdr-req">*</span></label>
                        <input type="text" id="received_by_name" name="received_by_name" class="mdr-input {{ $errors->has('received_by_name') ? 'is-invalid' : '' }}"
                            value="{{ old('received_by_name') }}" required maxlength="150">
                        @error('received_by_name') <span class="mdr-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="mdr-field">
                        <label class="mdr-label" for="received_by_position">Cargo / Puesto</label>
                        <input type="text" id="received_by_position" name="received_by_position" class="mdr-input {{ $errors->has('received_by_position') ? 'is-invalid' : '' }}"
                            value="{{ old('received_by_position') }}" maxlength="100">
                        @error('received_by_position') <span class="mdr-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="mdr-field">
                        <label class="mdr-label" for="received_by_phone">Teléfono</label>
                        <input type="text" id="received_by_phone" name="received_by_phone" class="mdr-input"
                            value="{{ old('received_by_phone') }}" maxlength="30">
                    </div>
                </div>

            </div>

            <div class="mdr-form-footer">
                <a href="{{ route('admin.material-delivery-reports.step', [$report, 5]) }}" class="mdr-btn-outline">← Anterior</a>
                <button type="submit" class="mdr-btn-primary" id="btnSign" disabled>Firmar y Completar</button>
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

    // El bitmap del canvas debe medir lo mismo que su caja CSS en ambas
    // dimensiones (no solo canvas.width) para que en móvil (min-height:220px
    // vs height="200") el trazo no quede desalineado del dedo. Los estilos
    // del contexto deben reaplicarse tras cada resize porque se resetean.
    function applyCtxStyles() {
        ctx.strokeStyle = '#111827';
        ctx.lineWidth   = 2;
        ctx.lineCap     = 'round';
        ctx.lineJoin    = 'round';
    }
    function resize() {
        const w = canvas.offsetWidth;
        const h = canvas.offsetHeight || 200;
        const saved = canvas.toDataURL();
        canvas.width  = w;
        canvas.height = h;
        applyCtxStyles();
        if (hasStroke) {
            const img = new Image();
            img.onload = () => ctx.drawImage(img, 0, 0);
            img.src = saved;
        }
    }
    resize();
    window.addEventListener('resize', resize);

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

    function clearSignature() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        hasStroke = false;
        empty.classList.remove('hidden');
        signBtn.disabled = true;
        dataIn.value = '';
    }

    document.getElementById('btnClear').addEventListener('click', clearSignature);
    const btnClearMobile = document.getElementById('btnClearMobile');
    if (btnClearMobile) btnClearMobile.addEventListener('click', clearSignature);

    document.getElementById('signForm').addEventListener('submit', function (e) {
        if (!hasStroke) {
            e.preventDefault();
            alert('Por favor dibuja la firma antes de continuar.');
            return;
        }
        dataIn.value = canvas.toDataURL('image/png');
    });
})();
</script>
@endpush
