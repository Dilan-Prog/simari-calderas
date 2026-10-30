{{-- Catálogo de términos y condiciones (create/edit). Espera: $quoteTerms, $selectedTermIds, $customTerms. --}}
@php
    $qtTerms    = collect($quoteTerms ?? []);
    $qtSelected = array_map('intval', (array) old('term_ids', $selectedTermIds ?? []));
    $qtCustom   = old('terms_custom', $customTerms ?? '');
    $qtOpen     = count($qtSelected) > 0 || trim((string) $qtCustom) !== '';
    $qtAdminUrl = Route::has('admin.erp-settings.index') ? route('admin.erp-settings.index') . '#quote-terms' : null;
@endphp
<style>
    .qterms-list{list-style:none;margin:0 0 12px;padding:0;display:flex;flex-direction:column;gap:6px}
    .qterms-item{display:flex;gap:10px;align-items:flex-start;padding:8px 10px;border:1px solid #e5e7eb;border-radius:8px}
    .qterms-item[hidden]{display:none}
    .qterms-item input{margin-top:3px;flex-shrink:0}
    .qterms-item__main{min-width:0;flex:1}
    .qterms-item__title{font-weight:700;cursor:pointer;display:block}
    .qterms-item__body{margin:2px 0 0;font-size:12px;color:#6b7280;white-space:pre-line;overflow-wrap:anywhere}
    .qterms-item__body.is-clamped{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .qterms-more{background:none;border:0;padding:0;margin-top:2px;color:#ff6213;cursor:pointer;font-size:12px}
    .qterms-scope{font-size:11px;font-weight:400;color:#9ca3af;margin-left:6px}
    .qterms-preview{margin:0;padding:10px 12px;background:#f9fafb;border:1px dashed #d1d5db;border-radius:8px;font:inherit;font-size:12px;color:#374151;white-space:pre-wrap;overflow-wrap:anywhere;min-height:38px}
    .qterms-sub{font-size:12px;color:#6b7280;margin:0 0 6px}
</style>
<div class="form-group" style="margin-top:4px;" id="qtermsRoot">
    <label class="form-label" style="margin-bottom:8px;">Términos y condiciones</label>
    <input type="hidden" name="terms_present" value="1">
    <details class="accordion-conditions" {{ $qtOpen ? 'open' : '' }}>
        <summary class="accordion-conditions__summary">Editar condiciones</summary>
        <div class="accordion-conditions__body">
            @if ($qtTerms->isEmpty())
                <p class="qterms-sub">
                    Aún no hay términos en el catálogo.
                    @if ($qtAdminUrl)
                        <a href="{{ $qtAdminUrl }}" target="_blank" rel="noopener">Crear términos</a>
                    @endif
                </p>
            @else
                <p class="qterms-sub">
                    Marca los términos que aplican a esta cotización.
                    @if ($qtAdminUrl)
                        <a href="{{ $qtAdminUrl }}" target="_blank" rel="noopener">Administrar términos</a>
                    @endif
                </p>
                <ul class="qterms-list" id="qtermsList">
                    @foreach ($qtTerms as $t)
                        <li class="qterms-item" data-applies="{{ $t->applies_to }}">
                            <input type="checkbox" name="term_ids[]" value="{{ $t->id }}" id="qterm{{ $t->id }}"
                                   data-title="{{ $t->title }}" data-body="{{ $t->body }}"
                                   {{ in_array((int) $t->id, $qtSelected, true) ? 'checked' : '' }}>
                            <div class="qterms-item__main">
                                <label class="qterms-item__title" for="qterm{{ $t->id }}">{{ $t->title }}<span class="qterms-scope">{{ ['product' => 'Productos', 'service' => 'Servicios'][$t->applies_to] ?? '' }}</span></label>
                                <p class="qterms-item__body is-clamped">{{ $t->body }}</p>
                                <button type="button" class="qterms-more" hidden>ver más</button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

            <label class="form-label" for="qtermsCustom" style="margin-bottom:6px;">Texto personalizado (opcional)</label>
            <textarea name="terms_custom" id="qtermsCustom" class="form-textarea" rows="4"
                      placeholder="Texto adicional solo para esta cotización">{{ $qtCustom }}</textarea>

            <p class="qterms-sub" style="margin-top:12px;">Así se verá en el PDF</p>
            <pre class="qterms-preview" id="qtermsPreview"></pre>
        </div>
    </details>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var root = document.getElementById('qtermsRoot');
    if (!root) return;
    var list = document.getElementById('qtermsList');
    var custom = document.getElementById('qtermsCustom');
    var preview = document.getElementById('qtermsPreview');
    var tbody = document.getElementById('itemsTableBody');

    function lineKinds() {
        var p = 0, s = 0;
        if (tbody) {
            tbody.querySelectorAll('tr:not(#emptyRow)').forEach(function (tr) {
                if (tr.dataset.servicePageId) s++; else p++;
            });
        }
        return { p: p, s: s };
    }

    function applyFilter() {
        if (!list) return;
        var k = lineKinds();
        var hide = null; // tipo de término a ocultar
        if (k.p > 0 && k.s === 0) hide = 'service';
        else if (k.s > 0 && k.p === 0) hide = 'product';
        list.querySelectorAll('.qterms-item').forEach(function (li) {
            var off = hide !== null && li.dataset.applies === hide;
            li.hidden = off;
            li.querySelector('input').disabled = off; // conserva checked, no se envía
        });
        render();
    }

    function render() {
        var lines = [];
        if (list) {
            list.querySelectorAll('input[type=checkbox]').forEach(function (cb) {
                if (cb.checked && !cb.disabled) lines.push('• ' + cb.dataset.title + ': ' + cb.dataset.body);
            });
        }
        var text = lines.join('\n\n'); // igual que QuoteTermsComposer
        var c = custom.value.trim();
        if (c) text = text ? text + '\n\n' + c : c;
        preview.textContent = text || 'Sin términos seleccionados.';
    }

    if (list) {
        list.addEventListener('change', render);
        list.querySelectorAll('.qterms-item').forEach(function (li) {
            var body = li.querySelector('.qterms-item__body');
            var btn = li.querySelector('.qterms-more');
            btn.addEventListener('click', function () {
                var clamped = body.classList.toggle('is-clamped');
                btn.textContent = clamped ? 'ver más' : 'ver menos';
            });
            // mostrar "ver más" solo si el texto realmente se recorta (al abrir el acordeón)
            function check() {
                if (body.classList.contains('is-clamped') && body.scrollHeight > body.clientHeight + 1) btn.hidden = false;
            }
            var det = root.querySelector('details');
            det.addEventListener('toggle', check);
            // el paso del asistente puede estar oculto al cargar: medir cuando el texto ya tenga tamaño
            if (window.ResizeObserver) new ResizeObserver(check).observe(body);
            check();
        });
    }
    custom.addEventListener('input', render);

    if (tbody && window.MutationObserver) {
        new MutationObserver(applyFilter).observe(tbody, { childList: true });
    }
    applyFilter();
});
</script>
