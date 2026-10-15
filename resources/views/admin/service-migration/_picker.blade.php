{{--
    Selector buscable "Servicio (web)" = Servicio ID (página de servicio tipo servicio).
    Vars:
      $name        (opcional) nombre del campo oculto. Por omisión: service_page_id
      $servicePage (opcional) ServicePage actualmente elegida (para mostrar "#id · Nombre")
      $value       (opcional) id elegido si no hay $servicePage (se resuelve aquí)
      $saveUrl     (opcional) si se da, guarda al instante por PATCH {service_page_id, sync}
      $sync        (opcional) bool: con $saveUrl, propaga a servicios técnicos y reportes
      $inputId     (opcional) id del input de texto
      $compact     (opcional) bool: versión angosta (tablas)
--}}
@php
    $pickerName = $name ?? 'service_page_id';
    $pickerPage = $servicePage ?? null;
    $pickerValue = $value ?? $pickerPage?->id;
    if (!$pickerPage && $pickerValue) {
        $pickerPage = \App\Models\ServicePage::find($pickerValue);
    }
    $pickerLabel = $pickerPage ? '#' . $pickerPage->id . ' · ' . $pickerPage->name : '';
    $pickerSave = $saveUrl ?? null;
    $pickerSync = (bool) ($sync ?? false);
    $pickerCompact = (bool) ($compact ?? false);
@endphp
<div class="smp {{ $pickerCompact ? 'smp--compact' : '' }}" data-smp
     data-search-url="{{ route('admin.service-migration.search') }}"
     @if ($pickerSave) data-save-url="{{ $pickerSave }}" data-sync="{{ $pickerSync ? 1 : 0 }}" @endif>
    <input type="hidden" name="{{ $pickerName }}" value="{{ $pickerValue }}" data-smp-value>
    <div class="smp-box">
        <input type="text" class="smp-input" data-smp-input @isset($inputId) id="{{ $inputId }}" @endisset
               placeholder="Buscar servicio web…" autocomplete="off" aria-label="Servicio (web)" value="{{ $pickerLabel }}" data-smp-label="{{ $pickerLabel }}">
        <button type="button" class="smp-clear" data-smp-clear title="Quitar Servicio ID" aria-label="Quitar Servicio ID" @if (!$pickerValue) hidden @endif>&times;</button>
    </div>
    <ul class="smp-list" data-smp-list hidden></ul>
    <span class="smp-status" data-smp-status role="status"></span>
</div>

