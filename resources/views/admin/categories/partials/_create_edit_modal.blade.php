    {{-- Create/Edit Modal --}}
    <div id="categoryModal" class="user-manager-modal client-manage-modal">
        <div class="user-manager-modal-content client-modal-content">
            <div class="user-manager-modal-header">
                <h2 id="categoryModalTitle">Nueva Categoría</h2>
                <button type="button" class="table-users-manager-action-btn cancel"
                    id="closeCategoryModal">✕</button>
            </div>

            <div id="category-modal-errors" class="user-manager-errors" style="display:none;"></div>

            <form class="user-manager-modal-body" id="categoryForm">
                @csrf
                <div class="user-manager-form">
                    <div>
                        <label class="supliers-manager-slider-label">Nivel de Categoría <span style="color:red">*</span></label>
                        <select class="users-manager-select" id="categoryLevel" name="level">
                            <option value="1">Categoría Principal (Nivel 1)</option>
                            <option value="2">Subcategoría (Nivel 2)</option>
                            <option value="3">Categoría Hija (Nivel 3)</option>
                        </select>
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Categoría Padre</label>
                        <div class="cat-combobox" id="categoryParentCombobox">
                            <input type="text" class="users-manager-input cat-combobox-input"
                                id="categoryParentSearch" placeholder="Buscar categoría..." autocomplete="off">
                            <select class="cat-combobox-native-select" id="categoryParent" name="parent_id">
                                <option value="">Ninguna (Categoría Principal)</option>
                                @foreach ($allCategories as $cat)
                                    <option value="{{ $cat->id }}" data-level="{{ $cat->level }}">
                                        {{ str_repeat('— ', $cat->level - 1) }}{{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                            <ul class="cat-combobox-list" id="categoryParentList"></ul>
                        </div>
                    </div>
                </div>

                <div class="user-manager-form">
                    <div>
                        <label class="supliers-manager-slider-label">Nombre <span style="color:red">*</span></label>
                        <input type="text" class="users-manager-input" name="name" id="categoryName"
                            placeholder="Ej: Calderas Industriales" value="{{ old('name') }}">
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Slug (URL) <span style="color:red">*</span></label>
                        <input type="text" class="users-manager-input" name="slug" id="categorySlug"
                            placeholder="categoria-ejemplo" value="{{ old('slug') }}">
                    </div>
                </div>

                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Descripción</label>
                    <textarea class="users-manager-input client-modal-textarea" name="description"
                        id="categoryDescription" rows="3"
                        placeholder="Descripción de la categoría...">{{ old('description') }}</textarea>
                </div>

                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">URL de Imagen</label>
                    <div class="img-picker-field">
                        <input type="text" class="users-manager-input" name="image_url"
                            id="categoryImageUrl" placeholder="https://..." value="{{ old('image_url') }}">
                        <button type="button" class="img-picker-trigger-btn" onclick="openImagePicker('categoryImageUrl')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                            Seleccionar
                        </button>
                    </div>
                </div>

                <div class="user-manager-form">
                    <div>
                        <label class="supliers-manager-slider-label">Orden <span style="color:red">*</span></label>
                        <input type="number" class="users-manager-input" name="sort_order"
                            id="categorySortOrder" value="{{ old('sort_order', 1) }}" min="0">
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Estado <span style="color:red">*</span></label>
                        <select class="users-manager-select" name="is_active" id="categoryIsActive">
                            <option value="1" {{ old('is_active', 1) == 1 ? 'selected' : '' }}>Activa</option>
                            <option value="0" {{ old('is_active', 1) == 0 ? 'selected' : '' }}>Inactiva</option>
                        </select>
                    </div>
                </div>

                <h3 style="margin-top:16px;margin-bottom:4px;">SEO</h3>
                <div class="show-user-divider"></div>

                <div class="user-manager-form">
                    <div>
                        <label class="supliers-manager-slider-label">Título SEO</label>
                        <input type="text" class="users-manager-input" name="seo_title"
                            id="categorySeoTitle" placeholder="Título para buscadores" value="{{ old('seo_title') }}">
                    </div>
                </div>
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Meta Description</label>
                    <textarea class="users-manager-input client-modal-textarea" name="seo_description"
                        id="categorySeoDesc" rows="3"
                        placeholder="Descripción para buscadores...">{{ old('seo_description') }}</textarea>
                </div>

                <h3 style="margin-top:16px;margin-bottom:4px;">Preguntas Frecuentes (FAQ)</h3>
                <div class="show-user-divider"></div>
                <p class="breadcrumb-clients-manager" style="margin:4px 0 8px;">
                    Aparecen en la página de esta categoría en el catálogo y como datos estructurados para Google.
                </p>
                <div id="categoryFaqEmpty" style="padding:12px;background:#f8fafc;border-radius:8px;color:#6b7280;font-size:13px;">
                    Esta categoría aún no tiene preguntas frecuentes.
                </div>
                <div id="categoryFaqList" style="display:none;flex-direction:column;gap:10px;"></div>
                <button type="button" class="button-secondary size-adjustment" id="categoryAddFaq" style="margin-top:8px;">
                    + Agregar pregunta
                </button>

                <div class="user-manager-modal-footer">
                    <button type="button" id="cancelCategoryModal"
                        class="button-secondary size-adjustment">Cancelar</button>
                    <button type="submit" class="button-primary size-adjustment"
                        id="categorySubmitBtn">Crear Categoría</button>
                </div>
            </form>
        </div>
    </div>
