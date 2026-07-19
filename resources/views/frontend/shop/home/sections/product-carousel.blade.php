@php
    $config = $section->config ?? [];
    $source = $config['source'] ?? 'featured';
    $limit = $config['limit'] ?? 12;

    if ($source === 'collection' && !empty($config['collection_id'])) {
        $collection = \App\Models\Collection::with('rules')->find($config['collection_id']);
        $products = $collection ? $collection->resolveProducts($limit) : collect();
    } else {
        $query = \App\Models\Products::query()
            ->where('is_active', true)
            ->where('publish_on_website', true)
            ->with(['images' => fn ($q) => $q->orderBy('sort_order')->limit(1), 'brand']);

        match ($source) {
            'category'    => $query->where('category_id', $config['category_id'] ?? 0),
            'brand'       => $query->where('brand_id', $config['brand_id'] ?? 0),
            'new'         => $query->where('is_new', true),
            'recommended' => $query->where('is_recommended', true),
            'manual'      => $query->whereIn('id', $config['product_ids'] ?? []),
            default       => $query->where('is_featured', true),
        };

        $products = $query->orderByDesc('created_at')->take($limit)->get();
    }
@endphp

<x-frontend.shop.product-carousel :title="$section->title" :products="$products" />
