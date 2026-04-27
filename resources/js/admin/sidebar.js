(function () {
    'use strict';

    const navItems = document.querySelectorAll('.sidebar-nav-item[data-section]');
    const overlay  = document.getElementById('adminNavOverlay');

    navItems.forEach(item => {
        item.addEventListener('click', function () {
            // Resaltar el ítem clickeado
            navItems.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            // Solo mostrar spinner si el item navega a una ruta real
            const isComingSoon = this.dataset.section === 'coming-soon';
            const hasHref      = this.hasAttribute('href') && this.getAttribute('href') !== '#' && this.getAttribute('href') !== '';

            if (!isComingSoon && hasHref && overlay) {
                overlay.classList.add('is-visible');
            }
        });
    });

    // Ocultar overlay si el usuario vuelve con el botón atrás del navegador
    window.addEventListener('pageshow', function (e) {
        if (e.persisted && overlay) overlay.classList.remove('is-visible');
    });

})();
