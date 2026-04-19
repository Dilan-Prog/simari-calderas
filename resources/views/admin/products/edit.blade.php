@extends('admin.layouts.master')
@section('title')
    Editar Producto - Admin
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css">
@endpush

@section('content')
<div class="pform-page">

    {{-- ── Sticky header ── --}}
    <div class="pform-page-header">

        <div class="pform-header-top">
            <div class="pform-header-left">
                <button class="pform-back-btn" id="pformBackBtn" type="button" title="Volver">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>
                    </svg>
                </button>
                <div>
                    <h1 class="pform-title">Editar Producto</h1>
                    <span class="pform-saved-badge" id="pformSavedBadge">✓ Guardado</span>
                </div>
            </div>

            <div class="pform-header-actions">
                <button class="pform-btn outline" id="pformBtnSeo" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                    </svg>
                    SEO del Producto
                </button>
                <button class="pform-btn outline" id="pformBtnDraft" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/>
                        <path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"/>
                        <path d="M7 3v4a1 1 0 0 0 1 1h7"/>
                    </svg>
                    Guardar Borrador
                </button>
                <button class="pform-btn primary" id="pformBtnPublish" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    Publicar Producto
                </button>
            </div>
        </div>

        {{-- ── Tabs ── --}}
        <div class="pform-tabs-row" role="tablist">

            <button class="pform-tab active" data-tab="pformPanel0" type="button" role="tab">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"/>
                    <path d="M12 22V12"/><polyline points="3.29 7 12 12 20.71 7"/><path d="m7.5 4.27 9 5.15"/>
                </svg>
                Información Básica
            </button>

            <button class="pform-tab" data-tab="pformPanel1" type="button" role="tab">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                    <circle cx="9" cy="9" r="2"/>
                    <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                </svg>
                Imágenes
            </button>

            <button class="pform-tab" data-tab="pformPanel2" type="button" role="tab">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" x2="12" y1="2" y2="22"/>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
                Precio e Inventario
            </button>

            <button class="pform-tab" data-tab="pformPanel3" type="button" role="tab">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 10a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-2.5a1 1 0 0 1-.8-.4l-.9-1.2A1 1 0 0 0 15 3h-2a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1Z"/>
                    <path d="M20 21a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1h-2.9a1 1 0 0 1-.88-.55l-.42-.85a1 1 0 0 0-.92-.6H13a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1Z"/>
                    <path d="M3 5a2 2 0 0 0 2 2h3"/><path d="M3 3v13a2 2 0 0 0 2 2h3"/>
                </svg>
                Organización
            </button>

            <button class="pform-tab" data-tab="pformPanel4" type="button" role="tab">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                </svg>
                Especificaciones Técnicas
            </button>

            <button class="pform-tab" data-tab="pformPanel5" type="button" role="tab">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/>
                    <path d="M14 2v4a2 2 0 0 0 2 2h4"/>
                    <path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>
                </svg>
                Documentación
            </button>

        </div>
    </div>

    {{-- ── Scrollable content ── --}}
    <div class="pform-content-area">
        <div class="pform-panel-wrap">

            {{-- Panel 0: Información Básica --}}
            <div class="pform-tab-panel active" id="pformPanel0" role="tabpanel">

                <div class="pform-panel">
                    <h2 class="pform-panel-title">Información Básica del Producto</h2>

                    <div class="pform-field">
                        <label class="pform-label" for="pformName">
                            Nombre del Producto <span class="pform-required">*</span>
                        </label>
                        <input type="text" id="pformName" class="pform-input"
                            placeholder="Ej: Caldera Industrial Hyperion 500" required />
                        <p class="pform-hint">Este nombre aparecerá en el catálogo y en los resultados de búsqueda</p>
                    </div>

                    <div class="pform-grid-3">
                        <div class="pform-field">
                            <label class="pform-label" for="pformSku">
                                SKU <span class="pform-required">*</span>
                            </label>
                            <input type="text" id="pformSku" class="pform-input" placeholder="HYP-500" required />
                        </div>
                        <div class="pform-field">
                            <label class="pform-label" for="pformBrand">
                                Marca <span class="pform-required">*</span>
                            </label>
                            <input type="text" id="pformBrand" class="pform-input" placeholder="SIMARI" required />
                        </div>
                        <div class="pform-field">
                            <label class="pform-label" for="pformModel">Modelo</label>
                            <input type="text" id="pformModel" class="pform-input" placeholder="Hyperion 500" />
                        </div>
                    </div>

                    <div class="pform-field">
                        <label class="pform-label" for="pformShortDesc">Descripción Corta</label>
                        <textarea id="pformShortDesc" class="pform-textarea" rows="3" maxlength="200"
                            placeholder="Resumen breve del producto (aparecerá en las tarjetas del catálogo)"></textarea>
                        <div class="pform-char-row">
                            <span class="pform-hint" style="margin:0">Máximo 200 caracteres</span>
                            <span class="pform-char-count" id="pformCharCount">0/200</span>
                        </div>
                    </div>

                    <div class="pform-field">
                        <label class="pform-label">Descripción Completa</label>
                        <div class="pform-quill-wrap">
                            <div id="pformQuillEditor"></div>
                        </div>
                        <p class="pform-hint">Usa el editor para crear una descripción rica con formato, tablas técnicas, listas de características, etc.</p>
                    </div>
                </div>

                <div class="pform-tips-box">
                    <p class="pform-tips-title">💡 Consejos para una buena descripción</p>
                    <ul class="pform-tips-list">
                        <li>• Incluye las características técnicas principales</li>
                        <li>• Menciona los beneficios y aplicaciones del producto</li>
                        <li>• Usa listas para facilitar la lectura</li>
                        <li>• Agrega tablas con especificaciones técnicas si es necesario</li>
                        <li>• Incluye información sobre garantía y certificaciones</li>
                    </ul>
                </div>

            </div>

            {{-- Panel 1: Imágenes --}}
            <div class="pform-tab-panel" id="pformPanel1" role="tabpanel">

                <div class="pform-panel">
                    <h2 class="pform-panel-title">Galería de Imágenes del Producto</h2>
                    <p class="pform-hint" style="margin-bottom:24px">Sube y organiza las imágenes de tu producto. Arrastra para reordenar.</p>

                    <label class="pform-dropzone" for="pformImageInput">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"
                            fill="none" stroke="#9ca3af" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/>
                            <line x1="12" x2="12" y1="3" y2="15"/>
                        </svg>
                        <p class="pform-dropzone-text">Haz clic para subir imágenes o arrástralas aquí</p>
                        <p class="pform-dropzone-sub">PNG, JPG, JPEG hasta 10MB por imagen</p>
                        <input type="file" id="pformImageInput" accept="image/png,image/jpeg,image/jpg" multiple style="display:none">
                    </label>

                    <div class="pform-placeholder" style="padding:40px 32px">
                        <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24"
                            fill="none" stroke="#d1d5db" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                            <circle cx="9" cy="9" r="2"/>
                            <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                        </svg>
                        <p class="pform-placeholder-title">No hay imágenes aún</p>
                        <p class="pform-placeholder-sub">Sube imágenes para empezar</p>
                    </div>
                </div>

                <div class="pform-tips-box">
                    <p class="pform-tips-title">💡 Mejores prácticas para imágenes</p>
                    <ul class="pform-tips-list">
                        <li>• Usa imágenes de alta calidad (mínimo 1200x1200px)</li>
                        <li>• Incluye diferentes ángulos del producto</li>
                        <li>• Agrega imágenes del producto en uso si es posible</li>
                        <li>• La primera imagen será la que aparezca en el catálogo</li>
                        <li>• Completa el texto ALT de cada imagen para mejorar el SEO</li>
                        <li>• Arrastra las imágenes para cambiar el orden de visualización</li>
                    </ul>
                </div>

            </div>

            {{-- Panel 2: Precio e Inventario --}}
            <div class="pform-tab-panel" id="pformPanel2" role="tabpanel">

                <div class="pform-panel">
                    <h2 class="pform-panel-title">Precio e Inventario</h2>

                    {{-- Precios --}}
                    <div class="pform-section">
                        <h3 class="pform-section-title">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="var(--secondary-color)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" x2="12" y1="2" y2="22"/>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                            </svg>
                            Precios
                        </h3>

                        <div class="pform-grid-2">
                            <div class="pform-field">
                                <label class="pform-label">Costo del Artículo <span class="pform-required">*</span></label>
                                <div class="pform-price-wrap">
                                    <span class="pform-price-prefix">$</span>
                                    <input type="number" id="pformCost" class="pform-input pform-input-prefixed"
                                        placeholder="0.00" step="0.01" min="0" required>
                                </div>
                                <p class="pform-hint">Costo de adquisición o producción</p>
                            </div>
                            <div class="pform-field">
                                <label class="pform-label">Precio de Venta <span class="pform-required">*</span></label>
                                <div class="pform-price-wrap">
                                    <span class="pform-price-prefix">$</span>
                                    <input type="number" id="pformPrice" class="pform-input pform-input-prefixed"
                                        placeholder="0.00" step="0.01" min="0" required>
                                </div>
                                <p class="pform-hint">Precio público del producto</p>
                            </div>
                        </div>

                        <div class="pform-grid-2">
                            <div class="pform-field">
                                <label class="pform-label">Precio de Oferta</label>
                                <div class="pform-price-wrap">
                                    <span class="pform-price-prefix">$</span>
                                    <input type="number" class="pform-input pform-input-prefixed"
                                        placeholder="0.00" step="0.01" min="0">
                                </div>
                                <p class="pform-hint">Opcional: precio en promoción</p>
                            </div>
                            <div class="pform-field">
                                <label class="pform-label">Moneda</label>
                                <select class="pform-select">
                                    <option value="MXN">MXN - Peso Mexicano</option>
                                    <option value="USD">USD - Dólar Americano</option>
                                    <option value="EUR">EUR - Euro</option>
                                </select>
                            </div>
                        </div>

                        <div class="pform-profit-card">
                            <h4 class="pform-profit-title">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/>
                                    <polyline points="16 7 22 7 22 13"/>
                                </svg>
                                Análisis de Rentabilidad
                            </h4>
                            <div class="pform-profit-grid">
                                <div>
                                    <p class="pform-profit-label">Costo</p>
                                    <p class="pform-profit-value" id="pformProfitCost">$0</p>
                                </div>
                                <div>
                                    <p class="pform-profit-label">Precio</p>
                                    <p class="pform-profit-value" style="color:var(--secondary-color)" id="pformProfitPrice">$0</p>
                                </div>
                                <div>
                                    <p class="pform-profit-label">Utilidad</p>
                                    <p class="pform-profit-value" style="color:#16a34a" id="pformProfitUtil">$0</p>
                                </div>
                            </div>
                            <div class="pform-profit-margin">
                                <p class="pform-profit-label">Margen de Ganancia</p>
                                <p class="pform-profit-margin-value" id="pformProfitMargin">0%</p>
                            </div>
                        </div>
                    </div>

                    {{-- Inventario --}}
                    <div class="pform-section">
                        <h3 class="pform-section-title">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="var(--secondary-color)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"/>
                                <path d="M12 22V12"/>
                                <polyline points="3.29 7 12 12 20.71 7"/>
                                <path d="m7.5 4.27 9 5.15"/>
                            </svg>
                            Inventario
                        </h3>

                        <div class="pform-grid-3">
                            <div class="pform-field">
                                <label class="pform-label">SKU <span class="pform-required">*</span></label>
                                <input type="text" class="pform-input" placeholder="PROD-001" required>
                            </div>
                            <div class="pform-field">
                                <label class="pform-label">Inventario Disponible <span class="pform-required">*</span></label>
                                <input type="number" class="pform-input" placeholder="0" min="0" required>
                            </div>
                            <div class="pform-field">
                                <label class="pform-label">Unidad de Medida</label>
                                <select class="pform-select">
                                    <option value="unidad">Unidad</option>
                                    <option value="pieza">Pieza</option>
                                    <option value="juego">Juego</option>
                                    <option value="kit">Kit</option>
                                    <option value="metro">Metro</option>
                                    <option value="kg">Kilogramo</option>
                                    <option value="litro">Litro</option>
                                </select>
                            </div>
                        </div>

                        <div class="pform-field" style="margin-bottom:0">
                            <label class="pform-label">Estado de Disponibilidad</label>
                            <div class="pform-avail-row">
                                <button type="button" class="pform-avail-btn active">
                                    <div class="pform-avail-btn-title">Disponible</div>
                                    <div class="pform-avail-btn-sub">En stock y listo para envío</div>
                                </button>
                                <button type="button" class="pform-avail-btn">
                                    <div class="pform-avail-btn-title">Bajo Pedido</div>
                                    <div class="pform-avail-btn-sub">Se fabrica al recibir orden</div>
                                </button>
                                <button type="button" class="pform-avail-btn">
                                    <div class="pform-avail-btn-title">Agotado</div>
                                    <div class="pform-avail-btn-sub">Sin inventario disponible</div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Panel 3: Organización --}}
            <div class="pform-tab-panel" id="pformPanel3" role="tabpanel">

                <div class="pform-panel">
                    <h2 class="pform-panel-title">Organización del Producto</h2>

                    {{-- Categorización --}}
                    <div class="pform-section">
                        <h3 class="pform-section-title">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="var(--secondary-color)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 10a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-2.5a1 1 0 0 1-.8-.4l-.9-1.2A1 1 0 0 0 15 3h-2a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1Z"/>
                                <path d="M20 21a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1h-2.9a1 1 0 0 1-.88-.55l-.42-.85a1 1 0 0 0-.92-.6H13a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1Z"/>
                                <path d="M3 5a2 2 0 0 0 2 2h3"/><path d="M3 3v13a2 2 0 0 0 2 2h3"/>
                            </svg>
                            Categorización del Catálogo
                        </h3>
                        <div class="pform-grid-3">
                            <div class="pform-field">
                                <label class="pform-label">Categoría Principal <span class="pform-required">*</span></label>
                                <select class="pform-select" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="Calderas">Calderas</option>
                                    <option value="Calentadores">Calentadores</option>
                                    <option value="Bombas de Calor">Bombas de Calor</option>
                                    <option value="Tratamiento de Agua">Tratamiento de Agua</option>
                                    <option value="Refacciones">Refacciones</option>
                                </select>
                            </div>
                            <div class="pform-field">
                                <label class="pform-label">Subcategoría</label>
                                <select class="pform-select">
                                    <option value="">Seleccionar...</option>
                                    <option value="Aire-Agua">Aire-Agua</option>
                                    <option value="Agua-Agua">Agua-Agua</option>
                                </select>
                            </div>
                            <div class="pform-field">
                                <label class="pform-label">Child Categoría</label>
                                <select class="pform-select" disabled>
                                    <option value="">Seleccionar...</option>
                                </select>
                            </div>
                        </div>
                        <div class="pform-breadcrumb">
                            <span style="color:#6b7280">Ruta de navegación:</span>
                            <strong style="color:#111827;margin-left:4px"> Catálogo &gt; Bombas de Calor</strong>
                        </div>
                    </div>

                    {{-- Etiquetas --}}
                    <div class="pform-section">
                        <h3 class="pform-section-title">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="var(--secondary-color)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/>
                                <circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/>
                            </svg>
                            Etiquetas
                        </h3>
                        <div class="pform-field" style="margin-bottom:0">
                            <label class="pform-label">Agregar Etiquetas</label>
                            <div class="pform-tag-row">
                                <input type="text" id="pformTagInput" class="pform-input"
                                    placeholder="Ejemplo: eficiente, industrial, premium...">
                                <button type="button" id="pformTagAdd" class="pform-btn primary">Agregar</button>
                            </div>
                            <p class="pform-hint">Las etiquetas ayudan a los clientes a encontrar el producto. Presiona Enter o clic en Agregar.</p>
                            <div class="pform-tag-chips" id="pformTagList"></div>
                        </div>
                    </div>

                    {{-- Badges --}}
                    <div class="pform-section">
                        <h3 class="pform-section-title">Destacados y Badges</h3>
                        <div class="pform-grid-3">

                            <button type="button" class="pform-badge-card">
                                <div class="pform-badge-card-header">
                                    <div class="pform-badge-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="pform-badge-name">Destacado</div>
                                        <div class="pform-toggle"></div>
                                    </div>
                                </div>
                                <p class="pform-badge-sub">Aparecerá en la sección de productos destacados</p>
                            </button>

                            <button type="button" class="pform-badge-card">
                                <div class="pform-badge-card-header">
                                    <div class="pform-badge-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/>
                                            <path d="M20 3v4"/><path d="M22 5h-4"/>
                                            <path d="M4 17v2"/><path d="M5 18H3"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="pform-badge-name">Nuevo</div>
                                        <div class="pform-toggle"></div>
                                    </div>
                                </div>
                                <p class="pform-badge-sub">Mostrará badge de "Nuevo" en el producto</p>
                            </button>

                            <button type="button" class="pform-badge-card">
                                <div class="pform-badge-card-header">
                                    <div class="pform-badge-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M7 10v12"/>
                                            <path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="pform-badge-name">Recomendado</div>
                                        <div class="pform-toggle"></div>
                                    </div>
                                </div>
                                <p class="pform-badge-sub">Aparecerá en sugerencias y recomendaciones</p>
                            </button>

                        </div>
                    </div>
                </div>

            </div>

            {{-- Panel 4: Especificaciones Técnicas --}}
            <div class="pform-tab-panel" id="pformPanel4" role="tabpanel">

                <div class="pform-panel">
                    <div class="pform-specs-header">
                        <div>
                            <h2 class="pform-panel-title" style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                                    fill="none" stroke="var(--secondary-color)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                                </svg>
                                Especificaciones Técnicas
                            </h2>
                            <p class="pform-hint" style="margin:0">Define las características técnicas del producto industrial</p>
                        </div>
                        <button type="button" class="pform-btn primary" id="pformAddSpec">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 12h14"/><path d="M12 5v14"/>
                            </svg>
                            Agregar Campo
                        </button>
                    </div>

                    <div class="pform-placeholder" id="pformSpecsEmpty">
                        <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24"
                            fill="none" stroke="#d1d5db" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                        </svg>
                        <p class="pform-placeholder-title">No hay especificaciones técnicas</p>
                        <p class="pform-placeholder-sub">Agrega campos técnicos para definir las características del producto</p>
                    </div>

                    <div id="pformSpecsList" class="pform-spec-list" style="display:none"></div>
                </div>

                <div class="pform-alert-box">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="pform-alert-box-icon">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" x2="12" y1="8" y2="12"/>
                        <line x1="12" x2="12.01" y1="16" y2="16"/>
                    </svg>
                    <div class="pform-alert-box-body">
                        <h4>Importante para productos industriales</h4>
                        <p>Las especificaciones técnicas son fundamentales para productos B2B. Incluye todos los datos técnicos relevantes como potencia, capacidad, presión, voltaje, certificaciones, etc. Esto ayudará a tus clientes a tomar decisiones informadas.</p>
                    </div>
                </div>

            </div>

            {{-- Panel 5: Documentación --}}
            <div class="pform-tab-panel" id="pformPanel5" role="tabpanel">

                <div class="pform-panel">
                    <h2 class="pform-panel-title">Documentación Técnica</h2>
                    <p class="pform-hint" style="margin-bottom:24px">Sube archivos PDF y documentos relacionados con el producto</p>

                    <div class="pform-doc-list">

                        <div class="pform-doc-row">
                            <div class="pform-doc-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/>
                                    <path d="M14 2v4a2 2 0 0 0 2 2h4"/>
                                    <path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>
                                </svg>
                            </div>
                            <div class="pform-doc-info">
                                <h3>Ficha Técnica (PDF)</h3>
                                <p>Documento con las especificaciones técnicas detalladas</p>
                            </div>
                            <label class="pform-doc-upload">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="17 8 12 3 7 8"/>
                                    <line x1="12" x2="12" y1="3" y2="15"/>
                                </svg>
                                Subir
                                <input type="file" hidden accept=".pdf">
                            </label>
                        </div>

                        <div class="pform-doc-row">
                            <div class="pform-doc-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/>
                                    <path d="M14 2v4a2 2 0 0 0 2 2h4"/>
                                    <path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>
                                </svg>
                            </div>
                            <div class="pform-doc-info">
                                <h3>Manual de Instalación</h3>
                                <p>Guía paso a paso para la instalación del producto</p>
                            </div>
                            <label class="pform-doc-upload">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="17 8 12 3 7 8"/>
                                    <line x1="12" x2="12" y1="3" y2="15"/>
                                </svg>
                                Subir
                                <input type="file" hidden accept=".pdf">
                            </label>
                        </div>

                        <div class="pform-doc-row">
                            <div class="pform-doc-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/>
                                    <path d="M14 2v4a2 2 0 0 0 2 2h4"/>
                                    <path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>
                                </svg>
                            </div>
                            <div class="pform-doc-info">
                                <h3>Catálogo del Producto</h3>
                                <p>Catálogo comercial con información del producto</p>
                            </div>
                            <label class="pform-doc-upload">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="17 8 12 3 7 8"/>
                                    <line x1="12" x2="12" y1="3" y2="15"/>
                                </svg>
                                Subir
                                <input type="file" hidden accept=".pdf">
                            </label>
                        </div>

                        <div class="pform-doc-row">
                            <div class="pform-doc-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/>
                                    <path d="M14 2v4a2 2 0 0 0 2 2h4"/>
                                    <path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>
                                </svg>
                            </div>
                            <div class="pform-doc-info">
                                <h3>Certificaciones</h3>
                                <p>Certificados de calidad, normas y homologaciones</p>
                            </div>
                            <label class="pform-doc-upload">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="17 8 12 3 7 8"/>
                                    <line x1="12" x2="12" y1="3" y2="15"/>
                                </svg>
                                Subir
                                <input type="file" hidden accept=".pdf">
                            </label>
                        </div>

                        <div class="pform-doc-row">
                            <div class="pform-doc-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/>
                                    <path d="M14 2v4a2 2 0 0 0 2 2h4"/>
                                    <path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>
                                </svg>
                            </div>
                            <div class="pform-doc-info">
                                <h3>Garantía</h3>
                                <p>Documento de términos de garantía</p>
                            </div>
                            <label class="pform-doc-upload">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="17 8 12 3 7 8"/>
                                    <line x1="12" x2="12" y1="3" y2="15"/>
                                </svg>
                                Subir
                                <input type="file" hidden accept=".pdf">
                            </label>
                        </div>

                        <div class="pform-doc-row">
                            <div class="pform-doc-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/>
                                    <path d="M14 2v4a2 2 0 0 0 2 2h4"/>
                                </svg>
                            </div>
                            <div class="pform-doc-info">
                                <h3>Documento Adicional</h3>
                                <p>Cualquier otro documento técnico relevante</p>
                            </div>
                            <label class="pform-doc-upload">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="17 8 12 3 7 8"/>
                                    <line x1="12" x2="12" y1="3" y2="15"/>
                                </svg>
                                Subir
                                <input type="file" hidden accept=".pdf,.doc,.docx">
                            </label>
                        </div>

                    </div>

                    <div class="pform-reco-box">
                        <h4>💡 Recomendaciones</h4>
                        <ul>
                            <li>• Los documentos PDF son más accesibles y profesionales</li>
                            <li>• Incluye siempre la ficha técnica del producto</li>
                            <li>• Los manuales de instalación reducen las consultas de soporte</li>
                            <li>• Las certificaciones generan confianza en clientes B2B</li>
                            <li>• Mantén los documentos actualizados con la versión más reciente</li>
                        </ul>
                    </div>
                </div>

            </div>

        </div>
    </div>

    {{-- ── SEO Modal overlay ── --}}
    <div class="pform-seo-modal" id="pformSeoModal" style="display:none">

        <div class="pform-seo-header">
            <div class="pform-seo-header-top">
                <button class="pform-back-btn" id="pformSeoClose" type="button" title="Cerrar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>
                    </svg>
                </button>
                <div style="flex:1">
                    <h1 class="pform-title">SEO del Producto</h1>
                    <p class="pform-hint" style="margin:0">Optimiza el producto para motores de búsqueda y redes sociales</p>
                </div>
                <div class="pform-seo-score-wrap">
                    <div class="pform-seo-score" id="pformSeoScoreVal">0%</div>
                    <p class="pform-hint" style="margin:4px 0 0;text-align:center">Puntuación SEO</p>
                </div>
            </div>
        </div>

        <div class="pform-seo-content">
            <div class="pform-seo-wrap">

                {{-- SEO Básico --}}
                <div class="pform-panel" style="margin-bottom:0">
                    <h2 class="pform-panel-title" style="display:flex;align-items:center;gap:8px">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                            fill="none" stroke="var(--secondary-color)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                        </svg>
                        SEO Básico
                    </h2>

                    <div class="pform-field">
                        <label class="pform-label" for="pformSeoTitle">Título SEO <span class="pform-required">*</span></label>
                        <input type="text" id="pformSeoTitle" class="pform-input"
                            placeholder="Bomba de Calor Rinnai 20HP" maxlength="60">
                        <div class="pform-char-row">
                            <span class="pform-hint" style="margin:0">Óptimo: 30-60 caracteres</span>
                            <span class="pform-char-count" id="pformSeoTitleCount">0/60</span>
                        </div>
                    </div>

                    <div class="pform-field">
                        <label class="pform-label" for="pformSeoSlug">URL Slug</label>
                        <div class="pform-tag-row">
                            <input type="text" id="pformSeoSlug" class="pform-input" placeholder="producto-ejemplo">
                            <button type="button" id="pformSeoAutoSlug" class="pform-btn outline">Generar Auto</button>
                        </div>
                        <p class="pform-hint">URL: <span style="color:#1d4ed8">simari.com/productos/<span id="pformSeoSlugPreview">producto-ejemplo</span></span></p>
                    </div>

                    <div class="pform-field">
                        <label class="pform-label" for="pformSeoMeta">Meta Description <span class="pform-required">*</span></label>
                        <textarea id="pformSeoMeta" class="pform-textarea" rows="3" maxlength="160"
                            placeholder="Descripción breve que aparecerá en los resultados de búsqueda de Google"></textarea>
                        <div class="pform-char-row">
                            <span class="pform-hint" style="margin:0">Óptimo: 120-160 caracteres</span>
                            <span class="pform-char-count" id="pformSeoMetaCount">0/160</span>
                        </div>
                    </div>

                    <div class="pform-field">
                        <label class="pform-label">Palabras Clave (Keywords)</label>
                        <input type="text" class="pform-input" placeholder="caldera, industrial, vapor, alta presión">
                        <p class="pform-hint">Separa las palabras clave con comas</p>
                    </div>

                    <div class="pform-field" style="margin-bottom:0">
                        <label class="pform-label">URL Canónica</label>
                        <input type="url" class="pform-input" placeholder="https://simari.com/productos/...">
                        <p class="pform-hint">Opcional: URL preferida para evitar contenido duplicado</p>
                    </div>
                </div>

                {{-- Open Graph --}}
                <div class="pform-panel" style="margin-bottom:0">
                    <h2 class="pform-panel-title" style="display:flex;align-items:center;gap:8px">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                            fill="none" stroke="var(--secondary-color)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/>
                            <line x1="8.59" x2="15.42" y1="13.51" y2="17.49"/>
                            <line x1="15.41" x2="8.59" y1="6.51" y2="10.49"/>
                        </svg>
                        Redes Sociales (Open Graph)
                    </h2>

                    <div class="pform-field">
                        <label class="pform-label">Título para Redes Sociales</label>
                        <input type="text" class="pform-input" placeholder="Bomba de Calor Rinnai 20HP">
                    </div>

                    <div class="pform-field">
                        <label class="pform-label">Descripción para Redes Sociales</label>
                        <textarea class="pform-textarea" rows="3"
                            placeholder="Descripción que aparecerá cuando se comparta en Facebook, LinkedIn, etc."></textarea>
                    </div>

                    <div class="pform-field" style="margin-bottom:0">
                        <label class="pform-label">Imagen para Redes Sociales</label>
                        <input type="url" class="pform-input" placeholder="URL de la imagen (1200x630px recomendado)">
                        <p class="pform-hint">Recomendado: 1200x630px &bull; Máximo: 5MB &bull; Formato: JPG o PNG</p>
                    </div>
                </div>

                {{-- Google Preview + Analysis --}}
                <div class="pform-panel" style="margin-bottom:0">
                    <h2 class="pform-panel-title" style="display:flex;align-items:center;gap:8px">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                            fill="none" stroke="var(--secondary-color)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        Vista Previa en Google
                    </h2>

                    <div class="pform-google-preview">
                        <div class="pform-google-url">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/>
                                <path d="M2 12h20"/>
                            </svg>
                            simari.com › productos › <span id="pformSeoSlugPreview2">producto-ejemplo</span>
                        </div>
                        <h3 class="pform-google-title" id="pformGoogleTitle">Bomba de Calor Rinnai 20HP</h3>
                        <p class="pform-google-desc" id="pformGoogleDesc">Agrega una meta descripción para ver cómo se mostrará tu producto en los resultados de búsqueda de Google...</p>
                    </div>

                    <div class="pform-seo-analysis">
                        <h4 class="pform-seo-analysis-title">Análisis SEO</h4>
                        <div class="pform-seo-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" x2="12" y1="8" y2="12"/>
                                <line x1="12" x2="12.01" y1="16" y2="16"/>
                            </svg>
                            <span>Título SEO: Mejorar longitud (30-60 caracteres)</span>
                        </div>
                        <div class="pform-seo-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" x2="12" y1="8" y2="12"/>
                                <line x1="12" x2="12.01" y1="16" y2="16"/>
                            </svg>
                            <span>Meta Description: Mejorar longitud (120-160 caracteres)</span>
                        </div>
                        <div class="pform-seo-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" x2="12" y1="8" y2="12"/>
                                <line x1="12" x2="12.01" y1="16" y2="16"/>
                            </svg>
                            <span>URL Slug: Falta configurar</span>
                        </div>
                    </div>
                </div>

                {{-- Tips --}}
                <div class="pform-tips-box">
                    <p class="pform-tips-title">💡 Consejos de SEO</p>
                    <ul class="pform-tips-list">
                        <li>• Incluye palabras clave relevantes en el título SEO</li>
                        <li>• Escribe una meta description atractiva que invite al clic</li>
                        <li>• Usa URLs amigables y descriptivas (evita números y códigos)</li>
                        <li>• La imagen Open Graph debe ser de alta calidad (1200x630px)</li>
                        <li>• Revisa la vista previa antes de publicar</li>
                        <li>• Actualiza el SEO periódicamente según el rendimiento</li>
                    </ul>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script>
