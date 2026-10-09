{{--
    Barra de resultados: total, botón "Filtros (N)" (solo <900px, abre el
    drawer) y selector de orden. Va dentro de <div data-catalog-region="toolbar">;
    WP-1 lo usa para `toolbarHtml`. Variables: $result, $category.
--}}
@php
    $total = (int) $result->total;
    $activeCount = (int) ($result->meta['activeCount'] ?? 0);
@endphp
<div class="catalog-toolbar">
    <p class="catalog-toolbar__total"><strong>{{ number_format($total) }}</strong> {{ $total === 1 ? 'resultado' : 'resultados' }}</p>

    <button type="button" class="catalog-toolbar__filters" data-catalog-drawer-open
        aria-controls="catalog-sidebar" aria-expanded="false">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="4" y1="6" x2="20" y2="6"/><line x1="7" y1="12" x2="17" y2="12"/><line x1="10" y1="18" x2="14" y2="18"/></svg>
        <span>Filtros{{ $activeCount > 0 ? ' (' . $activeCount . ')' : '' }}</span>
    </button>

    <div class="catalog-toolbar__sort">
        <label for="catalog-sort" class="catalog-toolbar__sort-label">Ordenar por</label>
        <select id="catalog-sort" class="catalog-toolbar__select" data-catalog-sort>
            @foreach ($result->sortOptions as $option)
                <option value="{{ $option['key'] }}" data-href="{{ $option['href'] }}" @selected(! empty($option['selected']))>{{ $option['label'] }}</option>
            @endforeach
        </select>
        <noscript>
            <ul class="catalog-toolbar__sort-links">
                @foreach ($result->sortOptions as $option)
                    <li><a href="{{ $option['href'] }}" @if (! empty($option['selected'])) aria-current="true" @endif>{{ $option['label'] }}</a></li>
                @endforeach
            </ul>
        </noscript>
    </div>
</div>
