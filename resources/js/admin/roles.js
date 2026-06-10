(function () {
    'use strict';

    var page         = document.querySelector('.roles-page');
    var urlShow      = page ? page.dataset.urlShow   : '';
    var urlUpdate    = page ? page.dataset.urlUpdate : '';
    var urlDelete    = page ? page.dataset.urlDelete : '';

    var overlay      = document.getElementById('roles-overlay');
    var drawerCreate = document.getElementById('roles-drawer-create');
    var drawerEdit   = document.getElementById('roles-drawer-edit');
    var modalShow    = document.getElementById('roles-modal-show');
    var modalDelete  = document.getElementById('roles-modal-delete');

    function showOverlay() { overlay.classList.add('is-visible'); }
    function hideOverlay()  { overlay.classList.remove('is-visible'); }

    function noOtherOpen() {
        return !drawerCreate.classList.contains('is-open') &&
               !drawerEdit.classList.contains('is-open') &&
               modalShow.style.display   === 'none' &&
               modalDelete.style.display === 'none';
    }

    window.openCreateDrawer = function () {
        drawerCreate.classList.add('is-open');
        showOverlay();
    };

    window.closeCreateDrawer = function () {
        drawerCreate.classList.remove('is-open');
        document.getElementById('roles-create-form').reset();
        clearAllModules('roles-create-form');
        if (noOtherOpen()) hideOverlay();
    };

    window.openEditDrawer = function (id, name, description, activeModules) {
        document.getElementById('edit-name').value = name;
        document.getElementById('edit-desc').value = description;
        document.getElementById('roles-edit-form').action = urlUpdate + '/' + id;

        document.getElementById('edit-modules-grid')
            .querySelectorAll('.roles-module-card')
            .forEach(function (card) {
                if (activeModules.indexOf(card.getAttribute('data-module')) !== -1) {
                    card.classList.add('is-active');
                } else {
                    card.classList.remove('is-active');
                }
            });

        drawerEdit.classList.add('is-open');
        showOverlay();
    };

    window.closeEditDrawer = function () {
        drawerEdit.classList.remove('is-open');
        if (noOtherOpen()) hideOverlay();
    };

    window.openShowModal = function (id) {
        var body = document.getElementById('roles-modal-show-body');
        body.innerHTML = '<div class="roles-modal-loading">Cargando...</div>';
        modalShow.style.display = 'flex';

        fetch(urlShow + '/' + id, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var role    = data.role;
            var perms   = data.permissions || [];
            var isAdmin = data.isAdmin || false;

            var chipsHtml = '';
            if (isAdmin) {
                chipsHtml = '<span class="roles-module-chip" style="background:#fff3ed;color:var(--secondary-color)">Todos los módulos</span>';
            } else if (perms.length > 0) {
                chipsHtml = perms.map(function (p) {
                    return '<span class="roles-module-chip">' + escHtml(p) + '</span>';
                }).join('');
            } else {
                chipsHtml = '<span style="color:#9ca3af;font-size:0.8rem">Sin permisos asignados</span>';
            }

            body.innerHTML =
                '<p class="roles-detail-name">'  + escHtml(role.name_role)           + '</p>' +
                '<p class="roles-detail-desc">'  + escHtml(role.description_role || '') + '</p>' +
                '<div class="roles-detail-row"><strong>Usuarios:</strong>&nbsp;' + role.users_count + '</div>' +
                '<div class="roles-detail-row"><strong>Creado:</strong>&nbsp;'   + escHtml(role.created_at) + '</div>' +
                '<div class="roles-label" style="margin-top:1rem;margin-bottom:0.5rem">Módulos con acceso</div>' +
                '<div class="roles-modules-list">' + chipsHtml + '</div>';
        })
        .catch(function () {
            body.innerHTML = '<p style="color:#ef4444;text-align:center">Error al cargar el rol.</p>';
        });
    };

    window.closeShowModal = function () {
        modalShow.style.display = 'none';
        if (noOtherOpen()) hideOverlay();
    };

    window.openDeleteModal = function (id, name, usersCount) {
        document.getElementById('roles-delete-name').textContent = name;
        document.getElementById('roles-delete-warning').textContent = usersCount > 0
            ? 'Este rol tiene ' + usersCount + ' usuario(s) asignado(s). Al eliminarlo perderán acceso al sistema.'
            : 'Esta acción no se puede deshacer.';
        document.getElementById('roles-delete-form').action = urlDelete + '/' + id;
        modalDelete.style.display = 'flex';
    };

    window.closeDeleteModal = function () {
        modalDelete.style.display = 'none';
        if (noOtherOpen()) hideOverlay();
    };

    window.toggleModule = function (card) {
        card.classList.toggle('is-active');
    };

    window.selectAllModules = function (formId) {
        document.getElementById(formId)
            .querySelectorAll('.roles-module-card')
            .forEach(function (c) { c.classList.add('is-active'); });
    };

    window.clearAllModules = function (formId) {
        document.getElementById(formId)
            .querySelectorAll('.roles-module-card')
            .forEach(function (c) { c.classList.remove('is-active'); });
    };

    window.serializePermissions = function (formId) {
        var form      = document.getElementById(formId);
        var container = form.querySelector('[id$="-permissions-container"]');
        container.innerHTML = '';
        form.querySelectorAll('.roles-module-card.is-active').forEach(function (card) {
            var input   = document.createElement('input');
            input.type  = 'hidden';
            input.name  = 'permissions[]';
            input.value = card.getAttribute('data-module');
            container.appendChild(input);
        });
    };

    window.closeAll = function () {
        drawerCreate.classList.remove('is-open');
        drawerEdit.classList.remove('is-open');
        modalShow.style.display   = 'none';
        modalDelete.style.display = 'none';
        hideOverlay();
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { closeAll(); }
    });

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
})();
