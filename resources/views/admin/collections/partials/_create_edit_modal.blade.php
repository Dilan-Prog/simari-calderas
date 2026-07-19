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
                    <input type="text" class="users-manager-input" name="image_url"
                        id="collectionImageUrl" placeholder="https://..." value="{{ old('image_url') }}">
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

                <div class="user-manager-modal-footer">
                    <button type="button" id="cancelCollectionModal"
                        class="button-secondary size-adjustment">Cancelar</button>
                    <button type="submit" class="button-primary size-adjustment"
                        id="collectionSubmitBtn">Crear Colección</button>
                </div>
            </form>
        </div>
    </div>
