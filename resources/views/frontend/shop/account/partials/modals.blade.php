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

{{-- MODAL: form dirección (REAL -- persiste vía CustomerAddressController).
     Validación en línea (campos obligatorios con *, caracteres permitidos, contorno rojo + mensaje) y, si el
     servidor devuelve errores, el modal se reabre con lo capturado y cada error en su campo. CSS/JS en línea (am-). --}}
@php
    $amOld = old('_am_form') === 'address';
    $amErr = fn (string $k) => $amOld ? $errors->first($k) : '';
    $amCls = fn (string $k) => $amErr($k) ? 'is-invalid' : '';
@endphp
<div class="eq-modal" x-show="modal === 'address-form'" x-cloak @click.self="closeModal()" data-am-modal>
    <div class="eq-modal__card eq-modal__card--form">
        <form method="POST" :action="modalData.actionUrl" novalidate data-am-form>
            @csrf
            <input type="hidden" name="_am_form" value="address">
            <input type="hidden" name="_am_url" :value="modalData.actionUrl ?? ''">
            <template x-if="modalData.method === 'PUT'">
                <input type="hidden" name="_method" value="PUT">
            </template>
            <div class="eq-modal__header">
                <div class="eq-modal__header-title" x-text="modalData.title ?? 'Agregar dirección'"></div>
                <button type="button" class="eq-modal__close" @click="closeModal()" aria-label="Cerrar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12" stroke-linecap="round"/></svg>
                </button>
            </div>
            <div class="eq-modal__body am-body">
                <p class="am-note"><span class="auth-req" aria-hidden="true">*</span> Campos obligatorios</p>

                <div class="portal-field {{ $amCls('label') }}">
                    <label for="am_label">Etiqueta <small class="am-opt">(opcional)</small></label>
                    <input type="text" id="am_label" name="label" placeholder="Ej. Oficina Corporativa" maxlength="100" data-rule="label" :value="modalData.label ?? ''">
                    <p class="am-err" data-err-for="label">{{ $amErr('label') }}</p>
                </div>
                <div class="portal-grid-2">
                    <div class="portal-field {{ $amCls('recipient_name') }}">
                        <label for="am_recipient">Nombre de quien recibe <span class="auth-req" aria-hidden="true">*</span></label>
                        <input type="text" id="am_recipient" name="recipient_name" maxlength="150" autocomplete="name" data-rule="name" required aria-required="true" :value="modalData.recipient_name ?? ''">
                        <p class="am-err" data-err-for="recipient_name">{{ $amErr('recipient_name') }}</p>
                    </div>
                    <div class="portal-field {{ $amCls('phone') }}">
                        <label for="am_phone">Teléfono <span class="auth-req" aria-hidden="true">*</span></label>
                        <input type="tel" id="am_phone" name="phone" maxlength="30" inputmode="tel" autocomplete="tel" placeholder="10 dígitos" data-rule="phone" required aria-required="true" :value="modalData.phone ?? ''">
                        <p class="am-err" data-err-for="phone">{{ $amErr('phone') }}</p>
                    </div>
                </div>
                <div class="portal-field {{ $amCls('address_line1') }}">
                    <label for="am_line1">Calle y número <span class="auth-req" aria-hidden="true">*</span></label>
                    <input type="text" id="am_line1" name="address_line1" maxlength="255" autocomplete="address-line1" placeholder="Ej. Av. Juárez #123" data-rule="street" required aria-required="true" :value="modalData.address_line1 ?? ''">
                    <p class="am-err" data-err-for="address_line1">{{ $amErr('address_line1') }}</p>
                </div>
                <div class="portal-grid-2">
                    <div class="portal-field {{ $amCls('address_line2') }}">
                        <label for="am_line2">Colonia, referencias <small class="am-opt">(opcional)</small></label>
                        <input type="text" id="am_line2" name="address_line2" maxlength="255" autocomplete="address-line2" data-rule="street-opt" :value="modalData.address_line2 ?? ''">
                        <p class="am-err" data-err-for="address_line2">{{ $amErr('address_line2') }}</p>
                    </div>
                    <div class="portal-field {{ $amCls('postal_code') }}">
                        <label for="am_postal">CP <span class="auth-req" aria-hidden="true">*</span></label>
                        <input type="text" id="am_postal" name="postal_code" maxlength="5" inputmode="numeric" autocomplete="postal-code" placeholder="5 dígitos" data-rule="postal" required aria-required="true" :value="modalData.postal_code ?? ''">
                        <p class="am-err" data-err-for="postal_code">{{ $amErr('postal_code') }}</p>
                    </div>
                </div>
                <div class="portal-grid-2">
                    <div class="portal-field {{ $amCls('state') }}">
                        <label for="am_state">Estado <span class="auth-req" aria-hidden="true">*</span></label>
                        <select id="am_state" name="state" data-rule="state" required aria-required="true">
                            <option value="">Selecciona un estado</option>
                            @foreach ($estadosMexico as $estado)
                                <option value="{{ $estado }}" :selected="modalData.state === '{{ $estado }}'">{{ $estado }}</option>
                            @endforeach
                        </select>
                        <p class="am-err" data-err-for="state">{{ $amErr('state') }}</p>
                    </div>
                    <div class="portal-field {{ $amCls('city') }}">
                        <label for="am_city">Ciudad <span class="auth-req" aria-hidden="true">*</span></label>
                        <input type="text" id="am_city" name="city" maxlength="100" autocomplete="address-level2" data-rule="city" required aria-required="true" :value="modalData.city ?? ''">
                        <p class="am-err" data-err-for="city">{{ $amErr('city') }}</p>
                    </div>
                </div>
                <label class="auth-check am-default">
                    <input type="checkbox" name="is_default" value="1" :checked="modalData.is_default ?? false"> Establecer como predeterminada
                </label>
            </div>
            <div class="eq-modal__actions eq-modal__actions--footer">
                <button type="button" class="eq-modal__btn" @click="closeModal()">Cancelar</button>
                <button type="submit" class="eq-modal__btn eq-modal__btn--primary">Guardar dirección</button>
            </div>
        </form>
    </div>
