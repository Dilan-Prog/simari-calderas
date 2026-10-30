{{-- Redactor de correo de la bandeja (vive dentro del modal _email_modal). Vars: $lead, $emailCompose (array|null, lo arma LeadInboxController::emailCompose()).
     CSS y JS van en linea (prefijo lwe-) a proposito: no requiere build y no toca los archivos compartidos de la bandeja. --}}
@php
    $c = is_array($emailCompose ?? null) ? $emailCompose : null;
    $canSend = $c && ! empty($c['can_send']);

    // ¿Se envio este formulario y regreso con errores? (el formulario de notas tambien usa "body", por eso se mira "subject").
    $oldInput = (array) session()->getOldInput();
    $submitted = array_key_exists('subject', $oldInput) || array_key_exists('to', $oldInput);
@endphp

@if (! $canSend)
    <p class="lw-soon">{{ $c['reason'] ?? 'El envío de correo no está disponible para este lead.' }}</p>
    @if ($lead->contact_email)<a class="erp-btn erp-btn--sm" href="mailto:{{ $lead->contact_email }}" target="_blank" rel="noopener">Abrir correo</a>@endif
@else
    @php
        $templates = $c['templates'] ?? [];
        $quotes = $c['quotes'] ?? [];
        $openTask = $c['open_task'] ?? null;
        $signature = trim((string) ($c['signature'] ?? ''));
        $oldQuotes = array_map('intval', (array) old('quote_ids', []));
        $showCc = old('cc') !== null && old('cc') !== '' || $errors->has('cc');
        $closeTask = (string) old('close_task', '1') === '1';
        $fileErrors = collect($errors->get('files'))->merge(collect($errors->get('files.*'))->flatten())->unique()->values();
        $quoteErrors = collect($errors->get('quote_ids'))->merge(collect($errors->get('quote_ids.*'))->flatten())->unique()->values();
    @endphp

    <form method="POST" action="{{ route('admin.leads-inbox.email.send', $lead) }}" enctype="multipart/form-data"
          class="lwe-form" data-lwe-form data-lwe-template-url="{{ route('admin.leads-inbox.email.template', [$lead, 0]) }}" novalidate>
        @csrf

        @if (! empty($c['in_reply_to']))
            <p class="lwe-reply"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 14L4 9l5-5"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/></svg> Responde al último correo del cliente</p>
        @endif

        <div class="lwe-field lwe-from">
            <span class="lwe-lbl">De</span>
            <span class="lwe-from-val">{{ $c['from'] ?? '' }}</span>
        </div>
        @if (! empty($c['uses_default_sender']))
            <p class="lwe-sender-warn" role="note">Se enviará desde {{ $c['sender_address'] ?? '' }}. Configura el buzón de la bandeja
                @if (\Illuminate\Support\Facades\Route::has('admin.integrations.index'))
                    en <a href="{{ route('admin.integrations.index') }}">Integraciones</a>
                @else
                    en Integraciones
                @endif
                para responder desde administracion@.</p>
        @endif

        <div class="lwe-field">
            <div class="lwe-lblrow">
                <label class="lwe-lbl" for="lwe-to">Para</label>
                <button type="button" class="lwe-link" data-lwe-cc-toggle aria-expanded="{{ $showCc ? 'true' : 'false' }}" aria-controls="lwe-cc-wrap" @if ($showCc) hidden @endif>+ Cc</button>
            </div>
            <input class="erp-input" type="email" id="lwe-to" name="to" value="{{ old('to', $c['to'] ?? $lead->contact_email) }}" maxlength="190" autocomplete="off" required
                   placeholder="correo@empresa.com" @error('to') aria-invalid="true" aria-describedby="lwe-to-err" @enderror>
            @error('to')<p class="lwe-err" id="lwe-to-err" role="alert">{{ $message }}</p>@enderror
        </div>

        <div class="lwe-field" id="lwe-cc-wrap" data-lwe-cc @unless ($showCc) hidden @endunless>
            <label class="lwe-lbl" for="lwe-cc">Cc</label>
            <input class="erp-input" type="text" id="lwe-cc" name="cc" value="{{ old('cc') }}" maxlength="400" autocomplete="off" inputmode="email"
                   placeholder="uno@empresa.com, otro@empresa.com" @error('cc') aria-invalid="true" aria-describedby="lwe-cc-err" @enderror>
            <p class="lwe-hint">Hasta 5 correos, separados por coma.</p>
            @error('cc')<p class="lwe-err" id="lwe-cc-err" role="alert">{{ $message }}</p>@enderror
        </div>

        @if (count($templates))
            <div class="lwe-field">
                <label class="lwe-lbl" for="lwe-template">Plantilla</label>
                <select class="erp-select" id="lwe-template" data-lwe-template>
                    <option value="">Sin plantilla</option>
                    @foreach ($templates as $tpl)
                        <option value="{{ $tpl['id'] }}">{{ $tpl['name'] }}</option>
                    @endforeach
                </select>
                <p class="lwe-hint" data-lwe-template-status role="status" aria-live="polite" hidden></p>
            </div>
        @endif

        <div class="lwe-field">
            <label class="lwe-lbl" for="lwe-subject">Asunto</label>
            <input class="erp-input" type="text" id="lwe-subject" name="subject" value="{{ old('subject', $c['subject'] ?? '') }}" maxlength="200" required
                   @error('subject') aria-invalid="true" aria-describedby="lwe-subject-err" @enderror>
            @error('subject')<p class="lwe-err" id="lwe-subject-err" role="alert">{{ $message }}</p>@enderror
        </div>

        <div class="lwe-field">
            <label class="lwe-lbl" for="lwe-body">Mensaje</label>
            <textarea class="erp-input lwe-body" id="lwe-body" name="body" rows="6" maxlength="20000" required data-lwe-body
                      placeholder="Escribe el correo…" @if ($errors->has('body') && $submitted) aria-invalid="true" aria-describedby="lwe-body-err" @endif>{{ old('body') }}</textarea>
            @if ($submitted)
                @error('body')<p class="lwe-err" id="lwe-body-err" role="alert">{{ $message }}</p>@enderror
            @endif
        </div>

        @if ($signature !== '')
            <div class="lwe-field">
                <span class="lwe-lbl" id="lwe-sig-lbl">Firma</span>
                <div class="lwe-sig" role="note" aria-labelledby="lwe-sig-lbl">{{ $signature }}</div>
                <p class="lwe-hint">Se agrega al final del correo.</p>
            </div>
        @endif

        @if (count($quotes))
            <fieldset class="lwe-field lwe-fieldset">
                <legend class="lwe-lbl">Adjuntar cotización (PDF)</legend>
                <div class="lwe-checks">
                    @foreach ($quotes as $q)
                        <label class="lwe-check">
                            <input type="checkbox" name="quote_ids[]" value="{{ $q['id'] }}" @checked(in_array((int) $q['id'], $oldQuotes, true))>
                            <span class="lwe-check-t"><b>{{ $q['number'] }}</b> <span class="lwe-dot" aria-hidden="true">·</span> {{ $q['status_label'] }} <span class="lwe-dot" aria-hidden="true">·</span> {{ $q['total_label'] }}</span>
                        </label>
                    @endforeach
                </div>
                @foreach ($quoteErrors as $msg)<p class="lwe-err" role="alert">{{ $msg }}</p>@endforeach
            </fieldset>
        @endif

        <div class="lwe-field">
            <label class="lwe-lbl" for="lwe-files">Adjuntar archivos</label>
            <input class="lwe-file" type="file" id="lwe-files" name="files[]" multiple data-lwe-files
                   accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.zip"
                   @if ($fileErrors->isNotEmpty()) aria-invalid="true" @endif aria-describedby="lwe-files-hint lwe-files-msg">
            <p class="lwe-hint" id="lwe-files-hint">Máximo 3 archivos de 10 MB (PDF, Word, Excel, imágenes o ZIP).@if ($submitted && $errors->any()) Si el envío falló, vuelve a elegir los archivos.@endif</p>
            <p class="lwe-err" id="lwe-files-msg" data-lwe-files-msg role="alert" hidden></p>
            @foreach ($fileErrors as $msg)<p class="lwe-err" role="alert">{{ $msg }}</p>@endforeach
            <ul class="lwe-filelist" data-lwe-filelist aria-label="Archivos seleccionados"></ul>
        </div>

        <div class="lwe-foot">
            @if ($openTask)
                <label class="lwe-check lwe-close">
                    <input type="hidden" name="close_task" value="0">
                    <input type="checkbox" name="close_task" value="1" @checked($closeTask)>
                    <span class="lwe-check-t">Marcar la tarea «{{ $openTask['title'] ?? 'Responder correo' }}» como completada al enviar</span>
                </label>
            @endif
            <button type="submit" class="erp-btn erp-btn--primary lwe-send" data-lwe-send><span data-lwe-send-label>Enviar correo</span></button>
        </div>
    </form>

    <style>
        .lwe-form { display: flex; flex-direction: column; gap: 10px; max-height: min(58vh, 560px); overflow-y: auto; overflow-x: hidden; padding: 2px 3px 0; -webkit-overflow-scrolling: touch; }
        .lwe-form .lwe-field { display: flex; flex-direction: column; gap: 4px; min-width: 0; margin: 0; padding: 0; border: 0; }
        .lwe-form .lwe-lbl { font-size: 11.5px; font-weight: 800; letter-spacing: .02em; color: var(--erp-muted); padding: 0; }
        .lwe-form .lwe-lblrow { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .lwe-form .lwe-from { flex-direction: row; align-items: baseline; gap: 8px; }
        .lwe-form .lwe-sender-warn { margin: -4px 0 0; padding: 8px 10px; border: 1px solid #f0c36d; border-left-width: 3px; border-radius: 6px; background: #fff7e0; color: #7a4b00; font-size: 12.5px; line-height: 1.4; }
        .lwe-form .lwe-sender-warn a { color: inherit; font-weight: 600; text-decoration: underline; }
        .lwe-form .lwe-from-val { font-size: 13px; color: var(--erp-text, var(--erp-ink)); overflow-wrap: anywhere; }
        .lwe-form .erp-input, .lwe-form .erp-select { width: 100%; box-sizing: border-box; border: 1px solid var(--lw-field-line, #d4d7dc); border-radius: 8px; background: var(--lw-field, #fff); color: var(--erp-ink); font-size: 14px; padding: 7px 10px; min-height: 36px; font-family: inherit; }
        .lw-pane .lwe-form textarea.lwe-body { min-height: 130px; max-height: 340px; resize: vertical; border: 1px solid var(--lw-field-line, #d4d7dc); border-radius: 8px; background: var(--lw-field, #fff); padding: 8px 10px; font-size: 14px; line-height: 1.45; field-sizing: fixed; overflow-y: auto; }
        .lwe-form .erp-input:focus-visible, .lwe-form .erp-select:focus-visible, .lw-pane .lwe-form textarea.lwe-body:focus-visible { outline: 2px solid var(--erp-accent); outline-offset: 1px; border-color: var(--erp-accent); }
        .lwe-form [aria-invalid="true"] { border-color: var(--erp-bad-dot, #dc2626) !important; }
        .lwe-form .lwe-hint { margin: 0; font-size: 11.5px; color: var(--erp-muted); }
        .lwe-form .lwe-err { margin: 0; font-size: 12px; font-weight: 600; color: var(--erp-bad); }
        .lwe-form .lwe-err[hidden], .lwe-form .lwe-hint[hidden], .lwe-form [hidden] { display: none; }
        .lwe-form .lwe-link { border: 0; background: transparent; padding: 2px 4px; margin: 0; font: inherit; font-size: 12px; font-weight: 800; color: var(--erp-info); cursor: pointer; border-radius: 6px; }
        .lwe-form .lwe-link:hover { text-decoration: underline; }
        .lwe-form .lwe-link:focus-visible, .lwe-form .lwe-x:focus-visible, .lwe-form .lwe-send:focus-visible { outline: 2px solid var(--erp-accent); outline-offset: 2px; }
        .lwe-form .lwe-reply { display: flex; align-items: center; gap: 6px; margin: 0; padding: 6px 10px; border-radius: 8px; background: var(--erp-info-bg); color: var(--erp-info); font-size: 12px; font-weight: 700; }
        .lwe-form .lwe-sig { white-space: pre-line; overflow-wrap: anywhere; font-size: 12.5px; line-height: 1.45; color: var(--erp-muted); border-left: 3px solid var(--erp-line); padding: 4px 10px; background: var(--erp-neu-bg, transparent); border-radius: 0 6px 6px 0; max-height: 120px; overflow-y: auto; }
        .lwe-form .lwe-checks { display: flex; flex-direction: column; gap: 4px; }
        .lwe-form .lwe-check { display: flex; align-items: flex-start; gap: 8px; font-size: 13px; color: var(--erp-ink); cursor: pointer; padding: 4px 2px; }
        .lwe-form .lwe-check input[type="checkbox"] { margin: 3px 0 0; width: 16px; height: 16px; flex-shrink: 0; accent-color: var(--erp-accent); }
        .lwe-form .lwe-check-t { min-width: 0; overflow-wrap: anywhere; }
        .lwe-form .lwe-dot { color: var(--erp-faint, var(--erp-muted)); }
        .lwe-form .lwe-file { width: 100%; box-sizing: border-box; font-size: 13px; color: var(--erp-ink); font-family: inherit; }
        .lwe-form .lwe-file:focus-visible { outline: 2px solid var(--erp-accent); outline-offset: 2px; }
        .lwe-form .lwe-filelist { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 4px; }
        .lwe-form .lwe-filelist:empty { display: none; }
        .lwe-form .lwe-filelist li { display: flex; align-items: center; gap: 8px; padding: 5px 6px 5px 10px; border: 1px solid var(--erp-line); border-radius: 8px; font-size: 12.5px; background: var(--lw-field, #fff); }
        .lwe-form .lwe-fname { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .lwe-form .lwe-fsize { color: var(--erp-muted); font-size: 11.5px; flex-shrink: 0; }
        .lwe-form .lwe-x { border: 0; background: transparent; color: var(--erp-muted); width: 26px; height: 26px; border-radius: 6px; font-size: 18px; line-height: 1; cursor: pointer; flex-shrink: 0; padding: 0; }
        .lwe-form .lwe-x:hover { background: var(--erp-bad-bg); color: var(--erp-bad); }
        .lwe-form .lwe-foot { position: sticky; bottom: 0; z-index: 1; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding: 8px 0 2px; margin-top: 2px; background: var(--erp-surface); border-top: 1px solid var(--erp-line-soft); }
        .lwe-form .lwe-close { flex: 1 1 220px; padding: 0; }
        .lwe-form .lwe-send { margin-left: auto; height: 38px; border-radius: 19px; padding: 0 22px; white-space: nowrap; }
        .lwe-form .lwe-send[disabled] { opacity: .7; cursor: progress; }
        @media (max-width: 600px) {
            .lwe-form { max-height: 62vh; }
            .lwe-form .lwe-foot { flex-direction: column; align-items: stretch; gap: 8px; }
            .lwe-form .lwe-close { flex: 0 0 auto; }
            .lwe-form .lwe-send { width: 100%; margin-left: 0; height: 42px; }
            .lwe-form .erp-input, .lwe-form .erp-select, .lw-pane .lwe-form textarea.lwe-body { font-size: 16px; }
        }
    </style>

    <script>
        (function () {
            var form = document.querySelector('[data-lwe-form]');
            if (!form || form.dataset.lweReady) return;
            form.dataset.lweReady = '1';

            var MAX_FILES = 3, MAX_BYTES = 10 * 1024 * 1024;
            var EXTS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'zip'];
            var q = function (s) { return form.querySelector(s); };

            // ── Cc ──
            var ccBtn = q('[data-lwe-cc-toggle]'), ccWrap = q('[data-lwe-cc]');
            if (ccBtn && ccWrap) {
                ccBtn.addEventListener('click', function () {
                    ccWrap.hidden = false;
                    ccBtn.hidden = true;
                    ccBtn.setAttribute('aria-expanded', 'true');
                    var i = ccWrap.querySelector('input');
                    if (i) i.focus();
                });
            }

            // ── Cuerpo autoajustable ──
            var body = q('[data-lwe-body]');
            function grow() {
                if (!body) return;
                body.style.height = 'auto';
                var max = 340;
                body.style.height = Math.min(Math.max(body.scrollHeight + 2, 130), max) + 'px';
            }
            if (body) {
                body.addEventListener('input', grow);
                grow();
                window.addEventListener('load', grow);
            }

            // ── Plantillas ──
            var tpl = q('[data-lwe-template]'), tplStatus = q('[data-lwe-template-status]');
            var subject = q('#lwe-subject');
            var lastTpl = '', tplToken = 0;
            function status(text, isErr) {
                if (!tplStatus) return;
                tplStatus.hidden = !text;
                tplStatus.textContent = text || '';
                tplStatus.className = 'lwe-hint' + (isErr ? ' lwe-err' : '');
            }
            if (tpl) {
                tpl.addEventListener('change', function () {
                    var id = tpl.value;
                    if (!id) { lastTpl = ''; status(''); return; }
                    var dirty = (subject && subject.value.trim() !== '') || (body && body.value.trim() !== '');
                    if (dirty && !window.confirm('Esto reemplazará el asunto y el mensaje que ya escribiste. ¿Continuar?')) {
                        tpl.value = lastTpl;
                        return;
                    }
                    var token = ++tplToken;
                    var url = form.dataset.lweTemplateUrl.replace(/0$/, String(id));
                    status('Cargando plantilla…', false);
                    tpl.disabled = true;
                    fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                        .then(function (r) { if (!r.ok) throw new Error('http ' + r.status); return r.json(); })
                        .then(function (d) {
                            if (token !== tplToken) return;
                            if (subject && typeof d.subject === 'string') subject.value = d.subject;
                            if (body && typeof d.body === 'string') { body.value = d.body; grow(); }
                            lastTpl = id;
                            status('Plantilla aplicada. Revisa el texto antes de enviar.', false);
                        })
                        .catch(function () {
                            if (token !== tplToken) return;
                            tpl.value = lastTpl;
                            status('No se pudo cargar la plantilla. Intenta de nuevo.', true);
                        })
                        .then(function () { if (token === tplToken) tpl.disabled = false; });
                });
            }

            // ── Archivos (maximo 3, 10 MB, tipos permitidos) ──
            var input = q('[data-lwe-files]'), list = q('[data-lwe-filelist]'), msg = q('[data-lwe-files-msg]');
            var files = [];
            function human(n) { return n >= 1048576 ? (n / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(n / 1024)) + ' KB'; }
            function note(text) { if (!msg) return; msg.hidden = !text; msg.textContent = text || ''; }
            function sync() {
                if (typeof DataTransfer === 'function' && input) {
                    try {
                        var dt = new DataTransfer();
                        files.forEach(function (f) { dt.items.add(f); });
                        input.files = dt.files;
                    } catch (e) { /* navegador sin soporte: queda lo que eligio el usuario */ }
                }
                if (!list) return;
                list.innerHTML = '';
                files.forEach(function (f, i) {
                    var li = document.createElement('li');
                    var name = document.createElement('span'); name.className = 'lwe-fname'; name.textContent = f.name; name.title = f.name;
                    var size = document.createElement('span'); size.className = 'lwe-fsize'; size.textContent = human(f.size);
                    var x = document.createElement('button'); x.type = 'button'; x.className = 'lwe-x'; x.textContent = '×';
                    x.setAttribute('aria-label', 'Quitar ' + f.name);
                    x.addEventListener('click', function () { files.splice(i, 1); note(''); sync(); });
                    li.appendChild(name); li.appendChild(size); li.appendChild(x);
                    list.appendChild(li);
                });
            }
            if (input) {
                input.addEventListener('change', function () {
                    var problems = [];
                    Array.prototype.slice.call(input.files || []).forEach(function (f) {
                        var ext = (f.name.split('.').pop() || '').toLowerCase();
                        if (EXTS.indexOf(ext) === -1) { problems.push('«' + f.name + '» no es un tipo permitido (PDF, Word, Excel, PNG, JPG o ZIP).'); return; }
                        if (f.size > MAX_BYTES) { problems.push('«' + f.name + '» pesa ' + human(f.size) + ' y el máximo es 10 MB.'); return; }
                        if (files.some(function (g) { return g.name === f.name && g.size === f.size; })) return;
                        if (files.length >= MAX_FILES) { problems.push('Solo puedes adjuntar hasta ' + MAX_FILES + ' archivos; «' + f.name + '» no se agregó.'); return; }
                        files.push(f);
                    });
                    note(problems.join(' '));
                    sync();
                });
            }

            // ── Envio (evita doble clic) ──
            var send = q('[data-lwe-send]'), sendLabel = q('[data-lwe-send-label]');
            var sending = false;
            form.addEventListener('submit', function (e) {
                if (sending) { e.preventDefault(); return; }
                var to = q('#lwe-to');
                if (to && !to.value.trim()) { e.preventDefault(); to.setAttribute('aria-invalid', 'true'); to.focus(); return; }
                if (subject && !subject.value.trim()) { e.preventDefault(); subject.setAttribute('aria-invalid', 'true'); subject.focus(); return; }
                if (body && !body.value.trim()) { e.preventDefault(); body.setAttribute('aria-invalid', 'true'); body.focus(); return; }
                sending = true;
                if (send) { send.disabled = true; send.setAttribute('aria-busy', 'true'); }
                if (sendLabel) sendLabel.textContent = 'Enviando…';
            });
            // Si el usuario regresa con "atras" (cache del navegador), reactiva el boton.
            window.addEventListener('pageshow', function (e) {
                if (!e.persisted) return;
                sending = false;
                if (send) { send.disabled = false; send.removeAttribute('aria-busy'); }
                if (sendLabel) sendLabel.textContent = 'Enviar correo';
            });
            form.addEventListener('input', function (e) {
                if (e.target && e.target.getAttribute && e.target.getAttribute('aria-invalid') === 'true') e.target.removeAttribute('aria-invalid');
            });
        })();
    </script>
@endif
