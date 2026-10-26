<script>
(function () {
    var urls = {
        userStore: @json(route('customer.team.users.store')),
        userUpdate: @json(route('customer.team.users.update', ['id' => '__ID__'])),
        userPass: @json(route('customer.team.users.password', ['id' => '__ID__'])),
        userDelete: @json(route('customer.team.users.destroy', ['id' => '__ID__'])),
        roleStore: @json(route('customer.team.roles.store')),
        roleUpdate: @json(route('customer.team.roles.update', ['id' => '__ID__'])),
        roleDelete: @json(route('customer.team.roles.destroy', ['id' => '__ID__']))
    };
    var activeModal = @json($activeModal);

    var $ = function (id) { return document.getElementById(id); };
    var lastTrigger = null;

    function show(modal, focusSel) {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('tm-modal-open');
        var el = focusSel && modal.querySelector(focusSel);
        if (el) setTimeout(function () { el.focus(); }, 30);
    }
    function hide(modal) {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        if (!document.querySelector('.tm-modal.is-open')) document.body.classList.remove('tm-modal-open');
        if (lastTrigger) { lastTrigger.focus(); lastTrigger = null; }
    }
    function url(tpl, id) { return tpl.replace('__ID__', id); }
    function clearErrors(form) {
        form.querySelectorAll('.tm-error').forEach(function (e) { e.remove(); });
    }

    /* ── Pestañas ── */
    var primary = $('tm-primary-action');
    function setTab(name) {
        document.querySelectorAll('[data-tm-tab]').forEach(function (b) {
            var on = b.getAttribute('data-tm-tab') === name;
            b.classList.toggle('is-active', on);
            b.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        $('tm-panel-usuarios').hidden = name !== 'usuarios';
        $('tm-panel-roles').hidden = name !== 'roles';
        if (name === 'roles') {
            primary.textContent = '+ Crear rol';
            primary.setAttribute('data-tm-open', 'role-create');
        } else {
            primary.textContent = '+ Agregar usuario';
            primary.setAttribute('data-tm-open', 'user-create');
        }
    }
    document.querySelectorAll('[data-tm-tab]').forEach(function (b) {
        b.addEventListener('click', function () { setTab(b.getAttribute('data-tm-tab')); });
    });
    setTab(@json($tab));

    /* ── Usuario (crear / editar) ── */
    var userModal = $('tm-modal-user');
    var userForm = $('tm-user-form');
    var userMethod = $('tm-user-method');
    var userKey = $('tm-user-modal-key');
    var userFields = ['first_name', 'last_name', 'email', 'phone', 'role_id'];

    function openUserCreate() {
        clearErrors(userForm);
        userForm.action = urls.userStore;
        userMethod.disabled = true;
        userKey.value = 'user-create';
        $('tm-user-title').textContent = 'Agregar usuario';
        userFields.forEach(function (f) { userForm.elements[f].value = ''; });
        userForm.elements['password'].value = '';
        $('tm-password-wrap').style.display = '';
        userForm.elements['password'].disabled = false;
        show(userModal, '#tm-first-name');
    }
    function openUserEdit(id, d) {
        clearErrors(userForm);
        userForm.action = url(urls.userUpdate, id);
        userMethod.disabled = false;
        userKey.value = 'user-edit:' + id;
        $('tm-user-title').textContent = 'Editar usuario';
        userFields.forEach(function (f) { userForm.elements[f].value = (d && d[f] != null) ? d[f] : ''; });
        // La contraseña se cambia con el botón "Contraseña" de la lista.
        $('tm-password-wrap').style.display = 'none';
        userForm.elements['password'].disabled = true;
        show(userModal, '#tm-first-name');
    }

    /* ── Contraseña ── */
    var passModal = $('tm-modal-pass');
    var passForm = $('tm-pass-form');
    function openPass(id, name) {
        clearErrors(passForm);
        passForm.action = url(urls.userPass, id);
        $('tm-pass-modal-key').value = 'user-pass:' + id;
        $('tm-pass-name').textContent = name;
        $('tm-pass-input').value = '';
        show(passModal, '#tm-pass-input');
    }

    /* ── Eliminar usuario / rol ── */
    function openUserDelete(id, name) {
        $('tm-user-delete-form').action = url(urls.userDelete, id);
        $('tm-udel-name').textContent = name;
        show($('tm-modal-user-delete'));
    }
    function openRoleDelete(id, name) {
        $('tm-role-delete-form').action = url(urls.roleDelete, id);
        $('tm-rdel-name').textContent = name;
        show($('tm-modal-role-delete'));
    }

    /* ── Rol (matriz de permisos) ── */
    var roleModal = $('tm-modal-role');
    var roleForm = $('tm-role-form');
    var roleMethod = $('tm-role-method');
    var roleKey = $('tm-role-modal-key');
    var boxes = roleForm.querySelectorAll('input[type="checkbox"][name="permissions[]"]');

    function setPermissions(list) {
        var set = {};
        (list || []).forEach(function (p) { set[p] = true; });
        boxes.forEach(function (b) { b.checked = !!set[b.value]; });
    }
    function openRoleCreate() {
        clearErrors(roleForm);
        roleForm.action = urls.roleStore;
        roleMethod.disabled = true;
        roleKey.value = 'role-create';
        $('tm-role-title').textContent = 'Crear rol';
        roleForm.elements['name'].value = '';
        roleForm.elements['description'].value = '';
        setPermissions([]);
        show(roleModal, '#tm-role-name');
    }
    function openRoleEdit(id, d) {
        clearErrors(roleForm);
        roleForm.action = url(urls.roleUpdate, id);
        roleMethod.disabled = false;
        roleKey.value = 'role-edit:' + id;
        $('tm-role-title').textContent = 'Editar rol';
        roleForm.elements['name'].value = d.name || '';
        roleForm.elements['description'].value = d.description || '';
        setPermissions(d.permissions || []);
        show(roleModal, '#tm-role-name');
    }

    // Coherencia de la matriz: cualquier acción exige "Ver"; quitar "Ver" quita el resto.
    roleForm.addEventListener('change', function (ev) {
        var box = ev.target;
        if (!box.matches || !box.matches('input[type="checkbox"][name="permissions[]"]')) return;
        var row = box.closest('tr');
        var view = row.querySelector('input[data-action="view"]');
        if (box.getAttribute('data-action') === 'view') {
            if (!box.checked) row.querySelectorAll('input[type="checkbox"]').forEach(function (b) { b.checked = false; });
        } else if (box.checked && view) {
            view.checked = true;
        }
    });
    roleForm.querySelectorAll('[data-tm-matrix]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var on = btn.getAttribute('data-tm-matrix') === 'all';
            boxes.forEach(function (b) { b.checked = on; });
        });
    });

    /* ── Disparadores ── */
    document.addEventListener('click', function (ev) {
        var close = ev.target.closest('[data-tm-close]');
        if (close) { hide(close.closest('.tm-modal')); return; }
        if (ev.target.classList && ev.target.classList.contains('tm-modal')) { hide(ev.target); return; }

        var t = ev.target.closest('[data-tm-open]');
        if (!t || t.disabled) return;
        lastTrigger = t;
        var id = t.getAttribute('data-id');
        switch (t.getAttribute('data-tm-open')) {
            case 'user-create': openUserCreate(); break;
            case 'user-edit': openUserEdit(id, JSON.parse(t.getAttribute('data-user'))); break;
            case 'user-pass': openPass(id, t.getAttribute('data-name')); break;
            case 'user-delete': openUserDelete(id, t.getAttribute('data-name')); break;
            case 'role-create': openRoleCreate(); break;
            case 'role-edit': openRoleEdit(id, JSON.parse(t.getAttribute('data-role'))); break;
            case 'role-delete': openRoleDelete(id, t.getAttribute('data-name')); break;
        }
    });
    document.addEventListener('keydown', function (ev) {
        if (ev.key !== 'Escape') return;
        var open = document.querySelector('.tm-modal.is-open');
        if (open) hide(open);
    });

    /* ── Reabrir el modal tras un error de validación ── */
    if (activeModal) {
        var parts = activeModal.split(':');
        var kind = parts[0], id = parts[1];
        if (kind === 'user-create') {
            userForm.action = urls.userStore; userMethod.disabled = true;
            $('tm-user-title').textContent = 'Agregar usuario';
            setTab('usuarios'); show(userModal);
        } else if (kind === 'user-edit') {
            userForm.action = url(urls.userUpdate, id); userMethod.disabled = false;
            $('tm-user-title').textContent = 'Editar usuario';
            $('tm-password-wrap').style.display = 'none';
            userForm.elements['password'].disabled = true;
            setTab('usuarios'); show(userModal);
        } else if (kind === 'user-pass') {
            passForm.action = url(urls.userPass, id);
            setTab('usuarios'); show(passModal);
        } else if (kind === 'role-create') {
            roleForm.action = urls.roleStore; roleMethod.disabled = true;
            $('tm-role-title').textContent = 'Crear rol';
            setTab('roles'); show(roleModal);
        } else if (kind === 'role-edit') {
            roleForm.action = url(urls.roleUpdate, id); roleMethod.disabled = false;
            $('tm-role-title').textContent = 'Editar rol';
            setTab('roles'); show(roleModal);
        }
    }
})();
</script>