</div>

@if ($amOld)
    {{-- El servidor rechazó la dirección: reabre el modal con lo capturado (los errores ya van pintados en cada campo). --}}
    <div hidden x-init="go('direcciones'); openModal('address-form', @js([
        'title' => old('_method') === 'PUT' ? 'Editar dirección' : 'Agregar dirección',
        'actionUrl' => old('_am_url'),
        'method' => old('_method') === 'PUT' ? 'PUT' : 'POST',
        'label' => old('label'), 'recipient_name' => old('recipient_name'), 'phone' => old('phone'),
        'postal_code' => old('postal_code'), 'state' => old('state'), 'city' => old('city'),
        'address_line1' => old('address_line1'), 'address_line2' => old('address_line2'),
        'is_default' => (bool) old('is_default'),
    ]))"></div>
@endif

<style>
    .eq-shop-account .am-body { display: flex; flex-direction: column; gap: 14px; }
    .eq-shop-account .am-body .portal-field { margin: 0; min-width: 0; }
    .eq-shop-account .am-body .portal-grid-2 { align-items: start; }
    .eq-shop-account .am-note { margin: 0; font-size: 12.5px; color: #4b5563; }
    .eq-shop-account .am-opt { font-weight: 500; color: #6b7280; }
    .eq-shop-account .am-body .auth-req { color: #c81e1e; font-weight: 800; }
    .eq-shop-account .am-body .portal-field select {
        width: 100%; box-sizing: border-box; height: 43px; padding: 0 38px 0 14px; font-size: 14px; font-family: inherit; color: #141516;
        border: 1px solid rgba(0, 0, 0, .15); border-radius: 6px; outline: none; cursor: pointer;
        -webkit-appearance: none; appearance: none; background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8' fill='none' stroke='%234b5563' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M1 1.5l5 5 5-5'/%3E%3C/svg%3E") no-repeat right 14px center;
    }
    .eq-shop-account .am-body .portal-field select:focus { border-color: var(--secondary-color); }
    .eq-shop-account .am-body .am-default { margin: 0; }
    .eq-shop-account .am-err { margin: 5px 0 0; font-size: 12px; line-height: 1.35; color: #c81e1e; }
    .eq-shop-account .am-err:empty { display: none; }
    .eq-shop-account .am-body .portal-field.is-invalid input:not([type="checkbox"]),
    .eq-shop-account .am-body .portal-field.is-invalid select { border-color: #c81e1e; background-color: #fff8f8; }
    .eq-shop-account .am-body .portal-field.is-invalid input:focus,
    .eq-shop-account .am-body .portal-field.is-invalid select:focus { box-shadow: 0 0 0 3px rgba(200, 30, 30, .12); }
</style>

<script>
    (function () {
        var wrap = document.querySelector('[data-am-modal]');
        var form = wrap && wrap.querySelector('[data-am-form]');
        if (!form) return;

        // Caracteres que NO se aceptan (se descartan al escribir o pegar).
        var STRIP = {
            name: /[^\p{L}\p{M}\s.'’\-]/gu,
            city: /[^\p{L}\p{M}\s.'’\-]/gu,
            label: /[^\p{L}\p{N}\p{M}\s.,&'’\-\/()]/gu,
            street: /[^\p{L}\p{N}\p{M}\s.,#°'’\-\/()]/gu,
            'street-opt': /[^\p{L}\p{N}\p{M}\s.,#°'’\-\/()]/gu,
            phone: /[^\d\s\-().+]/g,
            postal: /\D/g
        };
        var HINT = {
            name: 'Solo letras, espacios, punto, apóstrofo y guion.',
            city: 'Solo letras, espacios, punto, apóstrofo y guion.',
            label: "Solo letras, números, espacios y . , & ' - / ( ).",
            street: 'Solo letras, números, espacios y . , # ° - / ( ).',
            'street-opt': 'Solo letras, números, espacios y . , # ° - / ( ).',
            phone: 'Solo números, espacios, guiones y paréntesis.',
            postal: 'El código postal solo lleva números.'
        };
        var NAME_OK = /^[\p{L}\p{M}][\p{L}\p{M}\s.'’\-]*$/u;
        var LABEL_OK = /^[\p{L}\p{N}][\p{L}\p{N}\p{M}\s.,&'’\-\/()]*$/u;
        var STREET_OK = /^[\p{L}\p{N}][\p{L}\p{N}\p{M}\s.,#°'’\-\/()]*$/u;
        var PHONE_OK = /^\+?[\d\s\-().]+$/;

        function box(el) { return form.querySelector('[data-err-for="' + el.name + '"]'); }
        function field(el) { return el.closest('.portal-field'); }

        function setError(el, msg) {
            var b = box(el), f = field(el);
            if (b) b.textContent = msg || '';
            if (f) f.classList.toggle('is-invalid', !!msg);
            el.setAttribute('aria-invalid', msg ? 'true' : 'false');
            return msg;
        }

        function check(el) {
            var rule = el.dataset.rule, v = el.value.trim();
            switch (rule) {
                case 'name': return !v ? 'Escribe el nombre de quien recibe.' : (NAME_OK.test(v) ? '' : HINT.name);
                case 'city': return !v ? 'Escribe la ciudad.' : (NAME_OK.test(v) ? '' : HINT.city);
                case 'label': return !v || LABEL_OK.test(v) ? '' : HINT.label;
                case 'street': return !v ? 'Escribe la calle y el número.' : (STREET_OK.test(v) ? '' : HINT.street);
                case 'street-opt': return !v || STREET_OK.test(v) ? '' : HINT['street-opt'];
                case 'phone':
                    if (!v) return 'Escribe un teléfono de contacto.';
                    if (!PHONE_OK.test(v)) return HINT.phone;
                    var n = v.replace(/\D/g, '').length;
                    return n >= 10 && n <= 15 ? '' : 'El teléfono debe tener entre 10 y 15 dígitos.';
                case 'postal': return !v ? 'Escribe tu código postal.' : (/^\d{5}$/.test(v) ? '' : 'El código postal debe tener 5 dígitos.');
                case 'state': return el.value ? '' : 'Selecciona un estado.';
            }
            return '';
        }

        var fields = [].slice.call(form.querySelectorAll('[data-rule]'));
        var touched = new WeakSet();

        fields.forEach(function (el) {
            el.addEventListener('input', function () {
                var strip = STRIP[el.dataset.rule];
                if (strip) {
                    var clean = el.value.replace(strip, '');
                    if (clean !== el.value) {
                        el.value = clean;
                        setError(el, HINT[el.dataset.rule]); // avisa por qué desapareció el carácter
                        return;
                    }
                }
                if (touched.has(el) || field(el).classList.contains('is-invalid')) setError(el, check(el));
            });
            el.addEventListener(el.tagName === 'SELECT' ? 'change' : 'blur', function () {
                touched.add(el);
                setError(el, check(el));
            });
        });

        form.addEventListener('submit', function (e) {
            var first = null;
            fields.forEach(function (el) {
                touched.add(el);
                if (setError(el, check(el)) && !first) first = el;
            });
            if (first) { e.preventDefault(); first.focus(); }
        });

        // Al cerrar el modal se limpian marcas y errores, para que el siguiente "Agregar/Editar" empiece limpio.
        function reset() {
            fields.forEach(function (el) { setError(el, ''); el.removeAttribute('aria-invalid'); });
            touched = new WeakSet();
        }
        if (window.MutationObserver) {
            var wasOpen = false; // el estado inicial "oculto" de la carga no debe borrar los errores que pintó el servidor
            new MutationObserver(function () {
                var hidden = getComputedStyle(wrap).display === 'none';
                if (!hidden) wasOpen = true;
                else if (wasOpen) { wasOpen = false; reset(); }
            }).observe(wrap, { attributes: true, attributeFilter: ['style'] });
        }
    })();
</script>

{{-- MODAL: eliminar dirección (REAL) --}}
<div class="eq-modal" x-show="modal === 'address-delete'" x-cloak @click.self="closeModal()">
    <div class="eq-modal__card">
        <div class="eq-modal__icon eq-modal__icon--danger">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <div class="eq-modal__title">¿Eliminar esta dirección?</div>
        <div class="eq-modal__text"><span x-text="modalData.label ?? 'Esta dirección'"></span> se eliminará de tu cuenta. Esta acción no se puede deshacer.</div>
        <form method="POST" :action="modalData.actionUrl" style="display:contents;">
            @csrf
            @method('DELETE')
            <input type="hidden" name="_am_form" value="address">
            <div class="eq-modal__actions">
                <button type="button" class="eq-modal__btn" @click="closeModal()">Cancelar</button>
                <button type="submit" class="eq-modal__btn eq-modal__btn--danger">Eliminar</button>
            </div>
        </form>
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
