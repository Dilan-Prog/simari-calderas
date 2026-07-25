<aside class="portal-sidebar">
    <div class="portal-sidebar__header">
        <div class="portal-sidebar__avatar">{{ strtoupper(mb_substr($customer->first_name, 0, 1) . mb_substr($customer->last_name, 0, 1)) }}</div>
        <div class="portal-sidebar__identity">
            <div class="portal-sidebar__name">{{ $customer->first_name }} {{ $customer->last_name }}</div>
            <div class="portal-sidebar__email">{{ $customer->email }}</div>
        </div>
    </div>

    <nav class="portal-sidebar__nav">
        <a href="#" :class="{ 'is-active': section === 'perfil' }" @click.prevent="go('perfil')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.5-6 8-6s8 2 8 6" stroke-linecap="round"/></svg>
            Mi perfil
        </a>
        <a href="#pedidos" :class="{ 'is-active': section === 'pedidos' }" @click.prevent="go('pedidos')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="17" rx="1.5"/><path d="M8 9h8M8 13h8M8 17h5" stroke-linecap="round"/></svg>
            Mis pedidos
        </a>
        <a href="#direcciones" :class="{ 'is-active': section === 'direcciones' }" @click.prevent="go('direcciones')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s7-7.2 7-12a7 7 0 1 0-14 0c0 4.8 7 12 7 12z"/><circle cx="12" cy="9" r="2.5"/></svg>
            Direcciones
        </a>
        <a href="#pagos" :class="{ 'is-active': section === 'pagos' }" @click.prevent="go('pagos')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18" stroke-linecap="round"/></svg>
            Métodos de pago
        </a>
        <a href="#favoritos" :class="{ 'is-active': section === 'favoritos' }" @click.prevent="go('favoritos')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20s-8-4.5-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 9c0 6.5-8 11-8 11z" stroke-linejoin="round"/></svg>
            Favoritos
        </a>

        @if ($customer->portal_access)
            <a href="{{ route('customer.dashboard') }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a4.5 4.5 0 0 0-6.4 6.4l-5 5V21h3.3l5-5a4.5 4.5 0 0 0 6.4-6.4l-2.8 2.8-2.3-2.3 2.8-2.8z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Portal de servicios
            </a>
        @elseif (!$portalRequestCompleted)
            <a href="#solicitar-portal" :class="{ 'is-active': section === 'solicitar-portal' }" @click.prevent="go('solicitar-portal')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a4.5 4.5 0 0 0-6.4 6.4l-5 5V21h3.3l5-5a4.5 4.5 0 0 0 6.4-6.4l-2.8 2.8-2.3-2.3 2.8-2.8z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Solicitar acceso al portal
            </a>
        @else
            <span class="portal-sidebar__pending">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3" stroke-linecap="round"/></svg>
                Solicitud de portal en revisión
            </span>
        @endif
    </nav>

    <div class="portal-sidebar__footer">
        <button type="button" @click="openModal('logout')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Cerrar sesión
        </button>
    </div>
</aside>
