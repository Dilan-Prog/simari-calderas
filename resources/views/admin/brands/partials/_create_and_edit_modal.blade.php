        {{-- Create/Edit Modal --}}
        <div id="brandModal" class="user-manager-modal client-manage-modal">
            <div class="user-manager-modal-content client-modal-content">
                <div class="user-manager-modal-header">
                    <h2 id="brandModalTitle">Nueva Marca</h2>
                    <button type="button" class="table-users-manager-action-btn cancel" id="closeBrandModal">✕</button>
                </div>

                <div id="brand-modal-errors" class="user-manager-errors" style="display:none;"></div>

                <form class="user-manager-modal-body" id="brandForm">
                    @csrf

                    <div class="user-manager-form">
                        <div>
                            <label class="supliers-manager-slider-label">
                                Nombre <span style="color:red">*</span>
                            </label>
                            <input type="text" class="users-manager-input" name="name" id="brandName"
                                placeholder="Ej: SIMARI">
                        </div>
                        <div>
                            <label class="supliers-manager-slider-label">
                                Slug <span style="color:red">*</span>
                            </label>
                            <input type="text" class="users-manager-input" name="slug" id="brandSlug"
                                placeholder="simari">
                        </div>
                    </div>

                    <div class="users-manager-email-camp" style="margin-top:12px;">
                        <label class="supliers-manager-slider-label">Descripción</label>
                        <textarea class="users-manager-input client-modal-textarea" name="description" id="brandDescription" rows="3"
                            placeholder="Descripción de la marca..."></textarea>
                    </div>

                    <div class="users-manager-email-camp" style="margin-top:12px;">
                        <label class="supliers-manager-slider-label">URL del Logo</label>
                        <div class="img-picker-field">
                            <input type="text" class="users-manager-input" name="logo_url" id="brandLogoUrl"
                                placeholder="https://...">
                            <button type="button" class="img-picker-trigger-btn" onclick="openImagePicker('brandLogoUrl')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                Seleccionar
                            </button>
                        </div>
                        {{-- Medida sacada del CSS real: el recuadro del logo mide 80 px de alto con 12 px de
                             relleno (carrusel de marcas) o 88 px con 16 px (bloque de marcas de Servicios),
                             así que el logo se ve a ~56 px de alto y ~110-180 px de ancho, con object-fit:
                             contain (se reduce sin recortarse). 360×120 px es 2x de ese espacio. --}}
                        <p style="margin:4px 0 0;font-size:12px;color:#9ca3af;">
                            <strong style="color:#6b7280;">Medida recomendada: 360 × 120 px</strong> (horizontal, proporción 3:1),
                            PNG con fondo transparente o SVG, de máximo ~100 KB. En el sitio se muestra a unos 56 px de alto
                            dentro de un recuadro, sin recortarse: deja un margen pequeño alrededor y evita logos muy cuadrados o muy largos.
                        </p>
                    </div>

                    <div class="users-manager-email-camp" style="margin-top:12px;">
                        <label class="supliers-manager-slider-label" for="brandLogoAlt">ALT del logotipo</label>
                        <div class="img-picker-field">
                            <input type="text" class="users-manager-input" name="logo_alt" id="brandLogoAlt"
                                maxlength="255" placeholder="Ej. Logotipo de Honeywell">
                            <button type="button" class="img-picker-trigger-btn" id="brandLogoAltUseName">
                                Usar nombre de la marca
                            </button>
                        </div>
                        <p style="margin:4px 0 0;font-size:12px;color:#9ca3af;">
                            Texto alternativo del logo para accesibilidad y SEO. Si lo dejas vacío, el sitio usa el nombre de la marca.
                            <span id="brandLogoAltCount">0</span>/255
                        </p>
                    </div>

                    <div class="users-manager-email-camp" style="margin-top:12px;">
                        <label class="supliers-manager-slider-label">URL de redirección (opcional)</label>
                        <div class="img-picker-field">
                            <input type="text" class="users-manager-input" name="redirect_url" id="brandRedirectUrl"
                                maxlength="500" placeholder="https://... o /catalogo/mi-marca">
                            <button type="button" class="img-picker-trigger-btn" id="brandRedirectPick">
                                Elegir destino
                            </button>
                        </div>
                        <p style="margin:4px 0 0;font-size:12px;color:#9ca3af;">
                            A dónde lleva el logo de la marca en el carrusel de marcas. Si lo dejas vacío, el logo no es un enlace.
                            Puedes pegar cualquier URL o elegir una página del sitio (producto, colección, categoría, servicio…).
                        </p>
                    </div>

                    <div class="user-manager-form" style="margin-top:12px;">
                        <div>
                            <label class="supliers-manager-slider-label">
                                Estado <span style="color:red">*</span>
                            </label>
                            <select class="users-manager-select" name="is_active" id="brandIsActive">
                                <option value="1">Activa</option>
                                <option value="0">Inactiva</option>
                            </select>
                        </div>
                    </div>

                    <div class="user-manager-modal-footer">
                        <button type="button" id="cancelBrandModal"
                            class="button-secondary size-adjustment">Cancelar</button>
                        <button type="submit" class="button-primary size-adjustment" id="brandSubmitBtn">Crear
                            Marca</button>
                    </div>
                </form>
            </div>
        </div>
