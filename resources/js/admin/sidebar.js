(function () {
    'use strict';

    const STORAGE_KEY = 'admin_sidebar_collapsed';
    const BREAKPOINT  = 1024;
    // Below this width (covers common 1280/1366/1440 laptop screens), the
    // 256px expanded sidebar leaves too little room for the content — most
    // module layouts (tables, two-column forms) were sized assuming more
    // space than that. Default to the 68px collapsed sidebar on first load
    // in this range to give those pages the room they need; a user who has
    // explicitly toggled the sidebar before always keeps their own choice.
    const LAPTOP_BREAKPOINT = 1440;

    const sidebar        = document.getElementById('adminSidebar');
    const adminMain      = document.querySelector('.admin-main');
    const collapseBtn    = document.getElementById('sidebarCollapseBtn');
    const mobileToggle   = document.getElementById('sidebarMobileToggle');
    const backdrop       = document.getElementById('sidebarBackdrop');
    const overlay        = document.getElementById('adminNavOverlay');
    const navItems       = document.querySelectorAll('.sidebar-nav-item[data-section]');
    const groupHeaders   = document.querySelectorAll('.sidebar-nav-group-header[data-group-toggle]');
    const GROUP_STORAGE_PREFIX = 'admin_sidebar_group_';

    function isMobile() {
        return window.innerWidth < BREAKPOINT;
    }

    function getDesiredCollapsed() {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored !== null) return stored === '1';
        return window.innerWidth < LAPTOP_BREAKPOINT;
    }

    /* ── Desktop: collapse / expand ── */
    // Applies the visual state only — used for the automatic laptop-width
    // default, which must NOT get written to localStorage, or it would
    // permanently overwrite "no preference yet" with whatever the very
    // first page load's width happened to produce.
    function applyCollapsed(collapsed) {
        sidebar.classList.toggle('collapsed', collapsed);
        if (adminMain) adminMain.classList.toggle('sidebar-collapsed', collapsed);
    }

    // Applies AND persists — used when the user explicitly toggles it.
    function setCollapsed(collapsed) {
        applyCollapsed(collapsed);
        localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
    }

    function toggleCollapse() {
        setCollapsed(!sidebar.classList.contains('collapsed'));
    }

    /* ── Mobile: open / close drawer ── */
    function openMobileSidebar() {
        sidebar.classList.add('is-open');
        backdrop.classList.add('is-visible');
        document.body.style.overflow = 'hidden';
    }

    function closeMobileSidebar() {
        sidebar.classList.remove('is-open');
        backdrop.classList.remove('is-visible');
        document.body.style.overflow = '';
    }

    /* ── Grupos del sidebar (Ecommerce / Servicios / Administración) ── */
    // Colapsados por defecto; se auto-expande únicamente el grupo que
    // contiene la sección activa, para no perder el contexto de dónde
    // está parado el usuario. La preferencia explícita de cada usuario
    // (una vez que hace click) se recuerda por grupo en localStorage.
    function getGroupExpanded(groupName, defaultExpanded) {
        const stored = localStorage.getItem(GROUP_STORAGE_PREFIX + groupName);
        if (stored !== null) return stored === '1';
        return defaultExpanded;
    }

    function setGroupExpanded(groupName, expanded) {
        localStorage.setItem(GROUP_STORAGE_PREFIX + groupName, expanded ? '1' : '0');
    }

    function applyGroupState(groupEl, expanded) {
        groupEl.classList.toggle('is-expanded', expanded);
    }

    function initGroups() {
        groupHeaders.forEach(function (header) {
            const groupEl = header.closest('.sidebar-nav-group');
            if (!groupEl) return;

            const groupName = header.dataset.groupToggle;
            const defaultExpanded = groupEl.classList.contains('has-active');
            applyGroupState(groupEl, getGroupExpanded(groupName, defaultExpanded));

            header.addEventListener('click', function () {
                const nowExpanded = !groupEl.classList.contains('is-expanded');
                applyGroupState(groupEl, nowExpanded);
                setGroupExpanded(groupName, nowExpanded);
            });
        });
    }

    /* ── Init ── */
    function init() {
        initGroups();
        if (!isMobile()) {
            applyCollapsed(getDesiredCollapsed());
        } else {
            sidebar.classList.remove('collapsed');
            if (adminMain) adminMain.classList.remove('sidebar-collapsed');
        }
    }

    /* ── Event listeners ── */
    if (collapseBtn) {
        collapseBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (!isMobile()) toggleCollapse();
        });
    }

    if (mobileToggle) {
        mobileToggle.addEventListener('click', function () {
            if (isMobile()) openMobileSidebar();
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeMobileSidebar);
    }

    /* Close mobile sidebar on nav item click */
    navItems.forEach(function (item) {
        item.addEventListener('click', function () {
            navItems.forEach(function (btn) { btn.classList.remove('active'); });
            this.classList.add('active');

            if (isMobile()) closeMobileSidebar();

            const isComingSoon = this.dataset.section === 'coming-soon';
            const hasHref      = this.hasAttribute('href') && this.getAttribute('href') !== '#' && this.getAttribute('href') !== '';

            if (!isComingSoon && hasHref && overlay) {
                overlay.classList.add('is-visible');
            }
        });
    });

    /* Resize handler: reset states when crossing breakpoint */
    window.addEventListener('resize', function () {
        if (!isMobile()) {
            closeMobileSidebar();
            applyCollapsed(getDesiredCollapsed());
        } else {
            sidebar.classList.remove('collapsed');
            if (adminMain) adminMain.classList.remove('sidebar-collapsed');
        }
    });

    /* Restore spinner overlay on back navigation */
    window.addEventListener('pageshow', function (e) {
        if (e.persisted && overlay) overlay.classList.remove('is-visible');
    });

    init();

})();
