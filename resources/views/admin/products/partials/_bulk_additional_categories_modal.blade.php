{{-- Acción masiva "Categorías adicionales" (barra de selección del listado).
     Agrega o quita las categorías elegidas a los productos seleccionados sin
     reemplazar las que ya tengan. La lógica JS vive en _scripts.blade.php. --}}
@permiso('products','edit')
<div id="bulkAdditionalCategoriesModal" class="del-confirm-overlay"
    data-url="{{ route('admin.products.bulk-additional-categories') }}">
    <div class="del-confirm-box" style="max-width:520px;text-align:left" role="dialog" aria-modal="true"
        aria-labelledby="bulkAdditionalCategoriesTitle">
        <h2 class="del-confirm-title" id="bulkAdditionalCategoriesTitle" style="text-align:left">Categorías adicionales</h2>
        <p class="del-confirm-desc" style="text-align:left">
            Se aplicará a <strong><span id="bulkAdditionalCategoriesCount">0</span> producto(s)</strong>.
            Agregar o quitar no cambia la categoría principal ni las demás categorías adicionales de cada producto.
        </p>
        @include('admin.products.partials._additional_categories_picker', [
            'acpId' => 'bulkAdditionalCategoriesPicker',
            'acpSelected' => [],
            'acpInputName' => null,
            'acpPrincipalInputId' => null,
        ])
        <p class="pform-hint" style="margin-top:8px;font-size:12.5px;color:#6b7280">
            Si una categoría ya es la principal de un producto, no se agrega como adicional a ese producto.
        </p>
        <div class="del-confirm-actions" style="margin-top:18px">
            <button type="button" class="button-secondary size-adjustment" id="bulkAdditionalCategoriesCancelBtn">Cancelar</button>
            <button type="button" class="button-secondary size-adjustment" data-acp-mode="remove">Quitar</button>
            <button type="button" class="button-primary size-adjustment" data-acp-mode="add">Agregar</button>
        </div>
    </div>
</div>
@endpermiso
