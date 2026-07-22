@forelse ($products as $product)
    <div class="search-overlay__item">
        <x-frontend.shop.product-card :product="$product" compact="true" />
    </div>
@empty
    <p class="search-overlay__empty">No encontramos productos para &ldquo;{{ $term }}&rdquo;.</p>
@endforelse
