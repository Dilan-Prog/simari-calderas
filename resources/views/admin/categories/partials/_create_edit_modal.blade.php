    {{-- Create/Edit Modal --}}
    <div id="categoryModal" class="user-manager-modal client-manage-modal">
        <div class="user-manager-modal-content client-modal-content">
            <div class="user-manager-modal-header">
                <h2 id="categoryModalTitle">Nueva Categoría</h2>
                <button type="button" class="table-users-manager-action-btn cancel" id="closeCategoryModal">✕</button>
            </div>

            <div id="category-modal-errors" class="user-manager-errors" style="display:none;"></div>

            <form class="user-manager-modal-body" id="categoryForm">
                @csrf
                <div class="user-manager-form">
                    <div>
                        <label class="supliers-manager-slider-label">Nivel de Categoría <span
                                style="color:red">*</span></label>
                        <select class="users-manager-select" id="categoryLevel" name="level">
                            <option value="1">Categoría Principal (Nivel 1)</option>
                            <option value="2">Subcategoría (Nivel 2)</option>
                            <option value="3">Categoría Hija (Nivel 3)</option>
                        </select>
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Categoría Padre</label>
                        <select class="users-manager-select" id="categoryParent" name="parent_id">
                            <option value="">Ninguna (Categoría Principal)</option>
                            @foreach ($categories as $cat)
                                @php
                                    $catSlug = $cat->slug;
                                    $parentSlug = $cat->parent ? $cat->parent->slug : '';
                                @endphp
                                <option value="{{ $cat->id }}" data-level="{{ $cat->level }}"
                                    data-parent-slug="{{ $parentSlug }}">
                                    {{ str_repeat('— ', $cat->level - 1) }}{{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
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
                    <textarea class="users-manager-input client-modal-textarea" name="description" id="categoryDescription" rows="3"
                        placeholder="Descripción de la categoría...">{{ old('description') }}</textarea>
                </div>

                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">URL de Imagen</label>
                    <input type="text" class="users-manager-input" name="image_url" id="categoryImageUrl"
                        placeholder="https://..." value="{{ old('image_url') }}">
                </div>

                <div class="user-manager-form">
                    <div>
                        <label class="supliers-manager-slider-label">Orden <span style="color:red">*</span></label>
                        <input type="number" class="users-manager-input" name="sort_order" id="categorySortOrder"
                            value="{{ old('sort_order', 1) }}" min="0">
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
                        <input type="text" class="users-manager-input" name="seo_title" id="categorySeoTitle"
                            placeholder="Título para buscadores" value="{{ old('seo_title') }}">
                    </div>
                </div>
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Meta Description</label>
                    <textarea class="users-manager-input client-modal-textarea" name="seo_description" id="categorySeoDesc" rows="3"
                        placeholder="Descripción para buscadores...">{{ old('seo_description') }}</textarea>
                </div>

                <div class="user-manager-modal-footer">
                    <button type="button" id="cancelCategoryModal"
                        class="button-secondary size-adjustment">Cancelar</button>
                    <button type="submit" class="button-primary size-adjustment" id="categorySubmitBtn">Crear
                        Categoría</button>
                </div>
            </form>
        </div>
    </div>
