{{--
    Cuadrícula de productos (o estado vacío). Va dentro de
    <div data-catalog-region="products">; WP-1 lo usa para `productsHtml`.
    Variables: $result, $category.
--}}
@if ($result->paginator->count() > 0)
    <div class="catalog-grid">
        @foreach ($result->paginator as $product)
            <div class="catalog-grid__item">
                <x-frontend.shop.product-card :product="$product" />
            </div>
        @endforeach
    </div>
@else
    <div class="catalog-empty">
        <p class="catalog-empty__text">No hay productos que coincidan con estos filtros.</p>
        @if (count($result->chips) > 0 || (int) ($result->meta['activeCount'] ?? 0) > 0)
            <a class="catalog-empty__clear" href="{{ $result->clearHref }}" data-catalog-link>Limpiar filtros</a>
        @endif
    </div>
@endif
