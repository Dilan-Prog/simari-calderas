{{-- MODAL: cerrar sesión (REAL — el único que persiste) --}}
<div class="eq-modal" x-show="modal === 'logout'" x-cloak @click.self="closeModal()" @keydown.escape.window="closeModal()">
    <div class="eq-modal__card">
        <div class="eq-modal__icon eq-modal__icon--danger">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div class="eq-modal__title">¿Cerrar sesión?</div>
        <div class="eq-modal__text">Tendrás que iniciar sesión de nuevo para acceder a tu cuenta.</div>
        <div class="eq-modal__actions">
            <button type="button" class="eq-modal__btn" @click="closeModal()">Cancelar</button>
            <form method="POST" action="{{ route('shop.logout') }}" style="flex:1;display:flex;">
                @csrf
                <button type="submit" class="eq-modal__btn eq-modal__btn--danger">Cerrar sesión</button>
            </form>
        </div>
    </div>
</div>

{{-- MODAL: confirmar guardar perfil (visual) --}}
<div class="eq-modal" x-show="modal === 'profile-save'" x-cloak @click.self="closeModal()">
    <div class="eq-modal__card">
        <div class="eq-modal__icon eq-modal__icon--accent">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div class="eq-modal__title">¿Guardar los cambios?</div>
        <div class="eq-modal__text">Se actualizará la información de tu perfil.</div>
        <div class="eq-modal__actions">
            <button type="button" class="eq-modal__btn" @click="closeModal()">Cancelar</button>
            <button type="button" class="eq-modal__btn eq-modal__btn--primary" @click="confirmProfile()">Guardar</button>
        </div>
    </div>
</div>

{{-- MODAL: confirmar actualizar contraseña (visual) --}}
<div class="eq-modal" x-show="modal === 'password-save'" x-cloak @click.self="closeModal()">
    <div class="eq-modal__card">
        <div class="eq-modal__icon eq-modal__icon--accent">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V7a4 4 0 1 1 8 0v4" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div class="eq-modal__title">¿Actualizar tu contraseña?</div>
        <div class="eq-modal__text">Deberás usar la nueva contraseña la próxima vez que inicies sesión.</div>
        <div class="eq-modal__actions">
            <button type="button" class="eq-modal__btn" @click="closeModal()">Cancelar</button>
            <button type="button" class="eq-modal__btn eq-modal__btn--primary" @click="confirmPassword()">Actualizar</button>
        </div>
    </div>
</div>

{{-- MODAL: form dirección (visual) --}}
<div class="eq-modal" x-show="modal === 'address-form'" x-cloak @click.self="closeModal()">
    <div class="eq-modal__card eq-modal__card--form">
        <div class="eq-modal__header">
            <div class="eq-modal__header-title" x-text="modalData.title ?? 'Agregar dirección'"></div>
            <button type="button" class="eq-modal__close" @click="closeModal()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12" stroke-linecap="round"/></svg>
            </button>
        </div>
        <div class="eq-modal__body">
            <div class="portal-field">
                <label>Etiqueta</label>
                <input type="text" placeholder="Ej. Oficina Corporativa" :value="modalData.label ?? ''">
            </div>
            <div class="portal-field">
                <label>Calle y número</label>
                <input type="text" :value="modalData.calle ?? ''">
            </div>
            <div class="portal-grid-2">
                <div class="portal-field">
                    <label>Colonia</label>
                    <input type="text" :value="modalData.colonia ?? ''">
                </div>
                <div class="portal-field">
                    <label>CP</label>
                    <input type="text" :value="modalData.cp ?? ''">
                </div>
            </div>
            <div class="portal-field">
                <label>Ciudad, Estado</label>
                <input type="text" :value="modalData.ciudad ?? ''">
            </div>
            <label class="auth-check">
                <input type="checkbox" :checked="modalData.isDefault ?? false"> Establecer como predeterminada
            </label>
        </div>
        <div class="eq-modal__actions eq-modal__actions--footer">
            <button type="button" class="eq-modal__btn" @click="closeModal()">Cancelar</button>
            <button type="button" class="eq-modal__btn eq-modal__btn--primary" @click="closeModal()">Guardar dirección</button>
        </div>
    </div>
</div>

