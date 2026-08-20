{{-- Crear/editar regla de envío — un solo modal reutilizado para ambos
     modos (el JS de _scripts.blade.php rellena los campos si es edición),
     mismo shell .del-confirm-overlay/.del-confirm-box que el resto del
     admin (ver resources/css/admin/components/ui-kit.css), mismo criterio
     que payment-methods/partials/_modal_form.blade.php. --}}
<div id="shippingRuleModal" class="del-confirm-overlay">
    <div class="del-confirm-box ap-modal-box">
        <div class="ap-modal-header">
            <div class="ap-modal-icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2" />
                    <path d="M15 18H9" />
                    <path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14" />
                    <circle cx="17" cy="18" r="2" />
                    <circle cx="7" cy="18" r="2" />
                </svg>
            </div>
            <div class="ap-modal-header-text">
                <h2 class="del-confirm-title" id="shippingRuleModalTitle">Nueva Regla de Envío</h2>
                <p class="del-confirm-desc">Define el costo de envío y el umbral de envío gratis.</p>
            </div>
            <button type="button" class="table-users-manager-action-btn cancel" id="closeShippingRuleModal">✕</button>
        </div>

        <div id="shipping-rule-modal-errors" class="ap-modal-errors" style="display:none;"></div>

        <form class="ap-modal-body" id="shippingRuleForm">
            @csrf

            {{-- Sección 1 — Aplica a --}}
            <div>
                <h3 class="pm-section-title" style="font-size:14px; font-weight:600; color:#111827; margin:0 0 12px;">
                    Aplica a <span style="color:red">*</span>
                </h3>
                <input type="hidden" name="scope_type" id="srScopeType" value="brand">
                <div class="sr-scope-grid">
                    <button type="button" class="sr-scope-card active" data-scope="brand">
                        <span class="sr-scope-card-name">Marca</span>
                        <span class="sr-scope-card-hint">Aplica a todos los productos de una marca</span>
                    </button>
                    <button type="button" class="sr-scope-card" data-scope="category">
                        <span class="sr-scope-card-name">Categoría</span>
                        <span class="sr-scope-card-hint">Aplica a todos los productos de una categoría</span>
                    </button>
                </div>
            </div>

            {{-- Select de marca --}}
            <div class="sr-scope-fields ap-field-group" data-scope="brand">
                <label class="supliers-manager-slider-label">Marca <span style="color:red">*</span></label>
                <select class="users-manager-select" name="brand_id" id="srBrandId">
                    <option value="">Selecciona una marca...</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}"
                            data-used="{{ $usedBrandIds->contains($brand->id) ? '1' : '0' }}">
                            {{ $brand->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Select de categoría --}}
            <div class="sr-scope-fields ap-field-group" data-scope="category">
                <label class="supliers-manager-slider-label">Categoría <span style="color:red">*</span></label>
                <select class="users-manager-select" name="category_id" id="srCategoryId">
                    <option value="">Selecciona una categoría...</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}"
                            data-used="{{ $usedCategoryIds->contains($category->id) ? '1' : '0' }}">
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Sección 2 — Costos --}}
            <div class="user-manager-form user-manager-form-3 ap-field-grid">
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">Costo de envío (MXN)</label>
                    <input type="number" class="users-manager-input" name="shipping_cost" id="srShippingCost" min="0"
                        step="0.01" placeholder="Sin cargo especial">
                </div>
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">Envío gratis desde (MXN)</label>
                    <input type="number" class="users-manager-input" name="free_shipping_threshold"
                        id="srFreeShippingThreshold" min="0" step="0.01" placeholder="Sin umbral">
                </div>
            </div>
            <p class="ap-readonly-note">
                El umbral se evalúa contra el subtotal (sin IVA) de solo los productos del carrito cubiertos por esta
                regla.
            </p>

            <label class="ap-primary-toggle" for="srIsActive">
                <input type="checkbox" id="srIsActive" name="is_active" checked>
                <div>
                    <strong>Activa</strong>
                    <span>Las reglas inactivas no se aplican al calcular el envío.</span>
                </div>
            </label>

            <div class="ap-modal-footer">
                <button type="button" id="cancelShippingRuleModal"
                    class="button-secondary size-adjustment">Cancelar</button>
                <button type="submit" class="button-primary size-adjustment" id="srSubmitBtn">Crear Regla</button>
            </div>
        </form>
    </div>
</div>
