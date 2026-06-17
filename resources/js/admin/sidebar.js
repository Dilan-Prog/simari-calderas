(function () {
    'use strict';

    const STORAGE_KEY = 'admin_sidebar_collapsed';
    const BREAKPOINT  = 1024;

    const sidebar        = document.getElementById('adminSidebar');
    const adminMain      = document.querySelector('.admin-main');
    const collapseBtn    = document.getElementById('sidebarCollapseBtn');
    const mobileToggle   = document.getElementById('sidebarMobileToggle');
    const backdrop       = document.getElementById('sidebarBackdrop');
    const overlay        = document.getElementById('adminNavOverlay');
    const navItems       = document.querySelectorAll('.sidebar-nav-item[data-section]');

    function isMobile() {
        return window.innerWidth < BREAKPOINT;
    }

    /* ── Desktop: collapse / expand ── */
    function setCollapsed(collapsed) {
        sidebar.classList.toggle('collapsed', collapsed);
        if (adminMain) adminMain.classList.toggle('sidebar-collapsed', collapsed);
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

    /* ── Init ── */
    function init() {
        if (!isMobile()) {
            const wasCollapsed = localStorage.getItem(STORAGE_KEY) === '1';
            setCollapsed(wasCollapsed);
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
            const wasCollapsed = localStorage.getItem(STORAGE_KEY) === '1';
            setCollapsed(wasCollapsed);
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
