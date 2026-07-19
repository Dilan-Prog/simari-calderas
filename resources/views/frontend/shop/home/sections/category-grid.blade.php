@php
    $config = $section->config ?? [];
    $categoryIds = $config['category_ids'] ?? [];

    $query = \App\Models\Category::where('is_active', true)->whereNull('parent_id');
    if (!empty($categoryIds)) {
        $query->whereIn('id', $categoryIds);
    }
    $categories = $query->orderBy('sort_order')->get();
@endphp

@if ($categories->count() > 0)
<section class="category-grid">
    @if ($section->title)
        <h2 class="category-grid__title">{{ $section->title }}</h2>
    @endif
    <div class="category-grid__items">
        @foreach ($categories as $category)
            <a href="{{ route('catalog.category', $category->slug) }}" class="category-grid__item">
                <div class="category-grid__img-wrap">
                    <img src="{{ $category->image_url ? asset($category->image_url) : asset('images/logo/equiterm-logo-blanco-color-3x.png') }}" alt="{{ $category->name }}" loading="lazy">
                </div>
                <span>{{ $category->name }}</span>
            </a>
        @endforeach
    </div>
</section>
@endif
