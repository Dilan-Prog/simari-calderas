@extends('admin.layouts.master')
@section('title', 'Reporte ' . $report->report_number)

@push('styles')
<style>
    .sr-show-wrap { font-family: 'Inter', sans-serif; background: #F8F9FA; flex: 1; overflow-y: auto; padding: 32px 32px 48px; }

    /* Header */
    .sr-show-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 24px; }
    .sr-show-title  { font-size: 22px; font-weight: 700; color: #111827; margin: 0 0 4px; }
    .sr-show-meta   { font-size: 13px; color: #6B7280; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .sr-show-actions { display: flex; gap: 8px; flex-shrink: 0; }

    .sr-badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 4px;
                font-size: 12px; font-weight: 500; border: 1px solid; white-space: nowrap; }
    .sr-badge--draft       { background:#F3F4F6; color:#6B7280; border-color:#D1D5DB; }
    .sr-badge--in-progress { background:#EFF6FF; color:#3B82F6; border-color:#BFDBFE; }
    .sr-badge--completed   { background:#F0FDF4; color:#16A34A; border-color:#BBF7D0; }
    .sr-badge--signed      { background:#F5F3FF; color:#7C3AED; border-color:#DDD6FE; }

    /* Cards */
    .sr-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,.06); margin-bottom: 16px; overflow: hidden; }
    .sr-card-header { padding: 14px 20px; background: #F9FAFB; border-bottom: 1px solid #F3F4F6;
                      font-size: 13px; font-weight: 600; color: #374151; }
    .sr-card-body   { padding: 20px; }

    .sr-dl { display: grid; grid-template-columns: 180px 1fr; gap: 10px 16px; font-size: 13px; }
    .sr-dt { color: #6B7280; }
    .sr-dd { color: #111827; font-weight: 500; margin: 0; }

    /* Measurements */
    .sr-mini-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .sr-mini-table thead th { background: #1A2535; color: #D1D5DC; padding: 10px 16px; text-align: left;
                               font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; }
    .sr-mini-table tbody td { padding: 10px 16px; border-bottom: 1px solid #F3F4F6; color: #374151; vertical-align: middle; }
    .sr-mini-table tbody tr:last-child td { border-bottom: none; }
    .sr-dot { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; }
    .sr-dot::before { content: ''; display: inline-block; width: 8px; height: 8px; border-radius: 50%; }
    .sr-dot.ok  { color: #16A34A; } .sr-dot.ok::before  { background: #16A34A; }
    .sr-dot.bad { color: #DC2626; } .sr-dot.bad::before { background: #DC2626; }
    .sr-dot.nd  { color: #9CA3AF; } .sr-dot.nd::before  { background: #D1D5DB; }

    .sr-check-list { display: flex; flex-wrap: wrap; gap: 8px; }
    .sr-check-chip { font-size: 12px; padding: 4px 10px; border-radius: 4px; background: #F0FDF4; color: #16A34A; border: 1px solid #BBF7D0; }

    /* Images grid */
    .sr-photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; }
    .sr-photo-item { border-radius: 8px; overflow: hidden; border: 1px solid #E5E7EB; aspect-ratio: 1 / 1;
                     cursor: pointer; transition: box-shadow .15s; }
    .sr-photo-item:hover { box-shadow: 0 0 0 2px #ff6213; }
    .sr-photo-item img { width: 100%; height: 100%; object-fit: cover; display: block; }

    /* Lightbox */
    .sr-lightbox { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.85);
                   z-index: 9999; align-items: center; justify-content: center; }
    .sr-lightbox.open { display: flex; }
    .sr-lightbox img { max-width: 90vw; max-height: 90vh; border-radius: 4px; }
    .sr-lightbox-close { position: absolute; top: 20px; right: 24px; color: #fff; font-size: 28px;
                          cursor: pointer; background: none; border: none; line-height: 1; }

    /* Signature */
    .sr-signature-img { max-height: 120px; border: 1px solid #E5E7EB; border-radius: 6px; padding: 8px; background: #fff; }
    .sr-signer-info { font-size: 13px; color: #374151; margin-top: 12px; }
    .sr-signer-info strong { display: block; font-size: 14px; color: #111827; }

    /* Buttons */
    .sr-btn { height: 36px; padding: 0 16px; border-radius: 6px; font-size: 13px; font-family: 'Inter', sans-serif;
               cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; border: none; transition: background .15s; }
    .sr-btn-primary  { background: #ff6213; color: #fff; } .sr-btn-primary:hover  { background: #de4a00; color: #fff; }
    .sr-btn-outline  { background: #fff; color: #374151; border: 1px solid #D1D5DB; } .sr-btn-outline:hover { background: #F9FAFB; color: #374151; }
    .sr-btn-sign     { background: #7C3AED; color: #fff; } .sr-btn-sign:hover { background: #6D28D9; color: #fff; }
    .sr-btn-danger   { background: #fff; color: #DC2626; border: 1px solid #FECACA; } .sr-btn-danger:hover { background: #FEF2F2; color: #DC2626; }

    @media (max-width: 768px) {
        .sr-show-wrap { padding: 16px; }
        .sr-show-header { flex-direction: column; }
        .sr-dl { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="sr-show-wrap">

    @if(session('success'))
        <div style="background:#F0FDF4; border:1px solid #BBF7D0; border-radius:6px; padding:12px 16px; margin-bottom:16px; font-size:13px; color:#16A34A;">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background:#FEF2F2; border:1px solid #FECACA; border-radius:6px; padding:12px 16px; margin-bottom:16px; font-size:13px; color:#DC2626;">
            {{ session('error') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="sr-show-header">
        <div>
            <div style="font-size:12px; color:#6B7280; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                <a href="{{ route('admin.service-reports.index') }}" style="color:#6B7280; text-decoration:none;">Reportes de Servicio</a>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                <span style="color:#374151;">{{ $report->report_number }}</span>
            </div>
            <h1 class="sr-show-title">
                {{ $report->report_number }}
                <span class="sr-badge {{ $report->status_color }}" style="font-size:13px; vertical-align:middle; margin-left:8px;">{{ $report->status_label }}</span>
            </h1>
            <div class="sr-show-meta">
                <span>{{ $report->service_type_label }}</span>
                <span>·</span>
                <span>{{ $report->service_date->translatedFormat('d \d\e F \d\e Y') }}</span>
                @if($report->customer_company)
                    <span>·</span>
                    <span>{{ $report->customer_name }} / {{ $report->customer_company }}</span>
                @else
                    <span>·</span>
                    <span>{{ $report->customer_name }}</span>
                @endif
            </div>
        </div>
        <div class="sr-show-actions">
            @if($report->isEditable())
                <a href="{{ route('admin.service-reports.edit', $report) }}" class="sr-btn sr-btn-outline">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                    Editar
                </a>
            @endif
            <button type="button" class="sr-btn sr-btn-outline" onclick="openPdfModal()">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M12 18v-6"/><path d="m9 15 3 3 3-3"/></svg>
                Vista previa PDF
            </button>
            @if($report->isEditable())
                <a href="{{ route('admin.service-reports.step', [$report, 6]) }}" class="sr-btn sr-btn-sign">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                    Firmar
                </a>
            @endif
            @if($report->isDeletable())
                <form method="POST" action="{{ route('admin.service-reports.destroy', $report) }}" style="display:inline;"
                    onsubmit="return confirm('¿Eliminar este reporte? Esta acción no se puede deshacer.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="sr-btn sr-btn-danger">Eliminar</button>
                </form>
            @endif
        </div>
    </div>

    {{-- Datos generales --}}
    <div class="sr-card">
        <div class="sr-card-header">Datos Generales</div>
        <div class="sr-card-body">
            <dl class="sr-dl">
                <dt class="sr-dt">Folio</dt>
                <dd class="sr-dd">{{ $report->report_number }}</dd>
                <dt class="sr-dt">Estado</dt>
                <dd class="sr-dd"><span class="sr-badge {{ $report->status_color }}">{{ $report->status_label }}</span></dd>
                <dt class="sr-dt">Tipo de Servicio</dt>
                <dd class="sr-dd">{{ $report->service_type_label }}</dd>
                <dt class="sr-dt">Fecha de Servicio</dt>
                <dd class="sr-dd">{{ $report->service_date->translatedFormat('d \d\e F \d\e Y') }}</dd>
                <dt class="sr-dt">Encargado</dt>
                <dd class="sr-dd">{{ $report->assigned_user_name }}</dd>
                @if($report->week_number)
                    <dt class="sr-dt">Semana</dt>
                    <dd class="sr-dd">{{ $report->week_number }}</dd>
                @endif
                @if($report->location)
                    <dt class="sr-dt">Ubicación</dt>
                    <dd class="sr-dd">{{ $report->location }}</dd>
                @endif
                <dt class="sr-dt">Creado por</dt>
                <dd class="sr-dd">{{ $report->createdBy?->first_name }} {{ $report->createdBy?->last_name }}</dd>
                <dt class="sr-dt">Fecha de creación</dt>
                <dd class="sr-dd">{{ $report->created_at->format('d/m/Y H:i') }}</dd>
            </dl>
        </div>
    </div>

    {{-- Cliente --}}
    <div class="sr-card">
        <div class="sr-card-header">Cliente</div>
        <div class="sr-card-body">
            <dl class="sr-dl">
                <dt class="sr-dt">Nombre</dt>
                <dd class="sr-dd">{{ $report->customer_name }}</dd>
                @if($report->customer_company)
                    <dt class="sr-dt">Empresa</dt>
                    <dd class="sr-dd">{{ $report->customer_company }}</dd>
                @endif
                @if($report->customer_rfc)
                    <dt class="sr-dt">RFC</dt>
                    <dd class="sr-dd">{{ $report->customer_rfc }}</dd>
                @endif
                @if($report->customer_phone)
                    <dt class="sr-dt">Teléfono</dt>
                    <dd class="sr-dd">{{ $report->customer_phone }}</dd>
                @endif
            </dl>
        </div>
    </div>

    {{-- Mediciones --}}
    @if($report->usesMeasurementsForm() && $report->measurements->isNotEmpty())
        <div class="sr-card">
            <div class="sr-card-header">Mediciones ({{ $report->measurements->count() }})</div>
            <div style="overflow-x:auto;">
                <table class="sr-mini-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Parámetro</th>
                            <th>Unidad</th>
                            <th>Resultado</th>
                            <th>Límite Mín.</th>
                            <th>Límite Máx.</th>
                            <th>Rango</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report->measurements as $i => $m)
                            <tr>
                                <td style="color:#9CA3AF;">{{ $i + 1 }}</td>
                                <td><strong>{{ $m->parameter }}</strong></td>
                                <td>{{ $m->unit ?? '—' }}</td>
                                <td><strong>{{ $m->result ?? '—' }}</strong></td>
                                <td style="color:#6B7280;">{{ $m->limit_min !== null ? $m->limit_min : '—' }}</td>
                                <td style="color:#6B7280;">{{ $m->limit_max !== null ? $m->limit_max : '—' }}</td>
                                <td>
                                    @if($m->in_range === true) <span class="sr-dot ok">Dentro del rango</span>
                                    @elseif($m->in_range === false) <span class="sr-dot bad">Fuera del rango</span>
                                    @else <span class="sr-dot nd">N/D</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Actividades --}}
    @if($report->usesActivityForm() && $report->activity)
        <div class="sr-card">
            <div class="sr-card-header">Actividades Realizadas</div>
            <div class="sr-card-body">
                <p style="font-size:13px; color:#374151; white-space:pre-wrap; margin:0 0 16px;">{{ $report->activity->content }}</p>
                @if($report->activity->systems_checked && count($report->activity->systems_checked))
                    <p style="font-size:12px; font-weight:600; color:#6B7280; text-transform:uppercase; letter-spacing:.05em; margin:0 0 8px;">Sistemas Revisados</p>
                    <div class="sr-check-list">
                        @foreach($report->activity->systems_checked as $sys)
                            <span class="sr-check-chip">{{ $sys }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Custom fields --}}
    @if($report->usesCustomForm() && $report->customFields->isNotEmpty())
        <div class="sr-card">
            <div class="sr-card-header">Campos Personalizados</div>
            <div class="sr-card-body">
                <dl class="sr-dl">
                    @foreach($report->customFields as $cf)
                        <dt class="sr-dt">{{ $cf->field_name }}</dt>
                        <dd class="sr-dd">{{ $cf->field_value ?? '—' }}</dd>
                    @endforeach
                </dl>
            </div>
        </div>
    @endif

    {{-- Observaciones --}}
    @if($report->observations || $report->recommendations || $report->include_sampling || $report->analyst_name)
        <div class="sr-card">
            <div class="sr-card-header">Observaciones y Recomendaciones</div>
            <div class="sr-card-body">
                @if($report->observations)
                    <p style="font-size:12px; font-weight:600; color:#6B7280; text-transform:uppercase; letter-spacing:.05em; margin:0 0 6px;">Observaciones</p>
                    <p style="font-size:13px; color:#374151; white-space:pre-wrap; margin:0 0 20px;">{{ $report->observations }}</p>
                @endif
                @if($report->recommendations)
                    <p style="font-size:12px; font-weight:600; color:#6B7280; text-transform:uppercase; letter-spacing:.05em; margin:0 0 6px;">Recomendaciones</p>
                    <p style="font-size:13px; color:#374151; white-space:pre-wrap; margin:0 0 20px;">{{ $report->recommendations }}</p>
                @endif
                @if($report->include_sampling)
                    <dl class="sr-dl" style="margin-bottom:16px;">
                        <dt class="sr-dt">Fecha de Muestreo</dt>
                        <dd class="sr-dd">
                            {{ str_pad($report->sampling_date_day, 2, '0', STR_PAD_LEFT) }}/{{ str_pad($report->sampling_date_month, 2, '0', STR_PAD_LEFT) }}/{{ $report->sampling_date_year }}
                        </dd>
                    </dl>
                @endif
                @if($report->analyst_name)
                    <dl class="sr-dl">
                        <dt class="sr-dt">Analista</dt>
                        <dd class="sr-dd">{{ $report->analyst_name }}</dd>
                        @if($report->analyst_position)
                            <dt class="sr-dt">Cargo Analista</dt>
                            <dd class="sr-dd">{{ $report->analyst_position }}</dd>
                        @endif
                    </dl>
                @endif
            </div>
        </div>
    @endif

    {{-- Evidencia fotográfica --}}
    @if($report->images->count())
        <div class="sr-card">
            <div class="sr-card-header">Evidencia Fotográfica ({{ $report->images->count() }})</div>
            <div class="sr-card-body">
                <div class="sr-photo-grid">
                    @foreach($report->images as $img)
                        <div class="sr-photo-item" onclick="openLightbox('{{ $img->url }}')">
                            <img src="{{ $img->url }}" alt="Evidencia">
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Firma --}}
    @if($report->status === 'signed' && $report->signature_data)
        <div class="sr-card">
            <div class="sr-card-header">Firma Digital</div>
            <div class="sr-card-body">
                <img src="{{ $report->signature_data }}" class="sr-signature-img" alt="Firma">
                <div class="sr-signer-info">
                    <strong>{{ $report->signature_name }}</strong>
                    <span>{{ $report->signature_position }}</span>
                    @if($report->signature_phone)
                        <span style="display:block; color:#6B7280; font-size:12px;">{{ $report->signature_phone }}</span>
                    @endif
                    <span style="display:block; color:#6B7280; font-size:12px; margin-top:4px;">
                        Firmado el {{ $report->signed_at->format('d/m/Y \a \l\a\s H:i') }}
                    </span>
                </div>
            </div>
        </div>
    @endif

</div>

{{-- Lightbox --}}
<div class="sr-lightbox" id="srLightbox" onclick="closeLightbox()">
    <button class="sr-lightbox-close" onclick="closeLightbox()">×</button>
    <img src="" id="srLightboxImg" alt="Evidencia" onclick="event.stopPropagation()">
</div>

{{-- Modal PDF --}}
<div id="sr-pdf-modal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,.65); align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:8px; width:90vw; max-width:960px; height:90vh; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.4);">

        <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 18px; background:#1a1a1a; border-bottom:2px solid #ff6213; flex-shrink:0;">
            <span style="color:#fff; font-size:14px; font-weight:600;">
                Vista previa — {{ $report->report_number }}
            </span>
            <div style="display:flex; gap:10px; align-items:center;">
                <a href="{{ route('admin.service-reports.download-pdf', $report) }}"
                   style="display:inline-flex; align-items:center; gap:6px; background:#ff6213; color:#fff; border:none; padding:7px 14px; border-radius:5px; font-size:12px; font-weight:600; cursor:pointer; text-decoration:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 17V3"/><path d="m6 11 6 6 6-6"/><path d="M19 21H5"/></svg>
                    Descargar PDF
                </a>
                <button onclick="closePdfModal()" style="background:transparent; border:none; cursor:pointer; color:#9CA3AF; font-size:20px; line-height:1; padding:4px 8px;" title="Cerrar">&times;</button>
            </div>
        </div>

        <iframe id="sr-pdf-frame" src="" style="flex:1; border:none; background:#525659;" title="Vista previa PDF"></iframe>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openLightbox(src) {
    document.getElementById('srLightboxImg').src = src;
    document.getElementById('srLightbox').classList.add('open');
}
function closeLightbox() {
    document.getElementById('srLightbox').classList.remove('open');
}

function openPdfModal() {
    const modal = document.getElementById('sr-pdf-modal');
    const frame = document.getElementById('sr-pdf-frame');
    modal.style.display = 'flex';
    if (!frame.src || frame.src === window.location.href) {
        frame.src = '{{ route('admin.service-reports.pdf-preview', $report) }}';
    }
    document.body.style.overflow = 'hidden';
}
function closePdfModal() {
    document.getElementById('sr-pdf-modal').style.display = 'none';
    document.body.style.overflow = '';
}
document.getElementById('sr-pdf-modal').addEventListener('click', function (e) {
    if (e.target === this) closePdfModal();
});
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closePdfModal();
        closeLightbox();
    }
});
</script>
@endpush
