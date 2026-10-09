import Alpine from './alpine-init.js';

/*
 * Catálogo público: filtros, orden y paginación por AJAX (sin recargar).
 *
 * Contrato con el servidor (misma ruta, Accept: application/json):
 *   { ok, total, liveMessage, title, url, page, pages, activeCount, noindex,
 *     sidebarHtml, chipsHtml, toolbarHtml, productsHtml, paginationHtml }
 * Cada `[data-catalog-region="<x>"]` recibe su HTML. Sin JS (o si el fetch falla)
 * todo sigue funcionando con navegación normal: los filtros son enlaces / un
 * <form method="GET">.
 *
 * Patrón de concurrencia igual al de searchOverlay (shared.js): debounce +
 * requestId (se descartan respuestas viejas) + AbortController.
 */

const DEBOUNCE_MS = 120;
const MOBILE_QUERY = '(max-width: 899.98px)';

const REGION_KEYS = {
    sidebar: 'sidebarHtml',
    chips: 'chipsHtml',
    toolbar: 'toolbarHtml',
    products: 'productsHtml',
    pagination: 'paginationHtml',
};

const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

const state = {
    requestId: 0,
    controller: null,
    timer: null,
    basePath: window.location.pathname,
    page: 1,
    drawerOpen: false,
};

const $ = (selector, scope = document) => scope.querySelector(selector);
const $$ = (selector, scope = document) => Array.from(scope.querySelectorAll(selector));
const region = (name) => $(`[data-catalog-region="${name}"]`);
const prefersReducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const isMobile = () => window.matchMedia(MOBILE_QUERY).matches;

function headerHeight() {
    const header = $('.eq-header');
    return header ? header.getBoundingClientRect().height : 0;
}

/* ── Estado de carga ─────────────────────────────────────────────────────── */

function setBusy(busy) {
    ['catalog-results', 'catalog-sidebar'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.setAttribute('aria-busy', busy ? 'true' : 'false');
    });
}

/* ── Navegación AJAX ─────────────────────────────────────────────────────── */

function navigate(url, { push = true } = {}) {
    let target;
    try {
        target = new URL(url, window.location.href);
    } catch (err) {
        window.location.assign(url);
        return;
    }

    // Otra ruta (otra categoría) cambia h1, breadcrumb, SEO y FAQ: navegación completa.
    if (target.origin !== window.location.origin || target.pathname !== state.basePath) {
        window.location.assign(target.href);
        return;
    }

    setBusy(true);
    clearTimeout(state.timer);
    state.timer = setTimeout(() => load(target.href, push), DEBOUNCE_MS);
}

async function load(url, push) {
    const requestId = ++state.requestId;
    if (state.controller) state.controller.abort();
    state.controller = new AbortController();

    try {
        const response = await fetch(url, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            signal: state.controller.signal,
        });
        if (!response.ok) throw new Error('catalog request failed: ' + response.status);
        const data = await response.json();
        if (requestId !== state.requestId) return; // llegó una respuesta más nueva
        if (!data || data.ok === false) throw new Error('catalog payload not ok');

        const finalUrl = resolveUrl(data.url, url);
        if (push) history.pushState({ catalog: true }, '', finalUrl);
        else if (data.url) history.replaceState({ catalog: true }, '', finalUrl);

        apply(data);
    } catch (err) {
        if (err && err.name === 'AbortError') return;
        if (requestId !== state.requestId) return;
        window.location.assign(url);
        return;
    } finally {
        if (requestId === state.requestId) setBusy(false);
    }
}

function resolveUrl(candidate, fallback) {
    try {
        const u = new URL(candidate || fallback, window.location.href);
        if (u.origin === window.location.origin) return u.pathname + u.search + u.hash;
    } catch (err) { /* usa el fallback */ }
    return fallback;
}

/* ── Aplicar la respuesta al DOM ─────────────────────────────────────────── */

function snapshotSidebar() {
    const container = region('sidebar');
    const scroller = $('[data-catalog-scroll]');
    const active = document.activeElement;
    const snapshot = {
        scrollTop: scroller ? scroller.scrollTop : 0,
        expanded: [],
        focusId: null,
        focusSelector: null,
    };
    if (!container) return snapshot;

    $$('[data-facet-group]', container).forEach((group) => {
        const more = $('[data-catalog-more]', group);
        if (more && more.getAttribute('aria-expanded') === 'true') {
            snapshot.expanded.push(group.dataset.facetGroup);
        }
    });

    if (active && container.contains(active)) {
        if (active.id) {
            snapshot.focusId = active.id;
        } else if (active.name) {
            snapshot.focusSelector = `[name="${CSS.escape(active.name)}"]`;
        }
    }
    return snapshot;
}

