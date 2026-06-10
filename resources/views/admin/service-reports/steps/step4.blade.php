@extends('admin.layouts.master')
@section('title', 'Reporte ' . $report->report_number . ' — Paso 4')

@push('styles')
<style>
    .sr-create-wrap { font-family: 'Inter', sans-serif; background: #F8F9FA; flex: 1; overflow-y: auto; padding: 32px; }
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

    .sr-form-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,.06); overflow: hidden; }
    .sr-form-header { padding: 20px 24px; border-bottom: 1px solid #F3F4F6; }
    .sr-form-header h2 { margin: 0 0 4px; font-size: 16px; font-weight: 600; color: #111827; }
    .sr-form-header p  { margin: 0; font-size: 13px; color: #6B7280; }
    .sr-form-body  { padding: 24px; }
    .sr-form-footer { padding: 16px 24px; border-top: 1px solid #F3F4F6; display: flex; justify-content: space-between; gap: 12px; }

    /* Upload zone */
    .sr-upload-zone {
        border: 2px dashed #D1D5DB; border-radius: 8px; padding: 32px 24px;
        text-align: center; cursor: pointer; transition: border-color .2s, background .2s;
        position: relative;
    }
    .sr-upload-zone:hover, .sr-upload-zone.drag-over { border-color: #ff6213; background: #FFF7F3; }
    .sr-upload-zone input[type="file"] {
        position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
    }
    .sr-upload-icon { color: #D1D5DB; margin-bottom: 12px; }
    .sr-upload-zone:hover .sr-upload-icon { color: #ff6213; }
    .sr-upload-text { font-size: 14px; font-weight: 500; color: #374151; margin: 0 0 4px; }
    .sr-upload-hint { font-size: 12px; color: #9CA3AF; margin: 0; }

    /* Image grid */
    .sr-img-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; margin-top: 20px; }
    .sr-img-item { position: relative; border-radius: 8px; overflow: hidden;
                   border: 1px solid #E5E7EB; aspect-ratio: 1 / 1; background: #F9FAFB; }
    .sr-img-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .sr-img-item-new { border-style: dashed; border-color: #ff6213; }
    .sr-img-delete { position: absolute; top: 6px; right: 6px; width: 24px; height: 24px;
                     border-radius: 50%; background: rgba(0,0,0,.55); color: #fff; border: none;
                     font-size: 14px; line-height: 1; cursor: pointer; display: flex; align-items: center;
                     justify-content: center; padding: 0; transition: background .15s; }
    .sr-img-delete:hover { background: #DC2626; }
    .sr-img-new-badge { position: absolute; bottom: 6px; left: 6px; font-size: 10px; font-weight: 600;
                        background: #ff6213; color: #fff; padding: 2px 6px; border-radius: 4px; }

    .sr-empty-hint { text-align: center; padding: 12px 0; font-size: 13px; color: #9CA3AF; }

    .sr-btn-primary { height: 40px; padding: 0 20px; border-radius: 8px; background: #ff6213; color: #fff;
                      font-size: 13px; font-weight: 500; font-family: 'Inter', sans-serif; border: none; cursor: pointer; transition: background .15s; }
    .sr-btn-primary:hover { background: #de4a00; }
    .sr-btn-outline { height: 40px; padding: 0 20px; border-radius: 8px; border: 1px solid #D1D5DB;
                      background: #fff; color: #374151; font-size: 13px; font-family: 'Inter', sans-serif;
                      cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
    .sr-btn-outline:hover { background: #F9FAFB; color: #374151; }

    @media (max-width: 768px) {
        .sr-create-wrap { padding: 16px; }
        .sr-step-label { display: none; }
        .sr-img-grid { grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); }
    }
</style>
@endpush

@section('content')
<div class="sr-create-wrap">

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
