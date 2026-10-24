/**
 * Notificaciones del panel: campana del sidebar, panel con la lista guardada, toasts y titulo parpadeante.
 * Contrato: GET admin.notifications.feed (?after_id), POST .read ({ids}|{all}), POST .prefs ({toast_seconds}).
 * Cada respuesta del feed dispara window 'equiterm:hot' con detail = {leads:[ids], tasks:[ids]}
 * (para resaltar filas en bandeja/embudo/tareas; escuchar con window.addEventListener('equiterm:hot', e => e.detail)).
 */
// Aviso emergente reutilizable por cualquier pantalla (confirmaciones y errores): window.eqToast({ type: 'ok'|'err'|'task'..., title, body, seconds }).
// Usa el mismo contenedor, estilo y duracion (preferencia del usuario) que los avisos de la campana.
window.eqToastSeconds = window.eqToastSeconds ?? 30;
window.eqToast = function (it) {
    const wrap = document.getElementById('eqToasts');
    if (!wrap) return;
    const paths = {
        ok: '<path d="M20 6L9 17l-5-5"/>',
        err: '<circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>',
        lead_incoming: '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        lead_reply: '<polyline points="9 17 4 12 9 7"/><path d="M20 18v-2a4 4 0 0 0-4-4H4"/>',
        task: '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
    };
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const t = document.createElement('div');
    t.className = 'eq-toast' + (it.type === 'ok' ? ' is-ok' : it.type === 'err' ? ' is-err' : '');
    t.setAttribute('role', it.type === 'err' ? 'alert' : 'status');
    t.innerHTML = '<span class="eq-notif-ico"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + (paths[it.type] || paths.task) + '</svg></span>' +
        '<span class="eq-notif-txt"><span class="eq-notif-title">' + esc(it.title) + '</span>' + (it.body ? '<span class="eq-notif-body">' + esc(it.body) + '</span>' : '') + '</span>';
    const x = document.createElement('button');
    x.type = 'button'; x.className = 'eq-toast-close'; x.setAttribute('aria-label', 'Cerrar aviso'); x.innerHTML = '&times;';
    t.appendChild(x);
    while (wrap.children.length >= 4) wrap.firstElementChild.remove();
    const close = () => { t.classList.add('is-leaving'); setTimeout(() => t.remove(), 220); };
    const secs = it.seconds ?? window.eqToastSeconds;
    if (secs > 0) {
        const bar = document.createElement('div');
        bar.className = 'eq-toast-bar';
        t.appendChild(bar);
        const anim = bar.animate([{ transform: 'scaleX(1)' }, { transform: 'scaleX(0)' }], { duration: secs * 1000, fill: 'forwards', easing: 'linear' });
        anim.onfinish = close;
        t.addEventListener('mouseenter', () => anim.pause());
        t.addEventListener('mouseleave', () => anim.play());
    }
    x.addEventListener('click', (e) => { e.stopPropagation(); close(); });
    t.addEventListener('click', () => { close(); if (it.url) window.location.href = it.url; });
    wrap.appendChild(t);
};

