{{--
    Grupo de casillas del sidebar del catálogo (marcas, disponibilidad, envío
    gratis, grupos técnicos). Lo incluye partials/sidebar.blade.php.

    Variables:
      $groupId  string   identificador estable del grupo (ids del DOM + restauración de "Mostrar más").
      $legend   string   título visible del grupo (<legend>).
      $options  array    [['label','count','selected','href','inputName','value'], ...] ya ordenadas.
      $max      int      cuántas opciones se muestran antes de "Mostrar más".

    Cada casilla es un <input type="checkbox" name value data-href>: con JS se
    navega a data-href (AJAX); sin JS el formulario GET del sidebar la envía.
--}}
@php
    $options = array_values($options);
    $visibleOptions = array_slice($options, 0, $max);
    $extraOptions = array_slice($options, $max);
    // Si una opción seleccionada quedó detrás de "Mostrar más", el grupo nace
    // expandido para que nunca se oculte un filtro activo.
    $extraHasSelected = collect($extraOptions)->contains(fn ($o) => ! empty($o['selected']));
    $moreId = 'catalog-facet-more-' . $groupId;
@endphp
<fieldset class="catalog-facet" data-facet-group="{{ $groupId }}">
    <legend class="catalog-facet__legend">{{ $legend }}</legend>

    <ul class="catalog-facet__list">
        @foreach ($visibleOptions as $option)
            @include('frontend.shop.catalog.partials.facet-option', ['groupId' => $groupId, 'option' => $option])
        @endforeach
    </ul>

    @if (count($extraOptions) > 0)
        <ul class="catalog-facet__list catalog-facet__extra {{ $extraHasSelected ? '' : 'is-collapsed' }}" id="{{ $moreId }}">
            @foreach ($extraOptions as $option)
                @include('frontend.shop.catalog.partials.facet-option', ['groupId' => $groupId, 'option' => $option])
            @endforeach
        </ul>
        <button type="button" class="catalog-facet__more" data-catalog-more
            aria-expanded="{{ $extraHasSelected ? 'true' : 'false' }}" aria-controls="{{ $moreId }}"
            data-label-more="Mostrar más" data-label-less="Mostrar menos">{{ $extraHasSelected ? 'Mostrar menos' : 'Mostrar más' }}</button>
    @endif
</fieldset>
