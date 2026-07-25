import Alpine from './alpine-init.js';

/* Tabs de la vista compartida de auth (login/registro). El toggle es
   instantáneo; replaceState mantiene la URL real de cada tab para que
   refresh/back conserven el estado. */
Alpine.data('authTabs', (initial, loginUrl, registerUrl) => ({
    tab: initial,

    switchTo(tab) {
        this.tab = tab;
        history.replaceState(null, '', tab === 'register' ? registerUrl : loginUrl);
    },
}));

/* Shell del portal Mi Cuenta: sección activa (sincronizada con el hash de
   la URL para que /cuenta#pedidos y los links del header funcionen), pedido
   seleccionado (demo) y estado de modales. */
Alpine.data('accountPortal', () => ({
    section: 'perfil',
    selectedOrder: null,
    modal: null,
    modalData: {},
    profileSaved: false,
    passwordSaved: false,

    hashMap: {
        '#pedidos': 'pedidos',
        '#direcciones': 'direcciones',
        '#pagos': 'pagos',
        '#favoritos': 'favoritos',
        '#solicitar-portal': 'solicitar-portal',
    },

    init() {
        this.applyHash();
        window.addEventListener('hashchange', () => this.applyHash());
    },

    applyHash() {
        const section = this.hashMap[window.location.hash];
        if (section) {
            this.section = section;
            this.selectedOrder = null;
        }
    },

    go(section) {
        this.section = section;
        this.selectedOrder = null;
        const hash = Object.keys(this.hashMap).find((h) => this.hashMap[h] === section);
        history.replaceState(null, '', hash ?? window.location.pathname);
    },

    openModal(name, data = {}) {
        this.modal = name;
        this.modalData = data;
    },

    closeModal() {
        this.modal = null;
        this.modalData = {};
    },

    confirmProfile() {
        this.closeModal();
        this.profileSaved = true;
        setTimeout(() => (this.profileSaved = false), 3000);
    },

    confirmPassword() {
        this.closeModal();
        this.passwordSaved = true;
        setTimeout(() => (this.passwordSaved = false), 3000);
    },
}));

/* Wizard de solicitud de acceso al portal: cada respuesta se envía al
   backend en cuanto se contesta (autosave). Retoma en la primera pregunta
   sin responder si el cliente dejó el wizard a medias. */
Alpine.data('portalWizard', (config) => ({
    step: 1,
    answers: {
        purchase_frequency: config.purchase_frequency ?? null,
        purchase_amount: config.purchase_amount ?? null,
        reason: config.reason ?? null,
    },

    init() {
        if (this.answers.purchase_frequency) this.step = 2;
        if (this.answers.purchase_frequency && this.answers.purchase_amount) this.step = 3;
        if (this.answers.purchase_frequency && this.answers.purchase_amount && this.answers.reason) this.step = 4;
    },

    get progress() {
        return Math.min(100, (this.step - 1) * 33.4);
    },

    async answer(field, value) {
        this.answers[field] = value;

        try {
            await fetch(config.answerUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ field, value }),
            });
        } catch (err) {
            // El autosave es best-effort; el paso final valida todo de nuevo.
        }

        this.step = Math.min(4, this.step + 1);
    },
}));

Alpine.start();