function restoreSidebar(snapshot) {
    const container = region('sidebar');
    if (!container) return;

    snapshot.expanded.forEach((groupId) => {
        const group = container.querySelector(`[data-facet-group="${CSS.escape(groupId)}"]`);
        const more = group && $('[data-catalog-more]', group);
        if (more) setMoreExpanded(more, true);
    });

    const scroller = $('[data-catalog-scroll]');
    if (scroller) scroller.scrollTop = snapshot.scrollTop;

    let toFocus = null;
    if (snapshot.focusId) toFocus = document.getElementById(snapshot.focusId);
    else if (snapshot.focusSelector) toFocus = container.querySelector(snapshot.focusSelector);
    if (toFocus) toFocus.focus({ preventScroll: true });
}

function setMoreExpanded(button, expanded) {
    const list = document.getElementById(button.getAttribute('aria-controls'));
    button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    button.textContent = expanded ? button.dataset.labelLess : button.dataset.labelMore;
    if (list) list.classList.toggle('is-collapsed', !expanded);
}

function setRobots(noindex) {
    let meta = $('meta[data-catalog-robots]');
    if (noindex) {
        if (!meta) {
            meta = document.createElement('meta');
            meta.setAttribute('name', 'robots');
            meta.setAttribute('data-catalog-robots', '');
            document.head.appendChild(meta);
        }
        meta.setAttribute('content', 'noindex,follow');
    } else if (meta) {
        meta.remove();
    }
}

function viewCountLabel(total) {
    const n = Number(total) || 0;
    return `Ver ${n.toLocaleString('es-MX')} ${n === 1 ? 'resultado' : 'resultados'}`;
}

function apply(data) {
    const sidebarSnapshot = snapshotSidebar();
    const previousPage = state.page;
    const pageChanged = typeof data.page === 'number' && data.page !== previousPage;

    Object.entries(REGION_KEYS).forEach(([name, key]) => {
        const el = region(name);
        if (el && typeof data[key] === 'string') el.innerHTML = data[key];
    });

    restoreSidebar(sidebarSnapshot);

    const live = document.getElementById('catalog-live');
    if (live) live.textContent = data.liveMessage || '';

    if (data.title) document.title = data.title;
    setRobots(!!data.noindex);

    const viewCount = $('[data-catalog-view-count]');
    if (viewCount) viewCount.textContent = viewCountLabel(data.total);

    // El botón "Filtros (N)" se volvió a pintar: respeta el estado del drawer.
    $$('[data-catalog-drawer-open]').forEach((btn) => btn.setAttribute('aria-expanded', state.drawerOpen ? 'true' : 'false'));

    if (typeof data.page === 'number') {
        state.page = data.page;
        const results = document.getElementById('catalog-results');
        if (results) results.dataset.catalogPage = String(data.page);
    }

    if (state.drawerOpen) return; // el fondo está bloqueado: no hay que mover la página

    if (pageChanged) {
        scrollToResults(true);
    } else {
        const grid = region('products');
        if (grid) {
            const top = grid.getBoundingClientRect().top;
            if (top < headerHeight() || top > window.innerHeight) scrollToResults(false);
        }
    }
}

function scrollToResults(focusHeading) {
    const results = document.getElementById('catalog-results');
    if (!results) return;
    results.scrollIntoView({ behavior: prefersReducedMotion() ? 'auto' : 'smooth', block: 'start' });
    if (focusHeading) {
        const heading = document.getElementById('catalog-results-heading');
        if (heading) heading.focus({ preventScroll: true });
    }
}

/* ── Drawer móvil de filtros ─────────────────────────────────────────────── */

function sidebarEl() {
    return document.getElementById('catalog-sidebar');
}

function openDrawer() {
    const aside = sidebarEl();
    if (!aside || state.drawerOpen || !isMobile()) return;
    state.drawerOpen = true;

    aside.classList.add('is-open');
    aside.setAttribute('role', 'dialog');
    aside.setAttribute('aria-modal', 'true');
    const overlay = $('[data-catalog-overlay]');
    if (overlay) overlay.hidden = false;
    document.documentElement.classList.add('catalog-drawer-open');
    $$('[data-catalog-drawer-open]').forEach((btn) => btn.setAttribute('aria-expanded', 'true'));

    requestAnimationFrame(() => {
        const close = $('.catalog-sidebar__close', aside);
        if (close) close.focus({ preventScroll: true });
    });
}

function closeDrawer({ restoreFocus = true } = {}) {
    const aside = sidebarEl();
    if (!aside || !state.drawerOpen) return;
    state.drawerOpen = false;

    aside.classList.remove('is-open');
    aside.removeAttribute('role');
    aside.removeAttribute('aria-modal');
    const overlay = $('[data-catalog-overlay]');
    if (overlay) overlay.hidden = true;
    document.documentElement.classList.remove('catalog-drawer-open');
    $$('[data-catalog-drawer-open]').forEach((btn) => btn.setAttribute('aria-expanded', 'false'));

    // Devuelve el foco al disparador (el botón se vuelve a pintar con cada respuesta, por eso se busca de nuevo).
    if (restoreFocus) {
        const opener = $('[data-catalog-drawer-open]');
        if (opener && opener.offsetParent !== null) opener.focus({ preventScroll: true });
    }
}

