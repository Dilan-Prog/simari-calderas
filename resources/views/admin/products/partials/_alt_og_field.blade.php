{{-- Vista previa 1200x630 + ALT de la imagen Open Graph (og_image_alt). --}}
<div id="pformOgPreview" style="margin-top:10px;max-width:420px;aspect-ratio:1200/630;border:1px dashed #d1d5db;border-radius:8px;overflow:hidden;background:#f9fafb;position:relative;">
    <img alt="Vista previa de la imagen Open Graph" style="display:none;width:100%;height:100%;object-fit:cover;">
    <div class="pform-og-preview-empty" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:12px;color:#9ca3af;">Vista previa 1200 × 630</div>
</div>

<div class="pform-field pform-alt-field" data-limit="255" style="margin:16px 0 0">
    <label class="pform-label" for="pformOgImageAlt">ALT de la imagen OG</label>
    <div style="display:flex;gap:8px;align-items:center;">
        <input type="text" id="pformOgImageAlt" name="og_image_alt" maxlength="255" form="{{ $formId }}"
            class="pform-input pform-alt-og @error('og_image_alt') pform-field-error @enderror"
            value="{{ old('og_image_alt', $ogAltValue ?? '') }}" placeholder="Describe la imagen que se verá al compartir">
        <button type="button" class="pform-alt-use-name" style="font-size:12px;background:none;border:none;color:var(--secondary-color,#ff6213);cursor:pointer;white-space:nowrap;">Usar nombre del producto</button>
    </div>
    <div style="display:flex;justify-content:space-between;">
        <p class="pform-hint" style="margin:4px 0 0">Máximo 255 caracteres. Si lo dejas vacío se usa el nombre del producto. Sin imagen OG este texto no se guarda.</p>
        <span class="pform-alt-count" style="font-size:11px;color:#6b7280;white-space:nowrap;"></span>
    </div>
    @error('og_image_alt')
        <p class="pform-error-msg">{{ $message }}</p>
    @enderror
</div>
