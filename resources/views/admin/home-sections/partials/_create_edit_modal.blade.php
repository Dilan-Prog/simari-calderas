{{--
    Markup del modal de crear/editar sección. NO se incluye directo: lo incluye
    admin.home-sections.partials.editor (que además trae el JS y documenta las
    variables). Lo que depende de la página (tipos, orígenes related_*, nombre
    interno, zona, notas de variables) se muestra/oculta en JS según
    HomeSectionEditor.open({page}) — los elementos con data-pages="a b c" solo
    se ven (y se envían) en esas páginas.

    Variables: $editorData (HomeSectionController::editorData()).
--}}
@php
    $hsCategories      = $editorData['categories'];
    $hsCategoryOptions = $editorData['categoryOptions'];
    $hsBrands          = $editorData['brands'];
    $hsCollections     = $editorData['collections'];

    $hsImgIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>';
    $hsSearchIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>';
@endphp
    {{-- Create/Edit Modal --}}
    <div id="homeSectionModal" class="user-manager-modal client-manage-modal">
        <div class="user-manager-modal-content client-modal-content">
            <div class="user-manager-modal-header">
                <h2 id="homeSectionModalTitle">Nueva Sección</h2>
                <button type="button" class="table-users-manager-action-btn cancel"
                    id="closeHomeSectionModal">✕</button>
            </div>

            <div id="home-section-modal-errors" class="user-manager-errors" style="display:none;"></div>

            <form class="user-manager-modal-body" id="homeSectionForm" novalidate>
                @csrf
                <input type="hidden" name="page" id="hsPage" value="home">
                <input type="hidden" name="product_id" id="hsProductId" value="" disabled>

                {{-- Plantilla compartida: aviso de que editarla cambia todos sus productos --}}
                <div class="hs-template-warning" id="hsTemplateWarning" style="display:none;"></div>

                <div class="user-manager-form">
                    <div>
                        <label class="supliers-manager-slider-label">Tipo de Sección <span style="color:red">*</span></label>
                        <select class="users-manager-select" id="hsType" name="type"></select>
                    </div>
                    <div data-pages="product_template product_custom">
                        <label class="supliers-manager-slider-label">Nombre interno <span class="hs-name-required" style="color:red">*</span></label>
                        <input type="text" class="users-manager-input" name="name" id="hsName" maxlength="150"
                            placeholder="Ej: Más vendidos de la misma marca">
                        <p class="hs-config-note" style="margin-top:4px;">Solo lo ves tú en el admin; no se muestra en la tienda.</p>
                    </div>
                </div>

                <div class="user-manager-form">
                    <div>
                        <div class="pform-label-row">
                            <label class="supliers-manager-slider-label">Título del encabezado (opcional)</label>
                            <button type="button" class="pform-insert-variable-btn" data-variable-target="hsTitle"
                                data-pages="product product_template product_custom">{ } Insertar variable</button>
                        </div>
                        <input type="text" class="users-manager-input" name="title" id="hsTitle"
                            placeholder="Ej: Productos Destacados">
                        <p class="hs-config-note" style="margin-top:4px;" data-pages="product product_template product_custom">
                            Puedes usar las variables del producto: <code>{nombre_producto}</code>, <code>{marca}</code>, <code>{modelo}</code>, <code>{categoria}</code>, <code>{precio}</code>, etc. — se sustituyen por los datos del producto que se esté viendo.
                        </p>
                        <p class="hs-config-note" style="margin-top:4px;" data-pages="collection">
                            Puedes usar <code>{coleccion}</code>; se sustituye por el nombre de la colección que se está viendo.
                        </p>
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Enlace del encabezado (opcional)</label>
                        <div id="hsHeadingLinkMount"></div>
                        <p class="hs-config-note" style="margin-top:4px;">El título se vuelve un enlace a este destino (necesita un título).</p>
                    </div>
                </div>

                <div class="user-manager-form">
                    <div data-pages="product_template product_custom">
                        <label class="supliers-manager-slider-label">Zona</label>
                        <select class="users-manager-select" name="zone" id="hsZone">
                            <option value="stack">Pila (ancho completo, bajo el producto)</option>
                            <option value="sidebar">Barra lateral</option>
                        </select>
                        <p class="hs-config-note" id="hsZoneNote" style="margin-top:4px;display:none;"></p>
                    </div>
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
                            <button type="button" class="img-picker-trigger-btn" onclick="openImagePicker('hsBannerImageUrl')">{!! $hsImgIcon !!} Seleccionar</button>
                        </div>
                    </div>
                    <div class="user-manager-form">
                        <div>
                            <label class="supliers-manager-slider-label">Destino del enlace</label>
                            <div id="hsBannerLinkMount"></div>
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
                            <button type="button" class="img-picker-trigger-btn" onclick="openImagePicker('hsLeftImageUrl')">{!! $hsImgIcon !!} Seleccionar</button>
                        </div>
                    </div>
                    <div class="user-manager-form">
                        <div>
                            <label class="supliers-manager-slider-label">Destino del enlace</label>
                            <div id="hsLeftLinkMount"></div>
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
                            <button type="button" class="img-picker-trigger-btn" onclick="openImagePicker('hsRightImageUrl')">{!! $hsImgIcon !!} Seleccionar</button>
                        </div>
                    </div>
                    <div class="user-manager-form">
                        <div>
                            <label class="supliers-manager-slider-label">Destino del enlace</label>
                            <div id="hsRightLinkMount"></div>
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
                                <option value="related_category" data-pages="product product_template product_custom">Misma categoría del producto</option>
                                <option value="related_brand" data-pages="product product_template product_custom">Misma marca del producto</option>
                                <option value="featured">Destacados</option>
                                <option value="new">Nuevos</option>
                                <option value="recommended">Recomendados</option>
                                <option value="category">Por Categoría</option>
                                <option value="brand">Por Marca</option>
                                <option value="collection">Por Colección</option>
                                <option value="tag">Por Etiqueta</option>
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
                            <label class="supliers-manager-slider-label">Categoría (incluye sus subcategorías)</label>
                            <select class="users-manager-select" name="category_id" id="hsCategoryId">
                                <option value="">Selecciona una categoría</option>
                                @foreach ($hsCategoryOptions as $opt)
                                    <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="hs-source-field" data-source="brand">
                            <label class="supliers-manager-slider-label">Marca</label>
                            <select class="users-manager-select" name="brand_id" id="hsBrandId">
                                <option value="">Selecciona una marca</option>
                                @foreach ($hsBrands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="hs-source-field" data-source="collection">
                            <label class="supliers-manager-slider-label">Colección</label>
                            <select class="users-manager-select" name="collection_id" id="hsCollectionId">
                                <option value="">Selecciona una colección</option>
                                @foreach ($hsCollections as $collection)
                                    <option value="{{ $collection->id }}">{{ $collection->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="hs-source-field" data-source="tag">
                            <label class="supliers-manager-slider-label">Etiqueta</label>
                            <input type="text" class="users-manager-input" name="tag" id="hsTag" list="hsTagList"
                                placeholder="Escribe una etiqueta de producto" autocomplete="off">
                            <datalist id="hsTagList"></datalist>
                        </div>
                    </div>
                    <div class="users-manager-email-camp hs-source-field" data-source="manual">
                        <label class="supliers-manager-slider-label">Productos</label>
                        <div class="hs-product-search" id="hsProductSearch">
                            <div class="hs-product-search__input-wrap">
                                {!! $hsSearchIcon !!}
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
                    <div class="users-manager-email-camp" data-pages="product product_template product_custom">
                        <input type="hidden" name="exclude_current" value="0">
                        <label class="hs-check-label">
                            <input type="checkbox" name="exclude_current" id="hsExcludeCurrent" value="1" checked>
                            Excluir el producto que se está viendo
                        </label>
                    </div>
                </div>

                {{-- product_carousel_banner --}}
                <div class="config-fields" data-type="product_carousel_banner">
                    <p class="hs-config-subtitle">Banner</p>
                    <div class="users-manager-email-camp">
                        <label class="supliers-manager-slider-label">URL de Imagen</label>
                        <div class="img-picker-field">
                            <input type="text" class="users-manager-input" name="pcb_banner_image_url" id="hsPcbBannerImageUrl" placeholder="https://...">
                            <button type="button" class="img-picker-trigger-btn" onclick="openImagePicker('hsPcbBannerImageUrl')">{!! $hsImgIcon !!} Seleccionar</button>
                        </div>
                    </div>
                    <div class="user-manager-form">
                        <div>
                            <label class="supliers-manager-slider-label">Destino del enlace</label>
                            <div id="hsPcbBannerLinkMount"></div>
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
                                <option value="related_category" data-pages="product product_template product_custom">Misma categoría del producto</option>
                                <option value="related_brand" data-pages="product product_template product_custom">Misma marca del producto</option>
                                <option value="featured">Destacados</option>
                                <option value="new">Nuevos</option>
                                <option value="recommended">Recomendados</option>
                                <option value="category">Por Categoría</option>
                                <option value="brand">Por Marca</option>
                                <option value="collection">Por Colección</option>
                                <option value="tag">Por Etiqueta</option>
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
                            <label class="supliers-manager-slider-label">Categoría (incluye sus subcategorías)</label>
                            <select class="users-manager-select" name="pcb_category_id" id="hsPcbCategoryId">
                                <option value="">Selecciona una categoría</option>
                                @foreach ($hsCategoryOptions as $opt)
                                    <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="hs-pcb-source-field" data-source="brand">
                            <label class="supliers-manager-slider-label">Marca</label>
                            <select class="users-manager-select" name="pcb_brand_id" id="hsPcbBrandId">
                                <option value="">Selecciona una marca</option>
                                @foreach ($hsBrands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="hs-pcb-source-field" data-source="collection">
                            <label class="supliers-manager-slider-label">Colección</label>
                            <select class="users-manager-select" name="pcb_collection_id" id="hsPcbCollectionId">
                                <option value="">Selecciona una colección</option>
                                @foreach ($hsCollections as $collection)
                                    <option value="{{ $collection->id }}">{{ $collection->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="hs-pcb-source-field" data-source="tag">
                            <label class="supliers-manager-slider-label">Etiqueta</label>
                            <input type="text" class="users-manager-input" name="pcb_tag" id="hsPcbTag" list="hsTagList"
                                placeholder="Escribe una etiqueta de producto" autocomplete="off">
                        </div>
                    </div>
                    <div class="users-manager-email-camp hs-pcb-source-field" data-source="manual">
                        <label class="supliers-manager-slider-label">Productos</label>
                        <div class="hs-product-search" id="hsPcbProductSearch">
                            <div class="hs-product-search__input-wrap">
                                {!! $hsSearchIcon !!}
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
                    <div class="users-manager-email-camp" data-pages="product product_template product_custom">
                        <input type="hidden" name="pcb_exclude_current" value="0">
                        <label class="hs-check-label">
                            <input type="checkbox" name="pcb_exclude_current" id="hsPcbExcludeCurrent" value="1" checked>
                            Excluir el producto que se está viendo
                        </label>
                    </div>
                </div>

                {{-- card_carousel: fichas imagen + texto + destino (solo barra lateral) --}}
                <div class="config-fields" data-type="card_carousel">
                    <p class="hs-config-note">
                        Carrusel de fichas para la <strong>barra lateral</strong> (máximo 12). Cada ficha lleva una imagen,
                        un texto corto y, opcionalmente, un destino.
                    </p>
                    <div class="hs-card-list" id="hsCardList"></div>
                    <button type="button" class="button-secondary size-adjustment" id="hsCardAdd" style="margin-top:8px;">+ Agregar ficha</button>
                </div>

                {{-- category_grid --}}
                <div class="config-fields" data-type="category_grid">
                    <div class="users-manager-email-camp">
                        <label class="supliers-manager-slider-label">Categorías a mostrar (vacío = todas las principales activas)</label>
                        <select class="users-manager-select" name="category_ids[]" id="hsCategoryIds" multiple size="6">
                            @foreach ($hsCategories as $cat)
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
                        <div class="pform-label-row">
                            <label class="supliers-manager-slider-label">Texto descriptivo (opcional, aparece bajo el título)</label>
                            <button type="button" class="pform-insert-variable-btn" data-variable-target="hsFaqDescription"
                                data-pages="product product_template product_custom">{ } Insertar variable</button>
                        </div>
                        <textarea class="users-manager-input client-modal-textarea" name="faq_description" id="hsFaqDescription" rows="2"
                            placeholder="Ej: Resolvemos las dudas más comunes sobre este producto."></textarea>
                    </div>
                    <p class="hs-config-note" data-pages="collection">
                        Las preguntas y respuestas se capturan <strong>en cada colección</strong>
                        (Colecciones → editar → SEO y Preguntas Frecuentes). Esta sección solo
                        define el título y el texto descriptivo; se oculta en colecciones sin preguntas.
                        Puedes usar <code>{coleccion}</code> en el título/descripción.
                    </p>
                    <p class="hs-config-note" data-pages="product product_template product_custom">
                        Las preguntas y respuestas se capturan <strong>en cada producto</strong>
                        (Productos → editar → botón SEO → Preguntas Frecuentes). Esta sección solo
                        define el título y el texto descriptivo; se oculta en productos sin preguntas.
                        Puedes usar las variables del producto: <code>{nombre_producto}</code>, <code>{marca}</code>,
                        <code>{modelo}</code>, <code>{categoria}</code>, <code>{precio}</code>, etc. en el título/descripción.
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
