{{-- Selector de "Producto/URL Canónica" para SEO — sustituye al antiguo
     checkbox "¿Es la URL Canónica?" + <input type="url"> de texto libre por
     un buscador en vivo de productos (resources/js/admin/canonical-picker.js).
     Shared by create.blade.php and edit.blade.php.

     Variables esperadas del caller:
       - $formId (string): id del <form> real (vive fuera de este bloque,
         igual que el resto de campos SEO) — 'productCreateForm' o
         'productEditForm'.
       - $excludeId (int|null): id del producto actual, para que no pueda
         elegirse a sí mismo como su propio canónico. null en Crear.
       - $canonicalProduct (\App\Models\Products|null): relación ya cargada
         del producto actual (solo Editar). null en Crear o si no aplica.
       - $canonicalUrl (string|null): URL personalizada ya guardada (solo
         Editar, modo "custom"). null en Crear o si no aplica.

     old() tiene prioridad sobre lo ya guardado (mismo patrón que el resto
     de #pform* de esta pantalla, ej. el cascade de categoría en
     edit_product/_scripts.blade.php) — así, si el guardado falla por OTRO
     campo, no se pierde la selección que el admin acababa de hacer aquí. --}}
@php
    $cpUsingOldInput = session()->hasOldInput('canonical_product_id') || session()->hasOldInput('canonical_url');

    if ($cpUsingOldInput) {
        $cpInitialProduct = null;
        $cpOldProductId = old('canonical_product_id');
        if ($cpOldProductId) {
            $cpOldProductModel = \App\Models\Products::select('id', 'name', 'sku', 'model', 'slug', 'brand_id')
                ->with('brand:id,name')
                ->find($cpOldProductId);
            if ($cpOldProductModel) {
                $cpInitialProduct = [
                    'id' => $cpOldProductModel->id,
                    'name' => $cpOldProductModel->name,
                    'sku' => $cpOldProductModel->sku,
                    'model' => $cpOldProductModel->model,
                    'slug' => $cpOldProductModel->slug,
                    'brand' => $cpOldProductModel->brand->name ?? null,
                ];
            }
        }
        $cpInitialCustomUrl = old('canonical_url', '');
    } else {
        $cpInitialProduct = ($canonicalProduct ?? null) ? [
            'id' => $canonicalProduct->id,
            'name' => $canonicalProduct->name,
            'sku' => $canonicalProduct->sku,
            'model' => $canonicalProduct->model,
            'slug' => $canonicalProduct->slug,
            'brand' => $canonicalProduct->brand->name ?? null,
        ] : null;
        $cpInitialCustomUrl = $canonicalUrl ?? '';
    }

    $cpContainerId = 'canonicalPickerContainer_' . ($formId ?? 'default');
@endphp

<div class="pform-field">
    <label class="pform-label">Producto / URL Canónica</label>
    <div id="{{ $cpContainerId }}" class="cp-picker-mount"></div>
    <p class="pform-hint">Deja esto vacío en el 99% de los casos (este producto es su propia URL Canónica para
        Google). Solo busca/elige otro producto —o escribe una URL personalizada— si este producto es muy
        parecido a otro que ya existe y quieres que Google indexe ese otro en su lugar.</p>
    @error('canonical_url')
        <p class="pform-error-msg">{{ $message }}</p>
    @enderror
    @error('canonical_product_id')
        <p class="pform-error-msg">{{ $message }}</p>
    @enderror
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        window.CanonicalPicker.mount(document.getElementById(@json($cpContainerId)), {
            excludeId: @json($excludeId ?? null),
            initialProduct: @json($cpInitialProduct),
            initialCustomUrl: @json($cpInitialCustomUrl),
            productIdInputName: 'canonical_product_id',
            urlInputName: 'canonical_url',
            urlInputId: 'pformCanonicalUrl',
            formId: @json($formId ?? null),
        });
    });
</script>
