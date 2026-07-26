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
                <input type="hidden" name="page" id="hsPage" value="{{ $page ?? 'home' }}">
                <div class="user-manager-form">
                    <div>
                        <label class="supliers-manager-slider-label">Tipo de Sección <span style="color:red">*</span></label>
                        <select class="users-manager-select" id="hsType" name="type">
                            @if (($page ?? 'home') === 'home')
                                <option value="hero_slider">Slider Principal</option>
                            @endif
                            <option value="banner">Banner</option>
                            <option value="dual_banner">Banner Doble</option>
                            <option value="product_carousel">Carrusel de Productos</option>
                            <option value="product_carousel_banner">Carrusel con Banner</option>
                            <option value="category_grid">Grid de Categorías</option>
                            <option value="brand_carousel">Carrusel de Marcas</option>
                            <option value="html_block">Bloque HTML</option>
                            @if (in_array($page ?? 'home', ['product', 'collection'], true))
                                <option value="faq">Preguntas Frecuentes</option>
                            @endif
                        </select>
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Título (opcional)</label>
                        <input type="text" class="users-manager-input" name="title" id="hsTitle"
                            placeholder="Ej: Productos Destacados">
                        @if (($page ?? 'home') === 'product')
                            <p class="hs-config-note" style="margin-top:4px;">
                                Puedes usar <code>{categoria}</code> y <code>{marca}</code>; se sustituyen por la categoría/marca del producto.
                            </p>
                        @elseif (($page ?? 'home') === 'collection')
                            <p class="hs-config-note" style="margin-top:4px;">
                                Puedes usar <code>{coleccion}</code>; se sustituye por el nombre de la colección que se está viendo.
                            </p>
                        @endif
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
                        <div class="img-picker-field">
                            <input type="text" class="users-manager-input" name="banner_image_url" id="hsBannerImageUrl" placeholder="https://...">
                            <button type="button" class="img-picker-trigger-btn" onclick="openImagePicker('hsBannerImageUrl')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                Seleccionar
                            </button>
                        </div>
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
                        <div class="img-picker-field">
                            <input type="text" class="users-manager-input" name="left_image_url" id="hsLeftImageUrl" placeholder="https://...">
                            <button type="button" class="img-picker-trigger-btn" onclick="openImagePicker('hsLeftImageUrl')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                Seleccionar
                            </button>
                        </div>
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
                        <div class="img-picker-field">
                            <input type="text" class="users-manager-input" name="right_image_url" id="hsRightImageUrl" placeholder="https://...">
                            <button type="button" class="img-picker-trigger-btn" onclick="openImagePicker('hsRightImageUrl')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                Seleccionar
                            </button>
                        </div>
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
                                @if (($page ?? 'home') === 'product')
                                    <option value="related_category">Misma categoría del producto</option>
                                    <option value="related_brand">Misma marca del producto</option>
                                @endif
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
                        <label class="supliers-manager-slider-label">Productos</label>
                        <div class="hs-product-search" id="hsProductSearch">
                            <div class="hs-product-search__input-wrap">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                                <input type="text" id="hsProductSearchInput" class="hs-product-search__input" placeholder="Buscar producto por nombre o SKU..." autocomplete="off">
                            </div>
                            <div class="hs-product-search__dropdown" id="hsProductSearchDropdown" style="display:none;">
                                <div class="hs-product-search__empty" id="hsProductSearchEmpty" style="display:none;">Sin resultados</div>
                                <ul class="hs-product-search__list" id="hsProductSearchList"></ul>
                            </div>
                        </div>
                        <div class="hs-product-chips" id="hsProductChips"></div>
                        <input type="hidden" id="hsProductIds">
                    </div>
                </div>

                {{-- product_carousel_banner --}}
                <div class="config-fields" data-type="product_carousel_banner">
                    <p class="hs-config-subtitle">Banner</p>
                    <div class="users-manager-email-camp">
                        <label class="supliers-manager-slider-label">URL de Imagen</label>
                        <div class="img-picker-field">
                            <input type="text" class="users-manager-input" name="pcb_banner_image_url" id="hsPcbBannerImageUrl" placeholder="https://...">
                            <button type="button" class="img-picker-trigger-btn" onclick="openImagePicker('hsPcbBannerImageUrl')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                Seleccionar
                            </button>
                        </div>
                    </div>
                    <div class="user-manager-form">
                        <div>
                            <label class="supliers-manager-slider-label">URL de Enlace</label>
                            <input type="text" class="users-manager-input" name="pcb_banner_link_url" id="hsPcbBannerLinkUrl" placeholder="/catalogo">
                        </div>
                        <div>
                            <label class="supliers-manager-slider-label">Texto Alternativo</label>
                            <input type="text" class="users-manager-input" name="pcb_banner_alt" id="hsPcbBannerAlt" placeholder="Descripción de la imagen">
                        </div>
                    </div>

                    <p class="hs-config-subtitle">Productos del carrusel</p>
                    <div class="user-manager-form">
                        <div>
                            <label class="supliers-manager-slider-label">Origen de Productos</label>
                            <select class="users-manager-select" name="pcb_source" id="hsPcbSource">
                                @if (($page ?? 'home') === 'product')
                                    <option value="related_category">Misma categoría del producto</option>
                                    <option value="related_brand">Misma marca del producto</option>
                                @endif
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
                            <input type="number" class="users-manager-input" name="pcb_limit" id="hsPcbLimit" value="10" min="1" max="50">
                        </div>
                    </div>
                    <div class="user-manager-form">
                        <div class="hs-pcb-source-field" data-source="category">
                            <label class="supliers-manager-slider-label">Categoría</label>
                            <select class="users-manager-select" name="pcb_category_id" id="hsPcbCategoryId">
                                <option value="">Selecciona una categoría</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="hs-pcb-source-field" data-source="brand">
                            <label class="supliers-manager-slider-label">Marca</label>
                            <select class="users-manager-select" name="pcb_brand_id" id="hsPcbBrandId">
                                <option value="">Selecciona una marca</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="hs-pcb-source-field" data-source="collection">
                            <label class="supliers-manager-slider-label">Colección</label>
                            <select class="users-manager-select" name="pcb_collection_id" id="hsPcbCollectionId">
                                <option value="">Selecciona una colección</option>
                                @foreach ($collections as $collection)
                                    <option value="{{ $collection->id }}">{{ $collection->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="users-manager-email-camp hs-pcb-source-field" data-source="manual">
                        <label class="supliers-manager-slider-label">Productos</label>
                        <div class="hs-product-search" id="hsPcbProductSearch">
                            <div class="hs-product-search__input-wrap">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                                <input type="text" id="hsPcbProductSearchInput" class="hs-product-search__input" placeholder="Buscar producto por nombre o SKU..." autocomplete="off">
                            </div>
                            <div class="hs-product-search__dropdown" id="hsPcbProductSearchDropdown" style="display:none;">
                                <div class="hs-product-search__empty" id="hsPcbProductSearchEmpty" style="display:none;">Sin resultados</div>
                                <ul class="hs-product-search__list" id="hsPcbProductSearchList"></ul>
                            </div>
                        </div>
                        <div class="hs-product-chips" id="hsPcbProductChips"></div>
                        <input type="hidden" id="hsPcbProductIds">
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

                {{-- faq --}}
                <div class="config-fields" data-type="faq">
                    <div class="users-manager-email-camp">
                        <label class="supliers-manager-slider-label">Texto descriptivo (opcional, aparece bajo el título)</label>
                        <textarea class="users-manager-input client-modal-textarea" name="faq_description" id="hsFaqDescription" rows="2"
                            placeholder="Ej: Resolvemos las dudas más comunes sobre este producto."></textarea>
                    </div>
                    <p class="hs-config-note">
                        @if (($page ?? 'home') === 'collection')
                            Las preguntas y respuestas se capturan <strong>en cada colección</strong>
                            (Colecciones → editar → SEO y Preguntas Frecuentes). Esta sección solo
                            define el título y el texto descriptivo; se oculta en colecciones sin preguntas.
                            Puedes usar <code>{coleccion}</code> en el título/descripción.
                        @else
                            Las preguntas y respuestas se capturan <strong>en cada producto</strong>
                            (Productos → editar → botón SEO → Preguntas Frecuentes). Esta sección solo
                            define el título y el texto descriptivo; se oculta en productos sin preguntas.
                            Puedes usar <code>{categoria}</code> y <code>{marca}</code> en el título/descripción.
                        @endif
                    </p>
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
