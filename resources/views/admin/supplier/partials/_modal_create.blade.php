<div id="supplierCreateModal" class="user-manager-modal client-manage-modal">
    <div class="user-manager-modal-content client-modal-content">
        <div class="user-manager-modal-header">
            <h2>Nuevo Proveedor</h2>
            <button type="button" class="table-users-manager-action-btn cancel" id="closeSupplierCreateModal">✕</button>
        </div>

        <form class="user-manager-modal-body" id="supplierCreateForm" action="{{ route('admin.suppliers.store') }}"
            method="POST">
            @csrf

            <h3>Información de la Empresa</h3>
            <div class="show-user-divider"></div>
            <div class="user-manager-form">
                <div>
                    <label class="supliers-manager-slider-label">Nombre de la Empresa *</label>
                    <input class="users-manager-input" type="text" name="company_name"
                        placeholder="Ej: Proveedor S.A. de C.V." value="{{ old('company_name') }}">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">RFC *</label>
                    <input class="users-manager-input" type="text" name="rfc" placeholder="XAXX010101000"
                        value="{{ old('rfc') }}">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Contacto Principal *</label>
                    <input class="users-manager-input" type="text" name="contact_name" placeholder="Ej: Juan Pérez"
                        value="{{ old('contact_name') }}">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Teléfono *</label>
                    <input class="users-manager-input" type="text" name="phone" placeholder="(449) 123-4567"
                        value="{{ old('phone') }}">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Email *</label>
                    <input class="users-manager-input" type="email" name="email" placeholder="contacto@empresa.mx"
                        value="{{ old('email') }}">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Sitio Web</label>
                    <input class="users-manager-input" type="text" name="website" placeholder="https://empresa.mx"
                        value="{{ old('website') }}">
                </div>
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Dirección</label>
                    <textarea class="users-manager-input client-modal-textarea" name="address"
                        placeholder="Calle, número, colonia, ciudad...">{{ old('address') }}</textarea>
                </div>
            </div>

            <h3>Condiciones Comerciales</h3>
            <div class="show-user-divider"></div>
            <div class="user-manager-form">
                <div>
                    <label class="supliers-manager-slider-label">Condiciones de Pago *</label>
                    <select class="users-manager-select" name="payment_terms">
                        <option value="">Seleccionar...</option>
                        <option value="contado" {{ old('payment_terms') == 'contado' ? 'selected' : '' }}>Contado
                        </option>
                        <option value="15 días" {{ old('payment_terms') == '15 días' ? 'selected' : '' }}>15 días
                        </option>
                        <option value="30 días" {{ old('payment_terms') == '30 días' ? 'selected' : '' }}>30 días
                        </option>
                        <option value="45 días" {{ old('payment_terms') == '45 días' ? 'selected' : '' }}>45 días
                        </option>
                        <option value="60 días" {{ old('payment_terms') == '60 días' ? 'selected' : '' }}>60 días
                        </option>
                    </select>
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Estado *</label>
                    <select class="users-manager-select" name="status">
                        <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Activo
                        </option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>
                            Inactivo</option>
                        <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>
                            Suspendido</option>
                    </select>
                </div>
            </div>

            <h3>Evaluación</h3>
            <div class="show-user-divider"></div>
            <div class="user-manager-form">
                <div>
                    <label class="supliers-manager-slider-label">Rating de Calidad (1-5)</label>
                    <input class="users-manager-input" type="number" name="rating_quality" min="0"
                        max="5" value="{{ old('rating_quality', 0) }}">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Rating de Cumplimiento (1-5)</label>
                    <input class="users-manager-input" type="number" name="rating_compliance" min="0"
                        max="5" value="{{ old('rating_compliance', 0) }}">
                </div>
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Notas de Evaluación</label>
                    <textarea class="users-manager-input client-modal-textarea" name="evaluation_notes"
                        placeholder="Comentarios sobre la evaluación del proveedor...">{{ old('evaluation_notes') }}</textarea>
                </div>
            </div>

            <h3>Notas Internas</h3>
            <div class="show-user-divider"></div>
            <div class="client-modal-address-section">
                <div>
                    <textarea class="users-manager-input client-modal-textarea" name="notes"
                        placeholder="Notas privadas sobre el proveedor...">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="user-manager-modal-footer">
                <button type="button" id="cancelSupplierCreateModal"
                    class="button-secondary size-adjustment">Cancelar</button>
                <button type="submit" class="button-primary size-adjustment">Guardar Proveedor</button>
            </div>
        </form>
    </div>
</div>
