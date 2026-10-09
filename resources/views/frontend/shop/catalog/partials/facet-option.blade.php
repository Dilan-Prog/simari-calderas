{{-- Una casilla de faceta (la incluye facet-group). Variables: $groupId, $option. --}}
@php
    $domId = 'catalog-f-' . $groupId . '-' . preg_replace('/[^A-Za-z0-9_-]/', '', (string) $option['value']);
    $count = (int) ($option['count'] ?? 0);
@endphp
<li class="catalog-facet__item">
    <input type="checkbox" class="catalog-facet__input" id="{{ $domId }}" form="catalog-filters-form"
        name="{{ $option['inputName'] }}" value="{{ $option['value'] }}"
        data-href="{{ $option['href'] }}" @checked(! empty($option['selected']))>
    <label for="{{ $domId }}" class="catalog-facet__label">
        <span class="catalog-facet__text">{{ $option['label'] }}</span>
        <span class="catalog-facet__count">{{ $count }}</span><span class="sr-only"> productos</span>
    </label>
</li>
