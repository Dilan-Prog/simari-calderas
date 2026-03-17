import './bootstrap';

// header

// Dropdowns del header en JavaScript puro (sin Alpine/Vue/React).
// Comportamiento soportado:
// - Hover: abre al entrar y cierra al salir.
// - Click: alterna abrir/cerrar.
// - Accesibilidad: aria-expanded, Escape para cerrar, ArrowDown para navegar.
// - Cierre global: click fuera del dropdown activo.
document.addEventListener('DOMContentLoaded', () => {
    // Si el header no existe en la pagina actual, no hacemos nada.
    const nav = document.querySelector('.main-nav-header');
    if (!nav) return;

    // Cada item con submenu.
    const dropdownItems = Array.from(nav.querySelectorAll('.has-dropdown'));

    // Guarda el dropdown actualmente abierto para cerrar los demas.
    let activeItem = null;
    const closeDelayMs = 300;
    const closeTimers = new WeakMap();

    // Cancela el cierre diferido de un dropdown.
    const cancelScheduledClose = (item) => {
        const timerId = closeTimers.get(item);
        if (!timerId) return;
        clearTimeout(timerId);
        closeTimers.delete(item);
    };

    // Programa cierre con retardo para evitar que se cierre al mover el cursor.
    const scheduleClose = (item) => {
        cancelScheduledClose(item);
        const timerId = setTimeout(() => {
            if (!item.matches(':hover')) {
                closeDropdown(item);
            }
        }, closeDelayMs);
        closeTimers.set(item, timerId);
    };

    // Cierra un dropdown concreto y limpia estado activo si corresponde.
    const closeDropdown = (item) => {
        if (!item) return;
        cancelScheduledClose(item);
        const button = item.querySelector('.nav-dropdown-toggle');
        const menu = item.querySelector('.dropdown-menu');
        if (!button || !menu) return;

        button.setAttribute('aria-expanded', 'false');
        menu.hidden = true;
        if (activeItem === item) activeItem = null;
    };

    // Abre un dropdown concreto y cierra cualquier otro abierto.
    const openDropdown = (item) => {
        cancelScheduledClose(item);
        const button = item.querySelector('.nav-dropdown-toggle');
        const menu = item.querySelector('.dropdown-menu');
        if (!button || !menu) return;

        if (activeItem && activeItem !== item) {
            closeDropdown(activeItem);
        }

        button.setAttribute('aria-expanded', 'true');
        menu.hidden = false;
        activeItem = item;
    };

    // Alterna estado abierto/cerrado para el dropdown del item.
    const toggleDropdown = (item) => {
        const button = item.querySelector('.nav-dropdown-toggle');
        if (!button) return;
        const isOpen = button.getAttribute('aria-expanded') === 'true';
        if (isOpen) {
            closeDropdown(item);
            return;
        }
        openDropdown(item);
    };

    // Eventos por cada dropdown.
    dropdownItems.forEach((item) => {
        const button = item.querySelector('.nav-dropdown-toggle');
        const menu = item.querySelector('.dropdown-menu');
        if (!button || !menu) return;

        // Click en boton: abre/cierra ese submenu.
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            cancelScheduledClose(item);
            toggleDropdown(item);
        });

        // Hover desktop.
        item.addEventListener('mouseenter', () => {
            cancelScheduledClose(item);
            openDropdown(item);
        });

        item.addEventListener('mouseleave', () => {
            scheduleClose(item);
        });

        // Si entra al menu, no cerrar; si sale, cerrar con retardo.
        menu.addEventListener('mouseenter', () => {
            cancelScheduledClose(item);
        });
        menu.addEventListener('mouseleave', () => {
            scheduleClose(item);
        });

        // Teclado: ArrowDown abre y enfoca el primer enlace del submenu.
        button.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                openDropdown(item);
                const firstLink = menu.querySelector('a');
                if (firstLink) firstLink.focus();
            }
        });
    });

    // Click fuera del dropdown activo: cerrar.
    document.addEventListener('click', (event) => {
        if (activeItem && !activeItem.contains(event.target)) {
            closeDropdown(activeItem);
        }
    });

    // Escape: cerrar dropdown activo y regresar foco al boton.
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && activeItem) {
            const button = activeItem.querySelector('.nav-dropdown-toggle');
            closeDropdown(activeItem);
            if (button) button.focus();
        }
    });
});

// end header


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
