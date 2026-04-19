@push('scripts')
<script>
    const updateUserUrl = '{{ route('admin.users.update', ':id') }}';
    // --- Helpers ---
    const closeModalWithAnim = (m) => {
        const mc = m.querySelector('.user-manager-modal-content');
        if (mc) {
            mc.style.transform = 'translateX(100%)';
            mc.style.transition = 'transform 0.2s ease-in';
        }
        m.style.transition = 'opacity 0.2s ease-in';
        setTimeout(() => {
            m.style.display = 'none';
            if (mc) {
                mc.style.transform = '';
                mc.style.transition = '';
            }
            m.style.transition = '';
        }, 300);
    };

    const maxContacts = 5;

    // --- Factory: builds a contact row for both modals ---
    function buildContactRow(data = {}, deleteBtnClass, onDelete) {
        const div = document.createElement('div');
        div.className = 'user-manager-form user-manager-form-3 emergency-contact-item';
        div.innerHTML = `
    <div>
        <label class="supliers-manager-slider-label email">Nombre del Contacto</label>
        <input type="text" class="users-manager-input" name="emergency_contact_name[]"
            placeholder="Ej: Juan Pérez">
    </div>
    <div>
        <label class="supliers-manager-slider-label email">Teléfono</label>
        <input type="text" class="users-manager-input" name="emergency_phone[]"
            placeholder="(449) 123-4567">
    </div>
    <div>
        <label class="supliers-manager-slider-label email">Parentesco</label>
        <input type="text" class="users-manager-input" name="relationship[]"
            placeholder="Ej: Hermano/a, Esposo/a">
    </div>
    <button type="button" class="table-users-manager-action-btn delete ${deleteBtnClass}">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 6h18"></path>
            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
            <line x1="10" x2="10" y1="11" y2="17"></line>
            <line x1="14" x2="14" y1="11" y2="17"></line>
        </svg>
    </button>`;
        // Set values via DOM property (not attribute) to avoid HTML injection
        const inputs = div.querySelectorAll('input');
        inputs[0].value = data.name || '';
        inputs[1].value = data.phone || '';
        inputs[2].value = data.relationship || '';
        div.querySelector('.' + deleteBtnClass).addEventListener('click', onDelete);
        return div;
    }

    // --- Create modal ---
    const openBtn = document.querySelector('.button-primary.size-adjustment');
    const modal = document.getElementById('userModal');
    const closeBtn = document.getElementById('closeModal');
    const cancelBtn = document.getElementById('cancelModal');

    if (openBtn) openBtn.addEventListener('click', () => {
        modal.style.display = 'flex';
    });
    if (closeBtn) closeBtn.addEventListener('click', () => closeModalWithAnim(modal));
    if (cancelBtn) cancelBtn.addEventListener('click', () => closeModalWithAnim(modal));

    let clickStartedOutside = false;
    window.addEventListener('mousedown', (e) => {
        clickStartedOutside = (e.target === modal || e.target === editModal);
    });
    window.addEventListener('mouseup', (e) => {
        if (clickStartedOutside && (e.target === modal || e.target === editModal)) {
            if (e.target === modal) closeModalWithAnim(modal);
            if (e.target === editModal) closeModalWithAnim(editModal);
        }
        clickStartedOutside = false;
    });

    // --- Create modal emergency contacts ---
    const addBtn = document.getElementById('addEmergencyContact');
    const container = document.getElementById('emergency-contacts-container');
    const textCounter = document.getElementById('emergencyText');

    function updateUI() {
        const total = container.querySelectorAll('.emergency-contact-item').length;
        textCounter.textContent = `Agregar otro contacto de emergencia (${total}/${maxContacts})`;
        addBtn.style.display = total >= maxContacts ? 'none' : 'flex';
    }

    function addCreateContactRow(data = {}) {
        const row = buildContactRow(data, 'delete-emergency-btn', () => {
            row.remove();
            updateUI();
        });
        container.appendChild(row);
        updateUI();
    }

    // Seed the first row via JS so the factory controls all rows
    container.innerHTML = '';
    addCreateContactRow();

    addBtn.addEventListener('click', () => {
        if (container.querySelectorAll('.emergency-contact-item').length < maxContacts) {
            addCreateContactRow();
        }
    });

    updateUI();

    // --- Edit modal ---
    const editModal = document.getElementById('editUserModal');
    const closeEditBtn = document.getElementById('closeEditModal');
    const cancelEditBtn = document.getElementById('cancelEditModal');
    const editForm = document.getElementById('editUserForm');
    const editEcContainer = document.getElementById('edit-emergency-contacts-container');
    const editAddBtn = document.getElementById('editAddEmergencyContact');
    const editEcText = document.getElementById('editEmergencyText');

    function updateEditUI() {
        const total = editEcContainer.querySelectorAll('.emergency-contact-item').length;
        editEcText.textContent = `Agregar otro contacto de emergencia (${total}/${maxContacts})`;
        editAddBtn.style.display = total >= maxContacts ? 'none' : 'flex';
    }

    function addEditContactRow(data = {}) {
        const row = buildContactRow(data, 'edit-delete-emergency-btn', () => {
            row.remove();
            updateEditUI();
        });
        editEcContainer.appendChild(row);
        updateEditUI();
    }

    editAddBtn.addEventListener('click', () => {
        if (editEcContainer.querySelectorAll('.emergency-contact-item').length < maxContacts) {
            addEditContactRow();
        }
    });

    let currentUserId = null;

    const openEditModal = (d) => {
        currentUserId = d.id;

        editForm.querySelector('[name="first_name"]').value = d.firstName || '';
        editForm.querySelector('[name="last_name"]').value = d.lastName || '';
        editForm.querySelector('[name="birthdate"]').value = d.birthdate || '';
        editForm.querySelector('[name="rfc"]').value = d.rfc || '';
        editForm.querySelector('[name="curp"]').value = d.curp || '';
        editForm.querySelector('[name="social_segurity_number"]').value = d.ssn || '';
        editForm.querySelector('[name="email"]').value = d.email || '';
        editForm.querySelector('[name="phone"]').value = d.phone || '';
        editForm.querySelector('[name="position"]').value = d.position || '';
        editForm.querySelector('[name="role_id"]').value = d.roleId || '';
        editForm.querySelector('[name="status"]').value = d.status || '';
        editForm.querySelector('[name="password"]').value = '';
        editForm.querySelector('[name="password_confirmation"]').value = '';

        const errorsContainer = document.getElementById('edit-errors-container');
        errorsContainer.style.display = 'none';
        errorsContainer.innerHTML = '';

        editEcContainer.innerHTML = '';
        const contacts = JSON.parse(d.contacts || '[]');
        if (contacts.length === 0) {
            addEditContactRow();
        } else {
            contacts.forEach(c => addEditContactRow({
                name: c.name,
                phone: c.phone,
                relationship: c.relationship
            }));
        }

        editModal.style.display = 'flex';
    };

    editForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const errorsContainer = document.getElementById('edit-errors-container');
        errorsContainer.style.display = 'none';
        errorsContainer.innerHTML = '';

        const url = updateUserUrl.replace(':id', currentUserId);
        const formData = new FormData(editForm);
        formData.append('_method', 'PUT');

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                closeModalWithAnim(editModal);
                updateTableRow(data.user);
                showToast('Usuario actualizado correctamente');
            } else if (response.status === 422) {
                const errorList = Object.values(data.errors).flat();
                errorsContainer.innerHTML = errorList.map(msg => `<p>${msg}</p>`).join('');
                errorsContainer.style.display = 'block';
            }
        } catch (err) {
            console.error('Error al actualizar usuario:', err);
        }
    });

    function updateTableRow(user) {
        const editBtn = document.querySelector(`.btn-edit-user[data-id="${user.id}"]`);
        if (!editBtn) return;
        const row = editBtn.closest('tr');

        row.querySelector('.users-manager-name-user').textContent =
            `${user.first_name} ${user.last_name}`;
        row.querySelector('.breadcrumb-users-manager.main').textContent = user.email;

        const badge = row.querySelector('.users-manager-badge[class*="role-"]');
        if (badge) {
            const roleName = user.role ? user.role.name_role_es : '';
            const lower = roleName.toLowerCase();
            badge.className = 'users-manager-badge ' +
                (lower === 'administrador' || lower === 'admin' ? 'role-admin' : 'role-employee');
            badge.textContent = roleName;
        }

        const statusBadge = row.querySelector('.users-manager-badge.status, .users-manager-badge.status-inactive');
        if (statusBadge) {
            if (user.status === 'active') {
                statusBadge.className = 'users-manager-badge status';
                statusBadge.textContent = 'Activo';
            } else {
                statusBadge.className = 'users-manager-badge status-inactive';
                statusBadge.textContent = 'Inactivo';
            }
        }

        const contacts = user.contact_emergency || [];
        const contactsJson = JSON.stringify(
            contacts.map(c => ({ name: c.name, phone: c.phone, relationship: c.relationship }))
        );

        editBtn.dataset.firstName = user.first_name;
        editBtn.dataset.lastName = user.last_name;
        editBtn.dataset.email = user.email;
        editBtn.dataset.phone = user.phone || '';
        editBtn.dataset.position = user.position || '';
        editBtn.dataset.birthdate = user.birthdate || '';
        editBtn.dataset.rfc = user.rfc || '';
        editBtn.dataset.curp = user.curp || '';
        editBtn.dataset.ssn = user.social_segurity_number || '';
        editBtn.dataset.roleId = user.role_id || '';
        editBtn.dataset.status = user.status || '';
        editBtn.dataset.contacts = contactsJson;

        const deleteBtn = row.querySelector('.btn-delete-user');
        if (deleteBtn) {
            deleteBtn.dataset.name = `${user.first_name} ${user.last_name}`;
            deleteBtn.dataset.email = user.email;
            deleteBtn.dataset.initial = user.first_name.charAt(0).toUpperCase();
        }
    }

    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    document.querySelectorAll('.btn-edit-user').forEach(btn => {
        btn.addEventListener('click', () => openEditModal(btn.dataset));
    });

    if (closeEditBtn) closeEditBtn.addEventListener('click', () => closeModalWithAnim(editModal));
    if (cancelEditBtn) cancelEditBtn.addEventListener('click', () => closeModalWithAnim(editModal));

    // --- Delete confirmation modal ---
    const deleteModal = document.getElementById('deleteUserModal');
    const deleteForm = document.getElementById('deleteUserForm');
    const delCancelBtn = document.getElementById('delConfirmCancel');
    const deleteBaseUrl = '{{ url('/admin/usuarios/eliminar-usuario') }}';

    document.querySelectorAll('.btn-delete-user').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const name = btn.dataset.name;
            const email = btn.dataset.email;
            const initial = btn.dataset.initial;

            document.getElementById('delConfirmName').textContent = name;
            document.getElementById('delConfirmEmail').textContent = email;
            document.getElementById('delConfirmAvatar').textContent = initial;

            deleteForm.action = deleteBaseUrl + '/' + id;
            deleteModal.classList.add('active');
        });
    });
    delCancelBtn.addEventListener('click', () => deleteModal.classList.remove('active'));
    deleteModal.addEventListener('click', (e) => {
        if (e.target === deleteModal) deleteModal.classList.remove('active');
    });
</script>
@endpush
