{{-- Formulario base de ServicePage, compartido por create.blade.php y edit.blade.php.
     $servicePage puede venir null (create) o con datos (edit). --}}
@php
    $sp = $servicePage ?? null;
@endphp
<div class="user-manager-form">
    <div>
        <label class="supliers-manager-slider-label">Nombre <span style="color:red">*</span></label>
        <input type="text" class="users-manager-input {{ $errors->has('name') ? 'is-invalid' : '' }}" name="name" id="spName" value="{{ old('name', $sp->name ?? '') }}" required>
        @error('name')
            <span class="field-error-msg">{{ $message }}</span>
        @enderror
    </div>
    <div>
        <label class="supliers-manager-slider-label">Slug (URL) <span style="color:red">*</span></label>
        <input type="text" class="users-manager-input {{ $errors->has('slug') ? 'is-invalid' : '' }}" name="slug" id="spSlug" value="{{ old('slug', $sp->slug ?? '') }}" placeholder="mantenimiento-preventivo" required>
        <p class="hs-config-note" style="margin-top:4px;">Página pública: <code>/servicio/{{ old('slug', $sp->slug ?? '...') }}</code></p>
        @error('slug')
            <span class="field-error-msg">{{ $message }}</span>
        @enderror
    </div>
</div>

<div class="users-manager-email-camp">
    <label class="supliers-manager-slider-label">Descripción corta (aparece en el encabezado de la página)</label>
    <textarea class="users-manager-input client-modal-textarea {{ $errors->has('short_description') ? 'is-invalid' : '' }}" name="short_description" rows="2">{{ old('short_description', $sp->short_description ?? '') }}</textarea>
    @error('short_description')
        <span class="field-error-msg">{{ $message }}</span>
    @enderror
</div>

<div class="users-manager-email-camp">
    <label class="supliers-manager-slider-label">Descripción completa</label>
    <textarea class="users-manager-input client-modal-textarea {{ $errors->has('description') ? 'is-invalid' : '' }}" name="description" rows="4">{{ old('description', $sp->description ?? '') }}</textarea>
    @error('description')
        <span class="field-error-msg">{{ $message }}</span>
    @enderror
</div>

<div class="user-manager-form">
    <div>
        <label class="supliers-manager-slider-label">Precio (opcional)</label>
        <input type="number" step="0.01" min="0" class="users-manager-input {{ $errors->has('price') ? 'is-invalid' : '' }}" name="price" value="{{ old('price', $sp->price ?? '') }}">
        @error('price')
            <span class="field-error-msg">{{ $message }}</span>
        @enderror
    </div>
    <div>
        <label class="supliers-manager-slider-label">Moneda</label>
        <input type="text" class="users-manager-input {{ $errors->has('currency') ? 'is-invalid' : '' }}" name="currency" value="{{ old('currency', $sp->currency ?? 'MXN') }}" maxlength="10">
        @error('currency')
            <span class="field-error-msg">{{ $message }}</span>
        @enderror
    </div>
</div>

<div class="users-manager-email-camp">
    <label class="supliers-manager-slider-label">Imagen de portada</label>
    <div class="img-picker-field">
        <input type="text" class="users-manager-input {{ $errors->has('cover_image_url') ? 'is-invalid' : '' }}" name="cover_image_url" id="spCoverImageUrl" value="{{ old('cover_image_url', $sp?->getRawOriginal('cover_image_url') ?? '') }}" placeholder="https://...">
        <button type="button" class="img-picker-trigger-btn" onclick="openImagePicker('spCoverImageUrl')">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
            Seleccionar
        </button>
    </div>
    @error('cover_image_url')
        <span class="field-error-msg">{{ $message }}</span>
    @enderror
</div>

<div class="user-manager-form">
    <div>
        <label class="supliers-manager-slider-label">Orden</label>
        <input type="number" class="users-manager-input {{ $errors->has('sort_order') ? 'is-invalid' : '' }}" name="sort_order" value="{{ old('sort_order', $sp->sort_order ?? 0) }}" min="0">
        @error('sort_order')
            <span class="field-error-msg">{{ $message }}</span>
        @enderror
    </div>
    <div>
        <label class="supliers-manager-slider-label">Estado</label>
        <select class="users-manager-select {{ $errors->has('is_active') ? 'is-invalid' : '' }}" name="is_active">
            <option value="1" @selected(old('is_active', $sp->is_active ?? true))>Activo</option>
            <option value="0" @selected(!old('is_active', $sp->is_active ?? true))>Inactivo</option>
        </select>
        @error('is_active')
            <span class="field-error-msg">{{ $message }}</span>
        @enderror
    </div>
</div>

<h3 style="margin-top:20px;margin-bottom:4px;">SEO y Preguntas Frecuentes</h3>
<div class="show-user-divider"></div>

<div class="user-manager-form">
    <div>
        <label class="supliers-manager-slider-label">Título SEO</label>
        <input type="text" class="users-manager-input {{ $errors->has('seo_title') ? 'is-invalid' : '' }}" name="seo_title" value="{{ old('seo_title', $sp->seo_title ?? '') }}" maxlength="160">
        @error('seo_title')
            <span class="field-error-msg">{{ $message }}</span>
        @enderror
    </div>
    <div>
        <label class="supliers-manager-slider-label">Imagen Open Graph</label>
        <div class="img-picker-field">
            <input type="text" class="users-manager-input {{ $errors->has('og_image_url') ? 'is-invalid' : '' }}" name="og_image_url" id="spOgImageUrl" value="{{ old('og_image_url', $sp->og_image_url ?? '') }}" placeholder="https://...">
            <button type="button" class="img-picker-trigger-btn" onclick="openImagePicker('spOgImageUrl')">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                Seleccionar
            </button>
        </div>
        @error('og_image_url')
            <span class="field-error-msg">{{ $message }}</span>
        @enderror
    </div>
</div>

<div class="users-manager-email-camp">
    <label class="supliers-manager-slider-label">Descripción SEO</label>
    <textarea class="users-manager-input client-modal-textarea {{ $errors->has('seo_description') ? 'is-invalid' : '' }}" name="seo_description" rows="2" maxlength="500">{{ old('seo_description', $sp->seo_description ?? '') }}</textarea>
    @error('seo_description')
        <span class="field-error-msg">{{ $message }}</span>
    @enderror
</div>

<div class="users-manager-email-camp">
    <label class="supliers-manager-slider-label">Preguntas frecuentes</label>
    <div id="spFaqRows" class="hs-faq-items"></div>
    <button type="button" class="button-secondary size-adjustment" id="btnAddSpFaq" style="margin-top:10px;">
        + Agregar pregunta
    </button>
    @error('faq_items')
        <span class="field-error-msg">{{ $message }}</span>
    @enderror
</div>

<script>window.SP_EXISTING_FAQS = @json($sp->faqs ?? []);</script>
