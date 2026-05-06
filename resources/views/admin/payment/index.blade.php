@extends('admin.layouts.master')

@section('title')
    Métodos de Pago - Admin
@endsection

@section('content')

    <!-- MAIN SECTION -->
    <section class="payment-method-section">
        <div class="container">
            <!-- HEADER -->
            <header class="payment-method-main">
                <div>
                    <h1>Métodos de Pago</h1>
                </div>

                <button id="btnNuevoMetodo" class="button-primary size-adjustment">
                    + Nuevo Método
                </button>
            </header>

            <div class="payment-method-container"></div>
        </div>
    </section>

    <!-- HIDDEN TEMPLATES: THEIR CONTENT IS CLONED WHEN OPENING THE MODAL -->
    <div id="createTemplate" style="display:none;">
        @include('admin.payment.create-payment-method')
    </div>

    <div id="editTemplate" style="display:none;">
        @include('admin.payment.edit-payment-method')
    </div>

    <div id="deleteTemplate" style="display:none;">
        @include('admin.payment.delete-payment-method')
    </div>

    <!-- TOAST -->
    <div class="pm-toast" id="pmToast">
        <div class="pm-toast-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 6 9 17l-5-5" />
            </svg>
        </div>
        <div class="pm-toast-body">
            <p class="pm-toast-title" id="pmToastTitle"></p>
            <p class="pm-toast-subtitle" id="pmToastSubtitle"></p>
        </div>
    </div>

    <!-- GLOBAL MODAL (overlay + drawer) -->
    <div class="payment-method-modal" id="globalModal">
        <div class="payment-method-overlay" id="modalOverlay"></div>

        <div id="modalContent" class="modal-content"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            /* ── STATE ─────────────────────────────────────────────── */
            let paymentMethods = JSON.parse(localStorage.getItem('paymentMethods')) || [
                {
                    id: 1,
                    name: 'Tarjeta de Crédito',
                    logo: '',
                    description: 'Paga con tarjeta de crédito.',
                    instructions: 'Ingresa los datos de tu tarjeta.',
                    commission: 2.5,
                    order: 1,
                    status: 'activo'
                },
                {
                    id: 2,
                    name: 'Transferencia Bancaria',
                    logo: '',
                    description: 'Pago vía transferencia.',
                    instructions: 'Usa los datos bancarios.',
                    commission: 0,
                    order: 2,
                    status: 'inactivo'
                }
            ];

            let editingId = null;

            const modal = document.getElementById('globalModal');
            const modalContent = document.getElementById('modalContent');
            const overlay = document.getElementById('modalOverlay');

            /* ── Toast ──────────────────────────────────────────────── */
            let toastTimer = null;

            function showToast(title, subtitle) {
                const toast = document.getElementById('pmToast');
                const elTitle = document.getElementById('pmToastTitle');
                const elSub = document.getElementById('pmToastSubtitle');

                elTitle.textContent = title;
                elSub.textContent = subtitle;

                toast.classList.add('pm-toast--visible');

                clearTimeout(toastTimer);
                toastTimer = setTimeout(() => {
                    toast.classList.remove('pm-toast--visible');
                }, 3500);
            }

            /* ── OPEN / CLOSE MODAL ───────────────────────────────── */
            function openModal() {
                editingId = null;

                modalContent.className = 'modal-content drawer-mode';
                modalContent.innerHTML = document.getElementById('createTemplate').innerHTML;

                attachCreateEvents();
                modal.classList.add('active');
            }

            function openEditModal(id) {
                const pm = paymentMethods.find(p => p.id === id);
                if (!pm) return;

                editingId = id;
                modalContent.className = 'modal-content drawer-mode';
                modalContent.innerHTML = document.getElementById('editTemplate').innerHTML;

                // Rellenar el formulario con los datos existentes
                const form = modalContent.querySelector('#paymentMethodForm');
                form.querySelector('[name="name"]').value = pm.name;
                form.querySelector('[name="logo"]').value = pm.logo;
                form.querySelector('[name="description"]').value = pm.description;
                form.querySelector('[name="instructions"]').value = pm.instructions;
                form.querySelector('[name="commission"]').value = pm.commission;
                form.querySelector('[name="order"]').value = pm.order;
                form.querySelector('[name="status"]').value = pm.status;

                attachEditEvents();
                modal.classList.add('active');
            }

            function closeModal() {
                modal.classList.remove('active');
                modalContent.innerHTML = '';
                editingId = null;
            }

            /* ── COMMON CLOSE EVENTS ──────────────────────────── */
            function bindCloseEvents() {
                modalContent.querySelector('#closeModal')?.addEventListener('click', closeModal);
                modalContent.querySelector('#cancelModal')?.addEventListener('click', closeModal);
            }

            /* ── CREATE LOGIC ────────────────────────────────────── */
            function attachCreateEvents() {
                bindCloseEvents();
                const form = modalContent.querySelector('#paymentMethodForm');

                form.addEventListener('submit', function (e) {
                    e.preventDefault();

                    const data = {
                        id: Date.now(),
                        name: form.querySelector('[name="name"]').value,
                        logo: form.querySelector('[name="logo"]').value,
                        description: form.querySelector('[name="description"]').value,
                        instructions: form.querySelector('[name="instructions"]').value,
                        commission: form.querySelector('[name="commission"]').value,
                        order: form.querySelector('[name="order"]').value,
                        status: form.querySelector('[name="status"]').value
                    };

                    paymentMethods.push(data);
                    localStorage.setItem('paymentMethods', JSON.stringify(paymentMethods));

                    renderPaymentMethods();
                    closeModal();
                    showToast('Método de pago creado', 'El método de pago fue creado correctamente.');
                });
            }

            /* ── EDIT LOGIC ───────────────────────────────────── */
            function attachEditEvents() {
                bindCloseEvents();
                const form = modalContent.querySelector('#paymentMethodForm');

                form.addEventListener('submit', function (e) {
                    e.preventDefault();

                    const data = {
                        id: editingId,
                        name: form.querySelector('[name="name"]').value,
                        logo: form.querySelector('[name="logo"]').value,
                        description: form.querySelector('[name="description"]').value,
                        instructions: form.querySelector('[name="instructions"]').value,
                        commission: form.querySelector('[name="commission"]').value,
                        order: form.querySelector('[name="order"]').value,
                        status: form.querySelector('[name="status"]').value
                    };

                    paymentMethods = paymentMethods.map(pm => pm.id === editingId ? data : pm);
                    localStorage.setItem('paymentMethods', JSON.stringify(paymentMethods));

                    renderPaymentMethods();
                    closeModal();
                    showToast('Método de pago actualizado', 'Los cambios fueron guardados correctamente.');
                });
            }

            /* ── DELETE LOGIC ───────────────────────────────────────────── */
            function deletePaymentMethod(id) {
                const pm = paymentMethods.find(p => p.id === id);
                if (!pm) return;

                modalContent.className = 'modal-content center-mode';
                modalContent.innerHTML = document.getElementById('deleteTemplate').innerHTML;

                requestAnimationFrame(() => {

                    bindCloseEvents();

                    const nameEl = modalContent.querySelector('#deleteModalName');
                    const metaEl = modalContent.querySelector('#deleteModalCommission');
                    const confirmBtn = modalContent.querySelector('#deleteConfirmBtn');

                    if (!nameEl || !metaEl || !confirmBtn) {
                        console.error('Error en template delete');
                        return;
                    }

                    nameEl.textContent = pm.name;
                    metaEl.textContent = `Comisión: ${pm.commission}% · ${pm.status === 'activo' ? 'Activo' : 'Inactivo'}`;

                    confirmBtn.onclick = null;

                    confirmBtn.onclick = () => {
                        paymentMethods = paymentMethods.filter(p => p.id !== id);
                        localStorage.setItem('paymentMethods', JSON.stringify(paymentMethods));

                        renderPaymentMethods();
                        closeModal();

                        showToast('Método eliminado', 'El método fue eliminado correctamente.');
                    };
                });

                // SHOW MODAL
                modal.classList.add('active');
            }

            /* ── CARD RENDER ─────────────────────────────────── */
            function renderPaymentMethods() {
                const container = document.querySelector('.payment-method-container');
                container.innerHTML = '';

                if (paymentMethods.length === 0) {
                    container.innerHTML = `
                                                                <div class="payment-method-empty">
                                                                    <p>No hay métodos de pago por el momento</p>
                                                                </div>`;
                    return;
                }

                paymentMethods.forEach(pm => {
                    const logoHtml = pm.logo
                        ? `<img src="${pm.logo}" style="width:100%; height:100%; object-fit:contain;" alt="${pm.name}">`
                        : `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="1.5"
                                stroke-linecap="round" stroke-linejoin="round">
                                <rect width="20" height="14" x="2" y="5" rx="2"/>
                                <line x1="2" x2="22" y1="10" y2="10"/>
                            </svg>`;

                    container.innerHTML += `
                        <div class="payment-method-card">

                            <!-- HEADER -->
                            <div class="payment-method-header">
                                <div class="payment-method-info">
                                    <div class="payment-method-icon">${logoHtml}</div>
                                    <div class="payment-method-text">
                                        <h3 class="payment-method-name">${pm.name}</h3>
                                        <p class="payment-method-commission">Comisión: ${pm.commission || 0}%</p>
                                    </div>
                                </div>
                                <span class="payment-method-status ${pm.status === 'activo' ? 'status-active' : 'status-inactive'}">
                                    ${pm.status === 'activo' ? 'Activo' : 'Inactivo'}
                                </span>
                            </div>

                            <!-- DESCRIPCIÓN -->
                            <p class="payment-method-description">${pm.description || ''}</p>

                            <!-- INSTRUCCIONES -->
                            <div class="payment-method-input">
                                ${pm.instructions || 'Sin instrucciones'}
                            </div>

                            <!-- ACCIONES -->
                            <div class="payment-method-actions">
                                <button class="payment-method-btn-edit" onclick="openEditModal(${pm.id})">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/>
                                    </svg>
                                    Editar
                                </button>

                                <button class="payment-method-btn-delete" onclick="deletePaymentMethod(${pm.id})">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 6h18"/>
                                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                        <line x1="10" x2="10" y1="11" y2="17"/>
                                        <line x1="14" x2="14" y1="11" y2="17"/>
                                    </svg>
                                </button>
                            </div>

                        </div>`;
                });
            }

            /* ── STATIC EVENTS ──────────────────────────────────── */
            document.getElementById('btnNuevoMetodo').addEventListener('click', openModal);
            modal.addEventListener('click', function (e) {

                const isInside = e.target.closest(
                    '.payment-method-modal-drawer, .pm-delete-modal-box'
                );

                if (!isInside) {
                    closeModal();
                }

            });

            /* ── EXPOSE TO GLOBAL SCOPE (used in onclick of HTML) ── */
            window.openEditModal = openEditModal;
            window.deletePaymentMethod = deletePaymentMethod;

            /* ── INITIAL RENDER ─────────────────────────────────────── */
            renderPaymentMethods();
        });
    </script>
@endsection