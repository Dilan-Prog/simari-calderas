{{--
    Sidebar de filtros del catálogo. Lo renderiza index.blade.php dentro de
    <div data-catalog-region="sidebar"> y WP-1 lo reutiliza para `sidebarHtml`
    del JSON. Variables: $result (App\Services\Catalog\CatalogResult), $category (?Category).

    Solo LEE el DTO: cada faceta ya trae href / inputName / value. Sin JS es un
    <form method="GET"> funcional (botón "Aplicar" en <noscript>); con JS cada
    casilla navega a su data-href sin recargar.

    HTML válido: las casillas viven fuera del <form id="catalog-filters-form">
    (que va vacío) y se asocian con el atributo form="…", así el formulario
    Desde/Hasta del precio puede ser su propio <form> sin anidar formularios.
--}}
@php
    $facets = $result->facets;
    $max = max(1, (int) ($facets['maxVisible'] ?? 6));
    $categories = $facets['categories'] ?? ['current' => null, 'back' => null, 'items' => []];
    $price = $facets['price'] ?? null;
    $brands = $facets['brands'] ?? [];
    $availability = $facets['availability'] ?? [];
    $freeShipping = $facets['free_shipping'] ?? null;
    $tech = $facets['tech'] ?? [];
    $params = $result->params;
    $filtersFormId = 'catalog-filters-form';
    $filtersAction = $price['formAction'] ?? ($category ? route('catalog.category', $category->slug) : route('catalog.index'));

    // Aplana un arreglo de parámetros ('marca' => [1,2], 'f' => [3 => [5]]) a pares [name, value].
    $flatten = function (array $data, ?string $prefix = null) use (&$flatten) {
        $pairs = [];
        foreach ($data as $key => $value) {
            $name = $prefix === null ? (string) $key : $prefix . '[' . $key . ']';
            if (is_array($value)) {
                $isList = array_is_list($value);
                foreach ($value as $k => $v) {
                    if (is_array($v)) {
                        $pairs = array_merge($pairs, $flatten([$k => $v], $name));
                    } else {
                        $pairs[] = [$isList ? $name . '[]' : $name . '[' . $k . ']', $v];
                    }
                }
            } else {
                $pairs[] = [$name, $value];
            }
        }
        return $pairs;
    };

    $categoryItems = $categories['items'] ?? [];
    $hasCategories = ! empty($categories['back']) || ! empty($categories['current']) || count($categoryItems) > 0;

    $brandOptions = collect($brands)->map(fn ($b) => [
        'label' => $b['name'], 'count' => $b['count'], 'selected' => $b['selected'], 'href' => $b['href'],
        'inputName' => $b['inputName'], 'value' => $b['value'],
    ])->all();
    $availabilityOptions = collect($availability)->map(fn ($a) => [
        'label' => $a['label'], 'count' => $a['count'], 'selected' => $a['selected'], 'href' => $a['href'],
        'inputName' => $a['inputName'], 'value' => $a['value'],
    ])->all();
