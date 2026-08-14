@extends('admin.layouts.master')
@section('title')
    Editar Producto - Admin
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css">
    {{-- FIX (validación de servidor): create.blade.php ya trae este bloque
         (usado por su validación de cliente en pform-field-error /
         pform-error-msg — ver _scripts_create.blade.php showFieldError()),
         pero edit.blade.php nunca lo tuvo pese a usar los MISMOS nombres de
         clase en su propio _scripts.blade.php. Sin esto, marcar un campo
         como inválido (cliente o servidor) no tenía ningún efecto visual
         aquí — las clases se aplicaban pero ninguna regla CSS las estilaba.
         Copiado tal cual de create.blade.php, no es un archivo .css. --}}
    <style>
        .pform-input.pform-field-error,
        .pform-select.pform-field-error,
        .pform-textarea.pform-field-error {
            border-color: #dc2626 !important;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1) !important;
        }

        .pform-error-msg {
            color: #dc2626;
            font-size: 12px;
            margin-top: 4px;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .pform-error-msg::before {
            content: '';
            display: inline-block;
            width: 14px;
            height: 14px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23dc2626' stroke-width='2'%3E%3Ccircle cx='12' cy='12' r='10'/%3E%3Cline x1='12' y1='8' x2='12' y2='12'/%3E%3Cline x1='12' y1='16' x2='12.01' y2='16'/%3E%3C/svg%3E");
            background-size: contain;
            background-repeat: no-repeat;
            flex-shrink: 0;
        }
    </style>
@endpush

@section('content')
    <div class="pform-page">

        {{-- ── Sticky header ── --}}
        <div class="pform-page-header">

            <div class="pform-header-top">
                <div class="pform-header-left">
                    <button class="pform-back-btn" id="pformBackBtn" type="button" title="Volver">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="m12 19-7-7 7-7" />
                            <path d="M19 12H5" />
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
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.3-4.3" />
                        </svg>
                        SEO del Producto
                    </button>
                    <button class="pform-btn outline" id="pformBtnDraft" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path
                                d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" />
                            <path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7" />
                            <path d="M7 3v4a1 1 0 0 0 1 1h7" />
                        </svg>
                        Guardar Borrador
                    </button>
                    <button class="pform-btn primary" id="pformBtnPublish" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path
                                d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        Publicar Producto
                    </button>
                </div>
            </div>

            {{-- ── Tabs ── --}}
            <div class="pform-tabs-row" role="tablist">
                <button class="pform-tab active" data-tab="pformPanel0" type="button" role="tab">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path
                            d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z" />
                        <path d="M12 22V12" />
                        <polyline points="3.29 7 12 12 20.71 7" />
                        <path d="m7.5 4.27 9 5.15" />
                    </svg>
                    Información Básica
                </button>
                <button class="pform-tab" data-tab="pformPanel1" type="button" role="tab">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
                        <circle cx="9" cy="9" r="2" />
                        <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />
                    </svg>
                    Imágenes
                </button>
                <button class="pform-tab" data-tab="pformPanel2" type="button" role="tab">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <line x1="12" x2="12" y1="2" y2="22" />
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                    </svg>
                    Precio e Inventario
                </button>
                <button class="pform-tab" data-tab="pformPanel3" type="button" role="tab">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path
                            d="M20 10a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-2.5a1 1 0 0 1-.8-.4l-.9-1.2A1 1 0 0 0 15 3h-2a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1Z" />
                        <path
                            d="M20 21a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1h-2.9a1 1 0 0 1-.88-.55l-.42-.85a1 1 0 0 0-.92-.6H13a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1Z" />
                        <path d="M3 5a2 2 0 0 0 2 2h3" />
                        <path d="M3 3v13a2 2 0 0 0 2 2h3" />
                    </svg>
                    Organización
                </button>
                <button class="pform-tab" data-tab="pformPanel4" type="button" role="tab">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path
                            d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
                    </svg>
                    Especificaciones Técnicas
                </button>
                <button class="pform-tab" data-tab="pformPanel5" type="button" role="tab">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
                        <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                        <path d="M10 9H8" />
                        <path d="M16 13H8" />
                        <path d="M16 17H8" />
                    </svg>
                    Documentación
                </button>
                <button class="pform-tab" data-tab="pformPanel6" type="button" role="tab">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                    Proveedores
                </button>
            </div>
        </div>

        {{-- ── Form wrapping all panels ── --}}
        {{-- ── Scrollable content ── --}}
        <div class="pform-content-area">
            <form id="productEditForm" method="POST" action="{{ route('admin.products.update', $product->id) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="is_active" id="pformIsActive" value="{{ old('is_active', $product->is_active ? 1 : 0) }}">
                <input type="hidden" name="is_featured" id="pformIsFeatured" value="{{ old('is_featured', $product->is_featured ? 1 : 0) }}">
                <input type="hidden" name="is_new" id="pformIsNew" value="{{ old('is_new', $product->is_new ? 1 : 0) }}">
                <input type="hidden" name="is_recommended" id="pformIsRecommended" value="{{ old('is_recommended', $product->is_recommended ? 1 : 0) }}">
                <input type="hidden" name="publish_on_website" id="pformPublishOnWebsite" value="{{ old('publish_on_website', $product->publish_on_website ? 1 : 0) }}">
                <div class="pform-panel-wrap">
                    {{-- Panel 0: Información Básica --}}
                    <div class="pform-tab-panel active" id="pformPanel0" role="tabpanel">
                        <div class="pform-panel">
                            <h2 class="pform-panel-title">Información Básica del Producto</h2>

                            <div class="pform-field">
                                <div class="pform-label-row">
                                    <label class="pform-label" for="pformName">
                                        Nombre del Producto <span class="pform-required">*</span>
                                    </label>
                                    <button type="button" class="pform-insert-variable-btn" data-variable-target="pformName">{ } Insertar variable</button>
                                </div>
                                <input type="text" id="pformName" name="name" class="pform-input @error('name') pform-field-error @enderror"
                                    placeholder="Ej: Caldera Industrial Hyperion 500" value="{{ old('name', $product->name) }}"
                                    required />
                                @error('name')
                                    <p class="pform-error-msg">{{ $message }}</p>
                                @enderror
                                <p class="pform-hint">Este nombre aparecerá en el catálogo y en los resultados de búsqueda.
                                    Puedes usar variables como {marca} o {modelo}.
                                </p>
                            </div>

                            <div class="pform-grid-3">
                                <div class="pform-field">
                                    <label class="pform-label" for="pformSku">
                                        SKU <span class="pform-required">*</span>
                                    </label>
                                    <input type="text" id="pformSku" class="pform-input @error('sku') pform-field-error @enderror"
                                        value="{{ old('sku', $product->sku) }}" disabled
                                        style="background:#f9fafb;color:#6b7280;cursor:not-allowed;" />
                                    <input type="hidden" name="sku" value="{{ old('sku', $product->sku) }}" />
                                    @error('sku')
                                        <p class="pform-error-msg">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="pform-field">
                                    {{-- FIX BUG 7: added the missing asterisk — create already
                                         marks Marca as required, edit didn't even though the
                                         required attribute was already there. --}}
                                    <label class="pform-label" for="pformBrand">Marca <span class="pform-required">*</span></label>
                                    <select id="pformBrand" name="brand_id" class="pform-select @error('brand_id') pform-field-error @enderror" required>
                                        <option value="">Seleccionar...</option>
                                        @foreach ($brands as $brand)
                                            <option value="{{ $brand->id }}"
                                                {{ (string) old('brand_id', $product->brand_id) === (string) $brand->id ? 'selected' : '' }}>
                                                {{ $brand->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('brand_id')
                                        <p class="pform-error-msg">{{ $message }}</p>
                                    @enderror
                                    <p class="pform-hint">Selecciona la marca en la pestaña <strong>Organización</strong>
                                    </p>
                                </div>
                                <div class="pform-field">
                                    <label class="pform-label" for="pformModel">Modelo</label>
                                    <input type="text" id="pformModel" name="model" class="pform-input @error('model') pform-field-error @enderror"
                                        placeholder="Hyperion 500" value="{{ old('model', $product->model ?? '') }}" />
                                    @error('model')
                                        <p class="pform-error-msg">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="pform-field">
                                <div class="pform-label-row">
                                    <label class="pform-label" for="pformShortDesc">Descripción Corta</label>
                                    <button type="button" class="pform-insert-variable-btn" data-variable-target="pformShortDesc">{ } Insertar variable</button>
                                </div>
                                <textarea id="pformShortDesc" name="short_description" class="pform-textarea @error('short_description') pform-field-error @enderror" rows="3" maxlength="200"
                                    placeholder="Resumen breve del producto">{{ old('short_description', $product->short_description) }}</textarea>
                                @error('short_description')
                                    <p class="pform-error-msg">{{ $message }}</p>
                                @enderror
                                <div class="pform-char-row">
                                    <span class="pform-hint" style="margin:0">Máximo 200 caracteres</span>
                                    <span class="pform-char-count"
                                        id="pformCharCount">{{ strlen(old('short_description', $product->short_description ?? '')) }}/200</span>
                                </div>
                            </div>

                            <div class="pform-field">
                                <div class="pform-label-row">
                                    <label class="pform-label">Descripción Completa</label>
                                    <button type="button" class="pform-insert-variable-btn" data-variable-target="quill:pformQuillEditor">{ } Insertar variable</button>
                                </div>
                                <div class="pform-quill-wrap">
                                    <div id="pformQuillEditor"></div>
                                </div>
                                <input type="hidden" name="description" id="pformDescHidden"
                                    value="{{ old('description', $product->description) }}">
                                @error('description')
                                    <p class="pform-error-msg">{{ $message }}</p>
                                @enderror
                                <p class="pform-hint">Usa el editor para crear una descripción rica con formato. Puedes usar variables como {marca} o {modelo}.</p>
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
                            <p class="pform-hint" style="margin-bottom:24px">Sube y organiza las imágenes de tu producto.
                                Arrastra las miniaturas para reordenar.
                            </p>

                            <div class="pform-dropzone" id="pformDropzone">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"
                                    viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                    <polyline points="17 8 12 3 7 8" />
                                    <line x1="12" x2="12" y1="3" y2="15" />
                                </svg>
                                <p class="pform-dropzone-text">Haz clic para agregar una imagen o arrástrala aquí</p>
                                {{-- FIX (reported bug): text said 10MB but the server rule
                                     (images.* => image|mimes:jpeg,jpg,png|max:2048) only allows
                                     2MB (2048 KB) — the UI was promising 5x more than it accepts. --}}
                                <p class="pform-dropzone-sub">PNG, JPG, JPEG hasta 2MB por imagen, o pega una URL</p>
                                <input type="file" id="pformImageInput" name="images[]"
                                    accept="image/png,image/jpeg,image/jpg" multiple style="display:none">
                            </div>
                            @foreach ($errors->keys() as $errorKey)
                                @if (\Illuminate\Support\Str::startsWith($errorKey, 'images') || \Illuminate\Support\Str::startsWith($errorKey, 'image_urls'))
                                    <p class="pform-error-msg">{{ $errors->first($errorKey) }}</p>
                                @endif
                            @endforeach

                            <div id="pformImageUrlInputs" style="display:none"></div>
                            <div id="pformImageOrderInputs" style="display:none"></div>

                            {{-- Imágenes existentes — arrastra para reordenar; el orden se
                                 guarda al instante (AJAX), sin esperar a "Guardar Cambios". --}}
                            @if($product->images->count())
                            <div id="pformExistingGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:12px;margin-top:16px;">
                                @foreach($product->images as $img)
                                <div class="pform-img-item pform-existing-img" data-id="{{ $img->id }}" draggable="true" title="Arrastra para reordenar">
                                    <img src="{{ $img->url }}" alt="" class="pform-img-item-thumb">
                                    @if($loop->first)
                                        <span class="pform-img-item-badge">Portada</span>
                                    @endif
                                    <div class="pform-img-item-info">Imagen #{{ $loop->iteration }}</div>
                                    <input type="checkbox" name="delete_images[]" value="{{ $img->id }}" id="delImg{{ $img->id }}" style="display:none">
                                    <button type="button" class="pform-img-item-remove pform-del-existing"
                                        onclick="toggleDeleteImg({{ $img->id }})">✕</button>
                                </div>
                                @endforeach
                            </div>
                            @endif

                            {{-- Grid de nuevas imágenes a subir --}}
                            <div id="pformImageGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:12px;margin-top:16px;"></div>

                            <div class="pform-placeholder" id="pformImagePlaceholder" style="padding:40px 32px;{{ $product->images->count() ? 'display:none' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56"
                                    viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
                                    <circle cx="9" cy="9" r="2" />
                                    <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />
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
                                <li>• La primera imagen será la que aparezca en el catálogo</li>
                            </ul>
                        </div>
                    </div>

                    {{-- Panel 2: Precio e Inventario --}}
                    <div class="pform-tab-panel" id="pformPanel2" role="tabpanel">
                        <div class="pform-panel">
                            <h2 class="pform-panel-title">Precio e Inventario</h2>

                            <div class="pform-section">
                                <h3 class="pform-section-title">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                        viewBox="0 0 24 24" fill="none" stroke="var(--secondary-color)"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="12" x2="12" y1="2" y2="22" />
                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                                    </svg>
                                    Precios
                                </h3>

                                <div class="pform-grid-2">
                                    <div class="pform-field">
                                        <label class="pform-label">Costo del Artículo <span
                                                class="pform-required">*</span></label>
                                        <div class="pform-price-wrap">
                                            <span class="pform-price-prefix">$</span>
                                            <input type="number" id="pformCost" name="cost"
                                                class="pform-input pform-input-prefixed @error('cost') pform-field-error @enderror" placeholder="0.00"
                                                step="0.01" min="0" value="{{ old('cost', $product->cost ?? '') }}"
                                                required>
                                        </div>
                                        @error('cost')
                                            <p class="pform-error-msg">{{ $message }}</p>
                                        @enderror
                                        <p class="pform-hint">Costo de adquisición o producción</p>
                                    </div>
                                    <div class="pform-field">
                                        <label class="pform-label">Precio de Venta <span
                                                class="pform-required">*</span></label>
                                        <div class="pform-price-wrap">
                                            <span class="pform-price-prefix">$</span>
                                            <input type="number" id="pformPrice" name="price"
                                                class="pform-input pform-input-prefixed @error('price') pform-field-error @enderror" placeholder="0.00"
                                                step="0.01" min="0" value="{{ old('price', $product->price ?? '') }}"
                                                required>
                                        </div>
                                        @error('price')
                                            <p class="pform-error-msg">{{ $message }}</p>
                                        @enderror
                                        <p class="pform-hint">Precio público del producto</p>
                                    </div>
                                </div>

                                <div class="pform-field">
                                    <label style="display:flex;align-items:center;gap:8px;font-size:14px;font-weight:500;color:#374151;cursor:pointer">
                                        <input type="checkbox" id="pformPriceIncludesTax" name="price_includes_tax" value="1" style="width:auto"
                                            {{ $product->price_includes_tax ? 'checked' : '' }}>
                                        ¿Este precio ya incluye IVA?
                                    </label>
                                    <p class="pform-hint">Si no lo marcas, el sistema sumará el IVA para calcular el precio final que verá el cliente.</p>
                                </div>

                                <div class="pform-field">
                                    <label class="pform-label">Costo de Envío (opcional)</label>
                                    <div class="pform-price-wrap">
                                        <span class="pform-price-prefix">$</span>
                                        <input type="number" id="pformShippingCost" name="shipping_cost"
                                            class="pform-input pform-input-prefixed @error('shipping_cost') pform-field-error @enderror" placeholder="0.00"
                                            step="0.01" min="0" value="{{ old('shipping_cost', $product->shipping_cost ?? '') }}">
                                    </div>
                                    @error('shipping_cost')
                                        <p class="pform-error-msg">{{ $message }}</p>
                                    @enderror
                                    <p class="pform-hint">Déjalo vacío para envío estándar. Solo llénalo si este producto tiene un cargo de flete especial.</p>
                                </div>

                                <div class="pform-field">
                                    <label class="pform-label">Envío gratis a partir de (opcional)</label>
                                    <div class="pform-price-wrap">
                                        <span class="pform-price-prefix">$</span>
                                        <input type="number" id="pformFreeShippingThreshold" name="free_shipping_threshold"
                                            class="pform-input pform-input-prefixed @error('free_shipping_threshold') pform-field-error @enderror" placeholder="0.00"
                                            step="0.01" min="0" value="{{ old('free_shipping_threshold', $product->free_shipping_threshold ?? '') }}">
                                    </div>
                                    @error('free_shipping_threshold')
                                        <p class="pform-error-msg">{{ $message }}</p>
                                    @enderror
                                    <p class="pform-hint">Si el producto ya tiene costo de envío, se vuelve gratis cuando el carrito alcance este monto. Déjalo vacío si no aplica.</p>
                                </div>

                                <div class="pform-grid-2">
                                    <div class="pform-field">
                                        <label class="pform-label">Precio de Oferta</label>
                                        <div class="pform-price-wrap">
                                            <span class="pform-price-prefix">$</span>
                                            <input type="number" name="compare_price"
                                                class="pform-input pform-input-prefixed @error('compare_price') pform-field-error @enderror" placeholder="0.00"
                                                step="0.01" min="0"
                                                value="{{ old('compare_price', $product->compare_price ?? '') }}">
                                        </div>
                                        @error('compare_price')
                                            <p class="pform-error-msg">{{ $message }}</p>
                                        @enderror
                                        <p class="pform-hint">Opcional: precio en promoción</p>
                                    </div>
                                    <div class="pform-field">
                                        <label class="pform-label">Moneda</label>
                                        {{-- FIX BUG 9: added name="currency" + preselected value —
                                             existed but was never submitted nor recovered. --}}
                                        <select class="pform-select @error('currency') pform-field-error @enderror" name="currency">
                                            <option value="MXN" {{ old('currency', $product->currency ?? 'MXN') == 'MXN' ? 'selected' : '' }}>MXN - Peso Mexicano</option>
                                            <option value="USD" {{ old('currency', $product->currency ?? 'MXN') == 'USD' ? 'selected' : '' }}>USD - Dólar Americano</option>
                                        </select>
                                        @error('currency')
                                            <p class="pform-error-msg">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div class="pform-profit-card">
                                    <h4 class="pform-profit-title">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                            viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
                                            <polyline points="16 7 22 7 22 13" />
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
                                            <p class="pform-profit-value" style="color:var(--secondary-color)"
                                                id="pformProfitPrice">$0</p>
                                        </div>
                                        <div>
                                            <p class="pform-profit-label">Utilidad</p>
                                            <p class="pform-profit-value" style="color:#16a34a" id="pformProfitUtil">$0
                                            </p>
                                        </div>
                                    </div>
                                    <div class="pform-profit-margin">
                                        <p class="pform-profit-label">Margen de Ganancia</p>
                                        <p class="pform-profit-margin-value" id="pformProfitMargin">0%</p>
                                    </div>
                                </div>

                                {{-- Desglose de IVA --}}
                                <div class="pform-profit-card" style="margin-top:12px;">
                                    <h4 class="pform-profit-title">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                            viewBox="0 0 24 24" fill="none" stroke="var(--secondary-color)" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="12" x2="12" y1="2" y2="22" />
                                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                                        </svg>
                                        Desglose de IVA
                                    </h4>
                                    <p class="pform-profit-label" id="pformExchangeRateHint" style="display:none; margin-bottom:8px;"></p>
                                    <div class="pform-profit-grid">
                                        <div>
                                            <p class="pform-profit-label">Precio base</p>
                                            <p class="pform-profit-value" id="pformIvaBase">$0</p>
                                        </div>
                                        <div>
                                            <p class="pform-profit-label" id="pformIvaRateLabel">IVA (16%)</p>
                                            <p class="pform-profit-value" id="pformIvaAmount">$0</p>
                                        </div>
                                        <div>
                                            <p class="pform-profit-label">Precio final</p>
                                            <p class="pform-profit-value" style="color:var(--secondary-color)" id="pformIvaFinal">$0</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="pform-section">
                                <h3 class="pform-section-title">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                        viewBox="0 0 24 24" fill="none" stroke="var(--secondary-color)"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path
                                            d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z" />
                                        <path d="M12 22V12" />
                                        <polyline points="3.29 7 12 12 20.71 7" />
                                        <path d="m7.5 4.27 9 5.15" />
                                    </svg>
                                    Inventario
                                </h3>

                                <div class="pform-grid-3">
                                    <div class="pform-field">
                                        <label class="pform-label">SKU <span class="pform-required">*</span></label>
                                        <input type="text" name="sku_inventory" class="pform-input @error('sku') pform-field-error @enderror"
                                            placeholder="PROD-001" value="{{ old('sku', $product->sku) }}" required disabled>
                                    </div>
                                    <div class="pform-field">
                                        <label class="pform-label">Inventario Disponible <span
                                                class="pform-required">*</span></label>
                                        <input type="number" name="stock" class="pform-input @error('stock') pform-field-error @enderror" placeholder="0"
                                            min="0" value="{{ old('stock', $product->stock ?? 0) }}" required>
                                        @error('stock')
                                            <p class="pform-error-msg">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="pform-field">
                                        <label class="pform-label">Unidad de Medida</label>
                                        {{-- FIX BUG 9: added name="stock_unit" + preselected value —
                                             existed but was never submitted nor recovered. --}}
                                        <select class="pform-select @error('stock_unit') pform-field-error @enderror" name="stock_unit">
                                            <option value="pieza" {{ old('stock_unit', $product->stock_unit ?? 'pieza') == 'pieza' ? 'selected' : '' }}>Pieza</option>
                                            <option value="juego" {{ old('stock_unit', $product->stock_unit ?? 'pieza') == 'juego' ? 'selected' : '' }}>Juego</option>
                                            <option value="kit" {{ old('stock_unit', $product->stock_unit ?? 'pieza') == 'kit' ? 'selected' : '' }}>Kit</option>
                                            <option value="metro" {{ old('stock_unit', $product->stock_unit ?? 'pieza') == 'metro' ? 'selected' : '' }}>Metro</option>
                                            <option value="kg" {{ old('stock_unit', $product->stock_unit ?? 'pieza') == 'kg' ? 'selected' : '' }}>Kilogramo</option>
                                            <option value="litro" {{ old('stock_unit', $product->stock_unit ?? 'pieza') == 'litro' ? 'selected' : '' }}>Litro</option>
                                        </select>
                                        @error('stock_unit')
                                            <p class="pform-error-msg">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div class="pform-field">
                                    <label class="pform-label">Punto de Reorden (opcional)</label>
                                    <input type="number" id="pformReorderPoint" name="reorder_point"
                                        class="pform-input @error('reorder_point') pform-field-error @enderror" placeholder="Ej. 10"
                                        min="0" step="1" value="{{ old('reorder_point', $product->reorder_point) }}">
                                    @error('reorder_point')
                                        <p class="pform-error-msg">{{ $message }}</p>
                                    @enderror
                                    <p class="pform-hint">Déjalo vacío si no quieres alertas de stock bajo para este producto. Se usa en Estadísticas &gt; Inventario.</p>
                                </div>

                                <div class="pform-field" style="margin-bottom:0">
                                    <label class="pform-label">Estado de Disponibilidad</label>
                                    <input type="hidden" name="availability" id="pformAvailability" value="{{ old('availability', $product->availability ?? 'available') }}">
                                    <div class="pform-avail-row">
                                        <button type="button"
                                            class="pform-avail-btn {{ old('availability', $product->availability ?? 'available') == 'available' ? 'active' : '' }}">
                                            <div class="pform-avail-btn-title">Disponible</div>
                                            <div class="pform-avail-btn-sub">En stock y listo para envío</div>
                                        </button>
                                        <button type="button" class="pform-avail-btn {{ old('availability', $product->availability ?? 'available') == 'on_order' ? 'active' : '' }}">
                                            <div class="pform-avail-btn-title">Bajo Pedido</div>
                                            <div class="pform-avail-btn-sub">Se fabrica al recibir orden</div>
                                        </button>
                                        <button type="button"
                                            class="pform-avail-btn {{ old('availability', $product->availability ?? 'available') == 'out_of_stock' ? 'active' : '' }}">
                                            <div class="pform-avail-btn-title">Agotado</div>
                                            <div class="pform-avail-btn-sub">Sin inventario disponible</div>
                                        </button>
                                    </div>
                                    @error('availability')
                                        <p class="pform-error-msg">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Panel 3: Organización --}}
                    <div class="pform-tab-panel" id="pformPanel3" role="tabpanel">
                        <div class="pform-panel">
                            <h2 class="pform-panel-title">Organización del Producto</h2>

                            <div class="pform-section">
                                <h3 class="pform-section-title">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                        viewBox="0 0 24 24" fill="none" stroke="var(--secondary-color)"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path
                                            d="M20 10a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-2.5a1 1 0 0 1-.8-.4l-.9-1.2A1 1 0 0 0 15 3h-2a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1Z" />
                                        <path
                                            d="M20 21a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1h-2.9a1 1 0 0 1-.88-.55l-.42-.85a1 1 0 0 0-.92-.6H13a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1Z" />
                                        <path d="M3 5a2 2 0 0 0 2 2h3" />
                                        <path d="M3 3v13a2 2 0 0 0 2 2h3" />
                                    </svg>
                                    Categorización del Catálogo
                                </h3>

                                <div class="pform-grid-3">
                                    {{-- Categoría principal --}}
                                    <div class="pform-field">
                                        <label class="pform-label">Categoría Principal <span
                                                class="pform-required">*</span></label>
                                        <select class="pform-select @error('category_id') pform-field-error @enderror" id="pformCategoryMain" required>
                                            <option value="">Seleccionar...</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}">
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Subcategoría --}}
                                    <div class="pform-field">
                                        <label class="pform-label">Subcategoría</label>
                                        <select class="pform-select" id="pformCategorySub"
                                            {{ $product->category_id ? '' : 'disabled' }}>
                                            <option value="">Seleccionar categoría primero...</option>
                                        </select>
                                    </div>

                                    {{-- Categoría hija --}}
                                    <div class="pform-field">
                                        <label class="pform-label">Categoría Hija</label>
                                        <select class="pform-select" id="pformCategoryChild"
                                            {{ $product->category_id ? '' : 'disabled' }}>
                                            <option value="">Seleccionar subcategoría primero...</option>
                                        </select>
                                    </div>
                                </div>

                                <input type="hidden" name="category_id" id="pformCategoryIdHidden" value="{{ old('category_id', $product->category_id) }}">

                                <div class="pform-breadcrumb">
                                    <span style="color:#6b7280">Ruta de navegación:</span>
                                    <strong style="color:#111827;margin-left:4px" id="pformBreadcrumbText">
                                        Catálogo{{ $product->category ? ' > ' . $product->category->name : '' }}
                                    </strong>
                                </div>
                                @error('category_id')
                                    <p class="pform-error-msg">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Etiquetas --}}
                            <div class="pform-section">
                                <h3 class="pform-section-title">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                        viewBox="0 0 24 24" fill="none" stroke="var(--secondary-color)"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path
                                            d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z" />
                                        <circle cx="7.5" cy="7.5" r=".5" fill="currentColor" />
                                    </svg>
                                    Etiquetas
                                </h3>
                                <div class="pform-field" style="margin-bottom:0">
                                    <label class="pform-label">Agregar Etiquetas</label>
                                    <div class="pform-tag-row">
                                        <div class="pform-tag-input-wrap">
                                            <input type="text" id="pformTagInput" class="pform-input"
                                                placeholder="Ejemplo: eficiente, industrial, premium..." autocomplete="off">
                                            <ul class="pform-tag-suggestions" id="pformTagSuggestions"></ul>
                                        </div>
                                        <button type="button" id="pformTagAdd"
                                            class="pform-btn primary">Agregar</button>
                                    </div>
                                    <p class="pform-hint">Las etiquetas ayudan a los clientes a encontrar el producto.</p>
                                    <div class="pform-tag-chips" id="pformTagList"></div>
                                    {{-- FIX BUG 3: hidden field synced by JS with the JSON-encoded
                                         tag chips; JS also pre-populates the chips from
                                         $product->tags on page load. --}}
                                    <input type="hidden" name="tags" id="pformTagsHidden" value="">
                                </div>
                            </div>

                            {{-- Badges --}}
                            <div class="pform-section">
                                <h3 class="pform-section-title">Destacados y Badges</h3>
                                <div class="pform-grid-3">

                                    <button type="button"
                                        class="pform-badge-card {{ old('is_featured', $product->is_featured) ? 'active' : '' }}"
                                        id="badgeFeatured">
                                        <div class="pform-badge-card-header">
                                            <div class="pform-badge-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path
                                                        d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="pform-badge-name">Destacado</div>
                                                <div class="pform-toggle"></div>
                                            </div>
                                        </div>
                                        <p class="pform-badge-sub">Aparecerá en la sección de productos destacados</p>
                                    </button>

                                    <button type="button"
                                        class="pform-badge-card {{ old('is_new', $product->is_new) ? 'active' : '' }}"
                                        id="badgeNew">
                                        <div class="pform-badge-card-header">
                                            <div class="pform-badge-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path
                                                        d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z" />
                                                    <path d="M20 3v4" />
                                                    <path d="M22 5h-4" />
                                                    <path d="M4 17v2" />
                                                    <path d="M5 18H3" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="pform-badge-name">Nuevo</div>
                                                <div class="pform-toggle"></div>
                                            </div>
                                        </div>
                                        <p class="pform-badge-sub">Mostrará badge de "Nuevo" en el producto</p>
                                    </button>

                                    <button type="button"
                                        class="pform-badge-card {{ old('is_recommended', $product->is_recommended) ? 'active' : '' }}"
                                        id="badgeRecommended">
                                        <div class="pform-badge-card-header">
                                            <div class="pform-badge-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M7 10v12" />
                                                    <path
                                                        d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="pform-badge-name">Recomendado</div>
                                                <div class="pform-toggle"></div>
                                            </div>
                                        </div>
                                        <p class="pform-badge-sub">Aparecerá en sugerencias y recomendaciones</p>
                                    </button>

                                    <button type="button"
                                        class="pform-badge-card {{ old('publish_on_website', $product->publish_on_website) ? 'active' : '' }}"
                                        id="badgePublishOnWebsite">
                                        <div class="pform-badge-card-header">
                                            <div class="pform-badge-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="10" />
                                                    <path d="M2 12h20" />
                                                    <path
                                                        d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="pform-badge-name">Publicar en sitio web</div>
                                                <div class="pform-toggle"></div>
                                            </div>
                                        </div>
                                        <p class="pform-badge-sub">Se mostrará en el catálogo público del sitio web</p>
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
                                    <h2 class="pform-panel-title"
                                        style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22"
                                            viewBox="0 0 24 24" fill="none" stroke="var(--secondary-color)"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path
                                                d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
                                        </svg>
                                        Especificaciones Técnicas
                                    </h2>
                                    <p class="pform-hint" style="margin:0">Define las características técnicas del
                                        producto industrial</p>
                                </div>
                                <button type="button" class="pform-btn primary" id="pformAddSpec">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M5 12h14" />
                                        <path d="M12 5v14" />
                                    </svg>
                                    Agregar Campo
                                </button>
                            </div>

                            <div class="pform-placeholder" id="pformSpecsEmpty">
                                <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56"
                                    viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path
                                        d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
                                </svg>
                                <p class="pform-placeholder-title">No hay especificaciones técnicas</p>
                                <p class="pform-placeholder-sub">Agrega campos técnicos para definir las características
                                    del producto</p>
                            </div>

                            <div id="pformSpecsList" class="pform-spec-list" style="display:none"></div>
                        </div>

                        <div class="pform-alert-box">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="pform-alert-box-icon">
                                <circle cx="12" cy="12" r="10" />
                                <line x1="12" x2="12" y1="8" y2="12" />
                                <line x1="12" x2="12.01" y1="16" y2="16" />
                            </svg>
                            <div class="pform-alert-box-body">
                                <h4>Importante para productos industriales</h4>
                                <p>Las especificaciones técnicas son fundamentales para productos B2B.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Panel 5: Documentación --}}
                    <div class="pform-tab-panel" id="pformPanel5" role="tabpanel">
                        <div class="pform-panel">
                            <h2 class="pform-panel-title">Documentación Técnica</h2>
                            <p class="pform-hint" style="margin-bottom:24px">Sube archivos PDF y documentos relacionados
                                con el producto</p>

                            {{-- FIX (Documentación tab): each row now carries a real field
                                 name (mapped 1:1 to product_documents.type) and, if the
                                 product already has a document of that type, shows it with
                                 a download link — previously always looked empty even when
                                 documents existed, because nothing ever saved them. --}}
                            <div class="pform-doc-list">
                                @foreach ([
                                    ['Ficha Técnica (PDF)', 'Documento con las especificaciones técnicas detalladas', 'ficha', 'doc_ficha', '.pdf'],
                                    ['Manual de Instalación', 'Guía paso a paso para la instalación del producto', 'manual', 'doc_manual', '.pdf'],
                                    ['Catálogo del Producto', 'Catálogo comercial con información del producto', 'catalogo', 'doc_catalogo', '.pdf'],
                                    ['Certificaciones', 'Certificados de calidad, normas y homologaciones', 'certificacion', 'doc_certificacion', '.pdf'],
                                    ['Garantía', 'Documento de términos de garantía', 'garantia', 'doc_garantia', '.pdf'],
                                    ['Documento Adicional', 'Cualquier otro documento técnico relevante', 'otro', 'doc_otro', '.pdf,.doc,.docx'],
                                ] as $doc)
                                    @php $existingDoc = $product->documents->firstWhere('type', $doc[2]); @endphp
                                    <div class="pform-doc-row">
                                        <div class="pform-doc-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
                                                <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                                                <path d="M10 9H8" />
                                                <path d="M16 13H8" />
                                                <path d="M16 17H8" />
                                            </svg>
                                        </div>
                                        <div class="pform-doc-info">
                                            <h3>{{ $doc[0] }}</h3>
                                            @if ($existingDoc)
                                                <p>
                                                    <a href="{{ $existingDoc->url }}" target="_blank" rel="noopener"
                                                        style="color:#1d4ed8">📎 {{ $existingDoc->original_name }}</a>
                                                    — sube otro para reemplazarlo
                                                </p>
                                            @else
                                                <p>{{ $doc[1] }}</p>
                                            @endif
                                        </div>
                                        <label class="pform-doc-upload">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                                <polyline points="17 8 12 3 7 8" />
                                                <line x1="12" x2="12" y1="3" y2="15" />
                                            </svg>
                                            Subir
                                            <input type="file" hidden accept="{{ $doc[4] }}" name="{{ $doc[3] }}" class="pform-doc-input">
                                        </label>
                                        @error($doc[3])
                                            <p class="pform-error-msg">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>

                            <div class="pform-reco-box">
                                <h4>💡 Recomendaciones</h4>
                                <ul>
                                    <li>• Los documentos PDF son más accesibles y profesionales</li>
                                    <li>• Incluye siempre la ficha técnica del producto</li>
                                    <li>• Las certificaciones generan confianza en clientes B2B</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- Panel 6: Proveedores --}}
                    <div class="pform-tab-panel" id="pformPanel6" role="tabpanel">
                        <div class="pform-panel">
                            <div class="pform-specs-header">
                                <div>
                                    <h2 class="pform-panel-title">Proveedores</h2>
                                    <p class="pform-hint" style="margin:0">Proveedores que surten este producto, cada
                                        uno con su propio SKU y costo. El marcado como "Principal" es el que se usa en
                                        exportaciones y en la variable {proveedor_sku}.</p>
                                </div>
                                <button type="button" class="pform-btn primary" id="pformAddSupplier">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M5 12h14" />
                                        <path d="M12 5v14" />
                                    </svg>
                                    Asignar Proveedor
                                </button>
                            </div>

                            <div class="pform-placeholder" id="pformSuppliersEmpty"
                                style="{{ $product->suppliers->count() ? 'display:none' : '' }}">
                                <p class="pform-placeholder-title">No hay proveedores asignados</p>
                                <p class="pform-placeholder-sub">Asigna proveedores para llevar su SKU y costo por
                                    separado</p>
                            </div>

                            <div id="pformSuppliersList" class="pform-spec-list"
                                style="{{ $product->suppliers->count() ? '' : 'display:none' }}">
                                @foreach ($product->suppliers as $supplier)
                                    <div class="pform-supplier-row"
                                        data-pivot-id="{{ $supplier->pivot->id }}"
                                        data-supplier-id="{{ $supplier->id }}"
                                        data-supplier-name="{{ $supplier->company_name }}"
                                        data-sku="{{ $supplier->pivot->sku }}"
                                        data-cost="{{ $supplier->pivot->cost }}"
                                        data-lead-time="{{ $supplier->pivot->lead_time_days }}"
                                        data-is-primary="{{ $supplier->pivot->is_primary ? 1 : 0 }}">
                                        <div class="pform-supplier-row__info">
                                            <strong>{{ $supplier->company_name }}</strong>
                                            @if ($supplier->pivot->is_primary)
                                                <span class="pform-supplier-badge">Principal</span>
                                            @endif
                                            <div class="pform-supplier-row__meta">
                                                SKU: {{ $supplier->pivot->sku ?? '—' }}
                                                &middot; Costo: {{ $supplier->pivot->cost !== null ? '$' . number_format($supplier->pivot->cost, 2) : '—' }}
                                                &middot; Lead time: {{ $supplier->pivot->lead_time_days ?? '—' }} días
                                            </div>
                                        </div>
                                        <div class="pform-supplier-row__actions">
                                            <button type="button" class="pform-spec-del pform-supplier-edit" title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                                            </button>
                                            <button type="button" class="pform-spec-del pform-supplier-delete" title="Eliminar">✕</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>

        {{-- cierra productEditForm --}}

        {{-- ── SEO Modal — fuera del form principal ── --}}
        <div class="pform-seo-modal" id="pformSeoModal" style="display:none">
            <div class="pform-seo-header">
                <div class="pform-seo-header-top">
                    <button class="pform-back-btn" id="pformSeoClose" type="button" title="Cerrar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="m12 19-7-7 7-7" />
                            <path d="M19 12H5" />
                        </svg>
                    </button>
                    <div style="flex:1">
                        <h1 class="pform-title">SEO del Producto</h1>
                        <p class="pform-hint" style="margin:0">Optimiza el producto para motores de búsqueda</p>
                    </div>
                    <div class="pform-seo-score-wrap">
                        <div class="pform-seo-score" id="pformSeoScoreVal">0%</div>
                        <p class="pform-hint" style="margin:4px 0 0;text-align:center">Puntuación SEO</p>
                    </div>
                    {{-- FIX BUG 6: convenience save button for the Marketing team so
                         they don't have to close the SEO panel to save. It does not
                         submit on its own — it triggers the same validated "Publicar
                         Producto" flow via a programmatic click, so no logic is
                         duplicated and the rest of the form is never ignored. --}}
                    <button type="button" class="pform-btn primary" id="pformSeoSaveBtn">
                        Aplicar cambios SEO
                    </button>
                </div>
            </div>

            <div class="pform-seo-content">
                <div class="pform-seo-wrap">

                    <div class="pform-panel" style="margin-bottom:0">
                        <h2 class="pform-panel-title">SEO Básico</h2>

                        <div class="pform-field">
                            <div class="pform-label-row">
                                <label class="pform-label" for="pformSeoTitle">Título SEO <span
                                        class="pform-required">*</span></label>
                                <button type="button" class="pform-insert-variable-btn" data-variable-target="pformSeoTitle">{ } Insertar variable</button>
                            </div>
                            <input type="text" id="pformSeoTitle" name="seo_title" class="pform-input @error('seo_title') pform-field-error @enderror"
                                placeholder="Bomba de Calor Rinnai 20HP" maxlength="60"
                                value="{{ old('seo_title', $product->seo_title ?? '') }}" form="productEditForm">
                            @error('seo_title')
                                <p class="pform-error-msg">{{ $message }}</p>
                            @enderror
                            <div class="pform-char-row">
                                <span class="pform-hint" style="margin:0">Óptimo: 30-60 caracteres</span>
                                <span class="pform-char-count"
                                    id="pformSeoTitleCount">{{ strlen(old('seo_title', $product->seo_title ?? '')) }}/60</span>
                            </div>
                        </div>

                        <div class="pform-field">
                            <label style="display:flex;align-items:center;gap:8px;font-size:14px;font-weight:500;color:#374151;cursor:pointer">
                                <input type="checkbox" id="pformIsCanonical" name="is_canonical" value="1" style="width:auto"
                                    {{ old('is_canonical', $product->canonical_url ? '0' : '1') == '1' ? 'checked' : '' }}
                                    form="productEditForm">
                                Es la URL Canónica de este producto
                            </label>
                            <p class="pform-hint">Marcado (normal): Google usa la URL de este mismo producto.
                                Desmárcalo solo si este producto es muy parecido a otro que ya existe y quieres que
                                Google indexe ese otro producto en su lugar.</p>
                        </div>

                        <div class="pform-field" id="pformCanonicalUrlWrap" style="{{ $product->canonical_url ? '' : 'display:none' }}">
                            <label class="pform-label" for="pformCanonicalUrl">URL Canónica</label>
                            <input type="url" id="pformCanonicalUrl" name="canonical_url" class="pform-input @error('canonical_url') pform-field-error @enderror"
                                maxlength="255" placeholder="https://equitermindustries.com.mx/producto/otro-producto-similar"
                                value="{{ old('canonical_url', $product->canonical_url ?? '') }}" form="productEditForm">
                            @error('canonical_url')
                                <p class="pform-error-msg">{{ $message }}</p>
                            @enderror
                            <p class="pform-hint">A qué producto debe apuntar la señal de Google.</p>
                        </div>

                        <div class="pform-field">
                            <label class="pform-label" for="pformSeoSlug">URL Slug</label>
                            <div class="pform-tag-row">
                                <input type="text" id="pformSeoSlug" name="slug" class="pform-input @error('slug') pform-field-error @enderror"
                                    placeholder="producto-ejemplo" value="{{ old('slug', $product->slug ?? '') }}"
                                    form="productEditForm">
                                <button type="button" id="pformSeoAutoSlug" class="pform-btn outline">Generar
                                    Auto</button>
                            </div>
                            @error('slug')
                                <p class="pform-error-msg">{{ $message }}</p>
                            @enderror
                            <p class="pform-hint">URL: <span style="color:#1d4ed8">simari.com/productos/<span
                                        id="pformSeoSlugPreview">{{ old('slug', $product->slug ?? 'producto-ejemplo') }}</span></span>
                            </p>
                            <label style="display:flex;align-items:center;gap:8px;font-size:14px;font-weight:500;color:#374151;cursor:pointer;margin-top:8px">
                                <input type="checkbox" id="pformRedirectOldSlug" name="redirect_old_slug" value="1" style="width:auto"
                                    {{ old('redirect_old_slug', '1') == '1' ? 'checked' : '' }} form="productEditForm">
                                Redirigir la URL anterior a la nueva (recomendado para SEO)
                            </label>
                        </div>

                        <div class="pform-field">
                            <div class="pform-label-row">
                                <label class="pform-label" for="pformSeoMeta">Meta Description <span
                                        class="pform-required">*</span></label>
                                <button type="button" class="pform-insert-variable-btn" data-variable-target="pformSeoMeta">{ } Insertar variable</button>
                            </div>
                            <textarea id="pformSeoMeta" name="seo_description" class="pform-textarea @error('seo_description') pform-field-error @enderror" rows="3" maxlength="160"
                                placeholder="Descripción breve que aparecerá en los resultados de búsqueda de Google" form="productEditForm">{{ old('seo_description', $product->seo_description ?? '') }}</textarea>
                            @error('seo_description')
                                <p class="pform-error-msg">{{ $message }}</p>
                            @enderror
                            <div class="pform-char-row">
                                <span class="pform-hint" style="margin:0">Óptimo: 120-160 caracteres</span>
                                <span class="pform-char-count"
                                    id="pformSeoMetaCount">{{ strlen(old('seo_description', $product->seo_description ?? '')) }}/160</span>
                            </div>
                        </div>

                        <div class="pform-field">
                            <label class="pform-label">Palabras Clave (Keywords)</label>
                            {{-- FIX BUG 5: added name="seo_keywords" + value — existed but was
                                 never submitted nor recovered. --}}
                            <input type="text" class="pform-input @error('seo_keywords') pform-field-error @enderror" name="seo_keywords" form="productEditForm"
                                value="{{ old('seo_keywords', $product->seo_keywords ?? '') }}"
                                placeholder="caldera, industrial, vapor, alta presión">
                            @error('seo_keywords')
                                <p class="pform-error-msg">{{ $message }}</p>
                            @enderror
                            <p class="pform-hint">Separa las palabras clave con comas</p>
                        </div>
                    </div>

                    {{-- FIX BUG 5: added the entire "Redes Sociales (Open Graph)" panel —
                         it existed in create_product/create.blade.php but was completely
                         missing here, so QA never saw it in Edit. --}}
                    <div class="pform-panel" style="margin-bottom:0">
                        <h2 class="pform-panel-title" style="display:flex;align-items:center;gap:8px">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                                fill="none" stroke="var(--secondary-color)" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <circle cx="18" cy="5" r="3" />
                                <circle cx="6" cy="12" r="3" />
                                <circle cx="18" cy="19" r="3" />
                                <line x1="8.59" x2="15.42" y1="13.51" y2="17.49" />
                                <line x1="15.41" x2="8.59" y1="6.51" y2="10.49" />
                            </svg>
                            Redes Sociales (Open Graph)
                        </h2>

                        <div class="pform-field">
                            <div class="pform-label-row">
                                <label class="pform-label" for="pformOgTitle">Título para Redes Sociales</label>
                                <button type="button" class="pform-insert-variable-btn" data-variable-target="pformOgTitle">{ } Insertar variable</button>
                            </div>
                            <input type="text" id="pformOgTitle" class="pform-input @error('og_title') pform-field-error @enderror" name="og_title" form="productEditForm"
                                value="{{ old('og_title', $product->og_title ?? '') }}" placeholder="Bomba de Calor Rinnai 20HP">
                            @error('og_title')
                                <p class="pform-error-msg">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="pform-field">
                            <div class="pform-label-row">
                                <label class="pform-label" for="pformOgDescription">Descripción para Redes Sociales</label>
                                <button type="button" class="pform-insert-variable-btn" data-variable-target="pformOgDescription">{ } Insertar variable</button>
                            </div>
                            <textarea id="pformOgDescription" class="pform-textarea @error('og_description') pform-field-error @enderror" rows="3" name="og_description" form="productEditForm"
                                placeholder="Descripción que aparecerá cuando se comparta en Facebook, LinkedIn, etc.">{{ old('og_description', $product->og_description ?? '') }}</textarea>
                            @error('og_description')
                                <p class="pform-error-msg">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="pform-field" style="margin-bottom:0">
                            <label class="pform-label">Imagen para Redes Sociales</label>
                            <div class="img-picker-field">
                                <input type="url" class="pform-input @error('og_image') pform-field-error @enderror" name="og_image" id="pformOgImage" form="productEditForm"
                                    value="{{ old('og_image', $product->og_image ?? '') }}"
                                    placeholder="URL de la imagen (1200x630px recomendado)">
                                <button type="button" class="img-picker-trigger-btn" onclick="openImagePicker('pformOgImage')">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                    Seleccionar
                                </button>
                            </div>
                            @error('og_image')
                                <p class="pform-error-msg">{{ $message }}</p>
                            @enderror
                            <p class="pform-hint">Recomendado: 1200x630px &bull; Máximo: 5MB &bull; Formato: JPG o PNG
                            </p>
                        </div>
                    </div>

                    {{-- Preguntas Frecuentes del producto --}}
                    <div class="pform-panel">
                        <div class="pform-specs-header">
                            <div>
                                <h2 class="pform-panel-title" style="margin-bottom:4px">Preguntas Frecuentes (FAQ)</h2>
                                <p class="pform-hint" style="margin:0">Aparecen en la ficha pública del producto y como datos estructurados para Google.</p>
                            </div>
                            <button type="button" class="pform-btn primary" id="pformAddFaq">+ Agregar pregunta</button>
                        </div>
                        <div class="pform-placeholder" id="pformFaqEmpty">
                            <p>Este producto aún no tiene preguntas frecuentes.</p>
                        </div>
                        <div id="pformFaqList" class="pform-spec-list" style="display:none"></div>
                    </div>

                    <div class="pform-panel" style="margin-bottom:0">
                        <h2 class="pform-panel-title">Vista Previa en Google</h2>
                        <div class="pform-google-preview">
                            <div class="pform-google-url">
                                simari.com › productos › <span
                                    id="pformSeoSlugPreview2">{{ $product->slug ?? 'producto-ejemplo' }}</span>
                            </div>
                            <h3 class="pform-google-title" id="pformGoogleTitle">
                                {{ $product->seo_title ?? $product->name }}</h3>
                            <p class="pform-google-desc" id="pformGoogleDesc">
                                {{ $product->seo_description ?? 'Agrega una meta descripción...' }}</p>
                        </div>

                        <div class="pform-seo-analysis">
                            <h4 class="pform-seo-analysis-title">Análisis SEO</h4>
                            <div class="pform-seo-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                    viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10" />
                                    <line x1="12" x2="12" y1="8" y2="12" />
                                    <line x1="12" x2="12.01" y1="16" y2="16" />
                                </svg>
                                <span>Título SEO: Mejorar longitud (30-60 caracteres)</span>
                            </div>
                            <div class="pform-seo-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                    viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10" />
                                    <line x1="12" x2="12" y1="8" y2="12" />
                                    <line x1="12" x2="12.01" y1="16" y2="16" />
                                </svg>
                                <span>Meta Description: Mejorar longitud (120-160 caracteres)</span>
                            </div>
                            <div class="pform-seo-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                    viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10" />
                                    <line x1="12" x2="12" y1="8" y2="12" />
                                    <line x1="12" x2="12.01" y1="16" y2="16" />
                                </svg>
                                <span>URL Slug: Falta configurar</span>
                            </div>
                        </div>
                    </div>

                    <div class="pform-tips-box">
                        <p class="pform-tips-title">💡 Consejos de SEO</p>
                        <ul class="pform-tips-list">
                            <li>• Incluye palabras clave relevantes en el título SEO</li>
                            <li>• Escribe una meta description atractiva que invite al clic</li>
                            <li>• Usa URLs amigables y descriptivas</li>
                        </ul>
                    </div>

                </div>
            </div>
        </div>
    </div>
    @include('admin.products.partials._image_source_modal')
    @include('admin.products.edit_product._modal_supplier_link')
    @include('admin.products.edit_product._scripts')
@endsection
