(function () {
    'use strict';

    const overlay = document.getElementById('roles-overlay');
    const drawerCreate = document.getElementById('roles-drawer-create');
    const drawerEdit   = document.getElementById('roles-drawer-edit');
    const modalShow    = document.getElementById('roles-modal-show');
    const modalDelete  = document.getElementById('roles-modal-delete');

    function showOverlay() {
        overlay.classList.add('is-visible');
    }
    function hideOverlay() {
        overlay.classList.remove('is-visible');
    }

    window.openCreateDrawer = function () {
        drawerCreate.classList.add('is-open');
        showOverlay();
    };
    window.closeCreateDrawer = function () {
        drawerCreate.classList.remove('is-open');
        hideOverlay();
    };

    window.openEditDrawer = function (id, name, description, activeModules) {
        document.getElementById('edit-name').value = name;
        document.getElementById('edit-desc').value = description;
        document.getElementById('roles-edit-form').action =
            document.getElementById('roles-edit-form').action.replace(/\/roles\/editar-rol\/\d*$/, '') + '/roles/editar-rol/' + id;

        const grid = document.getElementById('edit-modules-grid');
        grid.querySelectorAll('.roles-module-card').forEach(function (card) {
            const mod = card.getAttribute('data-module');
            if (activeModules.indexOf(mod) !== -1) {
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
        hideOverlay();
    };

    window.openShowModal = function (id) {
        const body = document.getElementById('roles-modal-show-body');
        body.innerHTML = '<div class="roles-modal-loading">Cargando...</div>';
        modalShow.style.display = 'flex';

        const base = window.location.pathname.replace(/\/roles(\/.*)?$/, '');
        fetch(base + '/roles/mostrar-rol/' + id, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            const role = data.role;
            const perms = data.permissions || [];
            const isAdmin = data.isAdmin || false;

            let chipsHtml = '';
            if (isAdmin) {
                chipsHtml = '<span class="roles-module-chip" style="background:#fff3ed;color:var(--secondary-color)">Todos los módulos</span>';
            } else if (perms.length > 0) {
                chipsHtml = perms.map(function (p) {
                    return '<span class="roles-module-chip">' + p + '</span>';
                }).join('');
            } else {
                chipsHtml = '<span style="color:#9ca3af;font-size:0.8rem">Sin permisos asignados</span>';
            }

            body.innerHTML =
                '<p class="roles-detail-name">' + escHtml(role.name_role) + '</p>' +
                '<p class="roles-detail-desc">' + escHtml(role.description_role || '') + '</p>' +
                '<div class="roles-detail-row"><strong>Usuarios:</strong> ' + role.users_count + '</div>' +
                '<div class="roles-detail-row"><strong>Creado:</strong> ' + role.created_at + '</div>' +
                '<div class="roles-label" style="margin-top:1rem;margin-bottom:0.5rem">Módulos con acceso</div>' +
                '<div class="roles-modules-list">' + chipsHtml + '</div>';
        })
        .catch(function () {
            body.innerHTML = '<p style="color:#ef4444;text-align:center">Error al cargar el rol.</p>';
        });
    };
    window.closeShowModal = function () {
        modalShow.style.display = 'none';
    };

    window.openDeleteModal = function (id, name, usersCount) {
        document.getElementById('roles-delete-name').textContent = name;
        const warning = document.getElementById('roles-delete-warning');
        if (usersCount > 0) {
            warning.textContent = 'Este rol tiene ' + usersCount + ' usuario(s) asignado(s). Al eliminarlo perderan acceso al sistema.';
        } else {
            warning.textContent = 'Esta accion no se puede deshacer.';
        }

        const base = window.location.pathname.replace(/\/roles(\/.*)?$/, '');
        document.getElementById('roles-delete-form').action = base + '/roles/eliminar-rol/' + id;
        modalDelete.style.display = 'flex';
    };
    window.closeDeleteModal = function () {
        modalDelete.style.display = 'none';
    };

    window.toggleModule = function (card) {
        card.classList.toggle('is-active');
    };

    window.selectAllModules = function (formId) {
        const form = document.getElementById(formId);
        form.querySelectorAll('.roles-module-card').forEach(function (c) {
            c.classList.add('is-active');
        });
    };
    window.clearAllModules = function (formId) {
        const form = document.getElementById(formId);
        form.querySelectorAll('.roles-module-card').forEach(function (c) {
            c.classList.remove('is-active');
        });
    };

    window.serializePermissions = function (formId) {
        const form = document.getElementById(formId);
        const container = form.querySelector('[id$="-permissions-container"]');
        container.innerHTML = '';

        form.querySelectorAll('.roles-module-card.is-active').forEach(function (card) {
            const input = document.createElement('input');
            input.type  = 'hidden';
            input.name  = 'permissions[]';
            input.value = card.getAttribute('data-module');
            container.appendChild(input);
        });
    };

    window.closeAll = function () {
        closeCreateDrawer();
        closeEditDrawer();
        closeShowModal();
        closeDeleteModal();
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeAll();
        }
    });

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
})();
