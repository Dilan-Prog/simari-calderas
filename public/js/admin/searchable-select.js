/* Select con buscador (reutilizable): cualquier <select data-searchable> se convierte en una lista desplegable con caja
   de busqueda (sin acentos ni mayusculas, varias palabras), teclado (flechas, Enter, Esc) y sin perder el <select> real:
   el formulario sigue enviando su valor y se dispara "change" al elegir. Sin dependencias ni build. */
(function () {
    'use strict';

    var MAX_VISIBLE = 300;

    function norm(s) {
        return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();
    }

    function injectStyle() {
        if (document.getElementById('ssStyle')) return;
        var css = '' +
            '.ss{position:relative;width:100%}' +
            '.ss-native{position:absolute!important;opacity:0;pointer-events:none;width:1px;height:1px;left:0;top:0;margin:0!important;padding:0!important;border:0!important}' +
            '.ss-btn{width:100%;height:40px;padding:0 36px 0 12px;border:1px solid #D1D5DB;border-radius:6px;background:#fff;color:#111827;font:inherit;font-size:14px;text-align:left;cursor:pointer;display:flex;align-items:center;position:relative;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}' +
            '.ss-btn:focus{outline:none;border-color:var(--secondary-color,#ff6213);box-shadow:0 0 0 3px rgba(255,98,19,.15)}' +
            '.ss-btn::after{content:"";position:absolute;right:13px;top:50%;width:7px;height:7px;border-right:2px solid #6B7280;border-bottom:2px solid #6B7280;transform:translateY(-70%) rotate(45deg)}' +
            '.ss-btn.is-placeholder{color:#6B7280}' +
            '.ss-label{overflow:hidden;text-overflow:ellipsis}' +
            '.ss-panel{position:absolute;z-index:60;left:0;right:0;top:calc(100% + 4px);background:#fff;border:1px solid #D1D5DB;border-radius:8px;box-shadow:0 12px 30px rgba(0,0,0,.14);overflow:hidden}' +
            '.ss-panel[hidden]{display:none}' +
            '.ss-search{width:100%;height:40px;border:0;border-bottom:1px solid #E5E7EB;padding:0 12px;font:inherit;font-size:14px;outline:none;box-sizing:border-box}' +
            '.ss-list{list-style:none;margin:0;padding:4px;max-height:260px;overflow-y:auto}' +
            '.ss-opt{padding:8px 10px;border-radius:6px;font-size:14px;color:#111827;cursor:pointer}' +
            '.ss-opt.is-active{background:#FFF1E8}' +
            '.ss-opt.is-selected{font-weight:700}' +
            '.ss-opt.is-clear{color:#6B7280}' +
            '.ss-empty{margin:0;padding:12px;font-size:13px;color:#6B7280}' +
            '.ss-more{margin:0;padding:6px 12px 10px;font-size:12px;color:#9CA3AF}';
        var st = document.createElement('style');
        st.id = 'ssStyle';
        st.textContent = css;
        document.head.appendChild(st);
    }

    function enhance(select) {
        if (select.dataset.ssReady) return;
        select.dataset.ssReady = '1';
        injectStyle();

        var wrap = document.createElement('div');
        wrap.className = 'ss';
        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);
        select.classList.add('ss-native');
        select.tabIndex = -1;
        select.setAttribute('aria-hidden', 'true');

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ss-btn';
        btn.setAttribute('aria-haspopup', 'listbox');
        btn.setAttribute('aria-expanded', 'false');
        var labelEl = select.id ? document.querySelector('label[for="' + select.id + '"]') : null;
        if (labelEl) {
            if (!labelEl.id) labelEl.id = select.id + '-label';
            btn.setAttribute('aria-labelledby', labelEl.id);
        }
        var btnText = document.createElement('span');
        btnText.className = 'ss-label';
        btn.appendChild(btnText);

        var panel = document.createElement('div');
        panel.className = 'ss-panel';
        panel.hidden = true;
        var search = document.createElement('input');
        search.type = 'text';
        search.className = 'ss-search';
        search.placeholder = select.dataset.searchPlaceholder || 'Buscar…';
        search.setAttribute('aria-label', 'Buscar');
        search.autocomplete = 'off';
        var list = document.createElement('ul');
        list.className = 'ss-list';
        list.setAttribute('role', 'listbox');
        var empty = document.createElement('p');
        empty.className = 'ss-empty';
        empty.textContent = select.dataset.emptyText || 'Sin resultados';
        empty.hidden = true;
        var more = document.createElement('p');
        more.className = 'ss-more';
        more.hidden = true;
        panel.appendChild(search);
        panel.appendChild(list);
        panel.appendChild(empty);
        panel.appendChild(more);
        wrap.appendChild(btn);
        wrap.appendChild(panel);

        var items = [];
        var active = -1;

        function readOptions() {
            items = [].slice.call(select.options).filter(function (o) { return !o.disabled; }).map(function (o) {
                return { value: o.value, text: o.textContent.replace(/\s+/g, ' ').trim(), n: norm(o.textContent) };
            });
        }

        function syncButton() {
            var opt = select.options[select.selectedIndex];
            var isPlaceholder = !opt || opt.value === '';
            btnText.textContent = opt ? opt.textContent.replace(/\s+/g, ' ').trim() : '';
            btn.classList.toggle('is-placeholder', isPlaceholder);
        }

        function render() {
            var words = norm(search.value).split(/\s+/).filter(Boolean);
            var shown = 0, hidden = 0, firstIdx = -1;
            list.innerHTML = '';
            items.forEach(function (it) {
                var ok = it.value === '' ? words.length === 0 : words.every(function (w) { return it.n.indexOf(w) !== -1; });
                if (!ok) return;
                if (shown >= MAX_VISIBLE) { hidden++; return; }
                var li = document.createElement('li');
                li.className = 'ss-opt' + (it.value === '' ? ' is-clear' : '') + (it.value === select.value ? ' is-selected' : '');
                li.setAttribute('role', 'option');
                li.dataset.value = it.value;
                li.textContent = it.text;
                li.setAttribute('aria-selected', it.value === select.value ? 'true' : 'false');
                list.appendChild(li);
                if (firstIdx === -1) firstIdx = shown;
                shown++;
            });
            empty.hidden = shown > 0;
            more.hidden = hidden === 0;
            more.textContent = hidden ? 'Hay ' + hidden + ' resultados más: escribe para afinar la búsqueda.' : '';
            setActive(words.length && shown > 0 ? (list.children[0] && list.children[0].classList.contains('is-clear') ? 1 : 0) : indexOfSelected());
        }

        function indexOfSelected() {
            for (var i = 0; i < list.children.length; i++) {
                if (list.children[i].dataset.value === select.value) return i;
            }
            return list.children.length ? 0 : -1;
        }

        function setActive(i) {
            if (i >= list.children.length) i = list.children.length - 1;
            active = i;
            for (var k = 0; k < list.children.length; k++) list.children[k].classList.toggle('is-active', k === i);
            if (i >= 0 && list.children[i]) list.children[i].scrollIntoView({ block: 'nearest' });
        }

        function open() {
            if (!panel.hidden) return;
            readOptions();
            search.value = '';
            render();
            panel.hidden = false;
            btn.setAttribute('aria-expanded', 'true');
            search.focus();
        }

        function close(focusBtn) {
            if (panel.hidden) return;
            panel.hidden = true;
            btn.setAttribute('aria-expanded', 'false');
            if (focusBtn) btn.focus();
        }

        function choose(value) {
            if (select.value !== value) {
                select.value = value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
            syncButton();
            close(true);
        }

        btn.addEventListener('click', function () { panel.hidden ? open() : close(true); });
        btn.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); open(); }
        });
        search.addEventListener('input', render);
        search.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown') { e.preventDefault(); setActive(Math.min(active + 1, list.children.length - 1)); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(Math.max(active - 1, 0)); }
            else if (e.key === 'Enter') { e.preventDefault(); if (active >= 0 && list.children[active]) choose(list.children[active].dataset.value); }
            else if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); close(true); }
            else if (e.key === 'Tab') { close(false); }
        });
        list.addEventListener('mousedown', function (e) { e.preventDefault(); });
        list.addEventListener('click', function (e) {
            var li = e.target.closest('.ss-opt');
            if (li) choose(li.dataset.value);
        });
        list.addEventListener('mousemove', function (e) {
            var li = e.target.closest('.ss-opt');
            if (li) setActive([].indexOf.call(list.children, li));
        });
        document.addEventListener('mousedown', function (e) { if (!wrap.contains(e.target)) close(false); });
        select.addEventListener('change', syncButton);
        if (labelEl) labelEl.addEventListener('click', function (e) { e.preventDefault(); btn.focus(); });

        syncButton();
    }

    function init() {
        [].forEach.call(document.querySelectorAll('select[data-searchable]'), enhance);
    }

    window.SearchableSelect = { enhance: enhance, init: init };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
