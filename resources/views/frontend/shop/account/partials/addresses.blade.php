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

    <div class="address-grid">
        @forelse ($customer->customer_addresses as $address)
            <div class="address-card">
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
