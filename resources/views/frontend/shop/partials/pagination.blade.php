{{--
    Paginación pública de la tienda (catálogo, colecciones, etc.). Reemplaza
    la vista por defecto de Laravel (Tailwind, en inglés, sin CSS propio en
    este sitio -- salía como texto plano "« Previous Next »").
    Uso: {{ $products->links('frontend.shop.partials.pagination') }}
    Mismo patrón/estructura que admin.components.pagination, con clases y
    paleta propias del storefront (--secondary-color).
--}}
@if ($paginator->hasPages())
    <div class="shop-pagination">
        <p class="shop-pagination__summary">
            Mostrando {{ $paginator->firstItem() }}&ndash;{{ $paginator->lastItem() }} de {{ $paginator->total() }} resultados
        </p>
        <nav class="shop-pagination__nav" role="navigation" aria-label="Paginación">
            <ul class="shop-pagination__list">
                {{-- Anterior --}}
                <li>
                    @if ($paginator->onFirstPage())
                        <span class="shop-pagination__btn is-disabled" aria-disabled="true">&lsaquo; Anterior</span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" class="shop-pagination__btn" rel="prev" aria-label="Página anterior">&lsaquo; Anterior</a>
                    @endif
                </li>

                {{-- Números de página --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li><span class="shop-pagination__btn shop-pagination__dots" aria-hidden="true">{{ $element }}</span></li>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li>
                                    <span class="shop-pagination__btn is-active" aria-current="page">{{ $page }}</span>
                                </li>
                            @else
                                <li>
                                    <a href="{{ $url }}" class="shop-pagination__btn">{{ $page }}</a>
                                </li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Siguiente --}}
                <li>
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" class="shop-pagination__btn" rel="next" aria-label="Página siguiente">Siguiente &rsaquo;</a>
                    @else
                        <span class="shop-pagination__btn is-disabled" aria-disabled="true">Siguiente &rsaquo;</span>
                    @endif
                </li>
            </ul>
        </nav>
    </div>
@endif
