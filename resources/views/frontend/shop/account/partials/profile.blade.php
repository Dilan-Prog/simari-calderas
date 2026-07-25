<section x-show="section === 'perfil'" x-cloak>
    <h1 class="portal-title">Mi perfil</h1>

    @if (!$customer->portal_access && !$portalRequestCompleted)
        <div class="portal-banner">
            <div>
                <strong>Portal de servicios técnicos</strong>
                <p>Solicita acceso para consultar tus reportes de servicio y servicios técnicos en línea.</p>
            </div>
            <button type="button" class="portal-btn" @click="go('solicitar-portal')">Solicitar acceso</button>
        </div>
    @elseif (!$customer->portal_access && $portalRequestCompleted)
        <div class="portal-banner portal-banner--pending">
            <div>
                <strong>Solicitud de portal en revisión</strong>
                <p>Gracias por la información, la estamos analizando. Te avisaremos cuando tu acceso esté activo.</p>
            </div>
        </div>
    @endif

    <div class="portal-card">
        <div class="portal-card__title">Datos personales</div>
        <form @submit.prevent="openModal('profile-save')">
            <div class="portal-grid-2">
                <div class="portal-field">
                    <label>Nombre</label>
                    <input type="text" value="{{ $customer->first_name }}">
                </div>
                <div class="portal-field">
                    <label>Apellido</label>
                    <input type="text" value="{{ $customer->last_name }}">
                </div>
                <div class="portal-field">
                    <label>Correo electrónico</label>
                    <input type="email" value="{{ $customer->email }}">
                </div>
                <div class="portal-field">
                    <label>Teléfono</label>
                    <input type="tel" value="{{ $customer->phone }}">
                </div>
                <div class="portal-field portal-field--full">
                    <label>Empresa</label>
                    <input type="text" value="{{ $customer->company }}">
                </div>
            </div>
            <div class="portal-card__actions">
                <button type="submit" class="portal-btn">Guardar cambios</button>
                <span class="portal-saved" x-show="profileSaved" x-cloak>✓ Cambios guardados</span>
            </div>
        </form>
    </div>

    <div class="portal-card">
        <div class="portal-card__title">Cambiar contraseña</div>
        <form @submit.prevent="openModal('password-save')">
            <div class="portal-grid-2 portal-grid-2--narrow">
                <div class="portal-field portal-field--full">
                    <input type="password" placeholder="Contraseña actual" autocomplete="current-password">
                </div>
                <div class="portal-field">
                    <input type="password" placeholder="Nueva contraseña" autocomplete="new-password">
                </div>
                <div class="portal-field">
                    <input type="password" placeholder="Confirmar contraseña" autocomplete="new-password">
                </div>
            </div>
            <div class="portal-card__actions">
                <button type="submit" class="portal-btn portal-btn--outline">Actualizar contraseña</button>
                <span class="portal-saved" x-show="passwordSaved" x-cloak>✓ Contraseña actualizada</span>
            </div>
        </form>
    </div>
</section>
