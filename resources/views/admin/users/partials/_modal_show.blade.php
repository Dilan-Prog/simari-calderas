<div class="show-user-overlay" id="showUserModal">
    <div class="show-user-panel">

        {{-- Header --}}
        <div class="show-user-header">
            <h2 class="show-user-header-title">Detalles del Usuario</h2>
            <button type="button" class="table-users-manager-action-btn cancel show-user-close-btn"
                id="closeShowModal">✕</button>
        </div>

        {{-- Body scrollable --}}
        <div class="show-user-body table-scroll">

            {{-- Avatar + nombre --}}
            <div class="show-user-hero">
                <div class="show-user-avatar" id="showAvatar">U</div>
                <p class="show-user-fullname" id="showFullName">—</p>
                <p class="show-user-email-hero" id="showEmailHero">—</p>
                <div class="show-user-badges">
                    <span class="users-manager-badge role-admin" id="showRoleBadge">—</span>
                    <span class="users-manager-badge status" id="showStatusBadge">—</span>
                </div>
            </div>

            {{-- Personal information --}}
            <div class="show-user-section">
                <h3 class="show-user-section-title">Información Personal</h3>
                <div class="show-user-divider"></div>
                <div class="show-user-grid">
                    <div class="show-user-field">
                        <span class="show-user-field-label">NOMBRE(S)</span>
                        <span class="show-user-field-value" id="showFirstName">—</span>
                    </div>
                    <div class="show-user-field">
                        <span class="show-user-field-label">APELLIDOS</span>
                        <span class="show-user-field-value" id="showLastName">—</span>
                    </div>
                    <div class="show-user-field">
                        <span class="show-user-field-label">FECHA DE NACIMIENTO</span>
                        <span class="show-user-field-value" id="showBirthdate">—</span>
                    </div>
                    <div class="show-user-field">
                        <span class="show-user-field-label">RFC</span>
                        <span class="show-user-field-value" id="showRfc">—</span>
                    </div>
                    <div class="show-user-field">
                        <span class="show-user-field-label">CURP</span>
                        <span class="show-user-field-value" id="showCurp">—</span>
                    </div>
                    <div class="show-user-field">
                        <span class="show-user-field-label">NÚMERO DE SEGURIDAD SOCIAL</span>
                        <span class="show-user-field-value" id="showSsn">—</span>
                    </div>
                </div>
            </div>

            {{-- Sección: Contacto --}}
            <div class="show-user-section">
                <h3 class="show-user-section-title">Contacto</h3>
                <div class="show-user-divider"></div>
                <div class="show-user-grid">
                    <div class="show-user-field show-user-field--full">
                        <span class="show-user-field-label">EMAIL</span>
                        <span class="show-user-field-value" id="showEmail">—</span>
                    </div>
                    <div class="show-user-field">
                        <span class="show-user-field-label">TELÉFONO</span>
                        <span class="show-user-field-value" id="showPhone">—</span>
                    </div>
                    <div class="show-user-field">
                        <span class="show-user-field-label">PUESTO / CARGO</span>
                        <span class="show-user-field-value" id="showPosition">—</span>
                    </div>
                </div>
            </div>

            {{-- Sección: Contacto de Emergencia --}}
            <div class="show-user-section">
                <h3 class="show-user-section-title">Contacto de Emergencia</h3>
                <div class="show-user-divider"></div>
                <div class="show-user-grid show-user-grid--3" id="showEmergencyContainer">
                    <div class="show-user-field">
                        <span class="show-user-field-label">NOMBRE DEL CONTACTO</span>
                        <span class="show-user-field-value" id="showEcName">—</span>
                    </div>
                    <div class="show-user-field">
                        <span class="show-user-field-label">TELÉFONO</span>
                        <span class="show-user-field-value" id="showEcPhone">—</span>
                    </div>
                    <div class="show-user-field">
                        <span class="show-user-field-label">PARENTESCO</span>
                        <span class="show-user-field-value" id="showEcRel">—</span>
                    </div>
                </div>
            </div>

            {{-- Sección: Cuenta --}}
            <div class="show-user-section">
                <h3 class="show-user-section-title">Cuenta</h3>
                <div class="show-user-divider"></div>
                <div class="show-user-grid">
                    <div class="show-user-field">
                        <span class="show-user-field-label">ROL ASIGNADO</span>
                        <span class="show-user-field-value" id="showRoleText">—</span>
                    </div>
                    <div class="show-user-field">
                        <span class="show-user-field-label">ESTADO</span>
                        <span id="showStatusText">—</span>
                    </div>
                    <div class="show-user-field">
                        <span class="show-user-field-label">FECHA DE CREACIÓN</span>
                        <span class="show-user-field-value" id="showCreatedAt">—</span>
                    </div>
                </div>
            </div>

        </div>{{-- fin body --}}

        {{-- Footer fijo --}}
        <div class="show-user-footer">
            <button type="button" class="button-secondary size-adjustment" id="cancelShowModal">Cerrar</button>
            <button type="button" class="button-primary size-adjustment" id="showEditBtn">Editar Usuario</button>
        </div>

    </div>
</div>
