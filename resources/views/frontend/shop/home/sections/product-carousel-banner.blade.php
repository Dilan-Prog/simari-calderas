@php
    // Contexto de la página ANTES de cualquier reasignación (ver
    // product-carousel.blade.php).
    $ctx = $product ?? $collection ?? $servicePage ?? null;

    $config = $section->config ?? [];
    $source = $config['source'] ?? 'featured';
    $limit = $config['limit'] ?? 12;
    $sourceCollection = null;

    if ($source === 'collection' && !empty($config['collection_id'])) {
        $sourceCollection = \App\Models\Collection::with('rules')->find($config['collection_id']);
        $products = $sourceCollection ? $sourceCollection->resolveProducts($limit) : collect();
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
                $source === 'category' ? ($config['category_id'] ?? 0) : (isset($product) ? $product->category_id : 0)
            )?->idsWithChildren() ?? [0])
            : [0];

        match ($source) {
            'category'    => $query->whereIn('category_id', $categoryIds),
            'brand'       => $query->where('brand_id', $config['brand_id'] ?? 0),
            'new'         => $query->where('is_new', true),
            'recommended' => $query->where('is_recommended', true),
            'manual'      => $query->whereIn('id', $config['product_ids'] ?? []),
            // Fuentes relativas al producto en cuyo contexto se renderiza la
            // sección (solo página de producto; en el Home degradan a vacío).
            'related_category' => $query->whereIn('category_id', $categoryIds)
                ->when(isset($product), fn ($q) => $q->where('id', '!=', $product->id)),
            'related_brand'    => $query->where('brand_id', isset($product) ? ($product->brand_id ?? 0) : 0)
                ->when(isset($product), fn ($q) => $q->where('id', '!=', $product->id)),
            default       => $query->where('is_featured', true),
        };

        $products = $query->orderByDesc('created_at')->take($limit)->get();
    }

    $banner = [
        'image_url' => $config['banner_image_url'] ?? null,
        'link_url'  => $config['banner_link_url'] ?? null,
        'alt'       => $config['banner_alt'] ?? null,
    ];

    $viewAllUrl = ($sourceCollection && $sourceCollection->is_active)
        ? route('collection.show', $sourceCollection->slug)
        : null;
@endphp

<x-frontend.shop.product-carousel :title="$section->resolveText($section->title, $ctx)" :products="$products" :banner="$banner" :view-all-url="$viewAllUrl" />
