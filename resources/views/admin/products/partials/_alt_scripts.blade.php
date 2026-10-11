{{--
    Textos ALT del formulario de producto (crear y editar).
    - Contador (recomendado <= 125) y botón "Usar nombre del producto" en cada
      campo .pform-alt-field (solo rellena si está vacío; nunca sobrescribe).
    - Espejo portada <-> primera imagen de la galería: #pformCoverAlt y el ALT
      de la primera imagen siempre dicen lo mismo (regla en
      ProductController::syncCoverAlt()).
    - Vista previa 1200x630 de la imagen OG (#pformOgImage).
    Expone window.pformAlt para que los scripts de galería creen campos y
    sincronicen la portada tras renderizar/reordenar/eliminar.
--}}
@push('scripts')
<script>
(function () {
    var LIMIT = 125;
    var nameEl = document.getElementById('pformName');
    var cover = document.getElementById('pformCoverAlt');

    function productName() { return nameEl ? nameEl.value.trim() : ''; }

    function refreshField(wrap) {
        if (!wrap) return;
        var input = wrap.querySelector('input[type=text]');
        if (!input) return;
        var limit = parseInt(wrap.getAttribute('data-limit') || LIMIT, 10);
        var count = wrap.querySelector('.pform-alt-count');
        if (count) {
            var n = input.value.length;
            count.textContent = n + ' / ' + limit;
            count.style.color = n > limit ? '#b45309' : '#6b7280';
        }
        input.placeholder = productName() || 'Describe la imagen';
        var btn = wrap.querySelector('.pform-alt-use-name');
        if (btn) btn.disabled = input.value.trim() !== '' || productName() === '';
    }

    function refreshAll() {
        document.querySelectorAll('.pform-alt-field').forEach(refreshField);
    }

    // Primer ALT de la galería, en el orden en que se mostrará: imágenes ya
    // guardadas que no estén marcadas para eliminar y, si no quedan, las nuevas.
    function firstAltInput() {
        var existing = document.querySelectorAll('#pformExistingGrid .pform-existing-img');
        for (var i = 0; i < existing.length; i++) {
            var chk = existing[i].querySelector('input[type=checkbox]');
            if (!chk || !chk.checked) return existing[i].querySelector('input.pform-alt-input');
        }
        return document.querySelector('#pformImageGrid input.pform-alt-input');
    }

    function syncCoverFromFirst() {
        var first = firstAltInput();
        if (cover && first) cover.value = first.value;
        refreshAll();
    }

    // Portada escrita antes de agregar imágenes: la primera imagen la adopta.
    function adoptCoverIntoFirst() {
        var first = firstAltInput();
        if (cover && first && first.value.trim() === '' && cover.value.trim() !== '') {
            first.value = cover.value;
            first.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    document.addEventListener('input', function (e) {
        var t = e.target;
        if (t === nameEl) { refreshAll(); return; }
        if (cover && t === cover) {
            var first = firstAltInput();
            if (first) { first.value = cover.value; first.dispatchEvent(new Event('input', { bubbles: true })); }
        } else if (cover && t.classList && t.classList.contains('pform-alt-input') && t === firstAltInput()) {
            cover.value = t.value;
        }
        if (t.closest) refreshField(t.closest('.pform-alt-field'));
        if (cover) refreshField(cover.closest('.pform-alt-field'));
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('.pform-alt-use-name') : null;
        if (!btn) return;
        var wrap = btn.closest('.pform-alt-field');
        var input = wrap.querySelector('input[type=text]');
        if (input.value.trim() === '' && productName() !== '') {
            input.value = productName();
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }
    });

    // Evita que arrastrar para reordenar secuestre la selección de texto del input.
    document.addEventListener('focusin', function (e) {
        var tile = e.target.closest ? e.target.closest('.pform-img-item') : null;
        if (tile && e.target.matches('input')) tile.draggable = false;
    });
    document.addEventListener('focusout', function (e) {
        var tile = e.target.closest ? e.target.closest('.pform-img-item') : null;
        if (tile && e.target.matches('input')) tile.draggable = true;
    });

    /* Vista previa 1200x630 de la imagen OG */
    var og = document.getElementById('pformOgImage');
    var ogPrev = document.getElementById('pformOgPreview');
    function refreshOgPreview() {
        if (!og || !ogPrev) return;
        var url = og.value.trim();
        var img = ogPrev.querySelector('img');
        var empty = ogPrev.querySelector('.pform-og-preview-empty');
        if (url) { img.src = url; img.style.display = 'block'; empty.style.display = 'none'; }
        else { img.removeAttribute('src'); img.style.display = 'none'; empty.style.display = 'flex'; }
    }
    if (og) { og.addEventListener('input', refreshOgPreview); og.addEventListener('change', refreshOgPreview); refreshOgPreview(); }

    /* Campo ALT (DOM) para los scripts de galería. */
    function buildField(value, name, onInput) {
        var wrap = document.createElement('div');
        wrap.className = 'pform-alt-field';
        wrap.setAttribute('data-limit', LIMIT);
        wrap.style.cssText = 'padding:6px;border-top:1px solid #e5e7eb;cursor:auto;';
        var label = document.createElement('label');
        label.textContent = 'Texto alternativo (ALT)';
        label.style.cssText = 'display:block;font-size:11px;font-weight:600;color:#374151;margin-bottom:3px;';
        var input = document.createElement('input');
        input.type = 'text';
        input.className = 'pform-input pform-alt-input';
        input.name = name;
        input.maxLength = 255;
        input.value = value || '';
        input.style.cssText = 'width:100%;font-size:12px;padding:5px 7px;';
        input.addEventListener('input', function () { onInput(input.value); });
        var row = document.createElement('div');
        row.style.cssText = 'display:flex;justify-content:space-between;align-items:center;margin-top:3px;gap:6px;';
        var count = document.createElement('span');
        count.className = 'pform-alt-count';
        count.style.cssText = 'font-size:10px;color:#6b7280;';
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'pform-alt-use-name';
        btn.textContent = 'Usar nombre del producto';
        btn.style.cssText = 'font-size:10px;background:none;border:none;color:var(--secondary-color,#ff6213);cursor:pointer;padding:0;text-align:right;';
        row.appendChild(count);
        row.appendChild(btn);
        wrap.appendChild(label);
        wrap.appendChild(input);
        wrap.appendChild(row);
        return wrap;
    }

    window.pformAlt = {
        buildField: buildField,
        refreshAll: refreshAll,
        syncCoverFromFirst: syncCoverFromFirst,
        adoptCoverIntoFirst: adoptCoverIntoFirst
    };

    refreshAll();
})();
</script>
@endpush