(function () {
    const btn = document.getElementById('eqNotifBtn');
    const wrap = document.getElementById('eqToasts');
    if (!btn || !btn.dataset.feedUrl || !wrap) return;

    const badge = document.getElementById('eqNotifBadge');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const MAX_TOASTS = 4, POLL_MS = 8000;
    const state = { latest: 0, unread: 0, seconds: 30, items: [], first: true, timer: null, busy: false };
    const baseTitle = document.title;
    let blinkTimer = null, blinkOn = false;

    const ICONS = {
        lead_incoming: '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        lead_reply: '<polyline points="9 17 4 12 9 7"/><path d="M20 18v-2a4 4 0 0 0-4-4H4"/>',
        task: '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
    };
    const icon = (t) => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + (ICONS[t] || ICONS.task) + '</svg>';
    const el = (tag, cls, html) => { const e = document.createElement(tag); if (cls) e.className = cls; if (html != null) e.innerHTML = html; return e; };
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    const api = (url, opts = {}) => fetch(url, {
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf, ...(opts.body ? { 'Content-Type': 'application/json' } : {}) },
        ...opts,
    }).then((r) => { if (!r.ok) throw new Error(r.status); return r.json(); });

    // ---- Panel ----
    const panel = el('div', 'eq-notif-panel');
    panel.id = 'eqNotifPanel'; panel.hidden = true;
    panel.setAttribute('role', 'dialog'); panel.setAttribute('aria-label', 'Notificaciones');
    panel.innerHTML =
        '<div class="eq-notif-head"><h2>Notificaciones</h2><button type="button" class="eq-notif-link" data-act="all">Marcar todas como leídas</button></div>' +
        '<ul class="eq-notif-list" data-list></ul>' +
        '<div class="eq-notif-foot"><label>Duración del aviso (s)<input type="number" min="0" max="300" data-secs></label><span class="eq-notif-hint">0 = no se cierra solo</span></div>';
    document.body.appendChild(panel);
    const listEl = panel.querySelector('[data-list]');
    const secsEl = panel.querySelector('[data-secs]');

    function renderList() {
        listEl.innerHTML = '';
        if (!state.items.length) { listEl.appendChild(el('li', 'eq-notif-empty', 'Sin notificaciones')); return; }
        state.items.forEach((it) => {
            const li = document.createElement('li');
            const b = el('button', 'eq-notif-item' + (it.read ? '' : ' is-unread'),
                '<span class="eq-notif-ico">' + icon(it.type) + '</span><span class="eq-notif-txt"><span class="eq-notif-title">' + esc(it.title) +
                '</span><span class="eq-notif-body">' + esc(it.body) + '</span><span class="eq-notif-ago">' + esc(it.ago) + '</span></span><span class="eq-notif-dot"></span>');
            b.type = 'button';
            b.addEventListener('click', () => open(it));
            li.appendChild(b); listEl.appendChild(li);
        });
    }

    function renderBadge() {
        const n = state.unread;
        badge.hidden = n <= 0;
        badge.textContent = n > 99 ? '99+' : String(n);
        btn.classList.toggle('has-unread', n > 0);
        btn.setAttribute('aria-label', n > 0 ? 'Notificaciones, ' + n + ' sin leer' : 'Notificaciones');
        if (n > 0 && !blinkTimer) {
            blinkTimer = setInterval(() => { blinkOn = !blinkOn; document.title = blinkOn ? '(' + state.unread + ') Equiterm' : baseTitle; }, 1500);
        } else if (n <= 0 && blinkTimer) { clearInterval(blinkTimer); blinkTimer = null; document.title = baseTitle; }
    }

    function markRead(payload) {
        return api(btn.dataset.readUrl, { method: 'POST', body: JSON.stringify(payload) })
            .then((r) => { state.unread = r.unread ?? state.unread; renderBadge(); }).catch(() => {});
    }

    function open(it) {
        if (!it.read) { it.read = true; markRead({ ids: [it.id] }); }
        if (it.url) window.location.href = it.url; else { renderList(); }
    }

    panel.querySelector('[data-act="all"]').addEventListener('click', () => {
        state.items.forEach((i) => { i.read = true; });
        state.unread = 0; renderBadge(); renderList(); markRead({ all: true });
    });

    let prefsT = null;
    secsEl.addEventListener('input', () => {
        clearTimeout(prefsT);
        prefsT = setTimeout(() => {
            let v = parseInt(secsEl.value, 10);
            if (isNaN(v)) return;
            v = v === 0 ? 0 : Math.min(300, Math.max(5, v));
            api(btn.dataset.prefsUrl, { method: 'POST', body: JSON.stringify({ toast_seconds: v }) })
                .then((r) => { state.seconds = r.toast_seconds; secsEl.value = r.toast_seconds; }).catch(() => {});
        }, 600);
    });

    function position() {
        const r = btn.getBoundingClientRect(), w = panel.offsetWidth, h = panel.offsetHeight;
        let left = r.right + 10;
        if (left + w > window.innerWidth - 8) left = Math.max(8, window.innerWidth - w - 8);
        const top = Math.max(8, Math.min(r.bottom - h, window.innerHeight - h - 8));
        panel.style.left = left + 'px'; panel.style.top = top + 'px';
    }
    function setOpen(v) {
        panel.hidden = !v; btn.setAttribute('aria-expanded', String(v));
        if (v) { renderList(); secsEl.value = state.seconds; position(); panel.querySelector('button')?.focus(); } else btn.focus();
    }
    btn.addEventListener('click', (e) => { e.stopPropagation(); setOpen(panel.hidden); });
    document.addEventListener('click', (e) => { if (!panel.hidden && !panel.contains(e.target) && !btn.contains(e.target)) setOpen(false); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !panel.hidden) setOpen(false); });
    window.addEventListener('resize', () => { if (!panel.hidden) position(); });

    // ---- Toasts ----
    function toast(it) {
        while (wrap.children.length >= MAX_TOASTS) wrap.firstElementChild.remove();
        const t = el('div', 'eq-toast', '<span class="eq-notif-ico">' + icon(it.type) + '</span><span class="eq-notif-txt"><span class="eq-notif-title">' + esc(it.title) +
            '</span><span class="eq-notif-body">' + esc(it.body) + '</span></span>');
        t.setAttribute('role', 'status');
        const x = el('button', 'eq-toast-close', '&times;'); x.type = 'button'; x.setAttribute('aria-label', 'Cerrar aviso');
        t.appendChild(x);
        const secs = state.seconds;
        let anim = null;
        const close = () => { t.classList.add('is-leaving'); setTimeout(() => t.remove(), 220); };
        if (secs > 0) {
            const bar = el('div', 'eq-toast-bar'); t.appendChild(bar);
            anim = bar.animate([{ transform: 'scaleX(1)' }, { transform: 'scaleX(0)' }], { duration: secs * 1000, fill: 'forwards', easing: 'linear' });
            anim.onfinish = close;
            t.addEventListener('mouseenter', () => anim.pause());
            t.addEventListener('mouseleave', () => anim.play());
        }
        x.addEventListener('click', (e) => { e.stopPropagation(); close(); });
        t.addEventListener('click', () => { close(); open(it); });
        wrap.appendChild(t);
    }

    // ---- Polling ----
    function poll() {
        if (state.busy || document.hidden) return;
        state.busy = true;
        const url = btn.dataset.feedUrl + (state.latest ? '?after_id=' + state.latest : '');
        api(url).then((d) => {
            if (d.enabled === false) { btn.hidden = true; stop(); return; }
            state.seconds = d.toast_seconds ?? state.seconds;
            window.eqToastSeconds = state.seconds;
            state.unread = d.unread ?? 0;
            const items = d.items || [];
            if (state.first) {
                state.items = items;
            } else if (items.length) {
                const known = new Set(state.items.map((i) => i.id));
                const fresh = items.filter((i) => !known.has(i.id));
                state.items = fresh.concat(state.items).slice(0, 30);
                fresh.slice().reverse().forEach(toast);
            }
            if (d.latest_id) state.latest = Math.max(state.latest, d.latest_id);
            state.first = false;
            renderBadge();
            if (!panel.hidden) renderList();
            if (d.hot) window.dispatchEvent(new CustomEvent('equiterm:hot', { detail: d.hot }));
        }).catch(() => {}).finally(() => { state.busy = false; });
    }
    function start() { if (!state.timer) state.timer = setInterval(poll, POLL_MS); }
    function stop() { clearInterval(state.timer); state.timer = null; }
    document.addEventListener('visibilitychange', () => { if (document.hidden) stop(); else { poll(); start(); } });

    poll(); start();
})();
