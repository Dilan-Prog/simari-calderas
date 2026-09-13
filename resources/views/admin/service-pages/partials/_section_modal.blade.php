{{-- Create/Edit Modal — secciones de este Servicio. Reusa las clases visuales
     de resources/css/admin/pages/home-sections.css (config-fields,
     hs-source-field, hs-product-search, etc.) para no duplicar CSS; solo los
     IDs cambian (prefijo ss) para no confundirlos con Home Sections. --}}
<div id="serviceSectionModal" class="user-manager-modal client-manage-modal">
    <div class="user-manager-modal-content client-modal-content">
        <div class="user-manager-modal-header">
            <h2 id="serviceSectionModalTitle">Nueva Sección</h2>
            <button type="button" class="table-users-manager-action-btn cancel"
                id="closeServiceSectionModal">✕</button>
        </div>

        <div id="service-section-modal-errors" class="user-manager-errors" style="display:none;"></div>

        <form class="user-manager-modal-body" id="serviceSectionForm">
            @csrf
            <div class="user-manager-form">
                <div>
                    <label class="supliers-manager-slider-label">Tipo de Sección <span style="color:red">*</span></label>
                    <select class="users-manager-select" id="ssType" name="type">
                        <option value="banner">Banner</option>
                        <option value="dual_banner">Banner Doble</option>
                        <option value="product_carousel">Carrusel de Productos</option>
                        <option value="product_carousel_banner">Carrusel con Banner</option>
                        <option value="category_grid">Grid de Categorías</option>
                        <option value="brand_carousel">Carrusel de Marcas</option>
                        <option value="html_block">Bloque HTML</option>
                        <option value="faq">Preguntas Frecuentes</option>
                        <option value="rich_header">Encabezado enriquecido ✨</option>
                        <option value="content_tabs">Descripción por secciones ✨</option>
                        <option value="benefits_grid">Beneficios / características ✨</option>
                        <option value="process_steps">Proceso / cómo funciona ✨</option>
                        <option value="gallery_carousel">Galería / carrusel ✨</option>
                        <option value="rating_reviews">Rating y reseñas ✨</option>
                        <option value="cta_final">CTA final ✨</option>
                    </select>
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Título (opcional)</label>
                    <input type="text" class="users-manager-input" name="title" id="ssTitle"
                        placeholder="Ej: Beneficios del servicio">
                    <p class="hs-config-note" style="margin-top:4px;">
                        Puedes usar <code>{servicio}</code>; se sustituye por el nombre de este servicio.
                    </p>
                </div>
            </div>

            <div class="user-manager-form">
                <div>
                    <label class="supliers-manager-slider-label">Orden</label>
                    <input type="number" class="users-manager-input" name="sort_order" id="ssSortOrder" value="0" min="0">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Estado</label>
                    <select class="users-manager-select" name="is_active" id="ssIsActive">
                        <option value="1" selected>Activa</option>
                        <option value="0">Inactiva</option>
                    </select>
                </div>
            </div>

            <h3 style="margin-top:16px;margin-bottom:4px;">Configuración</h3>
            <div class="show-user-divider"></div>

            {{-- banner --}}
            <div class="config-fields" data-type="banner">
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">URL de Imagen</label>
                    <div class="img-picker-field">
                        <input type="text" class="users-manager-input" name="banner_image_url" id="ssBannerImageUrl" placeholder="https://...">
                        <button type="button" class="img-picker-trigger-btn" onclick="openImagePicker('ssBannerImageUrl')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                            Seleccionar
                        </button>
                    </div>
                </div>
                <div class="user-manager-form">
                    <div>
                        <label class="supliers-manager-slider-label">URL de Enlace</label>
                        <input type="text" class="users-manager-input" name="banner_link_url" id="ssBannerLinkUrl" placeholder="/servicio/otro-servicio">
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Texto Alternativo</label>
                        <input type="text" class="users-manager-input" name="banner_alt" id="ssBannerAlt" placeholder="Descripción de la imagen">
                    </div>
                </div>
            </div>

            {{-- dual_banner --}}
            <div class="config-fields" data-type="dual_banner">
                <p class="hs-config-subtitle">Banner Izquierdo</p>
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">URL de Imagen</label>
                    <div class="img-picker-field">
                        <input type="text" class="users-manager-input" name="left_image_url" id="ssLeftImageUrl" placeholder="https://...">
                        <button type="button" class="img-picker-trigger-btn" onclick="openImagePicker('ssLeftImageUrl')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                            Seleccionar
                        </button>
                    </div>
                </div>
                <div class="user-manager-form">
                    <div>
                        <label class="supliers-manager-slider-label">URL de Enlace</label>
                        <input type="text" class="users-manager-input" name="left_link_url" id="ssLeftLinkUrl">
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Texto Alternativo</label>
                        <input type="text" class="users-manager-input" name="left_alt" id="ssLeftAlt">
                    </div>
                </div>
                <p class="hs-config-subtitle">Banner Derecho</p>
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">URL de Imagen</label>
                    <div class="img-picker-field">
                        <input type="text" class="users-manager-input" name="right_image_url" id="ssRightImageUrl" placeholder="https://...">
                        <button type="button" class="img-picker-trigger-btn" onclick="openImagePicker('ssRightImageUrl')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                            Seleccionar
                        </button>
                    </div>
                </div>
                <div class="user-manager-form">
                    <div>
                        <label class="supliers-manager-slider-label">URL de Enlace</label>
                        <input type="text" class="users-manager-input" name="right_link_url" id="ssRightLinkUrl">
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Texto Alternativo</label>
                        <input type="text" class="users-manager-input" name="right_alt" id="ssRightAlt">
                    </div>
                </div>
            </div>

            {{-- product_carousel --}}
            <div class="config-fields" data-type="product_carousel">
                <div class="user-manager-form">
                    <div>
                        <label class="supliers-manager-slider-label">Origen de Productos</label>
                        <select class="users-manager-select" name="source" id="ssSource">
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
                        <input type="number" class="users-manager-input" name="limit" id="ssLimit" value="10" min="1" max="50">
                    </div>
                </div>
                <div class="user-manager-form">
                    <div class="hs-source-field" data-source="category">
                        <label class="supliers-manager-slider-label">Categoría</label>
                        <select class="users-manager-select" name="category_id" id="ssCategoryId">
                            <option value="">Selecciona una categoría</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hs-source-field" data-source="brand">
                        <label class="supliers-manager-slider-label">Marca</label>
                        <select class="users-manager-select" name="brand_id" id="ssBrandId">
                            <option value="">Selecciona una marca</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hs-source-field" data-source="collection">
                        <label class="supliers-manager-slider-label">Colección</label>
                        <select class="users-manager-select" name="collection_id" id="ssCollectionId">
                            <option value="">Selecciona una colección</option>
                            @foreach ($collections as $collection)
                                <option value="{{ $collection->id }}">{{ $collection->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="users-manager-email-camp hs-source-field" data-source="manual">
                    <label class="supliers-manager-slider-label">Productos</label>
                    <div class="hs-product-search" id="ssProductSearch">
                        <div class="hs-product-search__input-wrap">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                            <input type="text" id="ssProductSearchInput" class="hs-product-search__input" placeholder="Buscar producto por nombre o SKU..." autocomplete="off">
                        </div>
                        <div class="hs-product-search__dropdown" id="ssProductSearchDropdown" style="display:none;">
                            <div class="hs-product-search__empty" id="ssProductSearchEmpty" style="display:none;">Sin resultados</div>
                            <ul class="hs-product-search__list" id="ssProductSearchList"></ul>
                        </div>
                    </div>
                    <div class="hs-product-chips" id="ssProductChips"></div>
                    <input type="hidden" id="ssProductIds">
                </div>
            </div>

            {{-- product_carousel_banner --}}
            <div class="config-fields" data-type="product_carousel_banner">
                <p class="hs-config-subtitle">Banner</p>
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">URL de Imagen</label>
                    <div class="img-picker-field">
                        <input type="text" class="users-manager-input" name="pcb_banner_image_url" id="ssPcbBannerImageUrl" placeholder="https://...">
                        <button type="button" class="img-picker-trigger-btn" onclick="openImagePicker('ssPcbBannerImageUrl')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                            Seleccionar
                        </button>
                    </div>
                </div>
                <div class="user-manager-form">
                    <div>
                        <label class="supliers-manager-slider-label">URL de Enlace</label>
                        <input type="text" class="users-manager-input" name="pcb_banner_link_url" id="ssPcbBannerLinkUrl" placeholder="/servicio/otro-servicio">
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Texto Alternativo</label>
                        <input type="text" class="users-manager-input" name="pcb_banner_alt" id="ssPcbBannerAlt" placeholder="Descripción de la imagen">
                    </div>
                </div>

                <p class="hs-config-subtitle">Productos del carrusel</p>
                <div class="user-manager-form">
                    <div>
                        <label class="supliers-manager-slider-label">Origen de Productos</label>
                        <select class="users-manager-select" name="pcb_source" id="ssPcbSource">
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
                        <input type="number" class="users-manager-input" name="pcb_limit" id="ssPcbLimit" value="10" min="1" max="50">
                    </div>
                </div>
                <div class="user-manager-form">
                    <div class="hs-pcb-source-field" data-source="category">
                        <label class="supliers-manager-slider-label">Categoría</label>
                        <select class="users-manager-select" name="pcb_category_id" id="ssPcbCategoryId">
                            <option value="">Selecciona una categoría</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hs-pcb-source-field" data-source="brand">
                        <label class="supliers-manager-slider-label">Marca</label>
                        <select class="users-manager-select" name="pcb_brand_id" id="ssPcbBrandId">
                            <option value="">Selecciona una marca</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hs-pcb-source-field" data-source="collection">
                        <label class="supliers-manager-slider-label">Colección</label>
                        <select class="users-manager-select" name="pcb_collection_id" id="ssPcbCollectionId">
                            <option value="">Selecciona una colección</option>
                            @foreach ($collections as $collection)
                                <option value="{{ $collection->id }}">{{ $collection->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="users-manager-email-camp hs-pcb-source-field" data-source="manual">
                    <label class="supliers-manager-slider-label">Productos</label>
                    <div class="hs-product-search" id="ssPcbProductSearch">
                        <div class="hs-product-search__input-wrap">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                            <input type="text" id="ssPcbProductSearchInput" class="hs-product-search__input" placeholder="Buscar producto por nombre o SKU..." autocomplete="off">
                        </div>
                        <div class="hs-product-search__dropdown" id="ssPcbProductSearchDropdown" style="display:none;">
                            <div class="hs-product-search__empty" id="ssPcbProductSearchEmpty" style="display:none;">Sin resultados</div>
                            <ul class="hs-product-search__list" id="ssPcbProductSearchList"></ul>
                        </div>
                    </div>
                    <div class="hs-product-chips" id="ssPcbProductChips"></div>
                    <input type="hidden" id="ssPcbProductIds">
                </div>
            </div>

            {{-- category_grid --}}
            <div class="config-fields" data-type="category_grid">
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Categorías a mostrar (vacío = todas las principales activas)</label>
                    <select class="users-manager-select" name="category_ids[]" id="ssCategoryIds" multiple size="6">
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
                    <textarea class="users-manager-input client-modal-textarea" name="html" id="ssHtml" rows="5" placeholder="<div>...</div>"></textarea>
                </div>
            </div>

            {{-- faq --}}
            <div class="config-fields" data-type="faq">
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Texto descriptivo (opcional, aparece bajo el título)</label>
                    <textarea class="users-manager-input client-modal-textarea" name="faq_description" id="ssFaqDescription" rows="2"
                        placeholder="Ej: Resolvemos las dudas más comunes sobre este servicio."></textarea>
                </div>
                <p class="hs-config-note">
                    Las preguntas y respuestas se capturan <strong>en este Servicio</strong>
                    (pestaña SEO y Preguntas Frecuentes, más arriba). Esta sección solo
                    define el título y el texto descriptivo; se oculta si el servicio no tiene preguntas.
                    Puedes usar <code>{servicio}</code> en el título/descripción.
                </p>
            </div>

            {{-- rich_header --}}
            <div class="config-fields" data-type="rich_header">
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Badges cortos (separados por ·, máx. 3)</label>
                    <input type="text" class="users-manager-input" name="rh_badges" id="ssRhBadges" placeholder="Garantía 6 meses · Reporte técnico incluido · Personal certificado">
                </div>
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Líneas de meta (una por línea, ej. cobertura/tiempo de respuesta)</label>
                    <textarea class="users-manager-input client-modal-textarea" id="ssRhMetaLines" rows="2" placeholder="Respuesta en menos de 2h hábiles&#10;Cobertura Jalisco, Nayarit y Bajío"></textarea>
                </div>
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Texto del botón</label>
                    <input type="text" class="users-manager-input" name="rh_whatsapp_text" id="ssRhWhatsappText" placeholder="Cotizar por WhatsApp" value="Cotizar por WhatsApp">
                    <p class="hs-config-note" style="margin-top:4px;">El CTA siempre abre WhatsApp; no existe botón de llamada.</p>
                </div>
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Precio mostrado (opcional, texto libre)</label>
                    <input type="text" class="users-manager-input" name="rh_price_label" id="ssRhPriceLabel" placeholder="$8,500 MXN + IVA">
                </div>
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Imágenes de fondo (de la galería del servicio, pestaña Multimedia)</label>
                    <select class="users-manager-select" name="rh_background_image_ids[]" id="ssRhBackgroundImageIds" multiple size="4">
                        @foreach ($servicePage->images as $img)
                            <option value="{{ $img->id }}">{{ $img->alt_text ?: ('Imagen #' . $img->id) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- content_tabs --}}
            <div class="config-fields" data-type="content_tabs">
                <p class="hs-config-note">Cada pestaña se muestra como una pestaña horizontal en público (ej. "En qué consiste", "Cuándo lo necesitas").</p>
                <div id="ssCtTabsRows" class="hs-repeat-rows"></div>
                <button type="button" class="button-secondary size-adjustment" id="btnAddCtTab" style="margin-top:10px;">+ Agregar pestaña</button>
            </div>

            {{-- benefits_grid --}}
            <div class="config-fields" data-type="benefits_grid">
                <p class="hs-config-note">Tarjetas de cifra + título + descripción (ej. "-12% · Menos combustible").</p>
                <div id="ssBgItemsRows" class="hs-repeat-rows"></div>
                <button type="button" class="button-secondary size-adjustment" id="btnAddBgItem" style="margin-top:10px;">+ Agregar beneficio</button>
            </div>

            {{-- process_steps --}}
            <div class="config-fields" data-type="process_steps">
                <p class="hs-config-note">Pasos numerados del proceso (ej. "1. Inspección — Boroscopía... — 1 h").</p>
                <div id="ssPsStepsRows" class="hs-repeat-rows"></div>
                <button type="button" class="button-secondary size-adjustment" id="btnAddPsStep" style="margin-top:10px;">+ Agregar paso</button>
            </div>

            {{-- gallery_carousel --}}
            <div class="config-fields" data-type="gallery_carousel">
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Imágenes a mostrar (vacío = toda la galería del servicio)</label>
                    <select class="users-manager-select" name="gc_image_ids[]" id="ssGcImageIds" multiple size="6">
                        @foreach ($servicePage->images as $img)
                            <option value="{{ $img->id }}">{{ $img->alt_text ?: ('Imagen #' . $img->id) }}</option>
                        @endforeach
                    </select>
                    @if ($servicePage->images->isEmpty())
                        <p class="hs-config-note" style="margin-top:4px;">Este servicio todavía no tiene imágenes en su galería (pestaña Multimedia).</p>
                    @endif
                </div>
            </div>

            {{-- rating_reviews --}}
            <div class="config-fields" data-type="rating_reviews">
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Texto descriptivo (opcional, aparece bajo el título)</label>
                    <textarea class="users-manager-input client-modal-textarea" name="rr_description" id="ssRrDescription" rows="2"></textarea>
                </div>
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Reseñas visibles antes de "Ver más" (por defecto 3)</label>
                    <input type="number" class="users-manager-input" name="rr_reviews_per_page" id="ssRrReviewsPerPage" value="3" min="1" max="20">
                </div>
                <p class="hs-config-note">
                    Las reseñas, el promedio y las estadísticas se capturan en la pestaña
                    <strong>Rating y reseñas</strong> de este Servicio. Esta sección solo controla el título y cuántas se muestran de entrada.
                </p>
            </div>

            {{-- cta_final --}}
            <div class="config-fields" data-type="cta_final">
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Título</label>
                    <input type="text" class="users-manager-input" name="cta_headline" id="ssCtaHeadline" placeholder="¿Listo para cotizar tu servicio?">
                </div>
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Texto de apoyo</label>
                    <textarea class="users-manager-input client-modal-textarea" name="cta_subtext" id="ssCtaSubtext" rows="2"></textarea>
                </div>
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Texto del botón</label>
                    <input type="text" class="users-manager-input" name="cta_whatsapp_text" id="ssCtaWhatsappText" value="Cotizar por WhatsApp">
                    <p class="hs-config-note" style="margin-top:4px;">El CTA siempre abre WhatsApp; no existe botón de llamada.</p>
                </div>
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Imagen de fondo (opcional)</label>
                    <select class="users-manager-select" name="cta_background_image_id" id="ssCtaBackgroundImageId">
                        <option value="">Sin imagen</option>
                        @foreach ($servicePage->images as $img)
                            <option value="{{ $img->id }}">{{ $img->alt_text ?: ('Imagen #' . $img->id) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="user-manager-modal-footer">
                <button type="button" id="cancelServiceSectionModal"
                    class="button-secondary size-adjustment">Cancelar</button>
                <button type="submit" class="button-primary size-adjustment"
                    id="serviceSectionSubmitBtn">Crear Sección</button>
            </div>
        </form>
    </div>
</div>