(function () {

    /* ── Tab switching ── */
    const tabs   = document.querySelectorAll('.pform-tab');
    const panels = document.querySelectorAll('.pform-tab-panel');

    tabs.forEach(tab => {
        tab.addEventListener('click', function () {
            tabs.forEach(t => t.classList.remove('active'));
            panels.forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            document.getElementById(this.dataset.tab).classList.add('active');
        });
    });

    /* ── Character counter (short desc) ── */
    const shortDesc = document.getElementById('pformShortDesc');
    const charCount = document.getElementById('pformCharCount');
    shortDesc.addEventListener('input', function () {
        charCount.textContent = this.value.length + '/200';
    });

    /* ── Quill editor ── */
    new Quill('#pformQuillEditor', {
        theme: 'snow',
        placeholder: 'Escribe la descripción detallada del producto. Puedes incluir títulos, listas, tablas, imágenes y enlaces...',
        modules: {
            toolbar: [
                [{ header: [1, 2, 3, 4, 5, 6, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                [{ align: [] }],
                ['link', 'image'],
                [{ color: [] }, { background: [] }],
                ['clean'],
            ]
        }
    });

    /* ── Back button ── */
    document.getElementById('pformBackBtn').addEventListener('click', function () {
        window.location.href = '/admin/products';
    });

    /* ── Save draft ── */
    document.getElementById('pformBtnDraft').addEventListener('click', function () {
        const badge = document.getElementById('pformSavedBadge');
        badge.style.color = '#16a34a';
        badge.textContent = '✓ Guardado';
        badge.classList.add('visible');
        clearTimeout(badge._timer);
        badge._timer = setTimeout(() => badge.classList.remove('visible'), 3000);
    });

    /* ── Publish ── */
    document.getElementById('pformBtnPublish').addEventListener('click', function () {
        const badge = document.getElementById('pformSavedBadge');
        badge.style.color = '#0054ff';
        badge.textContent = '✓ Publicado';
        badge.classList.add('visible');
        clearTimeout(badge._timer);
        badge._timer = setTimeout(() => badge.classList.remove('visible'), 3000);
    });

    /* ── Profitability card ── */
    const costInput  = document.getElementById('pformCost');
    const priceInput = document.getElementById('pformPrice');

    function updateProfit() {
        const cost   = parseFloat(costInput.value)  || 0;
        const price  = parseFloat(priceInput.value) || 0;
        const profit = price - cost;
        const margin = price > 0 ? ((profit / price) * 100).toFixed(2) : '0.00';
        document.getElementById('pformProfitCost').textContent   = '$' + cost.toLocaleString('es-MX');
        document.getElementById('pformProfitPrice').textContent  = '$' + price.toLocaleString('es-MX');
        document.getElementById('pformProfitUtil').textContent   = '$' + profit.toLocaleString('es-MX');
        document.getElementById('pformProfitMargin').textContent = margin + '%';
        const color = profit >= 0 ? '#16a34a' : '#dc2626';
        document.getElementById('pformProfitUtil').style.color   = color;
        document.getElementById('pformProfitMargin').style.color = color;
    }

    costInput.addEventListener('input', updateProfit);
    priceInput.addEventListener('input', updateProfit);

    /* ── Availability buttons ── */
    document.querySelectorAll('.pform-avail-row').forEach(function (row) {
        row.querySelectorAll('.pform-avail-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                row.querySelectorAll('.pform-avail-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });
    });

    /* ── Badge toggle cards ── */
    document.querySelectorAll('.pform-badge-card').forEach(function (card) {
        card.addEventListener('click', function () {
            this.classList.toggle('active');
        });
    });

    /* ── Tags ── */
    function addTag() {
        const input = document.getElementById('pformTagInput');
        const val   = input.value.trim();
        if (!val) return;
        const chip = document.createElement('span');
        chip.className   = 'pform-tag-chip';
        chip.textContent = val;
        chip.title       = 'Clic para eliminar';
        chip.addEventListener('click', function () { this.remove(); });
        document.getElementById('pformTagList').appendChild(chip);
        input.value = '';
        input.focus();
    }

    document.getElementById('pformTagAdd').addEventListener('click', addTag);
    document.getElementById('pformTagInput').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); addTag(); }
    });

    /* ── Specs ── */
    const addSpecBtn = document.getElementById('pformAddSpec');
    const specsList  = document.getElementById('pformSpecsList');
    const specsEmpty = document.getElementById('pformSpecsEmpty');

    addSpecBtn.addEventListener('click', function () {
        specsEmpty.style.display = 'none';
        specsList.style.display  = 'flex';

        const row = document.createElement('div');
        row.className = 'pform-spec-row';
        row.innerHTML =
            '<input type="text" class="pform-input" placeholder="Nombre del campo (ej: Potencia)">' +
            '<input type="text" class="pform-input" placeholder="Valor (ej: 20 HP)">' +
            '<button type="button" class="pform-spec-del" title="Eliminar">' +
                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                    '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>' +
                '</svg>' +
            '</button>';

        row.querySelector('.pform-spec-del').addEventListener('click', function () {
            row.remove();
            if (specsList.children.length === 0) {
                specsList.style.display = 'none';
                specsEmpty.style.display = 'flex';
            }
        });

        specsList.appendChild(row);
    });

    /* ── SEO modal ── */
    const seoModal = document.getElementById('pformSeoModal');

    document.getElementById('pformBtnSeo').addEventListener('click', function () {
        seoModal.style.display = 'flex';
    });

    document.getElementById('pformSeoClose').addEventListener('click', function () {
        seoModal.style.display = 'none';
    });

    /* ── SEO: title char counter ── */
    const seoTitle      = document.getElementById('pformSeoTitle');
    const seoTitleCount = document.getElementById('pformSeoTitleCount');

    seoTitle.addEventListener('input', function () {
        seoTitleCount.textContent = this.value.length + '/60';
        updateGooglePreview();
        updateSeoScore();
    });

    /* ── SEO: meta char counter ── */
    const seoMeta      = document.getElementById('pformSeoMeta');
    const seoMetaCount = document.getElementById('pformSeoMetaCount');

    seoMeta.addEventListener('input', function () {
        seoMetaCount.textContent = this.value.length + '/160';
        updateGooglePreview();
        updateSeoScore();
    });

    /* ── SEO: slug ── */
    const seoSlug = document.getElementById('pformSeoSlug');

    seoSlug.addEventListener('input', function () {
        const s = this.value || 'producto-ejemplo';
        document.getElementById('pformSeoSlugPreview').textContent  = s;
        document.getElementById('pformSeoSlugPreview2').textContent = s;
        updateSeoScore();
    });

    document.getElementById('pformSeoAutoSlug').addEventListener('click', function () {
        const src  = document.getElementById('pformName').value || seoTitle.value || 'producto-ejemplo';
        const slug = src.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9\s-]/g, '')
            .trim().replace(/\s+/g, '-');
        seoSlug.value = slug;
        seoSlug.dispatchEvent(new Event('input'));
    });

    function updateGooglePreview() {
        document.getElementById('pformGoogleTitle').textContent =
            seoTitle.value || 'Bomba de Calor Rinnai 20HP';
        document.getElementById('pformGoogleDesc').textContent =
            seoMeta.value || 'Agrega una meta descripción para ver cómo se mostrará tu producto en los resultados de búsqueda de Google...';
    }

    function updateSeoScore() {
        const tLen = seoTitle.value.length;
        const mLen = seoMeta.value.length;
        const sLen = seoSlug.value.length;
        let score  = 0;

        if (tLen >= 30 && tLen <= 60) score += 34;
        else if (tLen > 0)            score += 17;

        if (mLen >= 120 && mLen <= 160) score += 33;
        else if (mLen > 0)              score += 16;

        if (sLen > 0) score += 33;

        const scoreEl = document.getElementById('pformSeoScoreVal');
        scoreEl.textContent = score + '%';
        scoreEl.style.color = score >= 67 ? '#16a34a' : score >= 34 ? '#d97706' : '#dc2626';

        const items   = document.querySelectorAll('.pform-seo-item');
        const titleOk = tLen >= 30 && tLen <= 60;
        const metaOk  = mLen >= 120 && mLen <= 160;
        const slugOk  = sLen > 0;

        setCheck(items[0], titleOk,
            'Título SEO: ' + (titleOk ? 'Longitud óptima ✓' : 'Mejorar longitud (30-60 caracteres)'));
        setCheck(items[1], metaOk,
            'Meta Description: ' + (metaOk ? 'Longitud óptima ✓' : 'Mejorar longitud (120-160 caracteres)'));
        setCheck(items[2], slugOk,
            'URL Slug: ' + (slugOk ? 'Configurado ✓' : 'Falta configurar'));
    }

    function setCheck(el, ok, text) {
        el.querySelector('span').textContent = text;
        el.querySelector('svg').style.stroke = ok ? '#16a34a' : '#d97706';
    }

})();
</script>
@endpush
