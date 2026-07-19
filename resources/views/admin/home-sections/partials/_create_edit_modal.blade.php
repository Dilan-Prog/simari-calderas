    {{-- Create/Edit Modal --}}
    <div id="homeSectionModal" class="user-manager-modal client-manage-modal">
        <div class="user-manager-modal-content client-modal-content">
            <div class="user-manager-modal-header">
                <h2 id="homeSectionModalTitle">Nueva Sección</h2>
                <button type="button" class="table-users-manager-action-btn cancel"
                    id="closeHomeSectionModal">✕</button>
            </div>

            <div id="home-section-modal-errors" class="user-manager-errors" style="display:none;"></div>

            <form class="user-manager-modal-body" id="homeSectionForm">
                @csrf
                <div class="user-manager-form">
                    <div>
                        <label class="supliers-manager-slider-label">Tipo de Sección <span style="color:red">*</span></label>
                        <select class="users-manager-select" id="hsType" name="type">
                            <option value="hero_slider">Slider Principal</option>
                            <option value="banner">Banner</option>
                            <option value="dual_banner">Banner Doble</option>
                            <option value="product_carousel">Carrusel de Productos</option>
                            <option value="category_grid">Grid de Categorías</option>
                            <option value="brand_carousel">Carrusel de Marcas</option>
                            <option value="html_block">Bloque HTML</option>
                        </select>
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Título (opcional)</label>
                        <input type="text" class="users-manager-input" name="title" id="hsTitle"
                            placeholder="Ej: Productos Destacados">
                    </div>
                </div>

                <div class="user-manager-form">
                    <div>
                        <label class="supliers-manager-slider-label">Orden</label>
                        <input type="number" class="users-manager-input" name="sort_order" id="hsSortOrder" value="0" min="0">
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Estado</label>
                        <select class="users-manager-select" name="is_active" id="hsIsActive">
                            <option value="1" selected>Activa</option>
                            <option value="0">Inactiva</option>
                        </select>
                    </div>
                </div>

                <h3 style="margin-top:16px;margin-bottom:4px;">Configuración</h3>
                <div class="show-user-divider"></div>

                {{-- hero_slider --}}
                <div class="config-fields" data-type="hero_slider">
                    <p class="hs-config-note">
                        Los slides de este slider se administran en una pantalla dedicada, disponible
                        después de crear la sección (botón <strong>“Gestionar slides”</strong> en la tabla).
                    </p>
                </div>

                {{-- banner --}}
                <div class="config-fields" data-type="banner">
                    <div class="users-manager-email-camp">
                        <label class="supliers-manager-slider-label">URL de Imagen</label>
                        <input type="text" class="users-manager-input" name="banner_image_url" id="hsBannerImageUrl" placeholder="https://...">
                    </div>
                    <div class="user-manager-form">
                        <div>
                            <label class="supliers-manager-slider-label">URL de Enlace</label>
                            <input type="text" class="users-manager-input" name="banner_link_url" id="hsBannerLinkUrl" placeholder="/catalogo">
                        </div>
                        <div>
                            <label class="supliers-manager-slider-label">Texto Alternativo</label>
                            <input type="text" class="users-manager-input" name="banner_alt" id="hsBannerAlt" placeholder="Descripción de la imagen">
                        </div>
                    </div>
                </div>

                {{-- dual_banner --}}
                <div class="config-fields" data-type="dual_banner">
                    <p class="hs-config-subtitle">Banner Izquierdo</p>
                    <div class="users-manager-email-camp">
                        <label class="supliers-manager-slider-label">URL de Imagen</label>
                        <input type="text" class="users-manager-input" name="left_image_url" id="hsLeftImageUrl" placeholder="https://...">
                    </div>
                    <div class="user-manager-form">
                        <div>
                            <label class="supliers-manager-slider-label">URL de Enlace</label>
                            <input type="text" class="users-manager-input" name="left_link_url" id="hsLeftLinkUrl">
                        </div>
                        <div>
                            <label class="supliers-manager-slider-label">Texto Alternativo</label>
                            <input type="text" class="users-manager-input" name="left_alt" id="hsLeftAlt">
                        </div>
                    </div>
                    <p class="hs-config-subtitle">Banner Derecho</p>
                    <div class="users-manager-email-camp">
                        <label class="supliers-manager-slider-label">URL de Imagen</label>
                        <input type="text" class="users-manager-input" name="right_image_url" id="hsRightImageUrl" placeholder="https://...">
                    </div>
                    <div class="user-manager-form">
                        <div>
                            <label class="supliers-manager-slider-label">URL de Enlace</label>
                            <input type="text" class="users-manager-input" name="right_link_url" id="hsRightLinkUrl">
                        </div>
                        <div>
                            <label class="supliers-manager-slider-label">Texto Alternativo</label>
                            <input type="text" class="users-manager-input" name="right_alt" id="hsRightAlt">
                        </div>
                    </div>
                </div>

                {{-- product_carousel --}}
                <div class="config-fields" data-type="product_carousel">
                    <div class="user-manager-form">
                        <div>
                            <label class="supliers-manager-slider-label">Origen de Productos</label>
                            <select class="users-manager-select" name="source" id="hsSource">
                                <option value="featured">Destacados</option>
                                <option value="new">Nuevos</option>
                                <option value="recommended">Recomendados</option>
                                <option value="category">Por Categoría</option>
                                <option value="brand">Por Marca</option>
                                <option value="collection">Por Colección</option>
                                <option value="manual">Selección Manual</option>
                            </select>
                        </div>
                        <div>
                            <label class="supliers-manager-slider-label">Límite de Productos</label>
                            <input type="number" class="users-manager-input" name="limit" id="hsLimit" value="10" min="1" max="50">
                        </div>
                    </div>
                    <div class="user-manager-form">
                        <div class="hs-source-field" data-source="category">
                            <label class="supliers-manager-slider-label">Categoría</label>
                            <select class="users-manager-select" name="category_id" id="hsCategoryId">
                                <option value="">Selecciona una categoría</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="hs-source-field" data-source="brand">
                            <label class="supliers-manager-slider-label">Marca</label>
                            <select class="users-manager-select" name="brand_id" id="hsBrandId">
                                <option value="">Selecciona una marca</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="hs-source-field" data-source="collection">
                            <label class="supliers-manager-slider-label">Colección</label>
                            <select class="users-manager-select" name="collection_id" id="hsCollectionId">
                                <option value="">Selecciona una colección</option>
                                @foreach ($collections as $collection)
                                    <option value="{{ $collection->id }}">{{ $collection->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="users-manager-email-camp hs-source-field" data-source="manual">
                        <label class="supliers-manager-slider-label">IDs de Productos (separados por coma)</label>
                        <input type="text" class="users-manager-input" id="hsProductIds" placeholder="12, 45, 78">
                    </div>
                </div>

                {{-- category_grid --}}
                <div class="config-fields" data-type="category_grid">
                    <div class="users-manager-email-camp">
                        <label class="supliers-manager-slider-label">Categorías a mostrar (vacío = todas las principales activas)</label>
                        <select class="users-manager-select" name="category_ids[]" id="hsCategoryIds" multiple size="6">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- brand_carousel --}}
                <div class="config-fields" data-type="brand_carousel">
                    <p class="hs-config-note">Este bloque muestra automáticamente todas las marcas activas. No requiere configuración adicional.</p>
                </div>

                {{-- html_block --}}
                <div class="config-fields" data-type="html_block">
                    <div class="users-manager-email-camp">
                        <label class="supliers-manager-slider-label">Contenido HTML</label>
                        <textarea class="users-manager-input client-modal-textarea" name="html" id="hsHtml" rows="5" placeholder="<div>...</div>"></textarea>
                    </div>
                </div>

                <div class="user-manager-modal-footer">
                    <button type="button" id="cancelHomeSectionModal"
                        class="button-secondary size-adjustment">Cancelar</button>
                    <button type="submit" class="button-primary size-adjustment"
                        id="homeSectionSubmitBtn">Crear Sección</button>
                </div>
            </form>
        </div>
    </div>
