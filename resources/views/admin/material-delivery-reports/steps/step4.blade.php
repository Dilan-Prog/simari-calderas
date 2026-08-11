@extends('admin.layouts.master')
@section('title', 'Reporte ' . $report->report_number . ' — Paso 4')

@push('styles')
    @vite('resources/css/material-delivery-reports.css')
@endpush

@section('content')
<div class="mdr-create-wrap mdr-page-step4">

    <div style="font-size:12px; color:#6B7280; margin-bottom:16px; display:flex; align-items:center; gap:6px;">
        <a href="{{ route('admin.material-delivery-reports.index') }}" style="color:#6B7280; text-decoration:none;">Reportes</a>
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        <span style="color:#374151; font-weight:500;">{{ $report->report_number }} — Paso 4</span>
    </div>

    <div class="mdr-progress">
        @php $stepLabels = ['Datos Generales','Líneas Entregadas','Observaciones','Evidencia Fotográfica','Resumen','Firma']; @endphp
        @foreach($stepLabels as $i => $label)
            @php $n = $i + 1; $cls = $n < 4 ? 'done' : ($n === 4 ? 'active' : ''); @endphp
            <div class="mdr-step-item {{ $cls }}">
                <div class="mdr-step-circle">@if($n < 4) ✓ @else {{ $n }} @endif</div>
                <span class="mdr-step-label">{{ $label }}</span>
            </div>
        @endforeach
    </div>

    @if(session('success'))
        <div style="background:#F0FDF4; border:1px solid #BBF7D0; border-radius:6px; padding:10px 16px; margin-bottom:16px; font-size:13px; color:#16A34A;">
            {{ session('success') }}
        </div>
    @endif

    @php
        $isSigned = $report->status === 'signed';
        $isAdmin = auth()->user()->isAdmin();
        // Reporte firmado: solo un admin puede seguir tocando imágenes (agregar
        // o quitar) aquí. Cualquier otro usuario obtiene una vista de solo lectura.
        $canManageImages = !$isSigned || $isAdmin;
    @endphp

    @if($isSigned)
        @if($isAdmin)
            <div style="background:#FFFBEB; border:1px solid #FDE68A; border-radius:6px; padding:10px 16px; margin-bottom:16px; font-size:13px; color:#92400E;">
                Este reporte ya está firmado. Como administrador puedes seguir agregando imágenes de evidencia; el resto del contenido ya no se puede modificar.
            </div>
        @else
            <div style="background:#F3F4F6; border:1px solid #E5E7EB; border-radius:6px; padding:10px 16px; margin-bottom:16px; font-size:13px; color:#4B5563;">
                Este reporte ya está firmado y no se pueden agregar ni eliminar imágenes.
            </div>
        @endif
    @endif

    <div class="mdr-form-card">
        <div class="mdr-form-header">
            <h2>Paso 4 — Evidencia Fotográfica</h2>
            <p>Adjunta imágenes de la entrega realizada. Puedes subir varias a la vez.</p>
        </div>

        <div class="mdr-form-body">

            {{-- Existing images --}}
            @if($images->count())
                <p style="font-size:13px; font-weight:500; color:#374151; margin:0 0 12px;">
                    Imágenes guardadas ({{ $images->count() }})
                </p>
                <div class="mdr-img-grid" id="savedGrid">
                    @foreach($images as $img)
                        <div class="mdr-img-item">
                            <img src="{{ $img->url }}" alt="{{ $img->caption ?? 'Evidencia' }}">
                            @if($canManageImages)
                                <form method="POST"
                                      action="{{ route('admin.material-delivery-reports.images.destroy', [$report, $img]) }}"
                                      onsubmit="return confirm('¿Eliminar esta imagen?');"
                                      style="display:contents;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="mdr-img-delete" title="Eliminar">×</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
                <hr style="border:none; border-top:1px solid #F3F4F6; margin:20px 0;">
            @endif

            {{-- Upload new images --}}
            @if($canManageImages)
            <form method="POST"
                  action="{{ route('admin.material-delivery-reports.save-step', [$report, 4]) }}"
                  enctype="multipart/form-data"
                  id="uploadForm">
                @csrf

                {{-- Botones cámara/galería (solo visible en mobile). Ninguno lleva
                     name="images[]": solo alimentan el acumulador JS de abajo, que
                     sincroniza todo hacia #imageInput (el único input que se envía). --}}
                <div class="mdr-mobile-camera-section">
                    <label class="mdr-camera-btn-label">
                        <input type="file" accept="image/*" capture="environment"
                               class="mdr-hidden-file-input" id="cameraInput">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3z"/>
                            <circle cx="12" cy="13" r="3"/>
                        </svg>
                        📷 Tomar foto
                    </label>
                    <label class="mdr-gallery-btn-label">
                        <input type="file" accept="image/*" multiple
                               class="mdr-hidden-file-input" id="galleryInput">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                            <circle cx="9" cy="9" r="2"/>
                            <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                        </svg>
                        🖼️ De galería
                    </label>
                </div>

                <div class="mdr-upload-zone" id="uploadZone">
                    <input type="file" name="images[]" id="imageInput" multiple accept="image/*">
                    <div class="mdr-upload-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                            <circle cx="9" cy="9" r="2"/>
                            <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                        </svg>
                    </div>
                    <p class="mdr-upload-text">Haz clic o arrastra imágenes aquí</p>
                    <p class="mdr-upload-hint">JPG, PNG, WEBP · Máximo 5 MB por imagen</p>
                </div>

                {{-- JS preview of selected files --}}
                <div id="previewGrid" class="mdr-img-grid" style="display:none;"></div>

                <div class="mdr-form-footer" style="padding:16px 0 0; border-top:none; margin-top:20px;">
                    @if($isSigned)
                        <a href="{{ route('admin.material-delivery-reports.show', $report) }}" class="mdr-btn-outline">← Volver al reporte</a>
                        <button type="submit" class="mdr-btn-primary">Agregar imágenes</button>
                    @else
                        <a href="{{ route('admin.material-delivery-reports.step', [$report, 3]) }}" class="mdr-btn-outline">← Anterior</a>
                        <div style="display:flex; gap:12px;">
                            <button type="submit" id="skipImagesBtn" class="mdr-btn-outline">Colocar después</button>
                            <button type="submit" class="mdr-btn-primary">Guardar y continuar →</button>
                        </div>
                    @endif
                </div>
            </form>
            @endif

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const input        = document.getElementById('imageInput');
    const zone         = document.getElementById('uploadZone');
    const previewGrid  = document.getElementById('previewGrid');
    const cameraInput  = document.getElementById('cameraInput');
    const galleryInput = document.getElementById('galleryInput');

    // Reporte firmado visto por alguien que no es admin: el formulario de
    // subida no se renderiza (vista de solo lectura), no hay nada que conectar.
    if (!input) return;

    // Cámara/galería/dropzone alimentan un único acumulador que se sincroniza
    // hacia #imageInput, el único input realmente enviado como images[].
    let acc = new DataTransfer();
    let renderGen = 0;

    function syncInput() {
        input.files = acc.files;
    }

    function renderPreviews() {
        previewGrid.innerHTML = '';
        const files = acc.files;
        if (!files.length) { previewGrid.style.display = 'none'; return; }
        previewGrid.style.display = 'grid';
        const gen = ++renderGen;
        Array.from(files).forEach(function (file, index) {
            const reader = new FileReader();
            reader.onload = function (e) {
                if (gen !== renderGen) return;
                const wrap = document.createElement('div');
                wrap.className = 'mdr-img-item mdr-img-item-new';
                wrap.innerHTML = '<img src="' + e.target.result + '" alt="">'
                               + '<span class="mdr-img-new-badge">Nueva</span>';
                const delBtn = document.createElement('button');
                delBtn.type = 'button';
                delBtn.className = 'mdr-img-delete';
                delBtn.title = 'Quitar';
                delBtn.textContent = '×';
                delBtn.addEventListener('click', function () {
                    const rest = new DataTransfer();
                    Array.from(acc.files).forEach(function (f, i) {
                        if (i !== index) rest.items.add(f);
                    });
                    acc = rest;
                    syncInput();
                    renderPreviews();
                });
                wrap.appendChild(delBtn);
                previewGrid.appendChild(wrap);
            };
            reader.readAsDataURL(file);
        });
    }

    function addFiles(fileList) {
        Array.from(fileList).forEach(function (f) { acc.items.add(f); });
        syncInput();
        renderPreviews();
    }

    input.addEventListener('change', function () {
        addFiles(this.files);
    });

    if (cameraInput) {
        cameraInput.addEventListener('change', function () {
            addFiles(this.files);
            this.value = '';
        });
    }
    if (galleryInput) {
        galleryInput.addEventListener('change', function () {
            addFiles(this.files);
            this.value = '';
        });
    }

    const skipBtn = document.getElementById('skipImagesBtn');
    if (skipBtn) {
        skipBtn.addEventListener('click', function () {
            // "Colocar después": omite subir imágenes ahora y avanza al
            // siguiente paso. Limpia el acumulador antes de que este submit
            // (a la misma ruta save-step) vaya sin imágenes.
            acc = new DataTransfer();
            syncInput();
        });
    }

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
        if (e.dataTransfer.files.length) {
            addFiles(e.dataTransfer.files);
        }
    });
})();
</script>
@endpush