function trapFocus(event) {
    const aside = sidebarEl();
    if (!aside) return;
    const items = $$(FOCUSABLE, aside).filter((el) => el.offsetParent !== null);
    if (items.length === 0) return;
    const first = items[0];
    const last = items[items.length - 1];
    const active = document.activeElement;

    if (!aside.contains(active)) {
        event.preventDefault();
        first.focus();
    } else if (event.shiftKey && active === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && active === last) {
        event.preventDefault();
        first.focus();
    }
}

/* ── Utilidades de eventos ───────────────────────────────────────────────── */

function inCatalog(el) {
    return !!(el && el.closest && el.closest('.eq-shop-catalog'));
}

function isPlainLeftClick(event) {
    return event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey;
}

function priceUrlFromForm(form) {
    const url = new URL(window.location.href);
    url.searchParams.delete('page');
    url.searchParams.delete('precio_min');
    url.searchParams.delete('precio_max');

    const clean = (name) => {
        const input = form.elements[name];
        const digits = input ? String(input.value).replace(/[^\d]/g, '') : '';
        return digits === '' ? null : parseInt(digits, 10);
    };
    let min = clean('precio_min');
    let max = clean('precio_max');
    if (min !== null && max !== null && min > max) [min, max] = [max, min];

    if (min !== null) url.searchParams.set('precio_min', String(min));
    if (max !== null) url.searchParams.set('precio_max', String(max));
    return url.href;
}

/* ── Listeners delegados (sobreviven al reemplazo de innerHTML) ──────────── */

document.documentElement.classList.add('catalog-js');
history.replaceState({ catalog: true }, '', window.location.href);

const initialResults = document.getElementById('catalog-results');
if (initialResults) state.page = parseInt(initialResults.dataset.catalogPage || '1', 10) || 1;

document.addEventListener('change', (event) => {
    const target = event.target;
    if (!inCatalog(target)) return;

    if (target.matches('input[type="checkbox"][data-href]')) {
        navigate(target.dataset.href);
        return;
    }
    if (target.matches('select[data-catalog-sort]')) {
        const option = target.selectedOptions[0];
        if (option && option.dataset.href) navigate(option.dataset.href);
    }
});

document.addEventListener('click', (event) => {
    const target = event.target;
    if (!target || !target.closest) return;

    const more = target.closest('[data-catalog-more]');
    if (more && inCatalog(more)) {
        setMoreExpanded(more, more.getAttribute('aria-expanded') !== 'true');
        return;
    }

    if (target.closest('[data-catalog-drawer-open]')) {
        openDrawer();
        return;
    }
    if (target.closest('[data-catalog-drawer-close]') || target.closest('[data-catalog-overlay]')) {
        closeDrawer();
        return;
    }

    const link = target.closest('[data-catalog-link], .shop-pagination a');
    if (!link || !inCatalog(link) || !link.href) return;
    if (event.defaultPrevented || !isPlainLeftClick(event)) return;
    if (link.target && link.target !== '_self') return;

    event.preventDefault();
    navigate(link.href);
});

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!form || !form.matches || !inCatalog(form)) return;

    if (form.matches('[data-catalog-price-form]')) {
        event.preventDefault();
        navigate(priceUrlFromForm(form));
    } else if (form.matches('[data-catalog-filters-form]')) {
        // Con JS cada casilla ya navega sola; el envío nativo (Enter) sería una recarga innecesaria.
        event.preventDefault();
    }
});

document.addEventListener('keydown', (event) => {
    if (!state.drawerOpen) return;
    if (event.key === 'Escape') {
        event.preventDefault();
        closeDrawer();
    } else if (event.key === 'Tab') {
        trapFocus(event);
    }
});

window.addEventListener('popstate', () => {
    if (window.location.pathname !== state.basePath) {
        window.location.reload();
        return;
    }
    clearTimeout(state.timer);
    setBusy(true);
    load(window.location.href, false);
});

// Al ensanchar la ventana a escritorio el drawer deja de existir.
const mobileMq = window.matchMedia(MOBILE_QUERY);
const onBreakpoint = (e) => { if (!e.matches) closeDrawer({ restoreFocus: false }); };
if (mobileMq.addEventListener) mobileMq.addEventListener('change', onBreakpoint);
else if (mobileMq.addListener) mobileMq.addListener(onBreakpoint);

Alpine.start();
