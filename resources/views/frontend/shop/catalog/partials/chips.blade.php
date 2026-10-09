{{--
    Chips de filtros activos + "Limpiar filtros". Va dentro de
    <div data-catalog-region="chips">; WP-1 lo usa para `chipsHtml`.
    Variables: $result, $category. No pinta nada si no hay chips.
--}}
@if (count($result->chips) > 0)
    <div class="catalog-chips">
        <ul class="catalog-chips__list" aria-label="Filtros aplicados">
            @foreach ($result->chips as $chip)
                <li class="catalog-chips__item">
                    <a class="catalog-chip" href="{{ $chip['removeHref'] }}" data-catalog-link
                        aria-label="Quitar filtro {{ $chip['label'] }}">
                        <span class="catalog-chip__label">{{ $chip['label'] }}</span>
                        <span class="catalog-chip__x" aria-hidden="true">&#10005;</span>
                    </a>
                </li>
            @endforeach
        </ul>
        <a class="catalog-chips__clear" href="{{ $result->clearHref }}" data-catalog-link>Limpiar filtros</a>
    </div>
@endif
