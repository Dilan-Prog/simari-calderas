    {{-- Create/Edit Modal --}}
    <div id="collectionModal" class="user-manager-modal client-manage-modal">
        <div class="user-manager-modal-content client-modal-content">
            <div class="user-manager-modal-header">
                <h2 id="collectionModalTitle">Nueva Colección</h2>
                <button type="button" class="table-users-manager-action-btn cancel"
                    id="closeCollectionModal">✕</button>
            </div>

            <div id="collection-modal-errors" class="user-manager-errors" style="display:none;"></div>

            <form class="user-manager-modal-body" id="collectionForm">
                @csrf
                <div class="user-manager-form">
                    <div>
                        <label class="supliers-manager-slider-label">Nombre <span style="color:red">*</span></label>
                        <input type="text" class="users-manager-input" name="name" id="collectionName"
                            placeholder="Ej: Calderas de Vapor" value="{{ old('name') }}">
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Slug (URL) <span style="color:red">*</span></label>
                        <input type="text" class="users-manager-input" name="slug" id="collectionSlug"
                            placeholder="coleccion-ejemplo" value="{{ old('slug') }}">
                    </div>
                </div>

                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Descripción</label>
                    <textarea class="users-manager-input client-modal-textarea" name="description"
                        id="collectionDescription" rows="3"
                        placeholder="Descripción de la colección...">{{ old('description') }}</textarea>
                </div>

                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">URL de Imagen</label>
                    <div class="img-picker-field">
                        <input type="text" class="users-manager-input" name="image_url"
                            id="collectionImageUrl" placeholder="https://..." value="{{ old('image_url') }}">
                        <button type="button" class="img-picker-trigger-btn" onclick="openImagePicker('collectionImageUrl')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                            Seleccionar
                        </button>
                    </div>
                </div>

                <div class="user-manager-form">
                    <div>
                        <label class="supliers-manager-slider-label">Tipo de Colección <span style="color:red">*</span></label>
                        <select class="users-manager-select" name="type" id="collectionType">
                            <option value="manual">Manual</option>
                            <option value="automatic">Automática</option>
                        </select>
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Orden <span style="color:red">*</span></label>
                        <input type="number" class="users-manager-input" name="sort_order"
                            id="collectionSortOrder" value="{{ old('sort_order', 0) }}" min="0">
                    </div>
                </div>

                <div class="user-manager-form">
                    <div>
                        <label class="supliers-manager-slider-label">Estado <span style="color:red">*</span></label>
                        <select class="users-manager-select" name="is_active" id="collectionIsActive">
                            <option value="1" {{ old('is_active', 1) == 1 ? 'selected' : '' }}>Activa</option>
                            <option value="0" {{ old('is_active', 1) == 0 ? 'selected' : '' }}>Inactiva</option>
                        </select>
                    </div>
                </div>

                {{-- Automatic-only fields --}}
                <div id="collectionAutomaticFields" data-type-fields="automatic" style="display:none;">
                    <h3 style="margin-top:16px;margin-bottom:4px;">Condiciones de Coincidencia</h3>
                    <div class="show-user-divider"></div>

                    <div class="users-manager-email-camp">
                        <label class="supliers-manager-slider-label">Coincidencia</label>
                        <select class="users-manager-select" name="match_type" id="collectionMatchType">
                            <option value="all">Todas las condiciones deben cumplirse</option>
                            <option value="any">Cualquiera de las condiciones</option>
                        </select>
                    </div>

                    <div id="collectionRuleRows" class="collection-rule-rows"></div>

                    <button type="button" id="btnAddRule" class="button-secondary size-adjustment"
                        style="margin-top:8px;">+ Agregar condición</button>

                    <template id="collectionRuleRowTemplate">
                        <div class="collection-rule-row">
                            <select class="users-manager-select rule-field" name="rule_field[]">
                                <option value="tag">Etiqueta</option>
                                <option value="category_id">Categoría</option>
                                <option value="brand_id">Marca</option>
                                <option value="price">Precio</option>
                            </select>
                            <select class="users-manager-select rule-operator" name="rule_operator[]">
                                <option value="equals">Es igual a</option>
                                <option value="greater_than" style="display:none;">Mayor que</option>
                                <option value="less_than" style="display:none;">Menor que</option>
                            </select>
                            <span class="rule-value-wrap">
                                <input type="text" class="users-manager-input rule-value rule-value-text"
                                    name="rule_value[]" placeholder="valor de la etiqueta">
                                <select class="users-manager-select rule-value rule-value-category" name="rule_value[]" style="display:none;" disabled>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                <select class="users-manager-select rule-value rule-value-brand" name="rule_value[]" style="display:none;" disabled>
                                    @foreach ($brands as $brand)
                                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                                <input type="number" class="users-manager-input rule-value rule-value-price"
                                    name="rule_value[]" placeholder="0.00" step="0.01" style="display:none;" disabled>
                            </span>
                            <button type="button" class="table-users-manager-action-btn delete btn-remove-rule">×</button>
                        </div>
                    </template>
                </div>

                {{-- SEO y FAQ de la página pública /coleccion/{slug} --}}
                <h3 style="margin-top:16px;margin-bottom:4px;">SEO y Preguntas Frecuentes</h3>
                <div class="show-user-divider"></div>
                <p class="hs-config-note" style="margin-bottom:12px;">
                    Estos datos alimentan la página pública de la colección
                    (<code>/coleccion/su-slug</code>): metaetiquetas para Google y preguntas
                    frecuentes que se muestran si la sección FAQ está activa en
                    Secciones del Sitio → Colecciones.
                </p>

                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Título SEO (máx. 160)</label>
                    <input type="text" class="users-manager-input" name="seo_title" id="collectionSeoTitle"
                        maxlength="160" placeholder="Ej: Calentadores Rinnai en oferta | Equiterm Industries">
                </div>

                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Descripción SEO (máx. 500)</label>
                    <textarea class="users-manager-input client-modal-textarea" name="seo_description" id="collectionSeoDescription"
                        rows="2" maxlength="500" placeholder="Descripción que aparece en los resultados de Google."></textarea>
                </div>

                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Imagen para redes (Open Graph, 1200x630 recomendado)</label>
                    <div class="img-picker-field">
                        <input type="text" class="users-manager-input" name="og_image_url" id="collectionOgImageUrl" placeholder="https://...">
                        <button type="button" class="img-picker-trigger-btn" onclick="openImagePicker('collectionOgImageUrl')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                            Seleccionar
                        </button>
                    </div>
                </div>

                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Preguntas frecuentes de la colección</label>
                    <div id="collectionFaqRows" class="hs-faq-items"></div>
                    <button type="button" class="button-secondary size-adjustment" id="btnAddCollectionFaq" style="margin-top:10px;">
                        + Agregar pregunta
                    </button>
                </div>

                <div class="user-manager-modal-footer">
                    <button type="button" id="cancelCollectionModal"
                        class="button-secondary size-adjustment">Cancelar</button>
                    <button type="submit" class="button-primary size-adjustment"
                        id="collectionSubmitBtn">Crear Colección</button>
                </div>
            </form>
        </div>
    </div>
