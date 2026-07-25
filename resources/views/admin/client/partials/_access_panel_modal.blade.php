{{-- Access Panel Modal --}}
<div id="accessModalOverlay" class="del-confirm-overlay" style="z-index:100000">
    <div class="del-confirm-box access-modal-box">

        {{-- Header --}}
        <div class="access-modal-header">
            <div class="access-modal-header-text">
                <h3 class="access-modal-title">Acceso del cliente</h3>
                <p class="access-modal-subtitle">Controla el estado del panel y la contraseña del cliente</p>
            </div>
            <button type="button" class="access-modal-close-btn" onclick="closeAccessModal()">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Customer info --}}
        <div class="access-modal-customer-info">
            <div class="access-modal-avatar" id="accessModalAvatar"></div>
            <div class="access-modal-customer-details">
                <p class="access-modal-customer-name" id="accessModalName"></p>
                <p class="access-modal-customer-email" id="accessModalEmail"></p>
            </div>
        </div>

        {{-- Status: client panel state --}}
        <div class="access-status-row">
            <div class="access-status-info">
                <span class="access-status-label">Estado del panel</span>
                <span id="accessStatusBadge" class="users-manager-badge status">Activo</span>
            </div>
            <button type="button" id="accessStatusToggleBtn" class="button-secondary size-adjustment"
                onclick="toggleClientStatus()">
                Desactivar panel del cliente
            </button>
        </div>

        {{-- Status: portal de servicios --}}
        <div class="access-status-row">
            <div class="access-status-info">
                <span class="access-status-label">Portal de servicios</span>
                <span id="portalAccessBadge" class="users-manager-badge status-inactive">Sin acceso</span>
            </div>
            <button type="button" id="portalToggleBtn" class="button-secondary size-adjustment"
                onclick="togglePortalAccess()">
                Activar portal
            </button>
        </div>

        {{-- Banner: solicitud de portal pendiente --}}
        <div id="portalRequestBanner" class="access-modal-banner hidden">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 style="flex-shrink:0;margin-top:1px">
                <circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>
            </svg>
            <span>Este cliente <strong>solicitó acceso al portal</strong>.
                <a href="#" id="portalRequestViewLink" onclick="loadPortalRequest(event)" style="color:#ff6213;font-weight:700;">Ver solicitud</a>
            </span>
        </div>

        {{-- Detalle de la solicitud (se llena por fetch) --}}
        <div id="portalRequestDetails" class="hidden" style="border:1px solid rgba(0,0,0,0.08);border-radius:8px;padding:14px 16px;margin-bottom:16px;font-size:13px;color:#374151;line-height:1.7;"></div>

        {{-- Section title: password reset --}}
        <h4 id="accessPasswordSectionTitle" class="access-section-title">Restablecer contraseña</h4>

        {{-- Banner: already has access --}}
        <div id="accessModalExistingBanner" class="access-modal-banner hidden">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 style="flex-shrink:0;margin-top:1px">
                <circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>
            </svg>
            <span>Este cliente ya tiene acceso al portal. Si continúas, su contraseña actual será reemplazada.</span>
        </div>

        {{-- Form --}}
        <form id="accessForm">
            @csrf
            <input type="hidden" id="accessCustomerId">

            <div class="access-form-group">
                <label class="supliers-manager-slider-label" for="accessPassword">Nueva contraseña</label>
                <div class="access-password-wrapper">
                    <input class="users-manager-input" type="password" id="accessPassword"
                           name="password" placeholder="Mínimo 8 caracteres" autocomplete="new-password">
                    <button type="button" class="access-password-toggle" onclick="toggleAccessPassword('accessPassword', this)">
                        <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <svg class="eye-off-icon hidden" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/>
                            <path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/>
                            <path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/>
                            <path d="m2 2 20 20"/>
                        </svg>
                    </button>
                </div>
                <span class="access-field-error hidden" id="errorPassword"></span>
            </div>

            <div class="access-form-group">
                <label class="supliers-manager-slider-label" for="accessPasswordConfirmation">Confirmar contraseña</label>
                <div class="access-password-wrapper">
                    <input class="users-manager-input" type="password" id="accessPasswordConfirmation"
                           name="password_confirmation" placeholder="Repite la contraseña" autocomplete="new-password">
                    <button type="button" class="access-password-toggle" onclick="toggleAccessPassword('accessPasswordConfirmation', this)">
                        <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <svg class="eye-off-icon hidden" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/>
                            <path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/>
                            <path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/>
                            <path d="m2 2 20 20"/>
                        </svg>
                    </button>
                </div>
                <span class="access-field-error hidden" id="errorPasswordConfirmation"></span>
            </div>

            {{-- Security hint --}}
            <div class="access-modal-hint">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     style="flex-shrink:0;margin-top:1px">
                    <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>
                </svg>
                <span>La contraseña se almacenará de forma segura. El cliente podrá iniciar sesión con su email y esta contraseña.</span>
            </div>

            {{-- Footer --}}
            <div class="access-modal-footer">
                <button type="button" class="button-secondary size-adjustment" onclick="closeAccessModal()">
                    Cancelar
                </button>
                <button type="submit" class="button-primary size-adjustment" id="accessSubmitBtn">
                    <span id="accessSubmitText">Otorgar acceso</span>
                    <span id="accessSubmitSpinner" class="access-spinner hidden"></span>
                </button>
            </div>
        </form>

    </div>
