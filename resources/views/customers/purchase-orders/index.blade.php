@extends('customers.layouts.master')

@section('title', 'Órdenes de Compra')

@push('styles')
    @vite('resources/css/admin/technical-services.css')
    <style>
        .po-card { background:var(--ts-bg-white); border-radius:8px; box-shadow:var(--ts-shadow-sm); padding:1.25rem; margin-bottom:1.25rem; }
        .po-card__title { font-size:1rem; font-weight:700; color:var(--ts-text-title); margin:0 0 .25rem; }
        .po-card__hint { font-size:.8125rem; color:var(--ts-text-muted); margin:0 0 1rem; }

        .po-drop {
            border:2px dashed var(--ts-border); border-radius:8px; padding:1.5rem 1rem; text-align:center;
            background:#FAFAFA; cursor:pointer; transition:border-color .15s, background .15s;
        }
        .po-drop:hover, .po-drop:focus-visible, .po-drop--over { border-color:var(--ts-primary); background:#FFF7F4; outline:none; }
        .po-drop--error { border-color:#DC2626; background:#FEF2F2; }
        .po-drop svg { color:var(--ts-primary); }
        .po-drop__main { font-size:.875rem; font-weight:600; color:var(--ts-text-title); margin:.5rem 0 .125rem; }
        .po-drop__sub { font-size:.75rem; color:var(--ts-text-muted); margin:0; }
        .po-drop input[type=file] { position:absolute; width:1px; height:1px; opacity:0; pointer-events:none; }

        .po-file { display:none; align-items:center; gap:.625rem; margin-top:.75rem; padding:.625rem .75rem; border:1px solid var(--ts-border); border-radius:6px; background:var(--ts-bg-white); }
        .po-file--on { display:flex; }
        .po-file__name { flex:1; min-width:0; font-size:.8125rem; font-weight:600; color:var(--ts-text-title); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .po-file__size { font-size:.75rem; color:var(--ts-text-muted); white-space:nowrap; }
        .po-file__rm { border:none; background:none; color:var(--ts-text-muted); cursor:pointer; font-size:1rem; line-height:1; padding:.25rem; }
        .po-file__rm:hover { color:#DC2626; }
        .po-clienterr { display:none; margin-top:.5rem; font-size:.8125rem; font-weight:500; color:#DC2626; }
        .po-clienterr--on { display:block; }

        .po-fields { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:.75rem; margin-top:1rem; }
        .po-field--wide { grid-column:1 / -1; }
        .po-label { display:block; font-size:.75rem; font-weight:600; color:var(--ts-text-body); margin-bottom:.25rem; }
        .po-input, .po-select, .po-textarea {
            width:100%; padding:0 .75rem; border:1px solid var(--ts-border); border-radius:6px; font-size:.8125rem;
            color:var(--ts-text-body); background:var(--ts-bg-white); outline:none; font-family:var(--ts-font); box-sizing:border-box;
        }
        .po-input, .po-select { height:36px; }
        .po-textarea { padding:.5rem .75rem; resize:vertical; min-height:64px; }
        .po-input:focus, .po-select:focus, .po-textarea:focus { border-color:var(--ts-primary); }
        .po-actions { display:flex; justify-content:flex-end; margin-top:1rem; }

        .po-btn {
            height:36px; padding:0 1rem; border:none; border-radius:6px; cursor:pointer;
            background:var(--ts-primary); color:#fff; font-size:.8125rem; font-weight:500;
            display:inline-flex; align-items:center; gap:.375rem; text-decoration:none;
        }
        .po-btn:hover { background:var(--ts-primary-hover); color:#fff; }
        .po-btn:disabled { opacity:.6; cursor:not-allowed; }
        .po-btn--ghost { background:var(--ts-bg-white); color:var(--ts-text-body); border:1px solid var(--ts-border); }
        .po-btn--ghost:hover { background:#F9FAFB; color:var(--ts-text-body); }

        .po-filters { display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; margin-bottom:.75rem; }
        .po-search { height:36px; min-width:220px; flex:1 1 220px; max-width:360px; padding:0 .75rem; border:1px solid var(--ts-border); border-radius:6px; font-size:.8125rem; outline:none; font-family:var(--ts-font); }
        .po-search:focus { border-color:var(--ts-primary); }
        .po-count { font-size:.8125rem; color:var(--ts-text-muted); margin:0 0 .75rem; }

        .po-folio { font-weight:600; color:var(--ts-text-title); }
        .po-quote { font-weight:600; color:var(--ts-primary); text-decoration:none; }
        .po-quote:hover { text-decoration:underline; }
        .po-quote small, .po-filecell small { display:block; font-size:.75rem; font-weight:500; color:var(--ts-text-muted); }
        .po-filecell { max-width:260px; }
        .po-filecell span { display:block; font-weight:600; color:var(--ts-text-title); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .po-notes { max-width:240px; font-size:.8125rem; color:var(--ts-text-body); }
        .po-muted { color:var(--ts-text-muted); }
        .po-del { background:none; border:none; padding:0; cursor:pointer; }

        @media (max-width: 900px) { .po-fields { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 640px) { .po-fields { grid-template-columns:1fr; } .po-search { max-width:none; flex-basis:100%; } .po-actions .po-btn { width:100%; justify-content:center; } }
    </style>
@endpush

@section('content')
@php
    $hasFilters = $search !== '';
    $uploaderOf = fn ($o) => $o->uploaded_by_member_id ? ($members[$o->uploaded_by_member_id] ?? 'Usuario') : 'Titular';
@endphp
<div class="ts-page">

    <div class="ts-breadcrumb">
        <span>Panel de Control</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m9 18 6-6-6-6"/>
        </svg>
        <span class="ts-breadcrumb__current">Órdenes de Compra</span>
    </div>

    <div class="ts-header-top">
        <div>
            <h1 class="ts-title">Órdenes de Compra</h1>
            <p class="ts-subtitle">Sube el PDF de tu orden de compra y consulta las que ya nos enviaste</p>
        </div>
    </div>

    @if($canCreate)
    <div class="po-card">
        <h2 class="po-card__title">Subir orden de compra</h2>
        <p class="po-card__hint">Solo archivos PDF, de hasta {{ $maxMb }} MB. Los datos opcionales nos ayudan a ubicarla más rápido.</p>

        <form method="POST" action="{{ route('customer.purchase-orders.store') }}" enctype="multipart/form-data" id="poForm" novalidate>
            @csrf

            <div class="po-drop @error('file') po-drop--error @enderror" id="poDrop" tabindex="0" role="button"
                 aria-label="Seleccionar archivo PDF">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/>
                </svg>
                <p class="po-drop__main">Arrastra tu PDF aquí o haz clic para elegirlo</p>
                <p class="po-drop__sub">Formato PDF · máximo {{ $maxMb }} MB</p>
                <input type="file" name="file" id="poFile" accept="application/pdf,.pdf" tabindex="-1">
            </div>

            <div class="po-file" id="poFileInfo">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                     stroke="#DC2626" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>
                </svg>
                <span class="po-file__name" id="poFileName"></span>
                <span class="po-file__size" id="poFileSize"></span>
                <button type="button" class="po-file__rm" id="poFileRm" aria-label="Quitar archivo">✕</button>
            </div>
            <div class="po-clienterr" id="poClientErr" role="alert"></div>
            @error('file')<div class="po-clienterr po-clienterr--on" role="alert">{{ $message }}</div>@enderror

            <div class="po-fields">
                <div>
                    <label class="po-label" for="po_number">Número de orden de compra</label>
                    <input type="text" id="po_number" name="po_number" class="po-input" maxlength="60"
                           value="{{ old('po_number') }}" placeholder="Folio de tu OC (opcional)">
                    @error('po_number')<span class="val-error-msg">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label class="po-label" for="po_date">Fecha de la orden</label>
                    <input type="date" id="po_date" name="po_date" class="po-input" value="{{ old('po_date') }}"
                           max="{{ now()->toDateString() }}">
                    @error('po_date')<span class="val-error-msg">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label class="po-label" for="quote_id">Cotización relacionada</label>
                    <select id="quote_id" name="quote_id" class="po-select">
                        <option value="">Ninguna</option>
                        @foreach($quotes as $q)
                            <option value="{{ $q->id }}" @selected((string) old('quote_id') === (string) $q->id)>
                                {{ $q->quote_number }} · ${{ number_format((float) $q->total, 2) }} {{ $q->currency ?: 'MXN' }}
                            </option>
                        @endforeach
                    </select>
                    @error('quote_id')<span class="val-error-msg">{{ $message }}</span>@enderror
                </div>
                <div class="po-field--wide">
                    <label class="po-label" for="notes">Notas</label>
                    <textarea id="notes" name="notes" class="po-textarea" maxlength="1000"
                              placeholder="Comentarios para nuestro equipo (opcional)">{{ old('notes') }}</textarea>
                    @error('notes')<span class="val-error-msg">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="po-actions">
                <button type="submit" class="po-btn" id="poSubmit">Subir orden de compra</button>
            </div>
        </form>
    </div>
    @endif

    <form method="GET" action="{{ route('customer.purchase-orders.index') }}" class="po-filters">
        <input type="search" name="q" class="po-search" value="{{ $search }}"
               placeholder="Buscar por folio o nombre de archivo" maxlength="100" aria-label="Buscar">
        <button type="submit" class="po-btn">Buscar</button>
        @if($hasFilters)
            <a href="{{ route('customer.purchase-orders.index') }}" class="po-btn po-btn--ghost">Limpiar búsqueda</a>
        @endif
    </form>

    @if($hasFilters)
        <p class="po-count">{{ $orders->total() }} {{ $orders->total() === 1 ? 'resultado' : 'resultados' }}</p>
    @endif

    {{-- Tabla (escritorio) --}}
    <div class="ts-table-wrap">
        <table class="ts-table">
            <thead>
                <tr>
                    <th>FECHA DE CARGA</th>
                    <th>FOLIO OC</th>
                    <th>COTIZACIÓN</th>
                    <th>ARCHIVO</th>
                    <th>SUBIDA POR</th>
                    <th>NOTAS</th>
                    <th>ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td class="ts-table-date">{{ $order->created_at->format('d/m/Y') }}<small class="po-muted" style="display:block">{{ $order->created_at->format('H:i') }}</small></td>
                        <td>
                            <span class="po-folio">{{ $order->po_number ?: '—' }}</span>
                            @if($order->po_date)<small class="po-muted" style="display:block">{{ $order->po_date->format('d/m/Y') }}</small>@endif
                        </td>
                        <td>
                            @if($order->quote)
                                <a class="po-quote" href="{{ route('customer.quotes.show', $order->quote->id) }}">{{ $order->quote->quote_number }}</a>
                            @else
                                <span class="po-muted">—</span>
                            @endif
                        </td>
                        <td class="po-filecell">
                            <span title="{{ $order->original_name }}">{{ $order->original_name }}</span>
                            <small>{{ $order->sizeLabel() }}</small>
                        </td>
                        <td>{{ $uploaderOf($order) }}</td>
                        <td class="po-notes">{{ $order->notes ? \Illuminate\Support\Str::limit($order->notes, 90) : '—' }}</td>
                        <td>
                            <div class="ts-table-actions">
                                <a href="{{ route('customer.purchase-orders.download', $order->id) }}" target="_blank" rel="noopener"
                                   class="ts-action-btn ts-action-btn--primary" title="Ver PDF" aria-label="Ver PDF {{ $order->original_name }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </a>
                                <a href="{{ route('customer.purchase-orders.download', [$order->id, 'download' => 1]) }}"
                                   class="ts-action-btn" title="Descargar" aria-label="Descargar {{ $order->original_name }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/>
                                    </svg>
                                </a>
                                @if($canDelete)
                                    <form method="POST" action="{{ route('customer.purchase-orders.destroy', $order->id) }}"
                                          class="po-del js-po-delete" data-name="{{ $order->original_name }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="ts-action-btn" title="Eliminar" aria-label="Eliminar {{ $order->original_name }}" style="color:#DC2626">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="ts-table-empty">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>
                                </svg>
                                <p>{{ $hasFilters ? 'No hay órdenes de compra con esa búsqueda' : 'Aún no has subido órdenes de compra' }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($orders->hasPages())
            <div class="ts-pagination">
                <span class="ts-pagination__info">
                    Mostrando {{ $orders->firstItem() }}–{{ $orders->lastItem() }}
                    de {{ $orders->total() }} registros
                </span>
                <div class="ts-pagination__links">
                    {{ $orders->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- Tarjetas (móvil) --}}
    <div class="ts-mobile-cards">
        @forelse($orders as $order)
            <div class="ts-mobile-card">
                <div class="ts-mobile-card__top">
                    <span class="po-folio ts-mobile-card__num">{{ $order->po_number ?: 'Sin folio' }}</span>
                    <span class="po-muted" style="font-size:.75rem">{{ $order->created_at->format('d/m/Y') }}</span>
                </div>
                <div class="ts-mobile-card__type po-filecell"><span>{{ $order->original_name }}</span><small>{{ $order->sizeLabel() }}</small></div>
                <div class="ts-mobile-card__meta">
                    <span>Subida por: {{ $uploaderOf($order) }}</span>
                    @if($order->quote)
                        <span>Cotización: <a class="po-quote" href="{{ route('customer.quotes.show', $order->quote->id) }}">{{ $order->quote->quote_number }}</a></span>
                    @endif
                </div>
                @if($order->notes)
                    <div class="po-notes" style="margin-top:.375rem">{{ \Illuminate\Support\Str::limit($order->notes, 120) }}</div>
                @endif
                <div class="ts-mobile-card__divider"></div>
                <div class="ts-mobile-card__footer">
                    @if($canDelete)
                        <form method="POST" action="{{ route('customer.purchase-orders.destroy', $order->id) }}"
                              class="po-del js-po-delete" data-name="{{ $order->original_name }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="po-del" style="color:#DC2626;font-size:.8125rem;font-weight:600">Eliminar</button>
                        </form>
                    @else
                        <span></span>
                    @endif
                    <a href="{{ route('customer.purchase-orders.download', $order->id) }}" target="_blank" rel="noopener"
                       class="ts-mobile-card__btn">Ver PDF →</a>
                </div>
            </div>
        @empty
            <div class="ts-mobile-empty">
                <p>{{ $hasFilters ? 'No hay órdenes de compra con esa búsqueda' : 'Aún no has subido órdenes de compra' }}</p>
            </div>
        @endforelse

        @if($orders->hasPages())
            <div class="ts-pagination" style="justify-content:center">
                <div class="ts-pagination__links">{{ $orders->links() }}</div>
            </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    // Confirmación al eliminar.
    document.querySelectorAll('form.js-po-delete').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            if (!confirm('¿Eliminar la orden de compra "' + (f.dataset.name || '') + '"? Esta acción no se puede deshacer.')) {
                e.preventDefault();
            }
        });
    });

    var form = document.getElementById('poForm');
    if (!form) return;

    var MAX = {{ (int) $maxMb }} * 1024 * 1024;
    var drop = document.getElementById('poDrop');
    var input = document.getElementById('poFile');
    var info = document.getElementById('poFileInfo');
    var nameEl = document.getElementById('poFileName');
    var sizeEl = document.getElementById('poFileSize');
    var errEl = document.getElementById('poClientErr');
    var submit = document.getElementById('poSubmit');

    function fmt(bytes) {
        return bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }
    function showError(msg) {
        errEl.textContent = msg || '';
        errEl.classList.toggle('po-clienterr--on', !!msg);
        drop.classList.toggle('po-drop--error', !!msg);
    }
    function clearFile() {
        input.value = '';
        info.classList.remove('po-file--on');
    }
    // Valida el archivo elegido; devuelve true si es utilizable.
    function check(file) {
        var isPdf = /\.pdf$/i.test(file.name) && (file.type === '' || file.type === 'application/pdf');
        if (!isPdf) { showError('El archivo debe ser un PDF.'); return false; }
        if (file.size > MAX) { showError('El PDF no puede pesar más de {{ (int) $maxMb }} MB (el tuyo pesa ' + fmt(file.size) + ').'); return false; }
        if (file.size === 0) { showError('El archivo está vacío.'); return false; }
        showError('');
        return true;
    }
    function pick(file) {
        if (!file) return;
        if (!check(file)) { clearFile(); return; }
        nameEl.textContent = file.name;
        sizeEl.textContent = fmt(file.size);
        info.classList.add('po-file--on');
    }

    drop.addEventListener('click', function () { input.click(); });
    drop.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); }
    });
    input.addEventListener('change', function () {
        if (input.files.length) pick(input.files[0]); else clearFile();
    });
    ['dragenter', 'dragover'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('po-drop--over'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('po-drop--over'); });
    });
    drop.addEventListener('drop', function (e) {
        var files = e.dataTransfer && e.dataTransfer.files;
        if (!files || !files.length) return;
        try {
            var dt = new DataTransfer();
            dt.items.add(files[0]);
            input.files = dt.files;
        } catch (err) { /* navegador sin DataTransfer: se pide elegirlo con el botón */ }
        if (input.files.length) pick(input.files[0]); else showError('Usa el clic para elegir el archivo.');
    });
    document.getElementById('poFileRm').addEventListener('click', function () { clearFile(); showError(''); });

    form.addEventListener('submit', function (e) {
        if (!input.files.length) { e.preventDefault(); showError('Selecciona el archivo PDF de tu orden de compra.'); return; }
        if (!check(input.files[0])) { e.preventDefault(); return; }
        submit.disabled = true;
        submit.textContent = 'Subiendo…';
    });
})();
</script>
@endpush
