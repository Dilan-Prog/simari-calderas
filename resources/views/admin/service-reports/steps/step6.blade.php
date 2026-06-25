@extends('admin.layouts.master')
@section('title', 'Reporte ' . $report->report_number . ' — Firma')

@push('styles')
    @vite('resources/css/service-reports.css')
@endpush

@section('content')
<div class="sr-create-wrap sr-page-step6">

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
                    <span class="sr-mobile-sign-hint">✍️ Firme con el dedo en el área de abajo</span>
                    <div class="sr-mobile-canvas-bar">
                        <button type="button" class="sr-btn-clear" id="btnClearMobile">Limpiar firma</button>
                    </div>
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
            alert('Por favor dibuja tu firma antes de continuar.');
            return;
        }
        dataIn.value = canvas.toDataURL('image/png');
    });
})();
</script>
@endpush