</div>

@push('scripts')
<script>
(function () {
    function openAccessModal(customerId, customerName, customerEmail, hasAccess, status, portalAccess, portalRequested) {
        document.getElementById('accessCustomerId').value = customerId;
        document.getElementById('accessModalName').textContent = customerName;
        document.getElementById('accessModalEmail').textContent = customerEmail;
        document.getElementById('accessModalAvatar').textContent = customerName.charAt(0).toUpperCase();

        renderPortalAccess(!!portalAccess);
        document.getElementById('portalRequestBanner').classList.toggle('hidden', !portalRequested);
        const details = document.getElementById('portalRequestDetails');
        details.classList.add('hidden');
        details.innerHTML = '';

        const sectionTitle = document.getElementById('accessPasswordSectionTitle');
        const submitText   = document.getElementById('accessSubmitText');
        const banner       = document.getElementById('accessModalExistingBanner');

        if (hasAccess) {
            sectionTitle.textContent = 'Restablecer contraseña';
            submitText.textContent   = 'Actualizar contraseña';
            banner.classList.remove('hidden');
        } else {
            sectionTitle.textContent = 'Otorgar acceso al portal';
            submitText.textContent   = 'Otorgar acceso';
            banner.classList.add('hidden');
        }

        renderClientStatus(status || 'inactive');

        document.getElementById('accessPassword').value             = '';
        document.getElementById('accessPasswordConfirmation').value = '';
        clearAccessErrors();

        document.getElementById('accessModalOverlay').classList.add('active');
    }

    function closeAccessModal() {
        document.getElementById('accessModalOverlay').classList.remove('active');
    }

    function renderClientStatus(status) {
        const badge      = document.getElementById('accessStatusBadge');
        const toggleBtn  = document.getElementById('accessStatusToggleBtn');
        const labels     = { active: 'Activo', inactive: 'Inactivo', suspended: 'Suspendido' };
        const classes    = { active: 'status', inactive: 'status-inactive', suspended: 'status-suspended' };

        badge.dataset.status = status;
        badge.textContent    = labels[status] || labels.inactive;
        badge.className      = 'users-manager-badge ' + (classes[status] || classes.inactive);

        toggleBtn.textContent = status === 'active'
            ? 'Desactivar panel del cliente'
            : 'Activar panel del cliente';
    }

    async function toggleClientStatus() {
        const customerId = document.getElementById('accessCustomerId').value;
        const badge      = document.getElementById('accessStatusBadge');
        const toggleBtn  = document.getElementById('accessStatusToggleBtn');
        const newStatus  = badge.dataset.status === 'active' ? 'inactive' : 'active';

        toggleBtn.disabled = true;

        try {
            const response = await fetch(`/admin/clientes/${customerId}/estado`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ status: newStatus }),
            });

            const data = await response.json();

            if (!response.ok) {
                showClientToast(data.message || 'Ocurrió un error', 'error');
                return;
            }

            renderClientStatus(data.status);
            showClientToast(data.message);

            const tableBadge = document.getElementById(`statusBadge-${customerId}`);
            if (tableBadge) {
                const classes = { active: 'status', inactive: 'status-inactive', suspended: 'status-suspended' };
                const labels  = { active: 'Activo', inactive: 'Inactivo', suspended: 'Suspendido' };
                tableBadge.dataset.status = data.status;
                tableBadge.textContent    = labels[data.status];
                tableBadge.className      = 'users-manager-badge ' + classes[data.status];
            }
        } catch (err) {
            showClientToast('Error de conexión. Intenta de nuevo.', 'error');
        } finally {
            toggleBtn.disabled = false;
        }
    }

    function renderPortalAccess(enabled) {
        const badge = document.getElementById('portalAccessBadge');
        const btn = document.getElementById('portalToggleBtn');

        badge.dataset.enabled = enabled ? '1' : '0';
        badge.textContent = enabled ? 'Con acceso' : 'Sin acceso';
        badge.className = 'users-manager-badge ' + (enabled ? 'status' : 'status-inactive');
        btn.textContent = enabled ? 'Desactivar portal' : 'Activar portal';
    }

    async function togglePortalAccess() {
        const customerId = document.getElementById('accessCustomerId').value;
        const badge = document.getElementById('portalAccessBadge');
        const btn = document.getElementById('portalToggleBtn');
        const enable = badge.dataset.enabled !== '1';

        btn.disabled = true;

        try {
            const response = await fetch(`/admin/clientes/${customerId}/portal`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ enabled: enable }),
            });

            const data = await response.json();

            if (!response.ok) {
                showClientToast(data.message || 'Ocurrió un error', 'error');
                return;
            }

            renderPortalAccess(data.portal_access);
            showClientToast(data.message);

            // Sincroniza el data-attribute del botón de la tabla para que
            // reabrir el modal muestre el estado correcto sin recargar.
            const rowBtn = document.querySelector(`.access_panel[data-customer-id="${customerId}"]`);
            if (rowBtn) rowBtn.dataset.portalAccess = data.portal_access ? 'true' : 'false';
        } catch (err) {
            showClientToast('Error de conexión. Intenta de nuevo.', 'error');
        } finally {
            btn.disabled = false;
        }
    }

    async function loadPortalRequest(e) {
        e.preventDefault();
        const customerId = document.getElementById('accessCustomerId').value;
        const details = document.getElementById('portalRequestDetails');

        try {
            const response = await fetch(`/admin/clientes/${customerId}/solicitud-portal`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await response.json();

            if (!response.ok || !data.success) {
                showClientToast(data.message || 'No se pudo cargar la solicitud.', 'error');
                return;
            }

            const r = data.request;
            const esc = (s) => String(s ?? '—').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            details.innerHTML = `
                <strong>Solicitud de acceso al portal</strong> <span style="color:#9ca3af;">· ${esc(r.completed_at ?? 'incompleta')}</span><br>
                <strong>Frecuencia de compra:</strong> ${esc(r.purchase_frequency)}<br>
                <strong>Monto por periodo:</strong> ${esc(r.purchase_amount)}<br>
                <strong>Motivo:</strong> ${esc(r.reason)}<br>
                <strong>Responsable:</strong> ${esc(r.responsible_name)}<br>
                <strong>RFC:</strong> ${esc(r.rfc)} · <strong>Régimen:</strong> ${esc(r.tax_regime)}<br>
                <strong>Contacto:</strong> ${esc(r.contact_email)} · ${esc(r.phone)}<br>
                ${r.certificate_url ? `<a href="${r.certificate_url}" style="color:#ff6213;font-weight:700;">Descargar Constancia de Situación Fiscal</a>` : '<span style="color:#9ca3af;">Sin constancia adjunta</span>'}`;
            details.classList.remove('hidden');
        } catch (err) {
            showClientToast('Error de conexión al cargar la solicitud.', 'error');
        }
    }

    function clearAccessErrors() {
        ['errorPassword', 'errorPasswordConfirmation'].forEach(id => {
            const el = document.getElementById(id);
            if (el) { el.textContent = ''; el.classList.add('hidden'); }
        });
        document.querySelectorAll('#accessForm .users-manager-input').forEach(el => {
            el.classList.remove('access-input-error');
        });
    }

    function toggleAccessPassword(inputId, btn) {
        const input      = document.getElementById(inputId);
        const eyeIcon    = btn.querySelector('.eye-icon');
        const eyeOffIcon = btn.querySelector('.eye-off-icon');

        if (input.type === 'password') {
            input.type = 'text';
            eyeIcon.classList.add('hidden');
            eyeOffIcon.classList.remove('hidden');
        } else {
            input.type = 'password';
            eyeIcon.classList.remove('hidden');
            eyeOffIcon.classList.add('hidden');
        }
    }

    document.getElementById('accessForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        clearAccessErrors();

        const customerId = document.getElementById('accessCustomerId').value;
        const password   = document.getElementById('accessPassword').value;
        const confirm    = document.getElementById('accessPasswordConfirmation').value;

        // Client-side validation
        let hasError = false;
        if (!password || password.length < 8) {
            showAccessError('errorPassword', 'accessPassword', 'La contraseña debe tener al menos 8 caracteres.');
            hasError = true;
        }
        if (!confirm) {
            showAccessError('errorPasswordConfirmation', 'accessPasswordConfirmation', 'Confirma la contraseña.');
            hasError = true;
        } else if (password !== confirm) {
            showAccessError('errorPasswordConfirmation', 'accessPasswordConfirmation', 'Las contraseñas no coinciden.');
            hasError = true;
        }
        if (hasError) return;

        const submitBtn  = document.getElementById('accessSubmitBtn');
        const submitText = document.getElementById('accessSubmitText');
        const spinner    = document.getElementById('accessSubmitSpinner');

        submitBtn.disabled = true;
        submitText.classList.add('hidden');
        spinner.classList.remove('hidden');

        const formData = new FormData();
        formData.append('password', password);
        formData.append('password_confirmation', confirm);

        try {
            const response = await fetch(`/admin/clientes/${customerId}/acceso`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const data = await response.json();

            if (!response.ok) {
                if (data.errors) {
                    if (data.errors.password)              showAccessError('errorPassword', 'accessPassword', data.errors.password[0]);
                    if (data.errors.password_confirmation) showAccessError('errorPasswordConfirmation', 'accessPasswordConfirmation', data.errors.password_confirmation[0]);
                } else {
                    showClientToast(data.message || 'Ocurrió un error', 'error');
                }
                return;
            }

            showClientToast(data.message || 'Acceso otorgado correctamente');
            closeAccessModal();

            // Update icon in table without page reload
            const btn = document.querySelector(`.access_panel[data-customer-id="${customerId}"]`);
            if (btn) {
                btn.classList.add('access-granted');
                btn.title = 'Restablecer contraseña del portal';
                btn.dataset.hasAccess = 'true';
            }

        } catch (err) {
            showClientToast('Error de conexión. Intenta de nuevo.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitText.classList.remove('hidden');
            spinner.classList.add('hidden');
        }
    });

    function showAccessError(errorId, inputId, message) {
        const errorEl = document.getElementById(errorId);
        const inputEl = document.getElementById(inputId);
        if (errorEl) { errorEl.textContent = message; errorEl.classList.remove('hidden'); }
        if (inputEl) inputEl.classList.add('access-input-error');
    }

    // Close on overlay click
    document.getElementById('accessModalOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeAccessModal();
    });

    // Expose globally
    window.openAccessModal  = openAccessModal;
    window.closeAccessModal = closeAccessModal;
    window.toggleAccessPassword = toggleAccessPassword;
    window.toggleClientStatus = toggleClientStatus;
    window.togglePortalAccess = togglePortalAccess;
    window.loadPortalRequest = loadPortalRequest;
})();
</script>
@endpush
