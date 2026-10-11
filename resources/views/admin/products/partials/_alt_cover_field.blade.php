{{-- ALT de la portada (cover_image_alt). Espejo de la primera imagen de la galería. --}}
<div class="pform-field pform-alt-field" data-limit="125" style="margin-top:20px">
    <label class="pform-label" for="pformCoverAlt">Texto alternativo (ALT) de la portada</label>
    <div style="display:flex;gap:8px;align-items:center;">
        <input type="text" id="pformCoverAlt" name="cover_image_alt" maxlength="255"
            class="pform-input pform-alt-input-cover @error('cover_image_alt') pform-field-error @enderror"
            value="{{ old('cover_image_alt', $coverAltValue ?? '') }}" placeholder="Describe la imagen de portada">
        <button type="button" class="pform-alt-use-name" style="font-size:12px;background:none;border:none;color:var(--secondary-color,#ff6213);cursor:pointer;white-space:nowrap;">Usar nombre del producto</button>
    </div>
    <div style="display:flex;justify-content:space-between;">
        <p class="pform-hint" style="margin:4px 0 0">La portada es la primera imagen de la galería: su ALT y este campo siempre son el mismo texto. Si lo dejas vacío se usa el nombre del producto.</p>
        <span class="pform-alt-count" style="font-size:11px;color:#6b7280;white-space:nowrap;"></span>
    </div>
    @error('cover_image_alt')
        <p class="pform-error-msg">{{ $message }}</p>
    @enderror
</div>
