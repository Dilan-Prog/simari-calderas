@extends('admin.layouts.master')
@section('title', 'Reporte ' . $report->report_number . ' — Paso 4')

@push('styles')
    @vite('resources/css/service-reports.css')
@endpush

@section('content')
<div class="sr-create-wrap sr-page-step4">

    <div style="font-size:12px; color:#6B7280; margin-bottom:16px; display:flex; align-items:center; gap:6px;">
        <a href="{{ route('admin.service-reports.index') }}" style="color:#6B7280; text-decoration:none;">Reportes</a>
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        <span style="color:#374151; font-weight:500;">{{ $report->report_number }} — Paso 4</span>
    </div>

    <div class="sr-progress">
        @php $stepLabels = ['Datos Generales','Mediciones / Actividades','Observaciones','Evidencia','Resumen','Firma']; @endphp
        @foreach($stepLabels as $i => $label)
            @php $n = $i + 1; $cls = $n < 4 ? 'done' : ($n === 4 ? 'active' : ''); @endphp
            <div class="sr-step-item {{ $cls }}">
                <div class="sr-step-circle">@if($n < 4) ✓ @else {{ $n }} @endif</div>
                <span class="sr-step-label">{{ $label }}</span>
            </div>
        @endforeach
    </div>

    @if(session('success'))
        <div style="background:#F0FDF4; border:1px solid #BBF7D0; border-radius:6px; padding:10px 16px; margin-bottom:16px; font-size:13px; color:#16A34A;">
            {{ session('success') }}
        </div>
    @endif

    <div class="sr-form-card">
        <div class="sr-form-header">
            <h2>Paso 4 — Evidencia Fotográfica</h2>
            <p>Adjunta imágenes del servicio realizado. Puedes subir varias a la vez.</p>
        </div>

        <div class="sr-form-body">

            {{-- Botones cámara/galería (solo visible en mobile) --}}
            <div class="sr-mobile-camera-section">
                <label class="sr-camera-btn-label">
                    <input type="file" name="images[]" accept="image/*" capture="environment"
                           class="sr-hidden-file-input" id="cameraInput">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3z"/>
                        <circle cx="12" cy="13" r="3"/>
                    </svg>
                    📷 Tomar foto
                </label>
                <label class="sr-gallery-btn-label">
                    <input type="file" name="images[]" accept="image/*" multiple
                           class="sr-hidden-file-input" id="galleryInput">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                        <circle cx="9" cy="9" r="2"/>
                        <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                    </svg>
                    🖼️ De galería
                </label>
            </div>

            {{-- Existing images --}}
            @if($images->count())
                <p style="font-size:13px; font-weight:500; color:#374151; margin:0 0 12px;">
                    Imágenes guardadas ({{ $images->count() }})
                </p>
                <div class="sr-img-grid" id="savedGrid">
                    @foreach($images as $img)
                        <div class="sr-img-item">
                            <img src="{{ $img->url }}" alt="Evidencia">
                            <form method="POST"
                                  action="{{ route('admin.service-reports.images.destroy', [$report, $img]) }}"
                                  onsubmit="return confirm('¿Eliminar esta imagen?');"
                                  style="display:contents;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="sr-img-delete" title="Eliminar">×</button>
                            </form>
                        </div>
                    @endforeach
                </div>
                <hr style="border:none; border-top:1px solid #F3F4F6; margin:20px 0;">
            @endif

            {{-- Upload new images --}}
            <form method="POST"
                  action="{{ route('admin.service-reports.save-step', [$report, 4]) }}"
                  enctype="multipart/form-data"
                  id="uploadForm">
                @csrf

                <div class="sr-upload-zone" id="uploadZone">
                    <input type="file" name="images[]" id="imageInput" multiple accept="image/*">
                    <div class="sr-upload-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                            <circle cx="9" cy="9" r="2"/>
                            <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                        </svg>
                    </div>
                    <p class="sr-upload-text">Haz clic o arrastra imágenes aquí</p>
                    <p class="sr-upload-hint">JPG, PNG, WEBP · Máximo 5 MB por imagen</p>
                </div>

                {{-- JS preview of selected files --}}
                <div id="previewGrid" class="sr-img-grid" style="display:none;"></div>

                <div class="sr-form-footer" style="padding:16px 0 0; border-top:none; margin-top:20px;">
                    <a href="{{ route('admin.service-reports.step', [$report, 3]) }}" class="sr-btn-outline">← Anterior</a>
                    <button type="submit" class="sr-btn-primary">Guardar y continuar →</button>
                </div>
            </form>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const input      = document.getElementById('imageInput');
    const zone       = document.getElementById('uploadZone');
    const previewGrid = document.getElementById('previewGrid');

    function showPreviews(files) {
        previewGrid.innerHTML = '';
        if (!files.length) { previewGrid.style.display = 'none'; return; }
        previewGrid.style.display = 'grid';
        Array.from(files).forEach(function (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                const wrap = document.createElement('div');
                wrap.className = 'sr-img-item sr-img-item-new';
                wrap.innerHTML = '<img src="' + e.target.result + '" alt="">'
                               + '<span class="sr-img-new-badge">Nueva</span>';
                previewGrid.appendChild(wrap);
            };
            reader.readAsDataURL(file);
        });
    }

    input.addEventListener('change', function () {
        showPreviews(this.files);
    });

    // Mobile camera/gallery inputs
    var cameraInput  = document.getElementById('cameraInput');
    var galleryInput = document.getElementById('galleryInput');
    function handleMobileFiles(files) { showPreviews(files); }
    if (cameraInput)  cameraInput.addEventListener('change',  function () { handleMobileFiles(this.files); });
    if (galleryInput) galleryInput.addEventListener('change', function () { handleMobileFiles(this.files); });

    zone.addEventListener('dragover', function (e) {
        e.preventDefault();
        zone.classList.add('drag-over');
    });
    zone.addEventListener('dragleave', function () {
        zone.classList.remove('drag-over');
    });
    zone.addEventListener('drop', function (e) {
        e.preventDefault();
        zone.classList.remove('drag-over');
        const dt = e.dataTransfer;
        if (dt.files.length) {
            input.files = dt.files;
            showPreviews(dt.files);
        }
    });
})();
</script>
@endpush
