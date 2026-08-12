/* ============================================================
   workflows-index.js — Listado de Automatizaciones (Workflows)
   Módulo vanilla JS (IIFE), sin dependencias nuevas.
   IDs/atributos exactos: ver resources/views/admin/workflows/index.blade.php
   ============================================================ */
(function () {
    'use strict';

    var grid = document.getElementById('wfGrid');
    var list = document.getElementById('wfList');
    if (!grid && !list) return;

    var searchInput  = document.getElementById('wfSearch');
    var sortSelect   = document.getElementById('wfSort');
    var viewGridBtn  = document.getElementById('wfViewGrid');
    var viewListBtn  = document.getElementById('wfViewList');
    var emptyFiltered = document.getElementById('wfEmptyFiltered');
    var chips = Array.prototype.slice.call(document.querySelectorAll('.wf-chip[data-filter]'));

    var csrfMeta  = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    var currentFilter = 'all';
    var currentSearch = '';

    // ── Helpers ──────────────────────────────────────────────

    function gridRows() {
        return grid ? Array.prototype.slice.call(grid.children).filter(function (el) {
            return el.hasAttribute('data-workflow-row');
        }) : [];
    }

    function listRows() {
        var body = document.getElementById('wfListBody');
        return body ? Array.prototype.slice.call(body.children).filter(function (el) {
            return el.hasAttribute('data-workflow-row');
        }) : [];
    }

    function allRows() {
        return gridRows().concat(listRows());
    }

    // ── Búsqueda + filtros (combinados) ─────────────────────

    function rowMatches(row) {
        var name = (row.getAttribute('data-name') || '');
        var matchesSearch = !currentSearch || name.indexOf(currentSearch) !== -1;

        var active  = row.getAttribute('data-active') === '1';
        var hasError = row.getAttribute('data-has-error') === '1';

        var matchesFilter = true;
        if (currentFilter === 'active') {
            matchesFilter = active;
        } else if (currentFilter === 'paused') {
            matchesFilter = !active;
        } else if (currentFilter === 'errors') {
            matchesFilter = hasError;
        }

        return matchesSearch && matchesFilter;
    }

    function applyFilters() {
        var visibleCount = 0;

        allRows().forEach(function (row) {
            var show = rowMatches(row);
            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        if (emptyFiltered) {
            emptyFiltered.style.display = visibleCount === 0 ? 'flex' : 'none';
        }

        // Si la vista activa es grid, oculta también la lista y viceversa
        // (el estado vacío filtrado se muestra una sola vez, no duplicado).
        toggleEmptyVisibilityPerView(visibleCount);
    }

    function toggleEmptyVisibilityPerView(visibleCount) {
        if (!emptyFiltered) return;
        // El bloque de estado vacío ya está fuera de #wfGrid/#wfList, así
        // que basta con un único visibleCount combinado (búsqueda y filtro
        // aplican igual en ambas vistas, siempre sincronizadas).
        emptyFiltered.style.display = visibleCount === 0 ? 'flex' : 'none';
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            currentSearch = searchInput.value.trim().toLowerCase();
            applyFilters();
        });
    }

    chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            chips.forEach(function (c) { c.classList.remove('wf-chip-active'); });
            chip.classList.add('wf-chip-active');
            currentFilter = chip.getAttribute('data-filter') || 'all';
            applyFilters();
        });
    });

    // ── Orden ────────────────────────────────────────────────

    function sortValue(row, key) {
        if (key === 'nombre') {
            return row.getAttribute('data-name') || '';
        }
        if (key === 'ejecuciones') {
            return parseFloat(row.getAttribute('data-runs')) || 0;
        }
        if (key === 'exito') {
            return parseFloat(row.getAttribute('data-success')) || 0;
        }
        // 'reciente' (por defecto)
        return parseFloat(row.getAttribute('data-edited')) || 0;
    }

    function sortContainer(container, key) {
        if (!container) return;
        var rows = Array.from(container.children).filter(function (el) {
            return el.hasAttribute('data-workflow-row');
        });

        rows.sort(function (a, b) {
            var va = sortValue(a, key);
            var vb = sortValue(b, key);

            if (key === 'nombre') {
                return va.localeCompare(vb);
            }
            // reciente / ejecuciones / exito: descendente
            return vb - va;
        });

        rows.forEach(function (row) {
            container.appendChild(row);
        });
    }

    function applySort() {
        if (!sortSelect) return;
        var key = sortSelect.value;
        sortContainer(grid, key);
        sortContainer(document.getElementById('wfListBody'), key);
    }

    if (sortSelect) {
        sortSelect.addEventListener('change', applySort);
    }

    // ── Toggle vista (grid / lista) ─────────────────────────

    function setView(view) {
        if (grid) grid.style.display = view === 'grid' ? '' : 'none';
        if (list) list.style.display = view === 'list' ? '' : 'none';

        if (viewGridBtn) viewGridBtn.classList.toggle('wf-view-btn-active', view === 'grid');
        if (viewListBtn) viewListBtn.classList.toggle('wf-view-btn-active', view === 'list');
    }

    if (viewGridBtn) {
        viewGridBtn.addEventListener('click', function () { setView('grid'); });
    }
    if (viewListBtn) {
        viewListBtn.addEventListener('click', function () { setView('list'); });
    }

    // ── Toggle activo (switch) — fetch POST a workflows.toggle ─

    document.addEventListener('change', function (e) {
        var checkbox = e.target;
        if (!checkbox.classList || !checkbox.classList.contains('wf-toggle-active')) return;

        var id = checkbox.getAttribute('data-id');
        if (!id) return;

        var wasChecked = checkbox.checked;
        checkbox.disabled = true;

        fetch('/admin/automatizaciones/' + id + '/toggle', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        })
            .then(function (response) {
                if (!response.ok) throw new Error('toggle failed');
                return response.json().catch(function () { return {}; });
            })
            .then(function (data) {
                var isActive = typeof data.is_active === 'boolean' ? data.is_active : wasChecked;
                checkbox.checked = isActive;

                // Mantiene sincronizados el switch equivalente en la otra
                // vista (grid <-> lista) y el data-active de ambas filas.
                var rowId = checkbox.closest('[data-workflow-row]');
                var workflowId = rowId ? rowId.getAttribute('data-workflow-row') : null;
                if (workflowId) {
                    allRows().forEach(function (row) {
                        if (row.getAttribute('data-workflow-row') !== workflowId) return;
                        row.setAttribute('data-active', isActive ? '1' : '0');
                        var sw = row.querySelector('.wf-toggle-active');
                        if (sw && sw !== checkbox) sw.checked = isActive;
                    });
                }

                applyFilters();
            })
            .catch(function () {
                checkbox.checked = !wasChecked;
            })
            .finally(function () {
                checkbox.disabled = false;
            });
    });

    // ── Menú de 3 puntos: cerrar al hacer click fuera ───────

    document.addEventListener('click', function (e) {
        document.querySelectorAll('.wf-menu[open]').forEach(function (menu) {
            if (!menu.contains(e.target)) {
                menu.removeAttribute('open');
            }
        });
    });

    // Cierra cualquier otro menú abierto al abrir uno nuevo (comportamiento
    // de dropdown simple, un menú a la vez).
    document.addEventListener('toggle', function (e) {
        var menu = e.target;
        if (!menu.classList || !menu.classList.contains('wf-menu') || !menu.open) return;
        document.querySelectorAll('.wf-menu[open]').forEach(function (other) {
            if (other !== menu) other.removeAttribute('open');
        });
    }, true);

    // ── Eliminar workflow (modal de confirmación) ───────────

    var deleteModal   = document.getElementById('deleteWorkflowModal');
    var delNameEl     = document.getElementById('delWorkflowName');
    var delCancelBtn  = document.getElementById('delWorkflowCancel');
    var delConfirmBtn = document.getElementById('delWorkflowConfirm');
    var pendingDeleteId = null;

    function openDeleteModal(id, name) {
        pendingDeleteId = id;
        if (delNameEl) delNameEl.textContent = name || '';
        if (deleteModal) deleteModal.style.display = 'flex';
    }

    function closeDeleteModal() {
        pendingDeleteId = null;
        if (deleteModal) deleteModal.style.display = 'none';
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('.btn-delete-workflow') : null;
        if (!btn) return;
        openDeleteModal(btn.getAttribute('data-id'), btn.getAttribute('data-name'));
    });

    if (delCancelBtn) {
        delCancelBtn.addEventListener('click', closeDeleteModal);
    }
    if (deleteModal) {
        deleteModal.addEventListener('click', function (e) {
            if (e.target === deleteModal) closeDeleteModal();
        });
    }

    if (delConfirmBtn) {
        delConfirmBtn.addEventListener('click', function () {
            if (!pendingDeleteId) return;
            var id = pendingDeleteId;
            delConfirmBtn.disabled = true;

            fetch('/admin/automatizaciones/' + id, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            })
                .then(function (response) {
                    if (!response.ok) throw new Error('delete failed');
                    var row = document.querySelectorAll('[data-workflow-row="' + id + '"]');
                    row.forEach(function (el) { el.remove(); });
                    closeDeleteModal();
                    applyFilters();
                })
                .catch(function () {
                    closeDeleteModal();
                    window.alert('No se pudo eliminar el workflow. Intenta de nuevo.');
                })
                .finally(function () {
                    delConfirmBtn.disabled = false;
                });
        });
    }

    // ── Estado inicial ───────────────────────────────────────

    applyFilters();
    applySort();
})();
