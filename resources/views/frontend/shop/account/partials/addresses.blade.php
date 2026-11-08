<section x-show="section === 'direcciones'" x-cloak>
    <div class="portal-title-row">
        <h1 class="portal-title">Direcciones</h1>
        <button type="button" class="portal-btn"
            @click="openModal('address-form', {
                title: 'Agregar dirección',
                actionUrl: '{{ route('shop.addresses.store') }}',
                method: 'POST',
                label: '', recipient_name: '', phone: '', postal_code: '', state: '', city: '', address_line2: '', address_line1: '', is_default: false,
            })">
            + Agregar dirección
        </button>
    </div>

    {{-- Aviso tras guardar/editar/eliminar desde el modal (flash 'address_saved'): se queda en esta pestaña, se
         cierra solo y la dirección afectada se resalta en la lista. CSS en línea (as-). --}}
    @php
        $saved = session('address_saved');
        $savedTitle = ['created' => 'Dirección agregada', 'updated' => 'Cambios guardados', 'deleted' => 'Dirección eliminada'][$saved['action'] ?? ''] ?? null;
    @endphp
    @if ($saved && $savedTitle)
        <div class="as-toast as-toast--{{ $saved['action'] }}" role="status" aria-live="polite"
            x-data="{ open: true }" x-show="open" x-init="setTimeout(() => open = false, 8000)" x-transition.opacity.duration.250ms>
            <span class="as-toast__ic" aria-hidden="true">
                @if ($saved['action'] === 'deleted')
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"/></svg>
                @else
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                @endif
            </span>
            <span class="as-toast__body">
                <strong>{{ $savedTitle }}</strong>
                <small>
                    @if ($saved['action'] === 'deleted')
                        {{ $saved['label'] }} ya no está en tu cuenta.
                    @else
                        {{ $saved['label'] }} · {{ $saved['summary'] }}@if ($saved['is_default']) · Es tu dirección predeterminada @endif
                    @endif
                </small>
            </span>
            <button type="button" class="as-toast__x" @click="open = false" aria-label="Cerrar aviso">&times;</button>
        </div>
    @endif

    <div class="address-grid">
        @forelse ($customer->customer_addresses as $address)
            <div class="address-card {{ ($saved['id'] ?? null) === $address->id ? 'is-flash' : '' }}"
                @if (($saved['id'] ?? null) === $address->id) x-init="setTimeout(() => $el.scrollIntoView({ behavior: 'smooth', block: 'center' }), 250)" @endif>
                @if ($address->is_default)
                    <span class="address-card__badge">Predeterminada</span>
                @endif
                <div class="address-card__label">{{ $address->label ?: 'Dirección' }}</div>
                <div class="address-card__lines">
                    {{ $address->recipient_name }}<br>
                    {{ $address->address_line1 }}<br>
                    @if ($address->address_line2){{ $address->address_line2 }}<br>@endif
                    {{ trim($address->city . ($address->state ? ', ' . $address->state : '')) }}<br>
                    @if ($address->postal_code)CP {{ $address->postal_code }}<br>@endif
                    {{ $address->phone }}
                </div>
                <div class="address-card__actions">
                    <a href="#" class="auth-link" @click.prevent="openModal('address-form', @js([
                        'title' => 'Editar dirección',
                        'actionUrl' => route('shop.addresses.update', $address),
                        'method' => 'PUT',
                        'label' => $address->label,
                        'recipient_name' => $address->recipient_name,
                        'phone' => $address->phone,
                        'postal_code' => $address->postal_code,
                        'state' => $address->state,
                        'city' => $address->city,
                        'address_line2' => $address->address_line2,
                        'address_line1' => $address->address_line1,
                        'is_default' => (bool) $address->is_default,
                    ]))">Editar</a>
                    <a href="#" class="auth-link auth-link--danger" @click.prevent="openModal('address-delete', @js([
                        'label' => $address->label ?: 'Esta dirección',
                        'actionUrl' => route('shop.addresses.destroy', $address),
                    ]))">Eliminar</a>
                </div>
            </div>
        @empty
            <div class="portal-empty">
                <p>Aún no tienes direcciones guardadas.</p>
            </div>
        @endforelse
    </div>
</section>

<style>
    .eq-shop-account .as-toast { display: flex; align-items: center; gap: 12px; margin: 0 0 18px; padding: 12px 14px; background: #fff; border: 1px solid rgba(23, 138, 60, .28); border-left: 4px solid #178a3c; border-radius: 10px; box-shadow: 0 6px 20px rgba(20, 21, 22, .08); }
    .eq-shop-account .as-toast--deleted { border-color: rgba(0, 0, 0, .12); border-left-color: #6b7280; }
    .eq-shop-account .as-toast__ic { flex: 0 0 32px; width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #fff; background: #178a3c; }
    .eq-shop-account .as-toast--deleted .as-toast__ic { background: #6b7280; }
    .eq-shop-account .as-toast__body { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 1px; }
    .eq-shop-account .as-toast__body strong { font-size: 14px; color: #141516; }
    .eq-shop-account .as-toast__body small { font-size: 12.5px; color: #4b5563; line-height: 1.4; overflow-wrap: anywhere; }
    .eq-shop-account .as-toast__x { flex-shrink: 0; width: 28px; height: 28px; border: 0; border-radius: 6px; background: transparent; color: #6b7280; font-size: 20px; line-height: 1; cursor: pointer; }
    .eq-shop-account .as-toast__x:hover { background: #f3f4f6; color: #141516; }
    .eq-shop-account .address-card.is-flash { animation: as-flash 3.2s ease-out 1; }
    @keyframes as-flash {
        0%, 55% { box-shadow: 0 0 0 3px rgba(255, 98, 19, .55); border-color: #ff6213; background: #fff8f3; }
        100% { box-shadow: 0 0 0 0 rgba(255, 98, 19, 0); }
    }
    @media (prefers-reduced-motion: reduce) { .eq-shop-account .address-card.is-flash { animation: none; border-color: #ff6213; } }
</style>
