@php
    // Contexto de la página ANTES de cualquier reasignación: en páginas de
    // colección $collection es la colección de la PÁGINA; la colección
    // fuente del carrusel se maneja aparte como $sourceCollection.
    $ctx = $product ?? $collection ?? null;

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

        match ($source) {
            'category'    => $query->where('category_id', $config['category_id'] ?? 0),
            'brand'       => $query->where('brand_id', $config['brand_id'] ?? 0),
            'new'         => $query->where('is_new', true),
            'recommended' => $query->where('is_recommended', true),
            'manual'      => $query->whereIn('id', $config['product_ids'] ?? []),
            // Fuentes relativas al producto en cuyo contexto se renderiza la
            // sección (solo página de producto; en el Home degradan a vacío).
            'related_category' => $query->where('category_id', isset($product) ? $product->category_id : 0)
                ->when(isset($product), fn ($q) => $q->where('id', '!=', $product->id)),
            'related_brand'    => $query->where('brand_id', isset($product) ? ($product->brand_id ?? 0) : 0)
                ->when(isset($product), fn ($q) => $q->where('id', '!=', $product->id)),
            default       => $query->where('is_featured', true),
        };

        $products = $query->orderByDesc('created_at')->take($limit)->get();
    }

    $viewAllUrl = ($sourceCollection && $sourceCollection->is_active)
        ? route('collection.show', $sourceCollection->slug)
        : null;
@endphp

<x-frontend.shop.product-carousel :title="$section->resolveText($section->title, $ctx)" :products="$products" :view-all-url="$viewAllUrl" />
