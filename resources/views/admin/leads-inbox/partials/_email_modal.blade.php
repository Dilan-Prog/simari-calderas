{{-- Modal de correo de la bandeja: resumen de la conversación a la izquierda y el redactor a la derecha.
     Vars: $lead, $emailCompose. Se abre desde el boton "Correo" (cabecera, pestaña del compositor o ficha del lead).
     CSS y JS van en linea (prefijo lwm-), igual que el redactor: no requiere build.
     El resumen se clona del hilo de la pagina (data-lw-thread), asi que siempre refleja los ultimos mensajes. --}}
@php
    $mOld = (array) session()->getOldInput();
    $mSubmitted = array_key_exists('subject', $mOld) || array_key_exists('to', $mOld);
    $mFileErr = collect($errors->keys())->contains(fn ($k) => str_starts_with($k, 'files') || str_starts_with($k, 'quote_ids'));
    // Se reabre solo si el envio fallo (errores o aviso de error); un envio correcto deja el modal cerrado y muestra el aviso.
    $mReopen = (session('composer_tab') === 'correo' && session('error'))
        || ($errors->any() && $mSubmitted)
        || $errors->hasAny(['to', 'cc', 'subject'])
        || $mFileErr;
    $mInitials = collect(preg_split('/\s+/', trim((string) $lead->display_name)) ?: [])
        ->filter(fn ($p) => preg_match('/\pL/u', $p))->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') ?: '?';
    $mSub = $lead->contact_company ?: optional($lead->customer)->company ?: ($lead->contact_email ?: '');
@endphp

<div class="lwm" data-lwm hidden>
    <div class="lwm-backdrop" data-lwm-close></div>
    <div class="lwm-dialog" role="dialog" aria-modal="true" aria-labelledby="lwm-title">
        <header class="lwm-head">
            <div class="lwm-head-t">
                <span class="lwm-eyebrow">Nuevo correo</span>
                <h2 id="lwm-title">{{ $lead->display_name }}</h2>
            </div>
            <button type="button" class="lwm-x" data-lwm-close aria-label="Cerrar">&times;</button>
        </header>
        <div class="lwm-body">
            <aside class="lwm-chat" aria-label="Resumen de la conversación">
                <div class="lwm-chat-h">
                    <span class="lwm-av" aria-hidden="true">{{ $mInitials }}</span>
                    <span class="lwm-chat-who"><b>{{ $lead->display_name }}</b>@if ($mSub !== '')<small>{{ $mSub }}</small>@endif</span>
                </div>
                <p class="lwm-chat-cap">Resumen de la conversación · últimos mensajes</p>
                <div class="lwm-chat-list" data-lwm-chat tabindex="0"></div>
            </aside>
            <div class="lwm-compose lw-pane">
                @include('admin.leads-inbox.partials._email_composer', ['lead' => $lead, 'emailCompose' => $emailCompose ?? null])
            </div>
        </div>
    </div>
</div>