@once
    @push('styles')
    <style>
        .smp { position: relative; display: block; min-width: 220px; }
        .smp--compact { min-width: 200px; }
        .smp-box { position: relative; }
        .smp-input { width: 100%; padding: 7px 28px 7px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; background: #fff; color: inherit; }
        .smp-input:focus { outline: 2px solid rgba(59,130,246,.35); border-color: #3b82f6; }
        .smp-clear { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); border: 0; background: transparent; font-size: 18px; line-height: 1; cursor: pointer; color: #6b7280; }
        .smp-list { position: absolute; z-index: 60; left: 0; right: 0; top: 100%; margin: 2px 0 0; padding: 4px 0; list-style: none; max-height: 240px; overflow: auto; background: #fff; border: 1px solid #d1d5db; border-radius: 6px; box-shadow: 0 8px 24px rgba(0,0,0,.12); min-width: 260px; }
        .smp-list[hidden] { display: none; }
        .smp-list li { padding: 7px 10px; font-size: 13px; cursor: pointer; color: #111827; }
        .smp-list li small { display: block; color: #6b7280; font-size: 11px; }
        .smp-list li:hover, .smp-list li.is-active { background: #eff6ff; }
        .smp-list li.smp-empty { cursor: default; color: #6b7280; }
        .smp-status { display: block; min-height: 14px; font-size: 11px; margin-top: 2px; color: #6b7280; }
        .smp-status.is-ok { color: #15803d; }
        .smp-status.is-err { color: #b91c1c; }
        @media (prefers-color-scheme: dark) {
            .smp-input, .smp-list { background: #1f2937; color: #f3f4f6; border-color: #4b5563; }
            .smp-list li { color: #f3f4f6; }
            .smp-list li:hover, .smp-list li.is-active { background: #374151; }
        }
    </style>
    @endpush
    @push('scripts')
    <script>
    (function () {
        if (window.SMPicker) return;
        var timer;

        function csrf() {
            var m = document.querySelector('meta[name="csrf-token"]');
            return m ? m.getAttribute('content') : '';
        }
        function parts(root) {
            return {
                value: root.querySelector('[data-smp-value]'),
                input: root.querySelector('[data-smp-input]'),
                clear: root.querySelector('[data-smp-clear]'),
                list: root.querySelector('[data-smp-list]'),
                status: root.querySelector('[data-smp-status]')
            };
        }
        function status(root, text, kind) {
            var s = parts(root).status;
            if (!s) return;
            s.textContent = text || '';
            s.className = 'smp-status' + (kind ? ' is-' + kind : '');
        }
        function closeList(root) { parts(root).list.hidden = true; }

        function save(root, id) {
            var url = root.getAttribute('data-save-url');
            if (!url) return;
            status(root, 'Guardando…');
            fetch(url, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ service_page_id: id || null, sync: root.getAttribute('data-sync') === '1' ? 1 : 0 })
            }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
              .then(function (res) {
                  if (!res.ok) {
                      var msg = (res.body && res.body.errors && res.body.errors.service_page_id && res.body.errors.service_page_id[0]) || (res.body && res.body.message) || 'No se pudo guardar.';
                      status(root, msg, 'err');
                      return;
                  }
                  status(root, res.body.message || 'Guardado.', 'ok');
                  root.dispatchEvent(new CustomEvent('smp:saved', { bubbles: true, detail: res.body }));
              })
              .catch(function () { status(root, 'Error de red al guardar.', 'err'); });
        }

        function set(root, id, label, doSave) {
            var p = parts(root);
            p.value.value = id || '';
            p.input.value = label || '';
            p.input.setAttribute('data-smp-label', label || '');
            p.clear.hidden = !id;
            closeList(root);
            p.value.dispatchEvent(new Event('change', { bubbles: true }));
            if (doSave) save(root, id);
        }

        function render(root, items) {
            var p = parts(root);
            p.list.innerHTML = '';
            if (!items.length) {
                var e = document.createElement('li');
                e.className = 'smp-empty';
                e.textContent = 'Sin servicios web que coincidan.';
                p.list.appendChild(e);
            }
            items.forEach(function (it) {
                var li = document.createElement('li');
                li.textContent = it.label;
                if (it.category) {
                    var sm = document.createElement('small');
                    sm.textContent = it.category;
                    li.appendChild(sm);
                }
                li.addEventListener('mousedown', function (ev) {
                    ev.preventDefault();
                    set(root, it.id, it.label, true);
                });
                p.list.appendChild(li);
            });
            p.list.hidden = false;
        }

        function search(root, q) {
            var url = root.getAttribute('data-search-url') + '?q=' + encodeURIComponent(q || '');
            fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.ok ? r.json() : []; })
                .then(function (items) { render(root, items); })
                .catch(function () { render(root, []); });
        }

        document.addEventListener('focusin', function (e) {
            var input = e.target.closest && e.target.closest('[data-smp-input]');
            if (!input) return;
            var root = input.closest('[data-smp]');
            input.select();
            search(root, '');
        });
        document.addEventListener('input', function (e) {
            var input = e.target.closest && e.target.closest('[data-smp-input]');
            if (!input) return;
            var root = input.closest('[data-smp]');
            clearTimeout(timer);
            timer = setTimeout(function () { search(root, input.value.replace(/^#\d+\s*·\s*/, '')); }, 250);
        });
        document.addEventListener('focusout', function (e) {
            var input = e.target.closest && e.target.closest('[data-smp-input]');
            if (!input) return;
            var root = input.closest('[data-smp]');
            setTimeout(function () {
                closeList(root);
                // Si no eligió nada, regresa al texto del valor vigente.
                input.value = input.getAttribute('data-smp-label') || '';
            }, 120);
        });
        document.addEventListener('click', function (e) {
            var btn = e.target.closest && e.target.closest('[data-smp-clear]');
            if (!btn) return;
            set(btn.closest('[data-smp]'), '', '', true);
        });

        window.SMPicker = {
            /** Fija el valor sin guardar (p. ej. prellenado heredado). Solo si está vacío cuando onlyIfEmpty. */
            set: function (root, id, label, onlyIfEmpty) {
                if (!root) return;
                if (onlyIfEmpty && parts(root).value.value) return;
                set(root, id, label, false);
            },
            /** Fija el valor y lo guarda de inmediato (si el selector tiene data-save-url). */
            setAndSave: function (root, id, label) {
                if (root) set(root, id, label, true);
            }
        };
    })();
    </script>
    @endpush
@endonce