{{-- MODAL: eliminar dirección (visual) --}}
<div class="eq-modal" x-show="modal === 'address-delete'" x-cloak @click.self="closeModal()">
    <div class="eq-modal__card">
        <div class="eq-modal__icon eq-modal__icon--danger">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div class="eq-modal__title">¿Eliminar esta dirección?</div>
        <div class="eq-modal__text"><span x-text="modalData.label ?? 'Esta dirección'"></span> se eliminará de tu cuenta. Esta acción no se puede deshacer.</div>
        <div class="eq-modal__actions">
            <button type="button" class="eq-modal__btn" @click="closeModal()">Cancelar</button>
            <button type="button" class="eq-modal__btn eq-modal__btn--danger" @click="closeModal()">Eliminar</button>
        </div>
    </div>
</div>

{{-- MODAL: form tarjeta (visual, con preview en vivo) --}}
<div class="eq-modal" x-show="modal === 'card-form'" x-cloak @click.self="closeModal()">
    <div class="eq-modal__card eq-modal__card--form"
        x-data="{ number: '', expiry: '',
            get digits() { return this.number.replace(/\D/g, '').slice(0, 16); },
            get masked() { const d = this.digits.padEnd(16, '•'); return d.match(/.{1,4}/g).join(' '); },
            get brand() { const d = this.digits; if (/^4/.test(d)) return 'Visa'; if (/^(5[1-5]|2[2-7])/.test(d)) return 'Mastercard'; if (/^3[47]/.test(d)) return 'American Express'; return 'Tarjeta'; },
            get gradient() { return this.brand === 'Visa' ? 'linear-gradient(135deg,#1a1f71,#2b3a9e)' : this.brand === 'Mastercard' ? 'linear-gradient(135deg,#3a2a1e,#5c3a1e)' : this.brand === 'American Express' ? 'linear-gradient(135deg,#0f6e6e,#12908f)' : 'linear-gradient(135deg,#3a3d40,#57595c)'; },
            format() { this.number = this.digits.match(/.{1,4}/g)?.join(' ') ?? ''; } }">
        <div class="eq-modal__header">
            <div class="eq-modal__header-title" x-text="modalData.title ?? 'Agregar tarjeta'"></div>
            <button type="button" class="eq-modal__close" @click="closeModal()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12" stroke-linecap="round"/></svg>
            </button>
        </div>
        <div class="eq-modal__body">
            <div class="bank-card bank-card--preview" :style="'background:' + gradient">
                <div class="bank-card__top">
                    <div class="bank-card__chip"></div>
                    <span class="bank-card__brand" x-text="brand"></span>
                </div>
                <div class="bank-card__number" x-text="masked"></div>
            </div>
            <div class="portal-field">
                <label>Número de tarjeta</label>
                <input type="text" maxlength="19" placeholder="0000 0000 0000 0000" x-model="number" @input="format()">
            </div>
            <div class="portal-grid-2">
                <div class="portal-field">
                    <label>Vencimiento (MM/AA)</label>
                    <input type="text" placeholder="09/28" x-model="expiry" maxlength="5">
                </div>
                <div class="portal-field">
                    <label>CVV</label>
                    <input type="text" maxlength="4" placeholder="123">
                </div>
            </div>
        </div>
        <div class="eq-modal__actions eq-modal__actions--footer">
            <button type="button" class="eq-modal__btn" @click="closeModal()">Cancelar</button>
            <button type="button" class="eq-modal__btn eq-modal__btn--primary" @click="closeModal()">Guardar tarjeta</button>
        </div>
    </div>
</div>

{{-- MODAL: eliminar tarjeta (visual) --}}
<div class="eq-modal" x-show="modal === 'card-delete'" x-cloak @click.self="closeModal()">
    <div class="eq-modal__card">
        <div class="eq-modal__icon eq-modal__icon--danger">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div class="eq-modal__title">¿Eliminar esta tarjeta?</div>
        <div class="eq-modal__text"><span x-text="(modalData.brand ?? 'Tarjeta') + ' •••• ' + (modalData.last4 ?? '')"></span> se eliminará de tu cuenta. Esta acción no se puede deshacer.</div>
        <div class="eq-modal__actions">
            <button type="button" class="eq-modal__btn" @click="closeModal()">Cancelar</button>
            <button type="button" class="eq-modal__btn eq-modal__btn--danger" @click="closeModal()">Eliminar</button>
        </div>
    </div>
</div>