<style>
    html.lwm-lock { overflow: hidden; }
    .lwm { position: fixed; inset: 0; z-index: 1200; display: flex; align-items: center; justify-content: center; padding: 20px; }
    .lwm[hidden] { display: none; }
    .lwm-backdrop { position: absolute; inset: 0; background: rgba(15, 18, 24, .55); backdrop-filter: blur(2px); }
    .lwm-dialog { position: relative; display: flex; flex-direction: column; width: min(1180px, 100%); height: min(780px, 100%); background: var(--erp-surface); color: var(--erp-text); border-radius: 16px; box-shadow: 0 24px 70px rgba(0, 0, 0, .35); overflow: hidden; }
    .lwm-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 20px; border-bottom: 1px solid var(--erp-line-soft); flex-shrink: 0; }
    .lwm-head-t { min-width: 0; }
    .lwm-eyebrow { display: block; font-size: 11px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--erp-info); }
    .lwm-head h2 { margin: 2px 0 0; font-size: 18px; font-weight: 800; color: var(--erp-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lwm-x { border: 0; background: transparent; width: 36px; height: 36px; border-radius: 10px; font-size: 26px; line-height: 1; color: var(--erp-muted); cursor: pointer; flex-shrink: 0; }
    .lwm-x:hover { background: var(--erp-neu-bg); color: var(--erp-ink); }
    .lwm-x:focus-visible { outline: 2px solid var(--erp-accent); outline-offset: 2px; }
    .lwm-body { flex: 1; min-height: 0; display: grid; grid-template-columns: minmax(300px, 380px) minmax(0, 1fr); }

    .lwm-chat { display: flex; flex-direction: column; min-height: 0; border-right: 1px solid var(--erp-line-soft); background: var(--lw-chat-bg); }
    .lwm-chat-h { display: flex; align-items: center; gap: 10px; padding: 12px 16px; background: var(--erp-surface); border-bottom: 1px solid var(--erp-line-soft); }
    .lwm-av { width: 34px; height: 34px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: var(--erp-accent-soft); color: var(--erp-acc); font-size: 13px; font-weight: 800; flex-shrink: 0; }
    .lwm-chat-who { display: flex; flex-direction: column; min-width: 0; }
    .lwm-chat-who b { font-size: 13.5px; color: var(--erp-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lwm-chat-who small { font-size: 11.5px; color: var(--erp-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lwm-chat-cap { margin: 0; padding: 8px 16px 0; font-size: 11px; font-weight: 700; color: var(--erp-muted); }
    .lwm-chat-list { flex: 1; min-height: 0; overflow-y: auto; padding: 8px 14px 16px; display: flex; flex-direction: column; gap: 4px; background-image: radial-gradient(var(--lw-chat-dot) 1px, transparent 1px); background-size: 18px 18px; }
    .lwm-chat-list .lw-bubble { max-width: 94%; font-size: 13px; padding: 6px 9px 4px 10px; cursor: pointer; }
    .lwm-chat-list .lw-bubble-b { display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 6; line-clamp: 6; overflow: hidden; }
    .lwm-chat-list .lw-bubble.is-open .lw-bubble-b { display: block; -webkit-line-clamp: unset; line-clamp: unset; }
    .lwm-chat-list .lw-day { margin: 8px 0 4px; }
    .lwm-chat-empty { margin: auto; text-align: center; font-size: 12.5px; color: var(--erp-muted); }

    .lwm-compose { min-width: 0; min-height: 0; overflow-y: auto; overflow-x: hidden; padding: 16px 22px 0; -webkit-overflow-scrolling: touch; }
    .lwm .lwe-form { max-height: none; overflow: visible; padding: 0; gap: 12px; }
    .lwm .lw-pane .lwe-form textarea.lwe-body, .lwm .lwe-form textarea.lwe-body { min-height: 220px; max-height: none; }
    .lwm .lwe-form .lwe-foot { padding: 10px 0 14px; margin-top: 4px; }

    @media (max-width: 900px) {
        .lwm { padding: 0; }
        .lwm-dialog { width: 100%; height: 100%; border-radius: 0; }
        .lwm-body { grid-template-columns: minmax(0, 1fr); grid-template-rows: minmax(110px, 30%) minmax(0, 1fr); }
        .lwm-chat { border-right: 0; border-bottom: 1px solid var(--erp-line-soft); }
        .lwm-chat-cap { display: none; }
        .lwm-compose { padding: 12px 14px 0; }
    }
</style>

<script>
    (function () {
        var root = document.querySelector('[data-lwm]');
        if (!root || root.dataset.lwmReady) return;
        root.dataset.lwmReady = '1';

        var chat = root.querySelector('[data-lwm-chat]');
        var MAX_NODES = 14;
        var lastFocus = null, observer = null;

        function thread() { return document.querySelector('[data-lw-thread]'); }

        // Copia los ultimos mensajes del hilo (sin las acciones de cada correo) al panel izquierdo.
        function renderChat() {
            var t = thread();
            if (!t || !chat) return;
            var atBottom = !chat.children.length || chat.scrollHeight - chat.scrollTop - chat.clientHeight < 60;
            var nodes = [].slice.call(t.children).filter(function (n) { return n.matches('.lw-msg, .lw-event, .lw-day'); }).slice(-MAX_NODES);
            chat.innerHTML = '';
            if (!nodes.length) {
                var p = document.createElement('p');
                p.className = 'lwm-chat-empty';
                p.textContent = 'Aún no hay mensajes en esta conversación.';
                chat.appendChild(p);
                return;
            }
            nodes.forEach(function (n) {
                var c = n.cloneNode(true);
                c.querySelectorAll('.lwa-bar, form').forEach(function (x) { x.remove(); });
                chat.appendChild(c);
            });
            if (atBottom) chat.scrollTop = chat.scrollHeight;
        }

        function open() {
            if (!root.hidden) return;
            lastFocus = document.activeElement;
            root.hidden = false;
            document.documentElement.classList.add('lwm-lock');
            renderChat();
            chat.scrollTop = chat.scrollHeight;

            var t = thread();
            if (t && window.MutationObserver) {
                observer = new MutationObserver(renderChat);
                observer.observe(t, { childList: true });
            }

            var form = root.querySelector('[data-lwe-form]');
            if (!form) return;
            var body = form.querySelector('[data-lwe-body]');
            if (body) body.dispatchEvent(new Event('input')); // reajusta la altura del mensaje (estaba oculto)
            var to = form.querySelector('#lwe-to');
            var target = form.querySelector('[aria-invalid="true"]')
                || (to && !to.value.trim() ? to : null)
                || body;
            if (target) setTimeout(function () { target.focus(); }, 30);
        }

        function close() {
            if (root.hidden) return;
            root.hidden = true;
            document.documentElement.classList.remove('lwm-lock');
            if (observer) { observer.disconnect(); observer = null; }
            if (lastFocus && lastFocus.focus && document.contains(lastFocus)) lastFocus.focus();
        }

        // Disparadores: boton Correo de la cabecera, "Escribir" de la ficha y la pestaña Correo del compositor.
        // En fase de captura para que la pestaña no cambie de panel (el panel de correo ya no vive ahi).
        document.addEventListener('click', function (e) {
            var trigger = e.target.closest && e.target.closest('[data-lw-mail-open], .lw-tabs [data-tab="correo"]');
            if (!trigger) return;
            e.preventDefault();
            e.stopPropagation();
            open();
        }, true);

        root.addEventListener('click', function (e) {
            if (e.target.closest('[data-lwm-close]')) { close(); return; }
            var bubble = e.target.closest('.lwm-chat-list .lw-bubble');
            if (bubble) bubble.classList.toggle('is-open');
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !root.hidden) { e.preventDefault(); close(); }
        });

        @if ($mReopen)
            if (document.readyState === 'complete') open();
            else window.addEventListener('load', open);
        @endif
    })();
</script>
