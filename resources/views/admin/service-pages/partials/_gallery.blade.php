{{-- Galería de imágenes del servicio — reusa el picker de media compartido
     (window.openImagePicker, ver resources/js/admin/image-picker.js) en vez
     de un uploader propio nuevo. La primera imagen (sort_order=0) es la
     portada y se sincroniza automáticamente con service_pages.cover_image_url
     (ver ServicePageController::syncCoverImage()). --}}
<div style="display:flex;justify-content:space-between;align-items:center;margin:36px 0 16px;">
    <div>
        <h2 style="margin:0;">Galería del servicio</h2>
        <p class="hs-config-note" style="margin-top:2px;">La primera imagen se usa como portada. Arrastra para reordenar.</p>
    </div>
    <button type="button" class="button-primary size-adjustment" id="btnAddServiceImage" style="background:#ff6213;border-color:#ff6213;">
        + Agregar imágenes
    </button>
</div>

<div id="serviceGalleryGrid" class="service-gallery-grid" data-base-url="{{ url('/admin/servicios-web/' . $servicePage->id . '/imagenes') }}">
    @foreach ($servicePage->images as $img)
        <div class="service-gallery-item" data-id="{{ $img->id }}" draggable="true">
            <div class="service-gallery-item__drag">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
            </div>
            @if ($loop->first)
                <span class="service-gallery-item__badge">PORTADA</span>
            @endif
            <button type="button" class="service-gallery-item__remove" title="Quitar">&times;</button>
            <img src="{{ $img->url }}" alt="{{ $img->alt_text }}">
            <input type="text" class="users-manager-input service-gallery-item__alt" placeholder="Texto alternativo" value="{{ $img->alt_text }}">
        </div>
    @endforeach
    <div class="service-gallery-item service-gallery-item--add" id="serviceGalleryDropzone">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
        <span>Arrastra o selecciona</span>
    </div>
</div>
