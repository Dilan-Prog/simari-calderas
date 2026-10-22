@extends('customers.layouts.master')

@section('title', 'Direcciones')

@push('styles')
    @vite('resources/css/admin/technical-services.css')
    <style>
        .ad-grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); }
        .ad-card { position: relative; display: flex; flex-direction: column; gap: .75rem; background: var(--ts-bg-white, #fff); border: 1px solid var(--ts-border, #E5E7EB); border-radius: 10px; padding: 1.125rem; box-shadow: var(--ts-shadow-sm, 0 1px 2px rgba(0,0,0,.05)); }
        .ad-card--default { border-color: var(--ts-primary, #ff6213); box-shadow: 0 0 0 1px var(--ts-primary, #ff6213); }
        .ad-card__head { display: flex; align-items: flex-start; justify-content: space-between; gap: .5rem; }
        .ad-card__label { margin: 0; font-size: .9375rem; font-weight: 700; color: var(--ts-text-title, #111827); overflow-wrap: anywhere; }
        .ad-badge { display: inline-flex; align-items: center; padding: .2rem .6rem; border-radius: 9999px; font-size: .6875rem; font-weight: 600; background: #FFF4EC; color: #C2410C; border: 1px solid #FED7AA; white-space: nowrap; }
        .ad-card__body { font-size: .8125rem; line-height: 1.55; color: var(--ts-text-body, #374151); flex: 1; overflow-wrap: anywhere; }
        .ad-card__body strong { color: var(--ts-text-title, #111827); }
        .ad-card__ref { margin-top: .375rem; font-size: .75rem; color: var(--ts-text-muted, #6B7280); }
        .ad-card__actions { display: flex; flex-wrap: wrap; gap: .5rem; padding-top: .75rem; border-top: 1px solid var(--ts-border-light, #F3F4F6); }
        .ad-card__actions form { margin: 0; }
        .ad-btn-sm { height: 34px; padding: 0 .75rem; font-size: .8125rem; }
        .ad-empty { text-align: center; padding: 3rem 1.25rem; }
        .ad-empty svg { color: var(--ts-text-light, #9CA3AF); margin-bottom: .75rem; }
        .ad-empty p { margin: 0 0 1.25rem; color: var(--ts-text-muted, #6B7280); font-size: .875rem; }

        .ad-modal { position: fixed; inset: 0; z-index: 1000; display: none; align-items: center; justify-content: center; padding: 1rem; background: rgba(17, 24, 39, .55); }
        .ad-modal.is-open { display: flex; }
        .ad-modal__card { background: #fff; border-radius: 12px; width: 100%; max-width: 560px; max-height: calc(100dvh - 2rem); display: flex; flex-direction: column; box-shadow: 0 20px 50px rgba(0,0,0,.25); }
        .ad-modal__card--sm { max-width: 420px; }
        .ad-modal__head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: 1rem 1.25rem; border-bottom: 1px solid var(--ts-border-light, #F3F4F6); }
        .ad-modal__title { margin: 0; font-size: 1.0625rem; font-weight: 700; color: var(--ts-text-title, #111827); }
        .ad-modal__close { border: 0; background: transparent; cursor: pointer; width: 36px; height: 36px; border-radius: 8px; color: var(--ts-text-muted, #6B7280); font-size: 1.125rem; line-height: 1; }
        .ad-modal__close:hover { background: #F3F4F6; }
        .ad-modal__body { padding: 1.25rem; overflow-y: auto; display: grid; gap: 1rem; grid-template-columns: 1fr 1fr; }
        .ad-modal__body .ad-full { grid-column: 1 / -1; }
        .ad-modal__foot { display: flex; justify-content: flex-end; gap: .75rem; padding: 1rem 1.25rem; border-top: 1px solid var(--ts-border-light, #F3F4F6); }
        .ad-modal form { display: flex; flex-direction: column; min-height: 0; margin: 0; }
        .ad-error { font-size: .75rem; color: #DC2626; }
        .ad-hint { font-size: .75rem; color: var(--ts-text-muted, #6B7280); }
        .ad-check { display: flex; align-items: center; gap: .5rem; font-size: .8125rem; color: var(--ts-text-body, #374151); cursor: pointer; min-height: 40px; }
        .ad-check input { width: 18px; height: 18px; accent-color: var(--ts-primary, #ff6213); }
        .ad-confirm { padding: 1.5rem 1.25rem .5rem; text-align: center; font-size: .875rem; color: var(--ts-text-body, #374151); line-height: 1.5; }
        .ad-confirm strong { color: var(--ts-text-title, #111827); overflow-wrap: anywhere; }
        .ad-confirm__note { margin-top: .5rem; font-size: .75rem; color: var(--ts-text-muted, #6B7280); }
        body.ad-modal-open { overflow: hidden; }

        @media (max-width: 640px) {
            .ad-grid { grid-template-columns: 1fr; }
            .ad-modal { align-items: flex-end; padding: 0; }
            .ad-modal__card { max-width: none; border-radius: 14px 14px 0 0; max-height: 94dvh; }
            .ad-modal__body { grid-template-columns: 1fr; }
            .ad-modal__foot { flex-direction: column-reverse; }
            .ad-modal__foot .ts-btn { width: 100%; justify-content: center; height: 46px; }
            .ad-card__actions .ts-btn { flex: 1 1 auto; justify-content: center; }
        }
    </style>
@endpush

@section('content')
@php
    $editingId = old('_address_id');
    $reopen = $errors->any() && old('_address_form');
@endphp
<div class="ts-page" id="ts-page">

    <div class="ts-breadcrumb">
        <span>Portal de servicios</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m9 18 6-6-6-6"/>
        </svg>
        <span class="ts-breadcrumb__current">Direcciones</span>
    </div>

    <div class="ts-header-top">
        <div>
            <h1 class="ts-title">Mis direcciones</h1>
            <p class="ts-subtitle">Guarda los domicilios donde recibes productos o te atendemos. Se comparten con tu cuenta de la tienda.</p>
        </div>
        @if ($addresses->isNotEmpty())
            <button type="button" class="ts-btn ts-btn--primary" data-ad-open="create">+ Agregar dirección</button>
        @endif
    </div>

    @if ($addresses->isEmpty())
        <div class="ts-card ad-empty">
            <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 21s7-7.2 7-12a7 7 0 1 0-14 0c0 4.8 7 12 7 12z"/><circle cx="12" cy="9" r="2.5"/>
            </svg>
            <p>Aún no tienes direcciones guardadas.</p>
            <button type="button" class="ts-btn ts-btn--primary" data-ad-open="create">Agregar mi primera dirección</button>
        </div>
    @else
        <div class="ad-grid">
            @foreach ($addresses as $address)
                <article class="ad-card {{ $address->is_default ? 'ad-card--default' : '' }}" aria-label="{{ $address->label ?: 'Dirección' }}">
                    <div class="ad-card__head">
                        <h2 class="ad-card__label">{{ $address->label ?: 'Dirección' }}</h2>
                        @if ($address->is_default)
                            <span class="ad-badge">Predeterminada</span>
                        @endif
                    </div>
                    <div class="ad-card__body">
                        <strong>{{ $address->recipient_name }}</strong><br>
                        {{ $address->address_line1 }}<br>
                        @if ($address->address_line2){{ $address->address_line2 }}<br>@endif
                        {{ trim($address->city . ($address->state ? ', ' . $address->state : '')) }}@if ($address->postal_code), C.P. {{ $address->postal_code }}@endif<br>
                        Tel. {{ $address->phone }}
                        @if ($address->reference)
                            <div class="ad-card__ref">Referencia: {{ $address->reference }}</div>
                        @endif
                    </div>
                    <div class="ad-card__actions">
                        <button type="button" class="ts-btn ad-btn-sm" data-ad-open="edit"
                                data-action="{{ route('customer.addresses.update', $address) }}"
                                data-address="{{ json_encode([
                                    'id' => $address->id,
                                    'label' => $address->label,
                                    'recipient_name' => $address->recipient_name,
                                    'phone' => $address->phone,
                                    'postal_code' => $address->postal_code,
                                    'state' => $address->state,
                                    'city' => $address->city,
                                    'address_line1' => $address->address_line1,
                                    'address_line2' => $address->address_line2,
                                    'reference' => $address->reference,
                                    'is_default' => (bool) $address->is_default,
                                ], JSON_UNESCAPED_UNICODE) }}">Editar</button>

                        @unless ($address->is_default)
                            <form method="POST" action="{{ route('customer.addresses.default', $address) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="ts-btn ad-btn-sm">Marcar como predeterminada</button>
                            </form>
                        @endunless

                        <button type="button" class="ts-btn ts-btn--danger ad-btn-sm" data-ad-open="delete"
                                data-action="{{ route('customer.addresses.destroy', $address) }}"
                                data-name="{{ $address->label ?: $address->address_line1 }}"
                                data-default="{{ $address->is_default && $addresses->count() > 1 ? '1' : '0' }}">Eliminar</button>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>

{{-- Modal: agregar / editar --}}
<div class="ad-modal" id="ad-modal-form" role="dialog" aria-modal="true" aria-labelledby="ad-form-title" aria-hidden="true">
    <div class="ad-modal__card">
        <form method="POST" action="{{ route('customer.addresses.store') }}" id="ad-form" novalidate>
            @csrf
            <input type="hidden" name="_method" value="POST" id="ad-method" disabled>
            <input type="hidden" name="_address_form" value="1">
            <input type="hidden" name="_address_id" value="{{ $editingId }}" id="ad-id">

            <div class="ad-modal__head">
                <h2 class="ad-modal__title" id="ad-form-title">Agregar dirección</h2>
                <button type="button" class="ad-modal__close" data-ad-close aria-label="Cerrar">&times;</button>
            </div>

            <div class="ad-modal__body">
                <div class="ts-field ad-full">
                    <label class="ts-label" for="ad-label">Etiqueta (opcional)</label>
                    <input type="text" class="ts-input" id="ad-label" name="label" maxlength="100"
                           placeholder="Ej. Oficina, Planta, Casa" value="{{ old('label') }}">
                    @error('label') <span class="ad-error">{{ $message }}</span> @enderror
                </div>

                <div class="ts-field">
                    <label class="ts-label" for="ad-recipient">Nombre de quien recibe <span class="ts-label__req">*</span></label>
                    <input type="text" class="ts-input" id="ad-recipient" name="recipient_name" maxlength="150" required
                           autocomplete="name" value="{{ old('recipient_name') }}">
                    @error('recipient_name') <span class="ad-error">{{ $message }}</span> @enderror
                </div>

                <div class="ts-field">
                    <label class="ts-label" for="ad-phone">Teléfono <span class="ts-label__req">*</span></label>
                    <input type="tel" class="ts-input" id="ad-phone" name="phone" maxlength="30" required
                           autocomplete="tel" inputmode="tel" value="{{ old('phone') }}">
                    @error('phone') <span class="ad-error">{{ $message }}</span> @enderror
                </div>

                <div class="ts-field ad-full">
                    <label class="ts-label" for="ad-line1">Calle y número <span class="ts-label__req">*</span></label>
                    <input type="text" class="ts-input" id="ad-line1" name="address_line1" maxlength="255" required
                           autocomplete="address-line1" value="{{ old('address_line1') }}">
                    @error('address_line1') <span class="ad-error">{{ $message }}</span> @enderror
                </div>

                <div class="ts-field">
                    <label class="ts-label" for="ad-line2">Colonia (opcional)</label>
                    <input type="text" class="ts-input" id="ad-line2" name="address_line2" maxlength="255"
                           autocomplete="address-line2" value="{{ old('address_line2') }}">
                    @error('address_line2') <span class="ad-error">{{ $message }}</span> @enderror
                </div>

                <div class="ts-field">
                    <label class="ts-label" for="ad-cp">Código postal <span class="ts-label__req">*</span></label>
                    <input type="text" class="ts-input" id="ad-cp" name="postal_code" maxlength="5" inputmode="numeric"
                           pattern="\d{5}" required autocomplete="postal-code" value="{{ old('postal_code') }}">
                    @error('postal_code') <span class="ad-error">{{ $message }}</span> @enderror
                </div>

                <div class="ts-field">
                    <label class="ts-label" for="ad-state">Estado <span class="ts-label__req">*</span></label>
                    <div class="ts-field-select-wrap">
                        <select class="ts-select-field" id="ad-state" name="state" required>
                            <option value="">Selecciona un estado</option>
                            @foreach ($estados as $estado)
                                <option value="{{ $estado }}" {{ old('state') === $estado ? 'selected' : '' }}>{{ $estado }}</option>
                            @endforeach
                        </select>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </div>
                    @error('state') <span class="ad-error">{{ $message }}</span> @enderror
                </div>

                <div class="ts-field">
                    <label class="ts-label" for="ad-city">Ciudad <span class="ts-label__req">*</span></label>
                    <input type="text" class="ts-input" id="ad-city" name="city" maxlength="100" required
                           autocomplete="address-level2" value="{{ old('city') }}">
                    @error('city') <span class="ad-error">{{ $message }}</span> @enderror
                </div>

                <div class="ts-field ad-full">
                    <label class="ts-label" for="ad-reference">Referencia (opcional)</label>
                    <input type="text" class="ts-input" id="ad-reference" name="reference" maxlength="255"
                           placeholder="Entre calles, color de fachada, etc." value="{{ old('reference') }}">
                    @error('reference') <span class="ad-error">{{ $message }}</span> @enderror
                </div>

                <div class="ad-full">
                    <label class="ad-check" for="ad-default">
                        <input type="checkbox" id="ad-default" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}>
                        Establecer como predeterminada
                    </label>
                </div>
            </div>

            <div class="ad-modal__foot">
                <button type="button" class="ts-btn" data-ad-close>Cancelar</button>
                <button type="submit" class="ts-btn ts-btn--primary">Guardar dirección</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: confirmar eliminación --}}
<div class="ad-modal" id="ad-modal-delete" role="alertdialog" aria-modal="true" aria-labelledby="ad-del-title" aria-hidden="true">
    <div class="ad-modal__card ad-modal__card--sm">
        <form method="POST" action="#" id="ad-delete-form">
            @csrf
            @method('DELETE')
            <div class="ad-modal__head">
                <h2 class="ad-modal__title" id="ad-del-title">¿Eliminar esta dirección?</h2>
                <button type="button" class="ad-modal__close" data-ad-close aria-label="Cerrar">&times;</button>
            </div>
            <div class="ad-confirm">
                <strong id="ad-del-name"></strong> se eliminará de tu cuenta. Esta acción no se puede deshacer.
                <div class="ad-confirm__note" id="ad-del-note" hidden>Era tu dirección predeterminada: la más reciente de las restantes ocupará su lugar.</div>
            </div>
            <div class="ad-modal__foot">
                <button type="button" class="ts-btn" data-ad-close>Cancelar</button>
                <button type="submit" class="ts-btn ts-btn--danger">Eliminar</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var storeUrl = @json(route('customer.addresses.store'));
    var reopen = @json((bool) $reopen);
    var editingId = @json($editingId ? (int) $editingId : null);
    var editUrlTpl = @json(route('customer.addresses.update', ['address' => '__ID__']));

    var formModal = document.getElementById('ad-modal-form');
    var delModal = document.getElementById('ad-modal-delete');
    var form = document.getElementById('ad-form');
    var methodInput = document.getElementById('ad-method');
    var idInput = document.getElementById('ad-id');
    var title = document.getElementById('ad-form-title');
    var lastTrigger = null;
    var fields = ['label', 'recipient_name', 'phone', 'postal_code', 'state', 'city', 'address_line1', 'address_line2', 'reference'];

    function show(modal, focusSel) {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('ad-modal-open');
        var el = modal.querySelector(focusSel);
        if (el) setTimeout(function () { el.focus(); }, 30);
    }
    function hide(modal) {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        if (!document.querySelector('.ad-modal.is-open')) document.body.classList.remove('ad-modal-open');
        if (lastTrigger) { lastTrigger.focus(); lastTrigger = null; }
    }
    function clearErrors() {
        form.querySelectorAll('.ad-error').forEach(function (e) { e.remove(); });
    }
    function setValues(d) {
        fields.forEach(function (f) { form.elements[f].value = (d && d[f]) || ''; });
        form.elements['is_default'].checked = !!(d && d.is_default);
    }

    function openCreate() {
        clearErrors();
        form.action = storeUrl;
        methodInput.disabled = true;
        idInput.value = '';
        title.textContent = 'Agregar dirección';
        setValues(null);
        show(formModal, '#ad-label');
    }
    function openEdit(action, d) {
        clearErrors();
        form.action = action;
        methodInput.value = 'PUT';
        methodInput.disabled = false;
        idInput.value = d.id;
        title.textContent = 'Editar dirección';
        setValues(d);
        show(formModal, '#ad-recipient');
    }

    document.addEventListener('click', function (ev) {
        var t = ev.target.closest('[data-ad-open]');
        if (t) {
            lastTrigger = t;
            var kind = t.getAttribute('data-ad-open');
            if (kind === 'create') openCreate();
            else if (kind === 'edit') openEdit(t.getAttribute('data-action'), JSON.parse(t.getAttribute('data-address')));
            else if (kind === 'delete') {
                document.getElementById('ad-delete-form').action = t.getAttribute('data-action');
                document.getElementById('ad-del-name').textContent = t.getAttribute('data-name');
                document.getElementById('ad-del-note').hidden = t.getAttribute('data-default') !== '1';
                show(delModal, '.ts-btn');
            }
            return;
        }
        if (ev.target.closest('[data-ad-close]')) {
            hide(ev.target.closest('.ad-modal'));
            return;
        }
        if (ev.target.classList && ev.target.classList.contains('ad-modal')) hide(ev.target);
    });
    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape') document.querySelectorAll('.ad-modal.is-open').forEach(hide);
    });

    // Tras un error de validación, reabrir con lo que escribió (old input ya
    // está en los campos del servidor) y con la acción correcta.
    if (reopen) {
        if (editingId) {
            form.action = editUrlTpl.replace('__ID__', editingId);
            methodInput.value = 'PUT';
            methodInput.disabled = false;
            title.textContent = 'Editar dirección';
        }
        show(formModal, '#ad-recipient');
    }
})();
</script>
@endpush