@endphp
<div class="catalog-filters">
    {{-- Formulario GET del sidebar: sus campos son las casillas y estos ocultos (q, orden, precio). --}}
    <form id="{{ $filtersFormId }}" method="GET" action="{{ $filtersAction }}" class="catalog-filters__form" data-catalog-filters-form>
        @if (($params->q ?? '') !== '')
            <input type="hidden" form="{{ $filtersFormId }}" name="q" value="{{ $params->q }}">
        @endif
        @if (($params->order ?? 'relevancia') !== 'relevancia')
            <input type="hidden" form="{{ $filtersFormId }}" name="orden" value="{{ $params->order }}">
        @endif
        @if (! is_null($params->priceMin ?? null))
            <input type="hidden" form="{{ $filtersFormId }}" name="precio_min" value="{{ $params->priceMin }}">
        @endif
        @if (! is_null($params->priceMax ?? null))
            <input type="hidden" form="{{ $filtersFormId }}" name="precio_max" value="{{ $params->priceMax }}">
        @endif
    </form>

    @if ($hasCategories)
        <fieldset class="catalog-facet" data-facet-group="categorias">
            <legend class="catalog-facet__legend">Categorías</legend>
            {{-- Enlaces de ruta normales (cambian h1, breadcrumb y SEO): navegación completa, sin data-catalog-link. --}}
            @if (! empty($categories['back']))
                <a class="catalog-cat__back" href="{{ $categories['back']['url'] }}">&lsaquo; {{ ltrim(preg_replace('/^\s*[‹<]\s*/u', '', $categories['back']['label'])) }}</a>
            @endif
            @if (! empty($categories['current']))
                <p class="catalog-cat__current">
                    <span>{{ $categories['current']['name'] }}</span>
                    <span class="catalog-facet__count">{{ (int) $categories['current']['count'] }}</span><span class="sr-only"> productos</span>
                </p>
            @endif
            @if (count($categoryItems) > 0)
                <ul class="catalog-facet__list catalog-cat__list {{ ! empty($categories['current']) ? 'is-nested' : '' }}">
                    @foreach ($categoryItems as $item)
                        <li class="catalog-facet__item">
                            <a class="catalog-cat__link" href="{{ $item['url'] }}">
                                <span class="catalog-facet__text">{{ $item['name'] }}</span>
                                <span class="catalog-facet__count">{{ (int) $item['count'] }}</span><span class="sr-only"> productos</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </fieldset>
    @endif

    @if ($price)
        <fieldset class="catalog-facet" data-facet-group="precio">
            <legend class="catalog-facet__legend">Precio</legend>
            @if (! empty($price['ranges']))
                <ul class="catalog-facet__list catalog-price__ranges">
                    @foreach ($price['ranges'] as $range)
                        <li class="catalog-facet__item">
                            <a class="catalog-price__range {{ ! empty($range['active']) ? 'is-active' : '' }}" href="{{ $range['href'] }}"
                                data-catalog-link @if (! empty($range['active'])) aria-current="true" @endif>
                                <span class="catalog-facet__text">{{ $range['label'] }}</span>
                                <span class="catalog-facet__count">{{ (int) $range['count'] }}</span><span class="sr-only"> productos</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
            <form method="GET" action="{{ $price['formAction'] }}" class="catalog-price__form" data-catalog-price-form>
                @foreach ($flatten($price['hidden'] ?? []) as [$hiddenName, $hiddenValue])
                    <input type="hidden" name="{{ $hiddenName }}" value="{{ $hiddenValue }}">
                @endforeach
                <label for="catalog-price-min" class="sr-only">Precio mínimo en pesos</label>
                <input id="catalog-price-min" class="catalog-price__input" type="number" name="precio_min" min="0" step="1" inputmode="numeric"
                    placeholder="Desde" value="{{ $price['selected']['min'] ?? '' }}">
                <span class="catalog-price__sep" aria-hidden="true">&ndash;</span>
                <label for="catalog-price-max" class="sr-only">Precio máximo en pesos</label>
                <input id="catalog-price-max" class="catalog-price__input" type="number" name="precio_max" min="0" step="1" inputmode="numeric"
                    placeholder="Hasta" value="{{ $price['selected']['max'] ?? '' }}">
                <button type="submit" class="catalog-price__submit" aria-label="Aplicar rango de precio">&rsaquo;</button>
            </form>
            <p class="catalog-facet__hint">Precios en MXN, sin IVA.</p>
        </fieldset>
    @endif

    @if (count($brandOptions) > 0)
        @include('frontend.shop.catalog.partials.facet-group', [
            'groupId' => 'marca', 'legend' => 'Marcas', 'options' => $brandOptions, 'max' => $max,
        ])
    @endif

    @if (count($availabilityOptions) > 0)
        @include('frontend.shop.catalog.partials.facet-group', [
            'groupId' => 'disp', 'legend' => 'Disponibilidad', 'options' => $availabilityOptions, 'max' => $max,
        ])
    @endif

    @if ($freeShipping && ((int) $freeShipping['count'] > 0 || ! empty($freeShipping['selected'])))
        @include('frontend.shop.catalog.partials.facet-group', [
            'groupId' => 'envio', 'legend' => 'Envío', 'max' => $max,
            'options' => [[
                'label' => $freeShipping['label'], 'count' => $freeShipping['count'], 'selected' => $freeShipping['selected'],
                'href' => $freeShipping['href'], 'inputName' => $freeShipping['inputName'], 'value' => $freeShipping['value'],
            ]],
        ])
    @endif

    @foreach ($tech as $group)
        @if (! empty($group['options']))
            @include('frontend.shop.catalog.partials.facet-group', [
                'groupId' => 'tec' . $group['id'], 'legend' => $group['name'], 'max' => $max,
                'options' => collect($group['options'])->map(fn ($o) => [
                    'label' => $o['label'], 'count' => $o['count'], 'selected' => $o['selected'], 'href' => $o['href'],
                    'inputName' => $o['inputName'], 'value' => $o['value'],
                ])->all(),
            ])
        @endif
    @endforeach

    <noscript>
        <button type="submit" form="{{ $filtersFormId }}" class="catalog-filters__apply">Aplicar</button>
    </noscript>
</div>
