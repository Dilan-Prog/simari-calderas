@php
    $config = $section->config ?? [];
    $categoryIds = $config['category_ids'] ?? [];

    // Con categorías elegidas a mano: exactamente esas (de cualquier nivel) y en el orden
    // que el admin les dio. Sin selección: todas las principales activas.
    $categoryIds = collect($categoryIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
    if ($categoryIds->isNotEmpty()) {
        $byId = \App\Models\Category::where('is_active', true)->whereIn('id', $categoryIds)->get()->keyBy('id');
        $categories = $categoryIds->map(fn ($id) => $byId->get($id))->filter()->values();
    } else {
        $categories = \App\Models\Category::where('is_active', true)->whereNull('parent_id')->orderBy('sort_order')->get();
    }
@endphp

@if ($categories->count() > 0)
<section class="category-grid">
    @if ($section->title)
        <h2 class="category-grid__title">{!! \App\Support\LinkText::render($section->title) !!}</h2>
    @endif
    <div class="category-grid__items">
        @foreach ($categories as $category)
            <a href="{{ route('catalog.category', $category->slug) }}" class="category-grid__item">
                <div class="category-grid__img-wrap">
                    <img src="{{ $category->image_url ?: asset('images/logo/equiterm-logo-blanco-color-3x.png') }}" alt="{{ $category->name }}" loading="lazy">
                </div>
                <span>{{ $category->name }}</span>
            </a>
        @endforeach
    </div>
</section>
@endif
