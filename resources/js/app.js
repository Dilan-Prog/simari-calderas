import './bootstrap';


// ------------------------------------------------------------------ //
// MEGAMENÚ DESKTOP — hover con retardo, click para toggle              //
// ------------------------------------------------------------------ //
document.addEventListener('DOMContentLoaded', () => {
    const navbar = document.getElementById('navbar');
    if (!navbar) return;

    const megaItems = Array.from(navbar.querySelectorAll('.has-mega[data-dropdown]'));
    let activeItem = null;
    const DELAY = 180;
    const timers = new WeakMap();

    const cancelTimer = (item) => {
        if (timers.has(item)) { clearTimeout(timers.get(item)); timers.delete(item); }
    };

    const openMega = (item) => {
        cancelTimer(item);
        if (activeItem && activeItem !== item) closeMega(activeItem);
        const btn = item.querySelector('.nav-btn');
        if (btn) btn.setAttribute('aria-expanded', 'true');
        item.classList.add('is-open');
        activeItem = item;
    };

    const closeMega = (item) => {
        if (!item) return;
        cancelTimer(item);
        const btn = item.querySelector('.nav-btn');
        if (btn) btn.setAttribute('aria-expanded', 'false');
        item.classList.remove('is-open');
        if (activeItem === item) activeItem = null;
    };

    const scheduleClose = (item) => {
        cancelTimer(item);
        const id = setTimeout(() => { if (!item.matches(':hover')) closeMega(item); }, DELAY);
        timers.set(item, id);
    };

    megaItems.forEach((item) => {
        const btn  = item.querySelector('.nav-btn');
        const menu = item.querySelector('.megamenu, .megamenu-full');

        item.addEventListener('mouseenter', () => openMega(item));
        item.addEventListener('mouseleave', () => scheduleClose(item));

        if (menu) {
            menu.addEventListener('mouseenter', () => cancelTimer(item));
            menu.addEventListener('mouseleave', () => scheduleClose(item));
        }

        if (btn) {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                item.classList.contains('is-open') ? closeMega(item) : openMega(item);
            });
            btn.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    openMega(item);
                    const firstLink = menu && menu.querySelector('a');
                    if (firstLink) firstLink.focus();
                }
            });
        }
    });

    document.addEventListener('click', (e) => {
        if (activeItem && !activeItem.contains(e.target)) closeMega(activeItem);
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && activeItem) {
            const btn = activeItem.querySelector('.nav-btn');
            closeMega(activeItem);
            if (btn) btn.focus();
        }
    });
});

// ------------------------------------------------------------------ //
// MENÚ MÓVIL — drawer lateral                                          //
// ------------------------------------------------------------------ //
document.addEventListener('DOMContentLoaded', () => {
    const hamburger  = document.getElementById('nav-hamburger');
    const mobileMenu = document.getElementById('mobile-menu');
    const closeBtn   = document.getElementById('mobile-menu-close');
    const backdrop   = document.getElementById('mobile-menu-backdrop');

    if (!hamburger || !mobileMenu) return;

    const openMenu = () => {
        mobileMenu.classList.add('is-open');
        mobileMenu.setAttribute('aria-hidden', 'false');
        hamburger.setAttribute('aria-expanded', 'true');
        document.body.classList.add('mobile-menu-open');
    };

    const closeMenu = () => {
        mobileMenu.classList.remove('is-open');
        mobileMenu.setAttribute('aria-hidden', 'true');
        hamburger.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('mobile-menu-open');
    };

    hamburger.addEventListener('click', () => {
        mobileMenu.classList.contains('is-open') ? closeMenu() : openMenu();
    });

    if (closeBtn) closeBtn.addEventListener('click', closeMenu);
    if (backdrop) backdrop.addEventListener('click', closeMenu);

    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeMenu(); });

    mobileMenu.querySelectorAll('.mobile-dropdown-toggle').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const submenu = toggle.nextElementSibling;
            const isOpen  = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            if (submenu) submenu.hidden = isOpen;
        });
    });
});


// hydraulic engineering 

document.addEventListener("DOMContentLoaded", function () {
    const tabs = document.querySelectorAll(".hydraulic-info-tab");
    const panels = {
        incluye: document.getElementById("hydraulic-panel-incluye"),
        beneficios: document.getElementById("hydraulic-panel-beneficios"),
        proceso: document.getElementById("hydraulic-panel-proceso")
    };

    tabs.forEach((tab) => {
        tab.addEventListener("click", function () {
            const target = this.getAttribute("data-tab");

            tabs.forEach((item) => item.classList.remove("hydraulic-info-tab--active"));
            this.classList.add("hydraulic-info-tab--active");

            Object.values(panels).forEach((panel) => {
                panel.classList.remove("hydraulic-info-panel--active");
            });

            if (panels[target]) {
                panels[target].classList.add("hydraulic-info-panel--active");
            }
        });
    });
});

// end hydraulic engineering
