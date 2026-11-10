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

    function showOverlay() { overlay.classList.add('is-visible'); syncScrollLock(); }
    function hideOverlay()  { overlay.classList.remove('is-visible'); syncScrollLock(); }

    // Con un modal abierto el fondo no debe desplazarse (sobre todo en celular).
    function syncScrollLock() {
        var anyOpen = drawerCreate.classList.contains('is-open') ||
                      drawerEdit.classList.contains('is-open') ||
                      modalShow.style.display === 'flex' ||
                      modalDelete.style.display === 'flex';
        document.body.classList.toggle('roles-scroll-lock', anyOpen);
    }

    function noOtherOpen() {
        return !drawerCreate.classList.contains('is-open') &&
               !drawerEdit.classList.contains('is-open') &&
               modalShow.style.display   === 'none' &&
               modalDelete.style.display === 'none';
    }

    window.openCreateDrawer = function () {
        drawerCreate.classList.add('is-open');
        showOverlay();
        setTimeout(function () { document.getElementById('create-name').focus(); }, 50);
    };

    window.closeCreateDrawer = function () {
        drawerCreate.classList.remove('is-open');
        document.getElementById('roles-create-form').reset();
        clearAllModules('roles-create-form');
        if (noOtherOpen()) hideOverlay();
    };

    window.openEditDrawer = function (id, name, description, permissionsByModule) {
        permissionsByModule = permissionsByModule || {};

        document.getElementById('edit-name').value = name;
        document.getElementById('edit-desc').value = description;
        document.getElementById('roles-edit-form').action = urlUpdate + '/' + id;
        // FIX: mantener el hidden "_role_id" sincronizado con el rol que se
        // está editando. Viaja con el POST y vuelve vía old('_role_id') si el
        // servidor rechaza la validación, permitiendo que el drawer se
        // reabra apuntando al rol correcto (ver _drawer_edit.blade.php).
        document.getElementById('edit-role-id').value = id;

        document.getElementById('edit-modules-grid')
            .querySelectorAll('input[type="checkbox"]')
            .forEach(function (cb) {
                var actions = permissionsByModule[cb.dataset.module] || [];
                cb.checked = actions.indexOf(cb.dataset.action) !== -1;
            });

        document.getElementById('edit-modules-grid')
            .querySelectorAll('.roles-module-card')
            .forEach(applyViewCascade);

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
        syncScrollLock();

        fetch(urlShow + '/' + id, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var role    = data.role;
            var perms   = data.permissions || [];
            var isAdmin = data.isAdmin || false;

            var moduleKeys = Object.keys(perms);
            var chipsHtml = '';
            if (isAdmin) {
                chipsHtml = '<span class="roles-module-chip" style="background:#fff3ed;color:var(--secondary-color)">Todos los módulos</span>';
            } else if (moduleKeys.length > 0) {
                chipsHtml = moduleKeys.map(function (moduleKey) {
                    var actionLabels = (perms[moduleKey] || []).map(function (action) {
                        return ACTION_LABELS[action] || action;
                    });
                    return '<span class="roles-module-chip">' +
                        escHtml(moduleDisplayName(moduleKey)) +
                        (actionLabels.length ? ': ' + escHtml(actionLabels.join(', ')) : '') +
                        '</span>';
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
        syncScrollLock();
    };

    window.openDeleteModal = function (id, name, usersCount) {
        document.getElementById('roles-delete-name').textContent = name;
        document.getElementById('roles-delete-warning').textContent = usersCount > 0
            ? 'Este rol tiene ' + usersCount + ' usuario(s) asignado(s). Al eliminarlo perderán acceso al sistema.'
            : 'Esta acción no se puede deshacer.';
        document.getElementById('roles-delete-form').action = urlDelete + '/' + id;
        modalDelete.style.display = 'flex';
        syncScrollLock();
    };

    window.closeDeleteModal = function () {
        modalDelete.style.display = 'none';
        if (noOtherOpen()) hideOverlay();
        syncScrollLock();
    };

    var ACTION_LABELS = {
        view:   'Lectura',
        create: 'Crear',
        edit:   'Editar',
        delete: 'Eliminar',
        log:    'Bitácora'
    };

    function moduleDisplayName(key) {
        var card = document.querySelector('.roles-module-card[data-module="' + key + '"]');
        var nameEl = card && card.querySelector('.roles-module-name');
        return nameEl ? nameEl.textContent : key;
    }

    // Cascada Lectura -> resto: sin "view" marcado, los otros 4 checkboxes
    // de acción del módulo se deshabilitan (y se desmarcan) porque no tiene
    // sentido otorgar crear/editar/eliminar/bitácora sin lectura.
    function applyViewCascade(card) {
        var viewCb = card.querySelector('.roles-action-view');
        var otherCbs = card.querySelectorAll('.roles-action-other');
        var enabled = !!(viewCb && viewCb.checked);
        otherCbs.forEach(function (cb) {
            cb.disabled = !enabled;
            if (!enabled) cb.checked = false;
        });
        card.classList.toggle('is-active', enabled || Array.prototype.some.call(otherCbs, function (cb) { return cb.checked; }));
    }

    document.addEventListener('change', function (e) {
        var target = e.target;
        if (!target.matches('.roles-module-actions input[type="checkbox"]')) return;
        var card = target.closest('.roles-module-card');
        if (!card) return;
        if (target.classList.contains('roles-action-view')) {
            applyViewCascade(card);
        } else {
            card.classList.toggle('is-active', card.querySelector('.roles-action-view').checked ||
                Array.prototype.some.call(card.querySelectorAll('.roles-action-other'), function (cb) { return cb.checked; }));
        }
    });

    // Aplica la cascada Lectura->resto a todas las tarjetas de ambos grids al
    // cargar la página, para que un formulario "Nuevo Rol" recién abierto (sin
    // ningún checkbox marcado) ya muestre los 4 checkboxes secundarios
    // deshabilitados en vez de habilitados-pero-sin-marcar.
    document.querySelectorAll('.roles-module-card').forEach(applyViewCascade);

    window.selectAllModules = function (formId) {
        document.getElementById(formId)
            .querySelectorAll('.roles-module-card')
            .forEach(function (card) {
                card.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
                    cb.disabled = false;
                    cb.checked = true;
                });
                card.classList.add('is-active');
            });
    };

    window.clearAllModules = function (formId) {
        document.getElementById(formId)
            .querySelectorAll('.roles-module-card')
            .forEach(function (card) {
                card.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
                    cb.checked = false;
                });
                applyViewCascade(card);
            });
    };

    window.serializePermissions = function (formId) {
        var form      = document.getElementById(formId);
        var container = form.querySelector('[id$="-permissions-container"]');
        container.innerHTML = '';
        form.querySelectorAll('.roles-module-actions input[type="checkbox"]:checked').forEach(function (cb) {
            var input   = document.createElement('input');
            input.type  = 'hidden';
            input.name  = 'permissions[]';
            input.value = cb.dataset.module + ':' + cb.dataset.action;
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

    // ── Tabla: buscador + paginación (todo en el navegador; los roles son pocos) ──
    var searchInput = document.getElementById('roles-search');
    var perPageSel  = document.getElementById('roles-per-page');
    var pagerInfo   = document.getElementById('roles-pager-info');
    var pagerNav    = document.getElementById('roles-pager-nav');
    var noResults   = document.querySelector('.roles-no-results');
    var allRows     = Array.prototype.slice.call(document.querySelectorAll('.roles-table tbody tr.roles-row'));
    var currentPage = 1;

    function normalize(str) {
        return String(str || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    function renderTable() {
        if (!pagerInfo) return;
        var term    = normalize(searchInput ? searchInput.value.trim() : '');
        var perPage = parseInt(perPageSel ? perPageSel.value : '10', 10) || 10;
        var matches = allRows.filter(function (tr) {
            var hit = !term || normalize(tr.dataset.search).indexOf(term) !== -1;
            tr.hidden = !hit;
            return hit;
        });

        var pages = Math.max(1, Math.ceil(matches.length / perPage));
        currentPage = Math.min(Math.max(1, currentPage), pages);
        var from = (currentPage - 1) * perPage;
        var to   = Math.min(from + perPage, matches.length);
        matches.forEach(function (tr, i) { tr.classList.toggle('is-paged-out', i < from || i >= to); });

        if (noResults) noResults.hidden = !(allRows.length && !matches.length);
        pagerInfo.innerHTML = matches.length
            ? 'Mostrando <strong>' + (from + 1) + '–' + to + '</strong> de <strong>' + matches.length + '</strong> ' + (matches.length === 1 ? 'rol' : 'roles')
            : (allRows.length ? 'Sin resultados' : '');

        var html = '';
        if (pages > 1) {
            html += '<button type="button" class="roles-pager__btn" data-page="' + (currentPage - 1) + '"' + (currentPage === 1 ? ' disabled' : '') + ' aria-label="Página anterior">‹</button>';
            for (var p = 1; p <= pages; p++) {
                html += '<button type="button" class="roles-pager__btn' + (p === currentPage ? ' is-current' : '') + '" data-page="' + p + '"' + (p === currentPage ? ' aria-current="page"' : '') + '>' + p + '</button>';
            }
            html += '<button type="button" class="roles-pager__btn" data-page="' + (currentPage + 1) + '"' + (currentPage === pages ? ' disabled' : '') + ' aria-label="Página siguiente">›</button>';
        }
        pagerNav.innerHTML = html;
    }

    if (searchInput) searchInput.addEventListener('input', function () { currentPage = 1; renderTable(); });
    if (perPageSel)  perPageSel.addEventListener('change', function () { currentPage = 1; renderTable(); });
    if (pagerNav) pagerNav.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-page]');
        if (!btn || btn.disabled) return;
        currentPage = parseInt(btn.dataset.page, 10);
        renderTable();
        var wrap = document.querySelector('.roles-toolbar');
        if (wrap) wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    renderTable();
    // Si el servidor regresó con un error de validación, el modal ya viene abierto.
    syncScrollLock();

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
})();
