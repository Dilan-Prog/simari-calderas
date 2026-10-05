@php
    // Contexto de la página ANTES de cualquier reasignación: en páginas de
    // colección $collection es la colección de la PÁGINA; la colección
    // fuente del carrusel se maneja aparte como $sourceCollection.
    $ctx = $product ?? $collection ?? $servicePage ?? null;
    // Producto de la página (solo existe en la página de producto): el
    // @foreach del componente reasigna $product en su propio scope, no aquí.
    $currentProduct = isset($product) ? $product : null;

    $config = $section->config ?? [];
    $source = $config['source'] ?? 'featured';
    $limit = $config['limit'] ?? 12;
    // Por defecto el producto que se está viendo no aparece en sus propios
    // carruseles (en cualquier fuente).
    $excludeCurrent = $currentProduct && ($config['exclude_current'] ?? true);
    $sourceCollection = null;

    if ($source === 'collection' && !empty($config['collection_id'])) {
        $sourceCollection = \App\Models\Collection::with('rules')->find($config['collection_id']);
        $products = $sourceCollection
            ? $sourceCollection->resolveProducts($excludeCurrent ? $limit + 1 : $limit)
            : collect();
        if ($excludeCurrent) {
            $products = $products->reject(fn ($p) => $p->id === $currentProduct->id)->take($limit)->values();
        }
    } else {
        $query = \App\Models\Products::query()
            ->where('is_active', true)
            ->where('publish_on_website', true)
            ->with(['images' => fn ($q) => $q->orderBy('sort_order'), 'brand']);

        // FIX: coincidencia exacta de category_id dejaba sin productos a
        // toda sección "Por Categoría" apuntando a una categoría padre (el
        // único tipo que el admin puede elegir) cuando los productos están
        // etiquetados con la subcategoría — /catalogo/{slug} sí las incluye
        // vía Category::idsWithChildren(), aquí faltaba el mismo alcance.
        $categoryIds = in_array($source, ['category', 'related_category'], true)
            ? (\App\Models\Category::find(
                $source === 'category' ? ($config['category_id'] ?? 0) : ($currentProduct ? $currentProduct->category_id : 0)
            )?->idsWithChildren() ?? [0])
            : [0];

        $tag = trim((string) ($config['tag'] ?? ''));

        match ($source) {
            'category'    => $query->whereIn('category_id', $categoryIds),
            'brand'       => $query->where('brand_id', $config['brand_id'] ?? 0),
            'new'         => $query->where('is_new', true),
            'recommended' => $query->where('is_recommended', true),
            'manual'      => $query->whereIn('id', $config['product_ids'] ?? []),
            // Etiqueta (products.tags, JSON array de strings); sin etiqueta
            // configurada no resuelve nada.
            'tag'         => $tag !== '' ? $query->whereJsonContains('tags', $tag) : $query->whereRaw('1 = 0'),
            // Fuentes relativas al producto en cuyo contexto se renderiza la
            // sección (solo página de producto; en el Home degradan a vacío).
            'related_category' => $query->whereIn('category_id', $categoryIds),
            'related_brand'    => $query->where('brand_id', $currentProduct ? ($currentProduct->brand_id ?? 0) : 0),
            default       => $query->where('is_featured', true),
        };

        // Las fuentes relativas siempre excluyen al producto actual; en el
        // resto depende de config['exclude_current'] (default true).
        if ($currentProduct && ($excludeCurrent || in_array($source, ['related_category', 'related_brand'], true))) {
            $query->where('id', '!=', $currentProduct->id);
        }

        $products = $query->orderByDesc('created_at')->take($limit)->get();
    }

    $viewAllUrl = ($sourceCollection && $sourceCollection->is_active)
        ? route('collection.show', $sourceCollection->slug)
        : null;

    // Encabezado: el título puede enlazar (heading_link); un enlace roto o
    // inexistente deja el título sin <a> (nunca href vacío ni '#').
    $headingTitle = $section->resolveText($section->title, $ctx);
    $headingUrl = $headingTitle ? \App\Support\LinkTarget::resolve($section->heading_link) : null;
    $headingNewTab = !empty($section->heading_link['new_tab']);
@endphp

@if (count($products) > 0)
<x-frontend.shop.product-carousel :title="$headingTitle" :products="$products" :view-all-url="$viewAllUrl" :heading-url="$headingUrl" :heading-new-tab="$headingNewTab" />
@endif
